<?php

namespace Tests\Feature;

use App\Models\JobOrder;
use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketWorkflowHttpTest extends TestCase
{
    use RefreshDatabase;

    private ServiceRequest $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DemoDataSeeder::class);

        $this->ticket = ServiceRequest::query()->firstOrFail();

        $this->ticket->update([
            'status' => 'open',
            'assigned_user_id' => null,
            'assigned_at' => null,
        ]);
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

    private function assignmentUrl(): string
    {
        return route(
            'admin.tickets.assignment.update',
            $this->ticket->id
        );
    }

    private function statusUrl(): string
    {
        return route(
            'admin.tickets.status.update',
            $this->ticket->id
        );
    }

    public function test_admin_can_assign_ticket_without_creating_job_order(): void
    {
        $admin = $this->user('admin');
        $staff = $this->user('staff');

        $before = JobOrder::count();

        $this->actingAs($admin)
            ->patch($this->assignmentUrl(), [
                'assigned_user_id' => $staff->id,
            ])
            ->assertRedirect(
                route('admin.tickets.show', $this->ticket->id)
            )
            ->assertSessionHasNoErrors();

        $ticket = $this->ticket->fresh();

        $this->assertSame('assigned', $ticket->status);
        $this->assertSame(
            $staff->id,
            $ticket->assigned_user_id
        );
        $this->assertNotNull($ticket->assigned_at);
        $this->assertSame($before, JobOrder::count());
    }

    public function test_active_staff_can_assign_and_reassign_ticket(): void
    {
        $actor = $this->user('staff');
        $first = $this->user('staff');
        $second = $this->user('staff');

        $this->actingAs($actor)
            ->patch($this->assignmentUrl(), [
                'assigned_user_id' => $first->id,
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($actor)
            ->patch($this->assignmentUrl(), [
                'assigned_user_id' => $second->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            $second->id,
            $this->ticket->fresh()->assigned_user_id
        );
    }

    public function test_invalid_and_ineligible_assignees_are_rejected(): void
    {
        $admin = $this->user('admin');

        foreach ([
            $this->user('customer')->id,
            $this->user('technician')->id,
            $this->user('staff', 'inactive')->id,
            $this->user('admin', 'inactive')->id,
            99999999,
        ] as $assigneeId) {
            $this->actingAs($admin)
                ->patch($this->assignmentUrl(), [
                    'assigned_user_id' => $assigneeId,
                ])
                ->assertSessionHasErrors('assigned_user_id');

            $this->assertSame(
                'open',
                $this->ticket->fresh()->status
            );

            $this->assertNull(
                $this->ticket->fresh()->assigned_user_id
            );
        }
    }

    public function test_customer_and_technician_cannot_manage_workflow(): void
    {
        $staff = $this->user('staff');

        foreach (['customer', 'technician'] as $role) {
            $actor = $this->user($role);

            $this->actingAs($actor)
                ->patch($this->assignmentUrl(), [
                    'assigned_user_id' => $staff->id,
                ])
                ->assertForbidden();

            $this->actingAs($actor)
                ->patch($this->statusUrl(), [
                    'status' => 'in_progress',
                ])
                ->assertForbidden();
        }

        $this->assertSame(
            'open',
            $this->ticket->fresh()->status
        );
    }

    public function test_status_cannot_advance_before_assignment(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->patch($this->statusUrl(), [
                'status' => 'in_progress',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(
            'open',
            $this->ticket->fresh()->status
        );
    }

    public function test_assigned_staff_can_complete_ticket_status_workflow(): void
    {
        $admin = $this->user('admin');
        $staff = $this->user('staff');

        $this->actingAs($admin)
            ->patch($this->assignmentUrl(), [
                'assigned_user_id' => $staff->id,
            ])
            ->assertSessionHasNoErrors();

        foreach ([
            'in_progress',
            'resolved',
            'closed',
        ] as $status) {
            $this->actingAs($staff)
                ->patch($this->statusUrl(), [
                    'status' => $status,
                ])
                ->assertRedirect(
                    route('admin.tickets.show', $this->ticket->id)
                )
                ->assertSessionHasNoErrors();

            $this->assertSame(
                $status,
                $this->ticket->fresh()->status
            );
        }
    }

    public function test_other_staff_cannot_advance_assigned_ticket(): void
    {
        $admin = $this->user('admin');
        $assignee = $this->user('staff');
        $otherStaff = $this->user('staff');

        $this->actingAs($admin)
            ->patch($this->assignmentUrl(), [
                'assigned_user_id' => $assignee->id,
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($otherStaff)
            ->patch($this->statusUrl(), [
                'status' => 'in_progress',
            ])
            ->assertForbidden();

        $this->assertSame(
            'assigned',
            $this->ticket->fresh()->status
        );

        $this->actingAs($admin)
            ->patch($this->statusUrl(), [
                'status' => 'in_progress',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            'in_progress',
            $this->ticket->fresh()->status
        );
    }

    public function test_invalid_transitions_and_closed_ticket_edits_are_rejected(): void
    {
        $admin = $this->user('admin');
        $staff = $this->user('staff');

        $this->actingAs($admin)
            ->patch($this->assignmentUrl(), [
                'assigned_user_id' => $staff->id,
            ])
            ->assertSessionHasNoErrors();

        foreach (['resolved', 'closed', 'assigned', 'invalid'] as $status) {
            $this->actingAs($admin)
                ->patch($this->statusUrl(), [
                    'status' => $status,
                ])
                ->assertSessionHasErrors('status');

            $this->assertSame(
                'assigned',
                $this->ticket->fresh()->status
            );
        }

        foreach ([
            'in_progress',
            'resolved',
            'closed',
        ] as $status) {
            $this->actingAs($admin)
                ->patch($this->statusUrl(), [
                    'status' => $status,
                ])
                ->assertSessionHasNoErrors();
        }

        $this->actingAs($admin)
            ->patch($this->assignmentUrl(), [
                'assigned_user_id' => $staff->id,
            ])
            ->assertSessionHasErrors('assigned_user_id');

        $this->actingAs($admin)
            ->patch($this->statusUrl(), [
                'status' => 'in_progress',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(
            'closed',
            $this->ticket->fresh()->status
        );
    }
}