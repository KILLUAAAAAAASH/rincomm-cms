<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ActivityLogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_logger_records_actor_target_metadata_and_request_context(): void
    {
        $actor = $this->createUser('admin');
        $target = $this->createUser('customer');

        $request = Request::create(
            '/test-activity',
            'POST',
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => '192.0.2.10',
                'HTTP_USER_AGENT' => 'Rincomm-Test-Agent',
            ]
        );

        $log = app(ActivityLogger::class)->record(
            action: 'user.deactivated',
            actor: $actor,
            target: $target,
            description: 'Deactivated a test account.',
            metadata: [
                'old_status' => 'active',
                'new_status' => 'inactive',
                'reason' => 'Testing',
            ],
            request: $request
        );

        $this->assertNotNull($log);

        $log->refresh();

        $this->assertSame(
            $actor->id,
            $log->actor_user_id
        );

        $this->assertSame(
            $target->id,
            $log->target_user_id
        );

        $this->assertSame(
            'user.deactivated',
            $log->action
        );

        $this->assertSame(
            '192.0.2.10',
            $log->ip_address
        );

        $this->assertSame(
            'Rincomm-Test-Agent',
            $log->user_agent
        );

        $this->assertSame(
            'inactive',
            $log->metadata['new_status']
        );

        $this->assertSame(
            'Testing',
            $log->metadata['reason']
        );
    }

    public function test_admin_can_view_activity_log_management_page(): void
    {
        $admin = $this->createUser('admin');

        ActivityLog::create([
            'actor_user_id' => $admin->id,
            'target_user_id' => $admin->id,
            'action' => 'user.logged_in',
            'description' => 'Administrator logged in.',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.activity-logs.index'));

        $response->assertOk();

        $response->assertViewIs(
            'admin.activity-logs.index'
        );

        $response->assertViewHas(
            'logs',
            function ($logs): bool {
                return $logs->contains(
                    fn(ActivityLog $log) =>
                    $log->action === 'user.logged_in'
                );
            }
        );
    }

    public function test_staff_can_view_activity_log_management_page(): void
    {
        $staff = $this->createUser('staff');

        $this
            ->actingAs($staff)
            ->get(route('admin.activity-logs.index'))
            ->assertOk();
    }

    public function test_customer_cannot_view_activity_logs(): void
    {
        $customer = $this->createUser('customer');

        $this
            ->actingAs($customer)
            ->get(route('admin.activity-logs.index'))
            ->assertForbidden();
    }

    public function test_guest_cannot_view_activity_logs(): void
    {
        $this
            ->get(route('admin.activity-logs.index'))
            ->assertRedirect(route('login'));
    }

    public function test_activity_logs_can_be_filtered_by_action(): void
    {
        $admin = $this->createUser('admin');

        ActivityLog::create([
            'actor_user_id' => $admin->id,
            'action' => 'user.logged_in',
            'description' => 'Logged in.',
        ]);

        ActivityLog::create([
            'actor_user_id' => $admin->id,
            'action' => 'user.logged_out',
            'description' => 'Logged out.',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(
                route(
                    'admin.activity-logs.index',
                    [
                        'action' => 'user.logged_in',
                    ]
                )
            );

        $response->assertOk();

        $response->assertViewHas(
            'logs',
            function ($logs): bool {
                return $logs->count() === 1
                    && $logs->first()->action === 'user.logged_in';
            }
        );
    }

    public function test_activity_logs_can_be_searched_by_description(): void
    {
        $admin = $this->createUser('admin');

        ActivityLog::create([
            'actor_user_id' => $admin->id,
            'action' => 'user.profile_updated',
            'description' => 'Updated subscriber contact information.',
        ]);

        ActivityLog::create([
            'actor_user_id' => $admin->id,
            'action' => 'user.logged_out',
            'description' => 'User signed out.',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(
                route(
                    'admin.activity-logs.index',
                    [
                        'search' => 'subscriber contact',
                    ]
                )
            );

        $response->assertOk();

        $response->assertViewHas(
            'logs',
            function ($logs): bool {
                return $logs->count() === 1
                    && $logs->first()->action === 'user.profile_updated';
            }
        );
    }

    public function test_activity_log_survives_when_related_user_is_deleted(): void
    {
        $actor = $this->createUser('admin');
        $target = $this->createUser('customer');

        $log = ActivityLog::create([
            'actor_user_id' => $actor->id,
            'target_user_id' => $target->id,
            'action' => 'user.deactivated',
            'description' => 'Account deactivated.',
        ]);

        $target->delete();

        $log->refresh();

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'id' => $log->id,
            ]
        );

        $this->assertNull(
            $log->target_user_id
        );
    }

    private function createUser(string $role): User
    {
        $user = User::factory()->create();

        $user->forceFill([
            'role' => $role,
            'account_status' => 'active',
        ])->save();

        return $user->refresh();
    }
}
