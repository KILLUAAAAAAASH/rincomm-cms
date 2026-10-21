<?php

namespace Tests\Feature;

use App\Models\JobOrder;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\TicketWorkflowService;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TicketWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    private ServiceRequest $ticket;
    private TicketWorkflowService $workflow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DemoDataSeeder::class);

        $this->ticket = ServiceRequest::query()
            ->firstOrFail();

        $this->ticket->update([
            'status' => 'open',
            'assigned_user_id' => null,
            'assigned_at' => null,
        ]);

        $this->workflow = app(TicketWorkflowService::class);
    }

    private function user(
        string $role = 'staff',
        string $accountStatus = 'active'
    ): User {
        $user = User::factory()->create();

        $user->forceFill([
            'role' => $role,
            'account_status' => $accountStatus,
        ])->save();

        return $user->refresh();
    }

    private function assertRejected(
        callable $action,
        string $exceptionClass
    ): void {
        try {
            $action();
        } catch (\Throwable $exception) {
            $this->assertInstanceOf(
                $exceptionClass,
                $exception
            );

            return;
        }

        $this->fail(
            "Expected {$exceptionClass} but no exception occurred."
        );
    }

    public function test_assignment_migration_fields_exist(): void
    {
        $this->assertTrue(
            Schema::hasColumns('service_requests', [
                'assigned_user_id',
                'assigned_at',
            ])
        );
    }

    public function test_open_ticket_can_be_assigned_to_active_staff(): void
    {
        $admin = $this->user('admin');
        $staff = $this->user('staff');

        $before = JobOrder::count();

        $ticket = $this->workflow->assign(
            $this->ticket,
            $staff,
            $admin
        );

        $this->assertSame('assigned', $ticket->status);
        $this->assertSame(
            $staff->id,
            $ticket->assigned_user_id
        );
        $this->assertNotNull($ticket->assigned_at);

        $this->assertSame(
            $staff->id,
            $ticket->assignedUser->id
        );

        $this->assertSame($before, JobOrder::count());
    }

    public function test_only_active_admin_or_staff_can_be_assignees(): void
    {
        $admin = $this->user('admin');

        foreach ([
            ['customer', 'active'],
            ['technician', 'active'],
            ['staff', 'inactive'],
            ['admin', 'inactive'],
        ] as [$role, $accountStatus]) {
            $candidate = $this->user(
                $role,
                $accountStatus
            );

            $this->assertRejected(
                fn () => $this->workflow->assign(
                    $this->ticket,
                    $candidate,
                    $admin
                ),
                ValidationException::class
            );

            $this->assertSame(
                'open',
                $this->ticket->fresh()->status
            );

            $this->assertNull(
                $this->ticket->fresh()->assigned_user_id
            );
        }

        $activeAdmin = $this->user('admin');

        $assigned = $this->workflow->assign(
            $this->ticket,
            $activeAdmin,
            $admin
        );

        $this->assertSame(
            $activeAdmin->id,
            $assigned->assigned_user_id
        );
    }

    public function test_unauthorized_actors_cannot_assign_tickets(): void
    {
        $assignee = $this->user('staff');

        foreach ([
            ['customer', 'active'],
            ['technician', 'active'],
            ['staff', 'inactive'],
            ['admin', 'inactive'],
        ] as [$role, $accountStatus]) {
            $actor = $this->user(
                $role,
                $accountStatus
            );

            $this->assertRejected(
                fn () => $this->workflow->assign(
                    $this->ticket,
                    $assignee,
                    $actor
                ),
                AuthorizationException::class
            );

            $this->assertSame(
                'open',
                $this->ticket->fresh()->status
            );
        }
    }

    public function test_assignment_can_be_changed_while_assigned(): void
    {
        $admin = $this->user('admin');
        $first = $this->user('staff');
        $second = $this->user('staff');

        $this->workflow->assign(
            $this->ticket,
            $first,
            $admin
        );

        $updated = $this->workflow->assign(
            $this->ticket,
            $second,
            $admin
        );

        $this->assertSame('assigned', $updated->status);
        $this->assertSame(
            $second->id,
            $updated->assigned_user_id
        );
    }

    public function test_assigned_staff_can_follow_full_status_sequence(): void
    {
        $admin = $this->user('admin');
        $staff = $this->user('staff');

        $this->workflow->assign(
            $this->ticket,
            $staff,
            $admin
        );

        foreach ([
            'in_progress',
            'resolved',
            'closed',
        ] as $status) {
            $updated = $this->workflow->transition(
                $this->ticket,
                $status,
                $staff
            );

            $this->assertSame(
                $status,
                $updated->status
            );

            $this->assertSame(
                $status,
                $this->ticket->fresh()->status
            );
        }
    }

    public function test_only_assigned_staff_or_admin_can_advance(): void
    {
        $admin = $this->user('admin');
        $staff = $this->user('staff');
        $otherStaff = $this->user('staff');

        $this->assertRejected(
            fn () => $this->workflow->transition(
                $this->ticket,
                'in_progress',
                $admin
            ),
            ValidationException::class
        );

        $this->workflow->assign(
            $this->ticket,
            $staff,
            $admin
        );

        $this->assertRejected(
            fn () => $this->workflow->transition(
                $this->ticket,
                'in_progress',
                $otherStaff
            ),
            AuthorizationException::class
        );

        $this->assertRejected(
            fn () => $this->workflow->transition(
                $this->ticket,
                'in_progress',
                $this->user('customer')
            ),
            AuthorizationException::class
        );

        $updated = $this->workflow->transition(
            $this->ticket,
            'in_progress',
            $admin
        );

        $this->assertSame(
            'in_progress',
            $updated->status
        );
    }

    public function test_skipped_backward_and_closed_transitions_fail(): void
    {
        $admin = $this->user('admin');
        $staff = $this->user('staff');

        $this->workflow->assign(
            $this->ticket,
            $staff,
            $admin
        );

        foreach (['resolved', 'closed', 'assigned'] as $invalid) {
            $this->assertRejected(
                fn () => $this->workflow->transition(
                    $this->ticket,
                    $invalid,
                    $staff
                ),
                ValidationException::class
            );
        }

        $this->assertSame(
            'assigned',
            $this->ticket->fresh()->status
        );

        foreach ([
            'in_progress',
            'resolved',
            'closed',
        ] as $status) {
            $this->workflow->transition(
                $this->ticket,
                $status,
                $staff
            );
        }

        $this->assertRejected(
            fn () => $this->workflow->transition(
                $this->ticket,
                'in_progress',
                $admin
            ),
            ValidationException::class
        );

        $this->assertRejected(
            fn () => $this->workflow->assign(
                $this->ticket,
                $staff,
                $admin
            ),
            ValidationException::class
        );

        $this->assertSame(
            'closed',
            $this->ticket->fresh()->status
        );
    }

    public function test_in_progress_ticket_cannot_be_reassigned(): void
    {
        $admin = $this->user('admin');
        $first = $this->user('staff');
        $second = $this->user('staff');

        $this->workflow->assign(
            $this->ticket,
            $first,
            $admin
        );

        $this->workflow->transition(
            $this->ticket,
            'in_progress',
            $first
        );

        $this->assertRejected(
            fn () => $this->workflow->assign(
                $this->ticket,
                $second,
                $admin
            ),
            ValidationException::class
        );

        $this->assertSame(
            $first->id,
            $this->ticket->fresh()->assigned_user_id
        );
    }
}