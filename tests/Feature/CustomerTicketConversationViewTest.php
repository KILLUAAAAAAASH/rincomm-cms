<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTicketConversationViewTest extends TestCase
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

    private function ticketUrl(): string
    {
        return route(
            'customer.tickets.show',
            $this->ticket->id
        );
    }

    public function test_customer_sees_own_and_support_replies(): void
    {
        $owner = $this->makeOwner();
        $staff = $this->makeUser('staff');

        $this->ticket->ticketMessages()->create([
            'user_id' => $owner->id,
            'message' => 'I need help with my invoice.',
        ]);

        $this->ticket->ticketMessages()->create([
            'user_id' => $staff->id,
            'message' => 'We will review your invoice.',
        ]);

        $this->actingAs($owner)
            ->get($this->ticketUrl())
            ->assertOk()
            ->assertSeeText('Support Conversation')
            ->assertSeeText('I need help with my invoice.')
            ->assertSeeText('We will review your invoice.')
            ->assertSeeText($owner->name)
            ->assertSeeText($staff->name)
            ->assertSeeText('Subscriber')
            ->assertSeeText('Rincomm Support');
    }

    public function test_replies_appear_in_chronological_order(): void
    {
        $owner = $this->makeOwner();

        $first = $this->ticket->ticketMessages()->create([
            'user_id' => $owner->id,
            'message' => 'FIRST CHRONOLOGICAL REPLY',
        ]);

        $second = $this->ticket->ticketMessages()->create([
            'user_id' => $owner->id,
            'message' => 'SECOND CHRONOLOGICAL REPLY',
        ]);

        $first->forceFill([
            'created_at' => now()->subMinutes(10),
        ])->save();

        $second->forceFill([
            'created_at' => now()->subMinutes(5),
        ])->save();

        $this->actingAs($owner)
            ->get($this->ticketUrl())
            ->assertOk()
            ->assertSeeInOrder([
                'FIRST CHRONOLOGICAL REPLY',
                'SECOND CHRONOLOGICAL REPLY',
            ]);
    }

    public function test_private_staff_notes_are_never_displayed(): void
    {
        $owner = $this->makeOwner();
        $admin = $this->makeUser('admin');

        $secret = 'CONFIDENTIAL RINCOMM INTERNAL NOTE 145';

        $this->ticket->ticketMessages()->create([
            'user_id' => $admin->id,
            'message' => 'This public reply is safe to display.',
        ]);

        $this->ticket->ticketNotes()->create([
            'user_id' => $admin->id,
            'note' => $secret,
        ]);

        $this->actingAs($owner)
            ->get($this->ticketUrl())
            ->assertOk()
            ->assertSeeText('This public reply is safe to display.')
            ->assertDontSee($secret, false)
            ->assertDontSeeText('Internal Staff Notes')
            ->assertDontSeeText('Private note')
            ->assertDontSee('name="note"', false)
            ->assertDontSee(
                route('admin.tickets.notes.store', $this->ticket->id),
                false
            );
    }

    public function test_open_ticket_displays_customer_reply_form(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->get($this->ticketUrl())
            ->assertOk()
            ->assertSeeText('Support Conversation')
            ->assertSeeText('No replies have been posted yet.')
            ->assertSeeText('Write a Reply')
            ->assertSeeText('Send Reply')
            ->assertSee('name="message"', false)
            ->assertSee(
                route(
                    'customer.tickets.replies.store',
                    $this->ticket->id
                ),
                false
            )
            ->assertDontSeeText('Save Internal Note');
    }

    public function test_closed_ticket_shows_history_without_reply_form(): void
    {
        $owner = $this->makeOwner();
        $staff = $this->makeUser('staff');

        $this->ticket->ticketMessages()->create([
            'user_id' => $staff->id,
            'message' => 'Final support resolution message.',
        ]);

        $this->ticket->update([
            'status' => 'closed',
        ]);

        $this->actingAs($owner)
            ->get($this->ticketUrl())
            ->assertOk()
            ->assertSeeText('Final support resolution message.')
            ->assertSeeText('This ticket is closed.')
            ->assertSeeText('no further replies can be posted.')
            ->assertDontSee('name="message"', false)
            ->assertDontSeeText('Send Reply');
    }

    public function test_customer_message_content_is_html_escaped(): void
    {
        $owner = $this->makeOwner();

        $dangerous = '<script>alert("customer")</script>';

        $this->ticket->ticketMessages()->create([
            'user_id' => $owner->id,
            'message' => $dangerous,
        ]);

        $this->actingAs($owner)
            ->get($this->ticketUrl())
            ->assertOk()
            ->assertSeeText($dangerous)
            ->assertDontSee($dangerous, false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_other_customer_cannot_view_conversation(): void
    {
        $owner = $this->makeOwner();
        $stranger = $this->makeUser('customer');

        $this->ticket->ticketMessages()->create([
            'user_id' => $owner->id,
            'message' => 'OWNER ONLY CONVERSATION',
        ]);

        $this->actingAs($stranger)
            ->get($this->ticketUrl())
            ->assertNotFound();
    }

    public function test_staff_and_technicians_cannot_view_customer_page(): void
    {
        foreach (['staff', 'technician'] as $role) {
            $actor = $this->makeUser($role);

            $this->actingAs($actor)
                ->get($this->ticketUrl())
                ->assertForbidden();
        }
    }
}