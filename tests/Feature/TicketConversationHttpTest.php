<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketConversationHttpTest extends TestCase
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

    private function makeUser(
        string $role,
        string $status = 'active'
    ): User {
        $user = User::factory()->create();

        $user->forceFill([
            'role' => $role,
            'account_status' => $status,
        ])->save();

        return $user->refresh();
    }

    private function makeOwner(): User
    {
        $owner = $this->makeUser('customer');

        $this->ticket->customer->forceFill([
            'user_id' => $owner->id,
        ])->save();

        return $owner;
    }

    private function adminReplyUrl(): string
    {
        return route(
            'admin.tickets.replies.store',
            $this->ticket->id
        );
    }

    private function adminNoteUrl(): string
    {
        return route(
            'admin.tickets.notes.store',
            $this->ticket->id
        );
    }

    private function customerReplyUrl(): string
    {
        return route(
            'customer.tickets.replies.store',
            $this->ticket->id
        );
    }

    public function test_admin_can_post_customer_visible_reply(): void
    {
        $admin = $this->makeUser('admin');

        $before = $this->ticket->ticketMessages()->count();

        $this->actingAs($admin)
            ->post($this->adminReplyUrl(), [
                'message' => 'We are reviewing your concern.',
            ])
            ->assertRedirect(
                route('admin.tickets.show', $this->ticket->id)
            )
            ->assertSessionHas('success');

        $this->assertSame(
            $before + 1,
            $this->ticket->ticketMessages()->count()
        );

        $this->assertDatabaseHas('ticket_messages', [
            'service_request_id' => $this->ticket->id,
            'user_id' => $admin->id,
            'message' => 'We are reviewing your concern.',
        ]);
    }

    public function test_staff_can_post_customer_visible_reply(): void
    {
        $staff = $this->makeUser('staff');

        $this->actingAs($staff)
            ->post($this->adminReplyUrl(), [
                'message' => 'Our support team is investigating.',
            ])
            ->assertRedirect(
                route('admin.tickets.show', $this->ticket->id)
            );

        $this->assertDatabaseHas('ticket_messages', [
            'service_request_id' => $this->ticket->id,
            'user_id' => $staff->id,
            'message' => 'Our support team is investigating.',
        ]);
    }

    public function test_support_employees_can_add_private_notes(): void
    {
        foreach (['admin', 'staff'] as $role) {
            $employee = $this->makeUser($role);

            $note = "Private investigation by {$role}";

            $this->actingAs($employee)
                ->post($this->adminNoteUrl(), [
                    'note' => $note,
                ])
                ->assertRedirect(
                    route('admin.tickets.show', $this->ticket->id)
                );

            $this->assertDatabaseHas('ticket_notes', [
                'service_request_id' => $this->ticket->id,
                'user_id' => $employee->id,
                'note' => $note,
            ]);
        }

        $this->assertSame(
            0,
            $this->ticket->ticketMessages()
                ->where('message', 'like', 'Private investigation%')
                ->count()
        );
    }

    public function test_customer_can_reply_to_owned_ticket(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->post($this->customerReplyUrl(), [
                'message' => 'Thank you for the update.',
            ])
            ->assertRedirect(
                route('customer.tickets.show', $this->ticket->id)
            )
            ->assertSessionHas('success');

        $this->assertDatabaseHas('ticket_messages', [
            'service_request_id' => $this->ticket->id,
            'user_id' => $owner->id,
            'message' => 'Thank you for the update.',
        ]);
    }

    public function test_other_customer_cannot_reply(): void
    {
        $this->makeOwner();

        $stranger = $this->makeUser('customer');

        $before = $this->ticket->ticketMessages()->count();

        $this->actingAs($stranger)
            ->post($this->customerReplyUrl(), [
                'message' => 'Unauthorized reply attempt.',
            ])
            ->assertNotFound();

        $this->assertSame(
            $before,
            $this->ticket->ticketMessages()->count()
        );
    }

    public function test_customer_cannot_create_internal_note(): void
    {
        $owner = $this->makeOwner();

        $before = $this->ticket->ticketNotes()->count();

        $this->actingAs($owner)
            ->post($this->adminNoteUrl(), [
                'note' => 'Customer should not create this.',
            ])
            ->assertForbidden();

        $this->assertSame(
            $before,
            $this->ticket->ticketNotes()->count()
        );
    }

    public function test_technician_cannot_access_conversation_endpoints(): void
    {
        $technician = $this->makeUser('technician');

        $beforeMessages = $this->ticket
            ->ticketMessages()->count();

        $beforeNotes = $this->ticket
            ->ticketNotes()->count();

        foreach ([
            [$this->adminReplyUrl(), ['message' => 'Unauthorized']],
            [$this->adminNoteUrl(), ['note' => 'Unauthorized']],
            [$this->customerReplyUrl(), ['message' => 'Unauthorized']],
        ] as [$url, $payload]) {
            $this->actingAs($technician)
                ->post($url, $payload)
                ->assertForbidden();
        }

        $this->assertSame(
            $beforeMessages,
            $this->ticket->ticketMessages()->count()
        );

        $this->assertSame(
            $beforeNotes,
            $this->ticket->ticketNotes()->count()
        );
    }

    public function test_staff_cannot_access_customer_reply_route(): void
    {
        $staff = $this->makeUser('staff');

        $this->actingAs($staff)
            ->post($this->customerReplyUrl(), [
                'message' => 'Incorrect role endpoint.',
            ])
            ->assertForbidden();
    }

    public function test_guests_cannot_submit_conversation_content(): void
    {
        foreach ([
            [$this->adminReplyUrl(), ['message' => 'Guest reply']],
            [$this->adminNoteUrl(), ['note' => 'Guest note']],
            [$this->customerReplyUrl(), ['message' => 'Guest reply']],
        ] as [$url, $payload]) {
            $this->post($url, $payload)
                ->assertRedirect(route('login'));
        }
    }

    public function test_blank_and_oversized_replies_are_rejected(): void
    {
        $admin = $this->makeUser('admin');

        $before = $this->ticket->ticketMessages()->count();

        foreach (['', str_repeat('X', 5001)] as $message) {
            $this->actingAs($admin)
                ->from(route('admin.tickets.show', $this->ticket->id))
                ->post($this->adminReplyUrl(), [
                    'message' => $message,
                ])
                ->assertSessionHasErrors('message');
        }

        $this->assertSame(
            $before,
            $this->ticket->ticketMessages()->count()
        );
    }

    public function test_invalid_internal_notes_are_rejected(): void
    {
        $staff = $this->makeUser('staff');

        $before = $this->ticket->ticketNotes()->count();

        foreach (['', str_repeat('X', 5001)] as $note) {
            $this->actingAs($staff)
                ->from(route('admin.tickets.show', $this->ticket->id))
                ->post($this->adminNoteUrl(), [
                    'note' => $note,
                ])
                ->assertSessionHasErrors('note');
        }

        $this->assertSame(
            $before,
            $this->ticket->ticketNotes()->count()
        );
    }

    public function test_closed_tickets_reject_staff_replies_and_notes(): void
    {
        $admin = $this->makeUser('admin');

        $this->ticket->update(['status' => 'closed']);

        $beforeMessages = $this->ticket
            ->ticketMessages()->count();

        $beforeNotes = $this->ticket
            ->ticketNotes()->count();

        $this->actingAs($admin)
            ->from(route('admin.tickets.show', $this->ticket->id))
            ->post($this->adminReplyUrl(), [
                'message' => 'Reply after ticket closure.',
            ])
            ->assertSessionHasErrors('ticket');

        $this->actingAs($admin)
            ->from(route('admin.tickets.show', $this->ticket->id))
            ->post($this->adminNoteUrl(), [
                'note' => 'Note after ticket closure.',
            ])
            ->assertSessionHasErrors('ticket');

        $this->assertSame(
            $beforeMessages,
            $this->ticket->ticketMessages()->count()
        );

        $this->assertSame(
            $beforeNotes,
            $this->ticket->ticketNotes()->count()
        );
    }

    public function test_closed_ticket_rejects_customer_reply(): void
    {
        $owner = $this->makeOwner();

        $this->ticket->update(['status' => 'closed']);

        $before = $this->ticket
            ->ticketMessages()->count();

        $this->actingAs($owner)
            ->from(route('customer.tickets.show', $this->ticket->id))
            ->post($this->customerReplyUrl(), [
                'message' => 'Reply after closure.',
            ])
            ->assertSessionHasErrors('ticket');

        $this->assertSame(
            $before,
            $this->ticket->ticketMessages()->count()
        );
    }
}