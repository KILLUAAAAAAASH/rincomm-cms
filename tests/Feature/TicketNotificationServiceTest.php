<?php

namespace Tests\Feature;

use App\Mail\TicketStatusMail;
use App\Models\Customer;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\TicketNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class TicketNotificationServiceTest extends TestCase
{
    private function ticket(
        ?string $customerEmail = 'subscriber@example.test',
        ?string $accountEmail = 'account@example.test'
    ): ServiceRequest {
        $user = new User();

        $user->forceFill([
            'id' => 10,
            'email' => $accountEmail,
        ]);

        $customer = new Customer();

        $customer->forceFill([
            'id' => 5,
            'user_id' => 10,
            'first_name' => 'Demo',
            'last_name' => 'Subscriber',
            'email' => $customerEmail,
        ]);

        $customer->setRelation('user', $user);

        $ticket = new ServiceRequest();

        $ticket->forceFill([
            'id' => 15,
            'customer_id' => 5,
            'ticket_number' => 'TKT-000015',
            'subject' => 'Intermittent internet connection',
            'status' => 'open',
        ]);

        $ticket->setRelation('customer', $customer);

        return $ticket;
    }

    public function test_creation_email_is_sent_only_after_commit(): void
    {
        Mail::fake();

        $ticket = $this->ticket();
        $service = app(TicketNotificationService::class);

        DB::transaction(function () use ($ticket, $service): void {
            $service->ticketCreated($ticket);

            Mail::assertNothingSent();
        });

        Mail::assertSent(
            TicketStatusMail::class,
            function (TicketStatusMail $mail): bool {
                return $mail->hasTo('subscriber@example.test')
                    && $mail->created === true
                    && $mail->ticketNumber === 'TKT-000015'
                    && $mail->status === 'open'
                    && $mail->ticketSubject ===
                        'Intermittent internet connection';
            }
        );

        Mail::assertSentCount(1);
    }

    public function test_status_email_is_sent_after_commit(): void
    {
        Mail::fake();

        $ticket = $this->ticket();
        $ticket->status = 'resolved';

        $service = app(TicketNotificationService::class);

        DB::transaction(function () use ($ticket, $service): void {
            $service->ticketStatusChanged($ticket);

            Mail::assertNothingSent();
        });

        Mail::assertSent(
            TicketStatusMail::class,
            fn (TicketStatusMail $mail): bool =>
                $mail->created === false
                && $mail->status === 'resolved'
                && $mail->hasTo('subscriber@example.test')
        );

        Mail::assertSentCount(1);
    }

    public function test_rolled_back_transaction_sends_no_email(): void
    {
        Mail::fake();

        $service = app(TicketNotificationService::class);

        try {
            DB::transaction(function () use ($service): void {
                $service->ticketCreated($this->ticket());

                Mail::assertNothingSent();

                throw new RuntimeException('Simulated rollback');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Simulated rollback',
                $exception->getMessage()
            );
        }

        Mail::assertNothingSent();
    }

    public function test_invalid_subscriber_email_is_skipped(): void
    {
        Mail::fake();
        Log::spy();

        $ticket = $this->ticket(
            'invalid-email',
            'invalid-account-email'
        );

        DB::transaction(function () use ($ticket): void {
            app(TicketNotificationService::class)
                ->ticketCreated($ticket);
        });

        Mail::assertNothingSent();

        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($message, $context) =>
                $message ===
                    'Ticket notification skipped: invalid subscriber email.'
                && $context['ticket_id'] === 15
            )
            ->once();
    }

    public function test_account_email_is_used_when_customer_email_missing(): void
    {
        Mail::fake();

        $ticket = $this->ticket(
            '',
            'account@example.test'
        );

        DB::transaction(function () use ($ticket): void {
            app(TicketNotificationService::class)
                ->ticketCreated($ticket);
        });

        Mail::assertSent(
            TicketStatusMail::class,
            fn (TicketStatusMail $mail): bool =>
                $mail->hasTo('account@example.test')
        );

        Mail::assertSentCount(1);
    }

    public function test_delivery_failure_does_not_abort_transaction(): void
    {
        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new RuntimeException('Simulated mail failure'));

        Log::spy();

        $completed = false;

        DB::transaction(function () use (&$completed): void {
            app(TicketNotificationService::class)
                ->ticketStatusChanged($this->ticket());

            $completed = true;
        });

        $this->assertTrue($completed);

        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($message, $context) =>
                $message === 'Ticket email notification failed.'
                && $context['ticket_id'] === 15
                && $context['notification_type'] === 'status_updated'
            )
            ->once();
    }
}