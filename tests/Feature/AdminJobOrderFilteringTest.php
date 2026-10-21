<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\JobOrder;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminJobOrderFilteringTest extends TestCase
{
    use RefreshDatabase;

    private function prepareAdmin(): Customer
    {
        $this->seed(DemoDataSeeder::class);

        $admin = User::factory()->create();

        $admin->forceFill([
            'role' => 'admin',
            'account_status' => 'active',
        ])->save();

        $this->actingAs($admin);

        return Customer::query()->firstOrFail();
    }

    private function makeJobOrder(
        Customer $customer,
        int $number,
        string $type = 'repair',
        string $status = 'pending',
        ?string $date = null,
        ?string $time = null
    ): JobOrder {
        return JobOrder::query()->create([
            'service_request_id' => null,
            'customer_id' => $customer->id,
            'technician_id' => null,
            'job_order_number' => sprintf(
                'JO-FILTER-%04d',
                $number
            ),
            'job_type' => $type,
            'description' => 'Isolated Job Order filtering test.',
            'scheduled_date' => $date,
            'scheduled_time' => $time,
            'status' => $status,
        ]);
    }

    private function listedNumbers(array $filters): array
    {
        $response = $this->get(
            route('admin.job-orders.index', $filters)
        );

        $response->assertOk();

        return $response
            ->viewData('jobOrders')
            ->getCollection()
            ->pluck('job_order_number')
            ->all();
    }

    public function test_search_supports_job_number_and_subscriber_fields(): void
    {
        $customer = $this->prepareAdmin();

        $this->makeJobOrder($customer, 1001);

        $jobNumber = 'JO-FILTER-1001';

        foreach ([
            $jobNumber,
            $customer->customer_code,
            $customer->first_name,
            $customer->last_name,
            $customer->email,
            $customer->phone,
        ] as $search) {
            $numbers = $this->listedNumbers([
                'search' => $search,
            ]);

            $this->assertContains(
                $jobNumber,
                $numbers,
                "Job Order not found by search: {$search}"
            );
        }

        $this->assertSame(
            [],
            $this->listedNumbers([
                'search' => 'JO-FILTER-NOT-FOUND',
            ])
        );
    }

    public function test_combined_job_type_and_status_filters(): void
    {
        $customer = $this->prepareAdmin();

        $this->makeJobOrder(
            $customer,
            1001,
            'repair',
            'pending'
        );

        $this->makeJobOrder(
            $customer,
            1002,
            'repair',
            'completed'
        );

        $this->makeJobOrder(
            $customer,
            1003,
            'site_survey',
            'pending'
        );

        $this->assertSame(
            ['JO-FILTER-1001'],
            $this->listedNumbers([
                'search' => 'JO-FILTER-',
                'job_type' => 'repair',
                'status' => 'pending',
            ])
        );

        $this->assertSame(
            ['JO-FILTER-1002'],
            $this->listedNumbers([
                'search' => 'JO-FILTER-',
                'job_type' => 'repair',
                'status' => 'completed',
            ])
        );
    }

    public function test_job_number_and_creation_order_sorting(): void
    {
        $customer = $this->prepareAdmin();

        $this->makeJobOrder($customer, 300);
        $this->makeJobOrder($customer, 100);
        $this->makeJobOrder($customer, 200);

        $expected = [
            'newest' => [
                'JO-FILTER-0200',
                'JO-FILTER-0100',
                'JO-FILTER-0300',
            ],
            'oldest' => [
                'JO-FILTER-0300',
                'JO-FILTER-0100',
                'JO-FILTER-0200',
            ],
            'job_asc' => [
                'JO-FILTER-0100',
                'JO-FILTER-0200',
                'JO-FILTER-0300',
            ],
            'job_desc' => [
                'JO-FILTER-0300',
                'JO-FILTER-0200',
                'JO-FILTER-0100',
            ],
        ];

        foreach ($expected as $sort => $numbers) {
            $this->assertSame(
                $numbers,
                $this->listedNumbers([
                    'search' => 'JO-FILTER-',
                    'sort' => $sort,
                ]),
                "Incorrect sorting: {$sort}"
            );
        }
    }

    public function test_schedule_sorting_places_unscheduled_jobs_last(): void
    {
        $customer = $this->prepareAdmin();

        $this->makeJobOrder(
            $customer,
            300,
            'repair',
            'pending'
        );

        $this->makeJobOrder(
            $customer,
            100,
            'repair',
            'assigned',
            now()->addDays(2)->toDateString(),
            '09:00'
        );

        $this->makeJobOrder(
            $customer,
            200,
            'repair',
            'assigned',
            now()->addDay()->toDateString(),
            '08:00'
        );

        $this->assertSame(
            [
                'JO-FILTER-0200',
                'JO-FILTER-0100',
                'JO-FILTER-0300',
            ],
            $this->listedNumbers([
                'search' => 'JO-FILTER-',
                'sort' => 'schedule_soon',
            ])
        );

        $this->assertSame(
            [
                'JO-FILTER-0100',
                'JO-FILTER-0200',
                'JO-FILTER-0300',
            ],
            $this->listedNumbers([
                'search' => 'JO-FILTER-',
                'sort' => 'schedule_latest',
            ])
        );
    }

    public function test_invalid_sort_defaults_to_newest(): void
    {
        $customer = $this->prepareAdmin();

        $this->makeJobOrder($customer, 100);
        $this->makeJobOrder($customer, 200);

        $response = $this->get(
            route('admin.job-orders.index', [
                'search' => 'JO-FILTER-',
                'sort' => 'invalid_sort_option',
            ])
        );

        $response
            ->assertOk()
            ->assertViewHas('sort', 'newest')
            ->assertSee('data-list-filters', false)
            ->assertSee('data-list-results', false);

        $this->assertSame(
            [
                'JO-FILTER-0200',
                'JO-FILTER-0100',
            ],
            $response
                ->viewData('jobOrders')
                ->getCollection()
                ->pluck('job_order_number')
                ->all()
        );
    }

    public function test_pagination_preserves_job_order_filters(): void
    {
        $customer = $this->prepareAdmin();

        for ($number = 1; $number <= 17; $number++) {
            $this->makeJobOrder(
                $customer,
                $number,
                'repair',
                'pending'
            );
        }

        $filters = [
            'search' => 'JO-FILTER-',
            'job_type' => 'repair',
            'status' => 'pending',
            'sort' => 'job_asc',
        ];

        $first = $this->get(
            route('admin.job-orders.index', $filters)
        );

        $first->assertOk();

        $firstPage = $first->viewData('jobOrders');

        $this->assertSame(17, $firstPage->total());
        $this->assertSame(15, $firstPage->count());
        $this->assertSame(1, $firstPage->currentPage());
        $this->assertSame(2, $firstPage->lastPage());

        $this->assertSame(
            array_map(
                fn ($n) => sprintf('JO-FILTER-%04d', $n),
                range(1, 15)
            ),
            $firstPage->getCollection()
                ->pluck('job_order_number')
                ->all()
        );

        $query = parse_url(
            $firstPage->url(2),
            PHP_URL_QUERY
        );

        parse_str($query ?: '', $params);

        foreach ($filters as $name => $value) {
            $this->assertSame($value, $params[$name] ?? null);
        }

        $this->assertSame('2', (string) ($params['page'] ?? ''));

        $second = $this->get(
            route(
                'admin.job-orders.index',
                array_merge($filters, ['page' => 2])
            )
        );

        $second->assertOk();

        $secondPage = $second->viewData('jobOrders');

        $this->assertSame(17, $secondPage->total());
        $this->assertSame(2, $secondPage->count());
        $this->assertSame(2, $secondPage->currentPage());

        $this->assertSame(
            [
                'JO-FILTER-0016',
                'JO-FILTER-0017',
            ],
            $secondPage->getCollection()
                ->pluck('job_order_number')
                ->all()
        );
    }
}