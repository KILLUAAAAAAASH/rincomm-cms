<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminActivityLogFilteringTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Archive Administrator',
            'email' => 'archive.admin@rincomm.test',
        ]);

        $this->admin->forceFill([
            'role' => 'admin',
            'account_status' => 'active',
        ])->save();

        $this->actingAs($this->admin);
    }

    private function makeLog(
        string $action,
        string $createdAt,
        string $description = 'Test audit record',
        ?User $actor = null,
        ?User $target = null,
        ?string $ip = null
    ): ActivityLog {
        $log = ActivityLog::query()->create([
            'actor_user_id' => ($actor ?? $this->admin)->id,
            'target_user_id' => $target?->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => $ip,
        ]);

        $log->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        return $log->refresh();
    }

    private function listedIds(array $filters = []): array
    {
        $response = $this->get(
            route('admin.activity-logs.index', $filters)
        );

        $response->assertOk();

        return $response->viewData('logs')
            ->getCollection()
            ->pluck('id')
            ->all();
    }

    public function test_search_matches_audit_fields_and_related_users(): void
    {
        $actor = User::factory()->create([
            'name' => 'Beacon Operator',
            'email' => 'beacon.operator@rincomm.test',
        ]);

        $target = User::factory()->create([
            'name' => 'Beacon Subscriber',
            'email' => 'beacon.subscriber@rincomm.test',
        ]);

        $match = $this->makeLog(
            'user.logged_in',
            '2026-10-10 09:00:00',
            'Beacon authentication event',
            $actor,
            $target,
            '198.51.100.73'
        );

        $this->makeLog(
            'user.logged_out',
            '2026-10-10 10:00:00',
            'Orchid session ended',
            ip: '203.0.113.88'
        );

        foreach ([
            'user.logged_in',
            'Beacon authentication',
            '198.51.100.73',
            'Beacon Operator',
            'beacon.operator@rincomm.test',
            'Beacon Subscriber',
            'beacon.subscriber@rincomm.test',
        ] as $search) {
            $this->assertSame(
                [$match->id],
                $this->listedIds(['search' => $search]),
                "Search failed for {$search}"
            );
        }

        $this->assertSame(
            [],
            $this->listedIds([
                'search' => 'NO-AUDIT-MATCH-FOUND',
            ])
        );
    }

    public function test_search_action_and_date_work_together(): void
    {
        $expected = $this->makeLog(
            'user.logged_in',
            '2026-10-10 09:00:00',
            'AuditCombination successful'
        );

        $this->makeLog(
            'user.logged_out',
            '2026-10-10 10:00:00',
            'AuditCombination logout'
        );

        $this->makeLog(
            'user.logged_in',
            '2026-10-11 09:00:00',
            'AuditCombination next day'
        );

        $this->assertSame(
            [$expected->id],
            $this->listedIds([
                'search' => 'AuditCombination',
                'action' => 'user.logged_in',
                'date' => '2026-10-10',
            ])
        );
    }

    public function test_single_date_matches_only_selected_day(): void
    {
        $first = $this->makeLog(
            'user.logged_in',
            '2026-10-10 00:00:00'
        );

        $last = $this->makeLog(
            'user.logged_out',
            '2026-10-10 23:59:59'
        );

        $this->makeLog(
            'user.logged_in',
            '2026-10-11 00:00:00'
        );

        $this->assertSame(
            [$last->id, $first->id],
            $this->listedIds([
                'date' => '2026-10-10',
            ])
        );

        $this->assertSame(
            [],
            $this->listedIds([
                'date' => '2026-10-12',
            ])
        );
    }

    public function test_newest_and_oldest_sorting(): void
    {
        $first = $this->makeLog(
            'user.logged_in',
            '2026-10-01 09:00:00'
        );

        $second = $this->makeLog(
            'user.logged_out',
            '2026-10-02 09:00:00'
        );

        $third = $this->makeLog(
            'user.profile_updated',
            '2026-10-03 09:00:00'
        );

        $this->assertSame(
            [$third->id, $second->id, $first->id],
            $this->listedIds(['sort' => 'newest'])
        );

        $this->assertSame(
            [$first->id, $second->id, $third->id],
            $this->listedIds(['sort' => 'oldest'])
        );
    }

    public function test_action_sorting_in_both_directions(): void
    {
        $loggedIn = $this->makeLog(
            'user.logged_in',
            '2026-10-10 09:00:00'
        );

        $activated = $this->makeLog(
            'user.activated',
            '2026-10-10 10:00:00'
        );

        $deactivated = $this->makeLog(
            'user.deactivated',
            '2026-10-10 11:00:00'
        );

        $this->assertSame(
            [$activated->id, $deactivated->id, $loggedIn->id],
            $this->listedIds(['sort' => 'action_asc'])
        );

        $this->assertSame(
            [$loggedIn->id, $deactivated->id, $activated->id],
            $this->listedIds(['sort' => 'action_desc'])
        );
    }

    public function test_invalid_sort_and_date_use_safe_defaults(): void
    {
        $older = $this->makeLog(
            'user.logged_in',
            '2026-10-01 09:00:00'
        );

        $newer = $this->makeLog(
            'user.logged_out',
            '2026-10-02 09:00:00'
        );

        $response = $this->get(
            route('admin.activity-logs.index', [
                'sort' => 'unknown_sort',
                'date' => '2026-02-30',
            ])
        );

        $response
            ->assertOk()
            ->assertViewHas('sort', 'newest')
            ->assertViewHas('date', '')
            ->assertSee('data-list-filters', false)
            ->assertSee('data-list-results', false);

        $this->assertSame(
            [$newer->id, $older->id],
            $response->viewData('logs')
                ->getCollection()
                ->pluck('id')
                ->all()
        );
    }

    public function test_pagination_preserves_all_active_filters(): void
    {
        $createdIds = [];

        for ($number = 1; $number <= 27; $number++) {
            $log = $this->makeLog(
                'user.logged_in',
                sprintf(
                    '2026-10-10 09:%02d:00',
                    $number
                ),
                "AuditPaging event {$number}"
            );

            $createdIds[] = $log->id;
        }

        $filters = [
            'search' => 'AuditPaging',
            'action' => 'user.logged_in',
            'date' => '2026-10-10',
            'sort' => 'action_asc',
        ];

        $response = $this->get(
            route('admin.activity-logs.index', $filters)
        );

        $response->assertOk();

        $page = $response->viewData('logs');

        $this->assertSame(27, $page->total());
        $this->assertSame(25, $page->count());
        $this->assertSame(2, $page->lastPage());

        $expected = array_reverse($createdIds);

        $this->assertSame(
            array_slice($expected, 0, 25),
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
                'admin.activity-logs.index',
                array_merge($filters, ['page' => 2])
            )
        );

        $second->assertOk();

        $secondPage = $second->viewData('logs');

        $this->assertSame(27, $secondPage->total());
        $this->assertSame(2, $secondPage->count());

        $this->assertSame(
            array_slice($expected, 25),
            $secondPage->getCollection()
                ->pluck('id')
                ->all()
        );
    }
}