<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\TicketMessage;
use App\Models\TicketNote;
use App\Models\User;
use App\Services\TicketConversationService;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Throwable;

class TicketConversationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ServiceRequest $ticket;

    private TicketConversationService $conversation;

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

        $this->conversation = app(
            TicketConversationService::class
        );
    }

    private function makeUser(
        string $role,
        string $accountStatus = 'active'
    ): User {
        $user = User::factory()->create();

        $user->forceFill([
            'role' => $role,
            'account_status' => $accountStatus,
        ])->save();

        return $user->refresh();
    }

    private function makeOwner(
        string $accountStatus = 'active'
    ): User {
        $user = $this->makeUser(
            'customer',
            $accountStatus
        );

        $this->ticket->customer->forceFill([
            'user_id' => $user->id,
        ])->save();

        return $user;
    }

    private function assertRejected(
        callable $operation,
        string $exceptionClass
    ): void {
        try {
            $operation();
        } catch (Throwable $exception) {
            $this->assertInstanceOf(
                $exceptionClass,
                $exception
            );

            return;
        }

        $this->fail(
            "Expected exception: {$exceptionClass}"
        );
    }

    public function test_owning_customer_can_reply(): void
    {
        $owner = $this->makeOwner();

        $before = $this->ticket
            ->ticketMessages()
            ->count();

        $message = $this->conversation->reply(
            $this->ticket,
            $owner,
            'Please check my billing concern.'
        );

        $this->assertInstanceOf(
            TicketMessage::class,
            $message
        );

        $this->assertSame(
            $this->ticket->id,
            $message->service_request_id
        );

        $this->assertSame(
            $owner->id,
            $message->user_id
        );

        $this->assertSame(
            'Please check my billing concern.',
            $message->message
        );

        $this->assertSame(
            $before + 1,
            $this->ticket->ticketMessages()->count()
        );

        $this->assertSame(
            'open',
            $this->ticket->fresh()->status
        );
    }

    public function test_admin_and_staff_can_reply_to_ticket(): void
    {
        foreach (['admin', 'staff'] as $role) {
            $actor = $this->makeUser($role);

            $message = $this->conversation->reply(
                $this->ticket,
                $actor,
                "Reply from {$role}"
            );

            $this->assertSame(
                $actor->id,
                $message->user_id
            );

            $this->assertSame(
                "Reply from {$role}",
                $message->message
            );
        }
    }

    public function test_other_customer_cannot_reply(): void
    {
        $this->makeOwner();

        $stranger = $this->makeUser('customer');

        $before = $this->ticket
            ->ticketMessages()
            ->count();

        $this->assertRejected(
            fn () => $this->conversation->reply(
                $this->ticket,
                $stranger,
                'Unauthorized customer reply'
            ),
            AuthorizationException::class
        );

        $this->assertSame(
            $before,
            $this->ticket->ticketMessages()->count()
        );
    }

    public function test_technician_cannot_reply(): void
    {
        $technician = $this->makeUser('technician');

        $this->assertRejected(
            fn () => $this->conversation->reply(
                $this->ticket,
                $technician,
                'Technician reply'
            ),
            AuthorizationException::class
        );
    }

    public function test_inactive_accounts_cannot_reply(): void
    {
        $inactiveCustomer = $this->makeOwner('inactive');

        foreach ([
            $inactiveCustomer,
            $this->makeUser('staff', 'inactive'),
            $this->makeUser('admin', 'inactive'),
        ] as $actor) {
            $this->assertRejected(
                fn () => $this->conversation->reply(
                    $this->ticket,
                    $actor,
                    'Inactive account reply'
                ),
                AuthorizationException::class
            );
        }
    }

    public function test_internal_notes_are_stored_separately(): void
    {
        $staff = $this->makeUser('staff');

        $messageCount = $this->ticket
            ->ticketMessages()
            ->count();

        $noteCount = $this->ticket
            ->ticketNotes()
            ->count();

        $note = $this->conversation->addInternalNote(
            $this->ticket,
            $staff,
            'Private billing investigation details.'
        );

        $this->assertInstanceOf(
            TicketNote::class,
            $note
        );

        $this->assertSame(
            $staff->id,
            $note->user_id
        );

        $this->assertSame(
            'Private billing investigation details.',
            $note->note
        );

        $this->assertSame(
            $noteCount + 1,
            $this->ticket->ticketNotes()->count()
        );

        $this->assertSame(
            $messageCount,
            $this->ticket->ticketMessages()->count()
        );

        $admin = $this->makeUser('admin');

        $adminNote = $this->conversation->addInternalNote(
            $this->ticket,
            $admin,
            'Administrative follow-up required.'
        );

        $this->assertSame(
            $admin->id,
            $adminNote->user_id
        );
    }

    public function test_customers_and_technicians_cannot_add_internal_notes(): void
    {
        $customer = $this->makeOwner();
        $technician = $this->makeUser('technician');

        $before = $this->ticket
            ->ticketNotes()
            ->count();

        foreach ([$customer, $technician] as $actor) {
            $this->assertRejected(
                fn () => $this->conversation->addInternalNote(
                    $this->ticket,
                    $actor,
                    'Attempted private note'
                ),
                AuthorizationException::class
            );
        }

        $this->assertSame(
            $before,
            $this->ticket->ticketNotes()->count()
        );
    }

    public function test_inactive_staff_and_admin_cannot_add_notes(): void
    {
        foreach (['staff', 'admin'] as $role) {
            $actor = $this->makeUser(
                $role,
                'inactive'
            );

            $this->assertRejected(
                fn () => $this->conversation->addInternalNote(
                    $this->ticket,
                    $actor,
                    'Inactive employee note'
                ),
                AuthorizationException::class
            );
        }
    }

    public function test_closed_tickets_reject_replies_and_notes(): void
    {
        $customer = $this->makeOwner();
        $staff = $this->makeUser('staff');

        $this->ticket->update([
            'status' => 'closed',
        ]);

        foreach ([$customer, $staff] as $actor) {
            $this->assertRejected(
                fn () => $this->conversation->reply(
                    $this->ticket,
                    $actor,
                    'Reply after closure'
                ),
                ValidationException::class
            );
        }

        $this->assertRejected(
            fn () => $this->conversation->addInternalNote(
                $this->ticket,
                $staff,
                'Note after closure'
            ),
            ValidationException::class
        );
    }

    public function test_blank_and_oversized_content_is_rejected(): void
    {
        $staff = $this->makeUser('staff');

        $longText = str_repeat('X', 5001);

        foreach (['   ', $longText] as $content) {
            $this->assertRejected(
                fn () => $this->conversation->reply(
                    $this->ticket,
                    $staff,
                    $content
                ),
                ValidationException::class
            );

            $this->assertRejected(
                fn () => $this->conversation->addInternalNote(
                    $this->ticket,
                    $staff,
                    $content
                ),
                ValidationException::class
            );
        }
    }

    public function test_non_closed_ticket_statuses_allow_communication(): void
    {
        $staff = $this->makeUser('staff');

        foreach ([
            'open',
            'assigned',
            'in_progress',
            'resolved',
        ] as $status) {
            $this->ticket->update([
                'status' => $status,
            ]);

            $message = $this->conversation->reply(
                $this->ticket,
                $staff,
                "Message while {$status}"
            );

            $note = $this->conversation->addInternalNote(
                $this->ticket,
                $staff,
                "Note while {$status}"
            );

            $this->assertSame(
                "Message while {$status}",
                $message->message
            );

            $this->assertSame(
                "Note while {$status}",
                $note->note
            );

            $this->assertSame(
                $status,
                $this->ticket->fresh()->status
            );
        }
    }

    public function test_content_is_trimmed_before_storage(): void
    {
        $staff = $this->makeUser('staff');

        $message = $this->conversation->reply(
            $this->ticket,
            $staff,
            '   Customer-facing reply   '
        );

        $note = $this->conversation->addInternalNote(
            $this->ticket,
            $staff,
            '   Private internal note   '
        );

        $this->assertSame(
            'Customer-facing reply',
            $message->message
        );

        $this->assertSame(
            'Private internal note',
            $note->note
        );
    }
}