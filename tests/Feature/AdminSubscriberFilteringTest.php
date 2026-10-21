<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSubscriberFilteringTest extends TestCase
{
    use RefreshDatabase;

    private function prepareAdmin(): void
    {
        $this->seed(DemoDataSeeder::class);

        $admin = User::factory()->create();

        $admin->forceFill([
            'role' => 'admin',
            'account_status' => 'active',
        ])->save();

        $this->actingAs($admin);
    }

    private function subscriber(
        string $code,
        string $firstName,
        string $lastName,
        string $status = 'active',
        ?string $middleName = null
    ): Customer {
        $user = User::factory()->create();

        $user->forceFill([
            'role' => 'customer',
            'account_status' => 'active',
        ])->save();

        return Customer::query()->create([
            'user_id' => $user->id,
            'customer_code' => $code,
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'phone' => '0912' . substr($code, -7),
            'email' => $user->email,
            'address' => 'Paniqui',
            'city' => 'Paniqui',
            'province' => 'Tarlac',
            'postal_code' => '2307',
            'billing_address' => 'Paniqui, Tarlac',
            'installation_address' => 'Paniqui, Tarlac',
            'status' => $status,
        ]);
    }

    public function test_full_name_search_supports_middle_names_and_sqlite(): void
    {
        $this->prepareAdmin();

        $this->subscriber(
            'CUST-SEARCH-101',
            'Arianna',
            'Villanueva',
            'active',
            'Marie'
        );

        $this->subscriber(
            'CUST-SEARCH-202',
            'Beatrice',
            'Santos'
        );

        $this->get(route('admin.subscribers.index', [
            'search' => 'Arianna Marie Villanueva',
        ]))
            ->assertOk()
            ->assertSee('CUST-SEARCH-101')
            ->assertDontSee('CUST-SEARCH-202');

        $this->get(route('admin.subscribers.index', [
            'search' => 'Beatrice Santos',
        ]))
            ->assertOk()
            ->assertSee('CUST-SEARCH-202')
            ->assertDontSee('CUST-SEARCH-101');
    }

    public function test_search_supports_code_email_and_phone(): void
    {
        $this->prepareAdmin();

        $subscriber = $this->subscriber(
            'CUST-4501234',
            'Leandro',
            'Navarro'
        );

        foreach ([
            'CUST-4501234',
            $subscriber->email,
            $subscriber->phone,
        ] as $term) {
            $this->get(route('admin.subscribers.index', [
                'search' => $term,
            ]))
                ->assertOk()
                ->assertSee('CUST-4501234');
        }
    }

    public function test_combined_search_and_status_filter(): void
    {
        $this->prepareAdmin();

        $this->subscriber(
            'CUST-FILTER-101',
            'Mika',
            'Rivera',
            'active'
        );

        $this->subscriber(
            'CUST-FILTER-202',
            'Nico',
            'Rivera',
            'suspended'
        );

        $this->get(route('admin.subscribers.index', [
            'search' => 'Rivera',
            'status' => 'suspended',
        ]))
            ->assertOk()
            ->assertSee('CUST-FILTER-202')
            ->assertDontSee('CUST-FILTER-101');
    }

    public function test_name_sorting_works_in_both_directions(): void
    {
        $this->prepareAdmin();

        $this->subscriber(
            'CUST-ORDER-101',
            'Maria',
            'Zamora'
        );

        $this->subscriber(
            'CUST-ORDER-202',
            'Carlos',
            'Alvarez'
        );

        $this->subscriber(
            'CUST-ORDER-303',
            'Paolo',
            'Mendoza'
        );

        $this->get(route('admin.subscribers.index', [
            'search' => 'CUST-ORDER-',
            'sort' => 'name_asc',
        ]))
            ->assertOk()
            ->assertSeeInOrder([
                'CUST-ORDER-202',
                'CUST-ORDER-303',
                'CUST-ORDER-101',
            ]);

        $this->get(route('admin.subscribers.index', [
            'search' => 'CUST-ORDER-',
            'sort' => 'name_desc',
        ]))
            ->assertOk()
            ->assertSeeInOrder([
                'CUST-ORDER-101',
                'CUST-ORDER-303',
                'CUST-ORDER-202',
            ]);
    }

    public function test_code_and_date_sorting_work(): void
    {
        $this->prepareAdmin();

        $first = $this->subscriber(
            'CUST-DATE-300',
            'First',
            'Record'
        );

        $second = $this->subscriber(
            'CUST-DATE-100',
            'Second',
            'Record'
        );

        $first->forceFill([
            'created_at' => '2025-01-01 09:00:00',
        ])->save();

        $second->forceFill([
            'created_at' => '2025-02-01 09:00:00',
        ])->save();

        $expected = [
            'code_asc' => [
                'CUST-DATE-100',
                'CUST-DATE-300',
            ],
            'code_desc' => [
                'CUST-DATE-300',
                'CUST-DATE-100',
            ],
            'oldest' => [
                'CUST-DATE-300',
                'CUST-DATE-100',
            ],
            'newest' => [
                'CUST-DATE-100',
                'CUST-DATE-300',
            ],
        ];

        foreach ($expected as $sort => $codes) {
            $this->get(route('admin.subscribers.index', [
                'search' => 'CUST-DATE-',
                'sort' => $sort,
            ]))
                ->assertOk()
                ->assertSeeInOrder($codes);
        }
    }

    public function test_invalid_sort_and_feature_context_are_preserved(): void
    {
        $this->prepareAdmin();

        $this->subscriber(
            'CUST-CONTEXT-101',
            'Sofia',
            'Reyes'
        );

        $this->get(route('admin.subscribers.index', [
            'search' => 'CUST-CONTEXT-',
            'sort' => 'invalid_sort_option',
            'feature' => 'customer-documents',
        ]))
            ->assertOk()
            ->assertSee('CUST-CONTEXT-101')
            ->assertSee('Customer Documents')
            ->assertSee('name="feature"', false)
            ->assertSee('value="customer-documents"', false)
            ->assertSee('Newest first');
    }
    public function test_pagination_preserves_subscriber_filters_and_feature_context(): void
    {
        $this->prepareAdmin();

        for ($number = 1; $number <= 25; $number++) {
            $this->subscriber(
                sprintf('CUST-PAGECHECK-%04d', $number),
                'Paging',
                'Test',
                'active'
            );
        }

        $filters = [
            'search' => 'CUST-PAGECHECK-',
            'status' => 'active',
            'sort' => 'code_asc',
            'feature' => 'customer-documents',
        ];

        $firstPage = $this->get(
            route('admin.subscribers.index', $filters)
        );

        $firstPage
            ->assertOk()
            ->assertSee('CUST-PAGECHECK-0001')
            ->assertSee('CUST-PAGECHECK-0020')
            ->assertDontSee('CUST-PAGECHECK-0021')
            ->assertDontSee('CUST-PAGECHECK-0025')
            ->assertSee('Customer Documents')
            ->assertSee('page=2', false)
            ->assertSee('feature=customer-documents', false);

        $firstPage->assertViewHas(
            'subscribers',
            fn ($page) =>
                $page->total() === 25
                && $page->count() === 20
                && $page->currentPage() === 1
                && $page->lastPage() === 2
        );

        $secondPage = $this->get(
            route(
                'admin.subscribers.index',
                array_merge($filters, ['page' => 2])
            )
        );

        $secondPage
            ->assertOk()
            ->assertDontSee('CUST-PAGECHECK-0001')
            ->assertDontSee('CUST-PAGECHECK-0020')
            ->assertSee('CUST-PAGECHECK-0021')
            ->assertSee('CUST-PAGECHECK-0025')
            ->assertSee('Customer Documents');

        $secondPage->assertViewHas(
            'subscribers',
            fn ($page) =>
                $page->total() === 25
                && $page->count() === 5
                && $page->currentPage() === 2
                && $page->firstItem() === 21
                && $page->lastItem() === 25
        );
    }
}
