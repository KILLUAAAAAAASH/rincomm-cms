<?php

namespace Tests\Feature;

use App\Models\JobOrder;
use App\Models\ServiceRequest;
use App\Models\Technician;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TechnicianSharedTicketNotesTest extends TestCase
{
    use RefreshDatabase;

    private JobOrder $jobOrder;
    private ServiceRequest $ticket;
    private User $technicianUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DemoDataSeeder::class);

        $this->ticket = ServiceRequest::query()->firstOrFail();

        $this->ticket->forceFill([
            'status' => 'open',
        ])->save();

        $technician = Technician::query()
            ->whereHas('user')
            ->firstOrFail();

        $this->technicianUser = $technician->user;

        $this->technicianUser->forceFill([
            'role' => 'technician',
            'account_status' => 'active',
        ])->save();

        $this->jobOrder = JobOrder::query()->firstOrFail();

        $this->jobOrder->forceFill([
            'technician_id' => $technician->id,
            'service_request_id' => $this->ticket->id,
            'status' => 'assigned',
        ])->save();
    }

    private function noteUrl(): string
    {
        return route(
            'technician.job-orders.ticket-notes.store',
            $this->jobOrder
        );
    }

    private function showUrl(): string
    {
        return route(
            'technician.job-orders.show',
            $this->jobOrder
        );
    }

    public function test_assigned_technician_can_read_and_post_shared_notes(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'account_status' => 'active',
        ]);

        $this->ticket->ticketNotes()->create([
            'user_id' => $staff->id,
            'note' => 'INTERNAL TEST: Staff diagnostic details',
        ]);

        $this->actingAs($this->technicianUser)
            ->get($this->showUrl())
            ->assertOk()
            ->assertSee('INTERNAL TEST: Staff diagnostic details')
            ->assertSee('Post Internal Note');

        Mail::fake();
        Notification::fake();

        $this->post($this->noteUrl(), [
            'note' => 'INTERNAL TEST: Technician field findings',
        ])
            ->assertRedirect($this->showUrl())
            ->assertSessionHas('success');

        $this->assertDatabaseHas('ticket_notes', [
            'service_request_id' => $this->ticket->id,
            'user_id' => $this->technicianUser->id,
            'note' => 'INTERNAL TEST: Technician field findings',
        ]);

        Mail::assertNothingSent();
        Notification::assertNothingSent();

        $this->actingAs($staff)
            ->get(route('admin.tickets.show', $this->ticket))
            ->assertOk()
            ->assertSee('INTERNAL TEST: Technician field findings');
    }

    public function test_unassigned_technician_cannot_access_notes(): void
    {
        $other = User::factory()->create([
            'role' => 'technician',
            'account_status' => 'active',
        ]);

        $this->actingAs($other)
            ->get($this->showUrl())
            ->assertNotFound();

        $this->post($this->noteUrl(), [
            'note' => 'INTERNAL TEST: Unauthorized',
        ])->assertNotFound();

        $this->assertDatabaseMissing('ticket_notes', [
            'service_request_id' => $this->ticket->id,
            'note' => 'INTERNAL TEST: Unauthorized',
        ]);
    }

    public function test_reassignment_revokes_technician_access(): void
    {
        $this->jobOrder->forceFill([
            'technician_id' => null,
        ])->save();

        $this->actingAs($this->technicianUser)
            ->get($this->showUrl())
            ->assertNotFound();

        $this->post($this->noteUrl(), [
            'note' => 'INTERNAL TEST: After reassignment',
        ])->assertNotFound();
    }

    public function test_completed_job_order_disables_ticket_notes(): void
    {
        $this->jobOrder->forceFill([
            'status' => 'completed',
        ])->save();

        $this->actingAs($this->technicianUser)
            ->get($this->showUrl())
            ->assertOk()
            ->assertDontSee('Post Internal Note');

        $this->post($this->noteUrl(), [
            'note' => 'INTERNAL TEST: Finished job',
        ])->assertNotFound();
    }

    public function test_closed_ticket_rejects_new_technician_notes(): void
    {
        $this->ticket->forceFill([
            'status' => 'closed',
        ])->save();

        $this->actingAs($this->technicianUser)
            ->get($this->showUrl())
            ->assertOk()
            ->assertDontSee('Post Internal Note');

        $this->post($this->noteUrl(), [
            'note' => 'INTERNAL TEST: Closed ticket',
        ])->assertSessionHasErrors('ticket');
    }

    public function test_customer_cannot_read_internal_ticket_notes(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'account_status' => 'active',
        ]);

        $this->ticket->customer->forceFill([
            'user_id' => $customer->id,
        ])->save();

        $this->ticket->ticketNotes()->create([
            'user_id' => $this->technicianUser->id,
            'note' => 'INTERNAL SECRET SENTINEL: customer must not see',
        ]);

        $this->actingAs($customer)
            ->get(route('customer.tickets.show', $this->ticket))
            ->assertOk()
            ->assertDontSee('INTERNAL SECRET SENTINEL');

        $this->post($this->noteUrl(), [
            'note' => 'INTERNAL TEST: Customer intrusion',
        ])->assertForbidden();
    }

    public function test_internal_note_validation_and_output_escaping(): void
    {
        $this->actingAs($this->technicianUser)
            ->post($this->noteUrl(), [
                'note' => '',
            ])
            ->assertSessionHasErrors('note');

        $this->post($this->noteUrl(), [
            'note' => '<script>alert(1)</script>',
        ])->assertRedirect($this->showUrl());

        $this->get($this->showUrl())
            ->assertOk()
            ->assertSee(
                '&lt;script&gt;alert(1)&lt;/script&gt;',
                false
            )
            ->assertDontSee(
                '<script>alert(1)</script>',
                false
            );
    }
}
