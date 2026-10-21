<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\ServicePlan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInvoiceFilteringTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private Subscription $subscription;
    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DemoDataSeeder::class);

        $admin = User::query()
            ->where('role', 'admin')
            ->firstOrFail();

        $this->actingAs($admin);

        $this->customer = Customer::query()->firstOrFail();

        $this->customer->update([
            'first_name' => 'Quasar',
            'last_name' => 'Navigator',
        ]);

        $plan = ServicePlan::query()->create([
            'name' => 'Filtering Test Fiber 50',
            'description' => 'Isolated invoice listing fixture',
            'speed_mbps' => '50.00',
            'monthly_fee' => '750.00',
            'is_custom' => false,
            'duration_months' => 1,
            'is_active' => true,
        ]);

        $this->subscription = Subscription::query()->create([
            'customer_id' => $this->customer->id,
            'service_plan_id' => $plan->id,
            'start_date' => '2026-01-01',
            'end_date' => '2027-01-01',
            'lock_in_months' => 12,
            'discount_amount' => '0.00',
            'is_custom_plan' => false,
            'status' => 'active',
        ]);
    }

    private function makeInvoice(
        string $billingDate,
        string $dueDate,
        string $status = 'issued',
        string $amount = '750.00',
        string $prefix = 'INV-FLT'
    ): Invoice {
        $this->sequence++;

        return Invoice::query()->create([
            'customer_id' => $this->customer->id,
            'subscription_id' => $this->subscription->id,
            'invoice_number' => sprintf(
                '%s-%03d',
                $prefix,
                $this->sequence
            ),
            'billing_date' => $billingDate,
            'due_date' => $dueDate,
            'subtotal' => $amount,
            'discount_amount' => '0.00',
            'adjustment_amount' => '0.00',
            'total_amount' => $amount,
            'status' => $status,
            'billed_service_plan_name' => 'Filtering Test Fiber 50',
            'billed_speed_mbps' => 50,
        ]);
    }

    private function listedIds(array $filters): array
    {
        $response = $this->get(
            route('admin.invoices.index', $filters)
        );

        $response->assertOk();

        return $response
            ->viewData('invoices')
            ->getCollection()
            ->pluck('id')
            ->all();
    }

    public function test_invoice_search_matches_number_and_customer_fields(): void
    {
        $invoice = $this->makeInvoice(
            '2026-06-01',
            '2026-06-11'
        );

        $terms = [
            $invoice->invoice_number,
            $this->customer->customer_code,
            'Quasar',
            'Navigator',
        ];

        foreach ($terms as $term) {
            $this->assertContains(
                $invoice->id,
                $this->listedIds(['q' => $term]),
                "Invoice search failed for {$term}"
            );
        }

        $this->assertSame(
            [],
            $this->listedIds([
                'q' => 'NO-SUCH-INVOICE-999999',
            ])
        );
    }

    public function test_sqlite_full_name_search_matches_subscriber(): void
    {
        $invoice = $this->makeInvoice(
            '2026-06-02',
            '2026-06-12'
        );

        $this->assertContains(
            $invoice->id,
            $this->listedIds([
                'q' => 'Quasar Navigator',
            ])
        );

        $response = $this->get(
            route('admin.invoices.index', [
                'q' => 'Quasar Navigator',
            ])
        );

        $response
            ->assertOk()
            ->assertSee($invoice->invoice_number)
            ->assertSee('Filtering Test Fiber 50');

        // Verify the matching invoice links to its own statement.
        // Do not depend on how often the URL appears in the layout.
        $response->assertSee(
            'href="' . e(route('admin.invoices.show', $invoice)) . '"',
            false
        );
    }

    public function test_all_invoice_status_filters(): void
    {
        $statuses = [
            'draft',
            'issued',
            'paid',
            'partially_paid',
            'overdue',
            'cancelled',
        ];

        $expected = [];

        foreach ($statuses as $index => $status) {
            $invoice = $this->makeInvoice(
                sprintf('2026-07-%02d', $index + 1),
                sprintf('2026-07-%02d', $index + 11),
                $status
            );

            $expected[$status] = $invoice->id;
        }

        foreach ($expected as $status => $id) {
            $this->assertSame(
                [$id],
                $this->listedIds([
                    'q' => 'INV-FLT-',
                    'status' => $status,
                ]),
                "Status filter failed for {$status}"
            );
        }
    }

    public function test_combined_search_status_and_sorting(): void
    {
        $older = $this->makeInvoice(
            '2026-08-01',
            '2026-08-11',
            'issued'
        );

        $newer = $this->makeInvoice(
            '2026-08-02',
            '2026-08-12',
            'issued'
        );

        $this->makeInvoice(
            '2026-08-03',
            '2026-08-13',
            'paid'
        );

        $this->assertSame(
            [$older->id, $newer->id],
            $this->listedIds([
                'q' => 'INV-FLT-',
                'status' => 'issued',
                'sort' => 'oldest',
            ])
        );
    }

    public function test_billing_and_due_date_sorting(): void
    {
        $a = $this->makeInvoice(
            '2026-09-01',
            '2026-09-30'
        );

        $b = $this->makeInvoice(
            '2026-09-02',
            '2026-09-10'
        );

        $c = $this->makeInvoice(
            '2026-09-03',
            '2026-09-20'
        );

        $this->assertSame(
            [$c->id, $b->id, $a->id],
            $this->listedIds([
                'q' => 'INV-FLT-',
                'sort' => 'newest',
            ])
        );

        $this->assertSame(
            [$a->id, $b->id, $c->id],
            $this->listedIds([
                'q' => 'INV-FLT-',
                'sort' => 'oldest',
            ])
        );

        $this->assertSame(
            [$b->id, $c->id, $a->id],
            $this->listedIds([
                'q' => 'INV-FLT-',
                'sort' => 'due_earliest',
            ])
        );

        $this->assertSame(
            [$a->id, $c->id, $b->id],
            $this->listedIds([
                'q' => 'INV-FLT-',
                'sort' => 'due_latest',
            ])
        );
    }

    public function test_amount_sorting_in_both_directions(): void
    {
        $low = $this->makeInvoice(
            '2026-10-01',
            '2026-10-11',
            amount: '100.00'
        );

        $high = $this->makeInvoice(
            '2026-10-02',
            '2026-10-12',
            amount: '900.00'
        );

        $medium = $this->makeInvoice(
            '2026-10-03',
            '2026-10-13',
            amount: '500.00'
        );

        $this->assertSame(
            [$high->id, $medium->id, $low->id],
            $this->listedIds([
                'q' => 'INV-FLT-',
                'sort' => 'amount_high',
            ])
        );

        $this->assertSame(
            [$low->id, $medium->id, $high->id],
            $this->listedIds([
                'q' => 'INV-FLT-',
                'sort' => 'amount_low',
            ])
        );
    }

    public function test_invalid_sort_and_status_use_safe_defaults(): void
    {
        $older = $this->makeInvoice(
            '2026-05-01',
            '2026-05-11'
        );

        $newer = $this->makeInvoice(
            '2026-05-02',
            '2026-05-12'
        );

        $response = $this->get(
            route('admin.invoices.index', [
                'q' => 'INV-FLT-',
                'status' => 'invalid_status',
                'sort' => 'invalid_sort',
            ])
        );

        $response
            ->assertOk()
            ->assertViewHas('status', '')
            ->assertViewHas('sort', 'newest')
            ->assertSee('data-list-filters', false)
            ->assertSee('data-list-results', false);

        $this->assertSame(
            [$newer->id, $older->id],
            $response->viewData('invoices')
                ->getCollection()
                ->pluck('id')
                ->all()
        );
    }

    public function test_pagination_preserves_filters_and_sort(): void
    {
        $invoices = [];

        for ($number = 1; $number <= 23; $number++) {
            $billingDate = \Carbon\Carbon::parse('2026-01-01')
                ->addDays($number)
                ->toDateString();

            $dueDate = \Carbon\Carbon::parse($billingDate)
                ->addDays(10)
                ->toDateString();

            $invoice = $this->makeInvoice(
                $billingDate,
                $dueDate,
                'issued',
                sprintf('%d.00', $number * 100),
                'INV-FLT-PAGE'
            );

            $invoices[] = $invoice->id;
        }

        $filters = [
            'q' => 'INV-FLT-PAGE',
            'status' => 'issued',
            'sort' => 'amount_high',
        ];

        $response = $this->get(
            route('admin.invoices.index', $filters)
        );

        $response->assertOk();

        $firstPage = $response->viewData('invoices');

        $this->assertSame(23, $firstPage->total());
        $this->assertSame(20, $firstPage->count());
        $this->assertSame(2, $firstPage->lastPage());

        $expected = array_reverse($invoices);

        $this->assertSame(
            array_slice($expected, 0, 20),
            $firstPage->getCollection()->pluck('id')->all()
        );

        $query = parse_url(
            $firstPage->url(2),
            PHP_URL_QUERY
        );

        parse_str($query ?: '', $params);

        foreach ($filters as $key => $value) {
            $this->assertSame(
                $value,
                $params[$key] ?? null
            );
        }

        $this->assertSame(
            '2',
            (string) ($params['page'] ?? '')
        );

        $second = $this->get(
            route(
                'admin.invoices.index',
                array_merge($filters, ['page' => 2])
            )
        );

        $second->assertOk();

        $page = $second->viewData('invoices');

        $this->assertSame(23, $page->total());
        $this->assertSame(3, $page->count());

        $this->assertSame(
            array_slice($expected, 20),
            $page->getCollection()->pluck('id')->all()
        );
    }
}