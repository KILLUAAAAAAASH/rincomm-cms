<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketConversationViewTest extends TestCase
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

        // Each test begins with a predictable conversation.
        $this->ticket->ticketMessages()->delete();
        $this->ticket->ticketNotes()->delete();
    }

    private function makeUser(string $role): User
    {
        $user = User::factory()->create();

        $user->forceFill([
            'role' => $role,
            'account_status' => 'active',
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

    private function staffUrl(): string
    {
        return route(
            'admin.tickets.show',
            $this->ticket->id
        );
    }

    private function customerUrl(): string
    {
        return route(
            'customer.tickets.show',
            $this->ticket->id
        );
    }

    public function test_admin_sees_replies_and_private_notes(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->makeOwner();

        $this->ticket->ticketMessages()->create([
            'user_id' => $customer->id,
            'message' => 'Customer billing clarification.',
        ]);

        $this->ticket->ticketMessages()->create([
            'user_id' => $admin->id,
            'message' => 'Administrator public response.',
        ]);

        $this->ticket->ticketNotes()->create([
            'user_id' => $admin->id,
            'note' => 'Private billing investigation.',
        ]);

        $this->actingAs($admin)
            ->get($this->staffUrl())
            ->assertOk()
            ->assertSeeText('Conversation')
            ->assertSeeText('Customer billing clarification.')
            ->assertSeeText('Administrator public response.')
            ->assertSeeText('Internal Staff Notes')
            ->assertSeeText('Private billing investigation.')
            ->assertSeeText($customer->name)
            ->assertSeeText($admin->name);
    }

    public function test_staff_sees_both_conversation_sections(): void
    {
        $staff = $this->makeUser('staff');

        $this->ticket->ticketMessages()->create([
            'user_id' => $staff->id,
            'message' => 'Public staff response.',
        ]);

        $this->ticket->ticketNotes()->create([
            'user_id' => $staff->id,
            'note' => 'Staff-only investigation.',
        ]);

        $this->actingAs($staff)
            ->get($this->staffUrl())
            ->assertOk()
            ->assertSeeText('Public staff response.')
            ->assertSeeText('Staff-only investigation.')
            ->assertSee(route('admin.tickets.replies.store', $this->ticket->id), false);
    }

    public function test_open_ticket_displays_reply_and_note_forms(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->get($this->staffUrl())
            ->assertOk()
            ->assertSeeText('No customer-visible replies')
            ->assertSeeText('No internal notes')
            ->assertSeeText('Reply to Subscriber')
            ->assertSeeText('Post Reply')
            ->assertSeeText('Add Private Internal Note')
            ->assertSeeText('Save Internal Note')
            ->assertSee(
                route('admin.tickets.replies.store', $this->ticket->id),
                false
            )
            ->assertSee(
                route('admin.tickets.notes.store', $this->ticket->id),
                false
            );
    }

    public function test_closed_ticket_displays_history_without_forms(): void
    {
        $admin = $this->makeUser('admin');

        $this->ticket->ticketMessages()->create([
            'user_id' => $admin->id,
            'message' => 'Final public reply.',
        ]);

        $this->ticket->ticketNotes()->create([
            'user_id' => $admin->id,
            'note' => 'Final private note.',
        ]);

        $this->ticket->update(['status' => 'closed']);

        $this->actingAs($admin)
            ->get($this->staffUrl())
            ->assertOk()
            ->assertSeeText('Final public reply.')
            ->assertSeeText('Final private note.')
            ->assertSeeText('No further replies can be posted.')
            ->assertSeeText('No further internal notes can be added.')
            ->assertDontSee('name="message"', false)
            ->assertDontSee('name="note"', false)
            ->assertDontSeeText('Post Reply')
            ->assertDontSeeText('Save Internal Note');
    }

    public function test_conversation_content_is_html_escaped(): void
    {
        $admin = $this->makeUser('admin');

        $dangerous = '<script>alert("ticket")</script>';

        $this->ticket->ticketMessages()->create([
            'user_id' => $admin->id,
            'message' => $dangerous,
        ]);

        $this->ticket->ticketNotes()->create([
            'user_id' => $admin->id,
            'note' => $dangerous,
        ]);

        $this->actingAs($admin)
            ->get($this->staffUrl())
            ->assertOk()
            ->assertSeeText($dangerous)
            ->assertDontSee($dangerous, false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_customer_cannot_access_staff_notes_page(): void
    {
        $owner = $this->makeOwner();
        $admin = $this->makeUser('admin');

        $secret = 'CONFIDENTIAL SUPPORT STAFF NOTE';

        $this->ticket->ticketNotes()->create([
            'user_id' => $admin->id,
            'note' => $secret,
        ]);

        $this->actingAs($owner)
            ->get($this->staffUrl())
            ->assertForbidden();

        $this->actingAs($owner)
            ->get($this->customerUrl())
            ->assertOk()
            ->assertDontSee($secret)
            ->assertDontSeeText('Internal Staff Notes')
            ->assertDontSeeText('Save Internal Note');
    }

    public function test_technician_cannot_view_conversation_page(): void
    {
        $technician = $this->makeUser('technician');

        $this->actingAs($technician)
            ->get($this->staffUrl())
            ->assertForbidden();
    }
}