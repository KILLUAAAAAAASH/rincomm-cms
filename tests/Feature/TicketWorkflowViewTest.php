<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketWorkflowViewTest extends TestCase
{
    use RefreshDatabase;

    private ServiceRequest $ticket;

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
    }

    private function user(
        string $role = 'staff',
        string $status = 'active',
        ?string $name = null
    ): User {
        $user = User::factory()->create();

        $attributes = [
            'role' => $role,
            'account_status' => $status,
        ];

        if ($name !== null) {
            $attributes['name'] = $name;
        }

        $user->forceFill($attributes)->save();

        return $user->refresh();
    }

    private function ticketUrl(): string
    {
        return route(
            'admin.tickets.show',
            $this->ticket->id
        );
    }

    private function assign(
        User $user,
        string $status = 'assigned'
    ): void {
        $this->ticket->update([
            'assigned_user_id' => $user->id,
            'assigned_at' => now(),
            'status' => $status,
        ]);
    }

    public function test_open_ticket_shows_assignment_without_status_action(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->get($this->ticketUrl())
            ->assertOk()
            ->assertSee('Manage Ticket Workflow')
            ->assertSee('Assign Support Employee')
            ->assertSee('Assign Ticket')
            ->assertDontSee('Move to In Progress')
            ->assertDontSee(
                'admin/tickets/'.$this->ticket->id.'/status'
            );
    }

    public function test_assigned_staff_sees_next_status_action(): void
    {
        $staff = $this->user(
            'staff',
            'active',
            'Rincomm Assigned Agent'
        );

        $this->assign($staff);

        $this->actingAs($staff)
            ->get($this->ticketUrl())
            ->assertOk()
            ->assertSee('Rincomm Assigned Agent')
            ->assertSee('Update Assignment')
            ->assertSee('Move to In Progress')
            ->assertSee(
                route(
                    'admin.tickets.status.update',
                    $this->ticket->id
                ),
                false
            );
    }

    public function test_other_staff_cannot_see_status_action(): void
    {
        $assignee = $this->user('staff');
        $otherStaff = $this->user('staff');

        $this->assign($assignee);

        $this->actingAs($otherStaff)
            ->get($this->ticketUrl())
            ->assertOk()
            ->assertSee('Manage Ticket Workflow')
            ->assertDontSee('Move to In Progress')
            ->assertSee(
                'Only the assigned support employee or an administrator'
            );
    }

    public function test_in_progress_ticket_shows_resolve_action(): void
    {
        $admin = $this->user('admin');
        $assignee = $this->user('staff');

        $this->assign($assignee, 'in_progress');

        $this->actingAs($admin)
            ->get($this->ticketUrl())
            ->assertOk()
            ->assertSee('Move to Resolved')
            ->assertDontSee('Assign Support Employee')
            ->assertDontSee('Move to Closed');
    }

    public function test_resolved_ticket_shows_close_action(): void
    {
        $admin = $this->user('admin');
        $assignee = $this->user('staff');

        $this->assign($assignee, 'resolved');

        $this->actingAs($admin)
            ->get($this->ticketUrl())
            ->assertOk()
            ->assertSee('Move to Closed')
            ->assertDontSee('Move to Resolved')
            ->assertDontSee('Assign Support Employee');
    }

    public function test_closed_ticket_shows_no_workflow_forms(): void
    {
        $admin = $this->user('admin');
        $assignee = $this->user('staff');

        $this->assign($assignee, 'closed');

        $this->actingAs($admin)
            ->get($this->ticketUrl())
            ->assertOk()
            ->assertSee(
                'No further workflow changes are permitted.'
            )
            ->assertDontSee('Assign Support Employee')
            ->assertDontSee('Move to In Progress')
            ->assertDontSee('Move to Resolved')
            ->assertDontSee('Move to Closed');
    }

    public function test_assignment_dropdown_excludes_ineligible_users(): void
    {
        $admin = $this->user(
            'admin',
            'active',
            'Rincomm Active Manager'
        );

        $staff = $this->user(
            'staff',
            'active',
            'Rincomm Eligible Agent'
        );

        $this->user(
            'staff',
            'inactive',
            'Rincomm Inactive Agent'
        );

        $this->user(
            'technician',
            'active',
            'Rincomm Field Technician'
        );

        $this->user(
            'customer',
            'active',
            'Rincomm Excluded Customer'
        );

        $this->actingAs($admin)
            ->get($this->ticketUrl())
            ->assertOk()
            ->assertSee('Rincomm Active Manager')
            ->assertSee('Rincomm Eligible Agent')
            ->assertDontSee('Rincomm Inactive Agent')
            ->assertDontSee('Rincomm Field Technician')
            ->assertDontSee('Rincomm Excluded Customer');
    }

    public function test_customers_and_technicians_cannot_view_staff_ticket_details(): void
    {
        foreach (['customer', 'technician'] as $role) {
            $this->actingAs($this->user($role))
                ->get($this->ticketUrl())
                ->assertForbidden();
        }
    }
}