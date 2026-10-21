<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserFilteringTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createUser(
            name: 'ZZZ Administrator',
            number: 9000,
            role: 'admin',
            status: 'active'
        );

        $this->actingAs($this->admin);
    }

    private function createUser(
        string $name,
        int $number,
        string $role = 'staff',
        string $status = 'active'
    ): User {
        $user = User::factory()->create([
            'name' => $name,
            'email' => sprintf(
                'filter-user-%04d@rincomm.test',
                $number
            ),
        ]);

        $user->forceFill([
            'role' => $role,
            'account_status' => $status,
            'employee_number' => $role === 'customer'
                ? null
                : sprintf('EMP-FILTER-%04d', $number),
        ])->save();

        return $user->refresh();
    }

    private function listedIds(array $filters): array
    {
        $response = $this->get(
            route('admin.users.index', $filters)
        );

        $response->assertOk();

        return $response
            ->viewData('users')
            ->getCollection()
            ->pluck('id')
            ->all();
    }

    public function test_search_matches_name_email_and_employee_number(): void
    {
        $employee = $this->createUser(
            name: 'Searchable Employee',
            number: 1001
        );

        foreach ([
            'Searchable Employee',
            'filter-user-1001@rincomm.test',
            'EMP-FILTER-1001',
        ] as $search) {
            $this->assertSame(
                [$employee->id],
                $this->listedIds([
                    'search' => $search,
                ]),
                "User search failed for: {$search}"
            );
        }

        $this->assertSame(
            [],
            $this->listedIds([
                'search' => 'USER-DOES-NOT-EXIST',
            ])
        );
    }

    public function test_combined_role_and_status_filters(): void
    {
        $staffActive = $this->createUser(
            'Matrix Staff Active',
            1101,
            'staff',
            'active'
        );

        $staffInactive = $this->createUser(
            'Matrix Staff Inactive',
            1102,
            'staff',
            'inactive'
        );

        $this->createUser(
            'Matrix Technician Active',
            1103,
            'technician',
            'active'
        );

        $this->assertSame(
            [$staffActive->id],
            $this->listedIds([
                'search' => 'Matrix',
                'role' => 'staff',
                'status' => 'active',
            ])
        );

        $this->assertSame(
            [$staffInactive->id],
            $this->listedIds([
                'search' => 'Matrix',
                'role' => 'staff',
                'status' => 'inactive',
            ])
        );

        $this->assertSame(
            [],
            $this->listedIds([
                'search' => 'Matrix',
                'role' => 'technician',
                'status' => 'inactive',
            ])
        );
    }

    public function test_all_supported_account_status_filters(): void
    {
        $active = $this->createUser(
            'StatusCase Active',
            1201,
            'staff',
            'active'
        );

        $inactive = $this->createUser(
            'StatusCase Inactive',
            1202,
            'staff',
            'inactive'
        );

        $pending = $this->createUser(
            'StatusCase Pending',
            1203,
            'staff',
            'pending_verification'
        );

        $expected = [
            'active' => [$active->id],
            'inactive' => [$inactive->id],
            'pending_verification' => [$pending->id],
        ];

        foreach ($expected as $status => $ids) {
            $this->assertSame(
                $ids,
                $this->listedIds([
                    'search' => 'StatusCase',
                    'status' => $status,
                ])
            );
        }
    }

    public function test_users_are_sorted_alphabetically(): void
    {
        $charlie = $this->createUser(
            'SortCase Charlie',
            1303
        );

        $alpha = $this->createUser(
            'SortCase Alpha',
            1301
        );

        $bravo = $this->createUser(
            'SortCase Bravo',
            1302
        );

        $this->assertSame(
            [
                $alpha->id,
                $bravo->id,
                $charlie->id,
            ],
            $this->listedIds([
                'search' => 'SortCase',
            ])
        );
    }

    public function test_invalid_role_and_status_are_ignored(): void
    {
        $employee = $this->createUser(
            'InvalidCase Employee',
            1401
        );

        $response = $this->get(
            route('admin.users.index', [
                'search' => 'InvalidCase',
                'role' => 'unknown_role',
                'status' => 'unknown_status',
            ])
        );

        $response
            ->assertOk()
            ->assertViewHas('role', 'unknown_role')
            ->assertViewHas('status', 'unknown_status')
            ->assertSee('data-user-filters', false)
            ->assertSee('data-user-results', false);

        $this->assertSame(
            [$employee->id],
            $response
                ->viewData('users')
                ->getCollection()
                ->pluck('id')
                ->all()
        );
    }

    public function test_pagination_preserves_search_role_and_status(): void
    {
        $expectedIds = [];

        for ($number = 1; $number <= 23; $number++) {
            $user = $this->createUser(
                sprintf('PagingCase Employee %02d', $number),
                2000 + $number,
                'staff',
                'active'
            );

            $expectedIds[] = $user->id;
        }

        $filters = [
            'search' => 'PagingCase',
            'role' => 'staff',
            'status' => 'active',
        ];

        $response = $this->get(
            route('admin.users.index', $filters)
        );

        $response->assertOk();

        $page = $response->viewData('users');

        $this->assertSame(23, $page->total());
        $this->assertSame(20, $page->count());
        $this->assertSame(1, $page->currentPage());
        $this->assertSame(2, $page->lastPage());

        $this->assertSame(
            array_slice($expectedIds, 0, 20),
            $page->getCollection()->pluck('id')->all()
        );

        $query = parse_url(
            $page->url(2),
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
                'admin.users.index',
                array_merge(
                    $filters,
                    ['page' => 2]
                )
            )
        );

        $second->assertOk();

        $secondPage = $second->viewData('users');

        $this->assertSame(23, $secondPage->total());
        $this->assertSame(3, $secondPage->count());
        $this->assertSame(2, $secondPage->currentPage());

        $this->assertSame(
            array_slice($expectedIds, 20),
            $secondPage
                ->getCollection()
                ->pluck('id')
                ->all()
        );
    }

    public function test_listing_preserves_account_protection_indicators(): void
    {
        $pending = $this->createUser(
            'ProtectionCase Pending',
            3001,
            'staff',
            'pending_verification'
        );

        $inactive = $this->createUser(
            'ProtectionCase Inactive',
            3002,
            'staff',
            'inactive'
        );

        $response = $this->get(
            route('admin.users.index')
        );

        $response
            ->assertOk()
            ->assertViewHas('activeAdministratorCount', 1)
            ->assertSee('Protected')
            ->assertSee('Awaiting Verification')
            ->assertSee('data-user-activate', false);

        $this->assertSame(
            'active',
            $this->admin->fresh()->account_status
        );

        $this->assertSame(
            'pending_verification',
            $pending->fresh()->account_status
        );

        $this->assertSame(
            'inactive',
            $inactive->fresh()->account_status
        );
    }
}