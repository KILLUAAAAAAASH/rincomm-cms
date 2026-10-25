<?php

namespace Tests\Feature;

use App\Mail\TicketStatusMail;
use App\Models\Customer;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\TicketCreationService;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class TicketCreationNotificationIntegrationTest extends TestCase
{
    use DatabaseTruncation;

    protected function setUp(): void
    {
        // SQLite :memory: connections do not survive between test cases.
        // Force a fresh migration instead of truncating missing tables.
        RefreshDatabaseState::$migrated = false;

        parent::setUp();
    }

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            // Prevent this test class from affecting other database tests.
            RefreshDatabaseState::$migrated = false;
        }
    }

    private function subscriber(): array
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $customer->user->forceFill([
            'role' => 'customer',
            'account_status' => 'active',
        ])->save();

        return [$customer->user->refresh(), $customer];
    }

    private function payload(): array
    {
        return [
            'request_type' => 'non_technical',
            'issue_type' => 'billing',
            'subject' => 'Billing clarification',
            'description' => 'Please explain the latest invoice.',
        ];
    }

    private function email(Customer $customer): string
    {
        return (string) (
            $customer->email ?: $customer->user?->email
        );
    }

    public function test_customer_creation_sends_one_confirmation_email(): void
    {
        [$user, $customer] = $this->subscriber();

        Mail::fake();

        $before = ServiceRequest::count();

        $response = $this->actingAs($user)->post(
            route('customer.tickets.store'),
            $this->payload()
        );

        $ticket = ServiceRequest::query()
            ->latest('id')
            ->firstOrFail();

        $response->assertRedirect(
            route('customer.tickets.show', $ticket->id)
        );

        $this->assertSame($before + 1, ServiceRequest::count());

        Mail::assertSent(
            TicketStatusMail::class,
            fn (TicketStatusMail $mail): bool =>
                $mail->hasTo($this->email($customer))
                && $mail->created === true
                && $mail->ticketNumber === $ticket->ticket_number
                && $mail->ticketSubject === $ticket->subject
                && $mail->status === 'open'
        );

        Mail::assertSentCount(1);
    }

    public function test_admin_creation_notifies_subscriber_once(): void
    {
        [, $customer] = $this->subscriber();

        $admin = User::factory()->create();

        $admin->forceFill([
            'role' => 'admin',
            'account_status' => 'active',
        ])->save();

        Mail::fake();

        $response = $this->actingAs($admin)->post(
            route('admin.tickets.store'),
            array_merge($this->payload(), [
                'customer_id' => $customer->id,
                'priority' => 'high',
            ])
        );

        $ticket = ServiceRequest::query()
            ->latest('id')
            ->firstOrFail();

        $response->assertRedirect(
            route('admin.tickets.show', $ticket->id)
        );

        Mail::assertSent(
            TicketStatusMail::class,
            fn (TicketStatusMail $mail): bool =>
                $mail->hasTo($this->email($customer))
                && $mail->created === true
                && $mail->ticketNumber === $ticket->ticket_number
        );

        Mail::assertSentCount(1);
    }

    public function test_invalid_ticket_does_not_send_email(): void
    {
        [$user] = $this->subscriber();

        Mail::fake();

        $before = ServiceRequest::count();

        $payload = $this->payload();
        $payload['issue_type'] = 'no_internet';

        $this->actingAs($user)
            ->post(route('customer.tickets.store'), $payload)
            ->assertSessionHasErrors('issue_type');

        $this->assertSame($before, ServiceRequest::count());

        Mail::assertNothingSent();
    }

    public function test_rolled_back_creation_does_not_send_email(): void
    {
        [, $customer] = $this->subscriber();

        Mail::fake();

        $before = ServiceRequest::count();

        try {
            DB::transaction(function () use ($customer): void {
                app(TicketCreationService::class)->create(
                    $customer,
                    $this->payload(),
                    false
                );

                Mail::assertNothingSent();

                throw new RuntimeException('Simulated rollback');
            });

            $this->fail('Expected transaction rollback.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Simulated rollback',
                $exception->getMessage()
            );
        }

        $this->assertSame($before, ServiceRequest::count());

        Mail::assertNothingSent();
    }
}