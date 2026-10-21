<?php

namespace Tests\Feature;

use App\Models\ServiceApplication;
use App\Models\ServiceArea;
use App\Models\ServicePlan;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminServiceApplicationFilteringTest extends TestCase
{
    use RefreshDatabase;

    private int $applicantId;
    private int $serviceAreaId;
    private int $servicePlanId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DemoDataSeeder::class);

        $admin = User::query()
            ->where('email', 'admin@rincomm.test')
            ->firstOrFail();

        $this->actingAs($admin);

        $this->applicantId = User::query()
            ->where('email', 'miguel.santos@rincomm.test')
            ->firstOrFail()
            ->id;

        $this->serviceAreaId = ServiceArea::query()
            ->firstOrFail()
            ->id;

        $this->servicePlanId = ServicePlan::query()
            ->firstOrFail()
            ->id;
    }

    private function makeApplication(
        int $number,
        string $status = 'pending',
        string $submittedAt = '2026-10-10 09:00:00',
        string $firstName = 'Applicant',
        ?string $middleName = null,
        string $lastName = 'Test'
    ): ServiceApplication {
        return ServiceApplication::query()->create([
            'application_number' => sprintf(
                'APP-2026-%04d',
                $number
            ),
            'user_id' => $this->applicantId,
            'service_area_id' => $this->serviceAreaId,
            'service_plan_id' => $this->servicePlanId,
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'phone' => '09170000001',
            'email' => 'applicant@example.test',
            'installation_address' => 'Paniqui, Tarlac',
            'billing_address' => 'Paniqui, Tarlac',
            'status' => $status,
            'submitted_at' => $submittedAt,
        ]);
    }

    private function listedNumbers(array $filters): array
    {
        $response = $this->get(
            route('admin.applications.index', $filters)
        );

        $response->assertOk();

        return $response
            ->viewData('applications')
            ->getCollection()
            ->pluck('application_number')
            ->all();
    }

    public function test_search_matches_application_number_name_email_and_phone(): void
    {
        $this->makeApplication(
            number: 1001,
            firstName: 'Aurora',
            middleName: 'Faye',
            lastName: 'Mercado'
        );

        foreach ([
            'APP-2026-1001',
            'Aurora',
            'Faye',
            'Mercado',
            'applicant@example.test',
            '09170000001',
        ] as $search) {
            $this->assertSame(
                ['APP-2026-1001'],
                $this->listedNumbers([
                    'search' => $search,
                ]),
                "Search failed for: {$search}"
            );
        }

        $this->assertSame(
            [],
            $this->listedNumbers([
                'search' => 'APP-NOT-FOUND',
            ])
        );
    }

    public function test_combined_search_and_status_filters(): void
    {
        $this->makeApplication(
            1001,
            'pending',
            firstName: 'Aurora'
        );

        $this->makeApplication(
            1002,
            'approved',
            firstName: 'Aurora'
        );

        $this->makeApplication(
            1003,
            'rejected',
            firstName: 'Brenda'
        );

        $this->makeApplication(
            1004,
            'cancelled',
            firstName: 'Celia'
        );

        $this->assertSame(
            ['APP-2026-1001'],
            $this->listedNumbers([
                'search' => 'Aurora',
                'status' => 'pending',
            ])
        );

        $this->assertSame(
            ['APP-2026-1002'],
            $this->listedNumbers([
                'search' => 'Aurora',
                'status' => 'approved',
            ])
        );

        $this->assertSame(
            [],
            $this->listedNumbers([
                'search' => 'Aurora',
                'status' => 'rejected',
            ])
        );
    }

    public function test_newest_and_oldest_submission_sorting(): void
    {
        $this->makeApplication(
            400,
            submittedAt: '2026-10-05 09:00:00'
        );

        $this->makeApplication(
            100,
            submittedAt: '2026-10-03 09:00:00'
        );

        $this->makeApplication(
            200,
            submittedAt: '2026-10-04 09:00:00'
        );

        $this->assertSame(
            [
                'APP-2026-0400',
                'APP-2026-0200',
                'APP-2026-0100',
            ],
            $this->listedNumbers([
                'search' => 'APP-2026-',
                'sort' => 'newest',
            ])
        );

        $this->assertSame(
            [
                'APP-2026-0100',
                'APP-2026-0200',
                'APP-2026-0400',
            ],
            $this->listedNumbers([
                'search' => 'APP-2026-',
                'sort' => 'oldest',
            ])
        );
    }

    public function test_application_number_sorting_in_both_directions(): void
    {
        $this->makeApplication(300);
        $this->makeApplication(100);
        $this->makeApplication(200);

        $this->assertSame(
            [
                'APP-2026-0100',
                'APP-2026-0200',
                'APP-2026-0300',
            ],
            $this->listedNumbers([
                'search' => 'APP-2026-',
                'sort' => 'application_asc',
            ])
        );

        $this->assertSame(
            [
                'APP-2026-0300',
                'APP-2026-0200',
                'APP-2026-0100',
            ],
            $this->listedNumbers([
                'search' => 'APP-2026-',
                'sort' => 'application_desc',
            ])
        );
    }

    public function test_applicant_name_sorting_in_both_directions(): void
    {
        $this->makeApplication(
            300,
            firstName: 'Ava',
            lastName: 'Zulu'
        );

        $this->makeApplication(
            100,
            firstName: 'Zoe',
            lastName: 'Alpha'
        );

        $this->makeApplication(
            200,
            firstName: 'Amy',
            lastName: 'Alpha'
        );

        $this->assertSame(
            [
                'APP-2026-0200',
                'APP-2026-0100',
                'APP-2026-0300',
            ],
            $this->listedNumbers([
                'search' => 'APP-2026-',
                'sort' => 'name_asc',
            ])
        );

        $this->assertSame(
            [
                'APP-2026-0300',
                'APP-2026-0100',
                'APP-2026-0200',
            ],
            $this->listedNumbers([
                'search' => 'APP-2026-',
                'sort' => 'name_desc',
            ])
        );
    }

    public function test_invalid_filters_fall_back_to_safe_defaults(): void
    {
        $this->makeApplication(
            100,
            submittedAt: '2026-10-03 09:00:00'
        );

        $this->makeApplication(
            200,
            submittedAt: '2026-10-04 09:00:00'
        );

        $response = $this->get(
            route('admin.applications.index', [
                'search' => 'APP-2026-',
                'status' => 'not_a_valid_status',
                'sort' => 'not_a_valid_sort',
            ])
        );

        $response
            ->assertOk()
            ->assertViewHas('status', '')
            ->assertViewHas('sort', 'newest')
            ->assertSee('data-list-filters', false)
            ->assertSee('data-list-results', false);

        $this->assertSame(
            [
                'APP-2026-0200',
                'APP-2026-0100',
            ],
            $response
                ->viewData('applications')
                ->getCollection()
                ->pluck('application_number')
                ->all()
        );
    }

    public function test_pagination_preserves_active_filters(): void
    {
        for ($number = 1; $number <= 23; $number++) {
            $this->makeApplication(
                $number,
                'pending'
            );
        }

        $filters = [
            'search' => 'APP-2026-',
            'status' => 'pending',
            'sort' => 'application_asc',
        ];

        $response = $this->get(
            route('admin.applications.index', $filters)
        );

        $response->assertOk();

        $page = $response->viewData('applications');

        $this->assertSame(23, $page->total());
        $this->assertSame(20, $page->count());
        $this->assertSame(1, $page->currentPage());
        $this->assertSame(2, $page->lastPage());

        $this->assertSame(
            array_map(
                fn ($n) => sprintf('APP-2026-%04d', $n),
                range(1, 20)
            ),
            $page->getCollection()
                ->pluck('application_number')
                ->all()
        );

        $query = parse_url(
            $page->url(2),
            PHP_URL_QUERY
        );

        parse_str($query ?: '', $params);

        foreach ($filters as $name => $value) {
            $this->assertSame(
                $value,
                $params[$name] ?? null
            );
        }

        $this->assertSame(
            '2',
            (string) ($params['page'] ?? '')
        );

        $second = $this->get(
            route(
                'admin.applications.index',
                array_merge(
                    $filters,
                    ['page' => 2]
                )
            )
        );

        $second->assertOk();

        $secondPage = $second->viewData('applications');

        $this->assertSame(23, $secondPage->total());
        $this->assertSame(3, $secondPage->count());
        $this->assertSame(2, $secondPage->currentPage());

        $this->assertSame(
            [
                'APP-2026-0021',
                'APP-2026-0022',
                'APP-2026-0023',
            ],
            $secondPage->getCollection()
                ->pluck('application_number')
                ->all()
        );
    }
}