<?php

namespace Tests\Feature;

use App\Mail\TicketStatusMail;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\TicketWorkflowService;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class TicketWorkflowNotificationIntegrationTest extends TestCase
{
    use DatabaseTruncation;

    private ServiceRequest $ticket;

    protected function setUp(): void
    {
        RefreshDatabaseState::$migrated = false;

        parent::setUp();

        $this->seed(DemoDataSeeder::class);

        $this->ticket = ServiceRequest::query()->firstOrFail();

        $this->ticket->update([
            'status' => 'open',
            'assigned_user_id' => null,
            'assigned_at' => null,
        ]);
    }

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            RefreshDatabaseState::$migrated = false;
        }
    }

    private function user(string $role = 'staff'): User
    {
        $user = User::factory()->create();

        $user->forceFill([
            'role' => $role,
            'account_status' => 'active',
        ])->save();

        return $user->refresh();
    }

    private function subscriberEmail(): string
    {
        $customer = $this->ticket
            ->customer()
            ->with('user')
            ->firstOrFail();

        $email = trim((string) (
            $customer->email ?: $customer->user?->email
        ));

        $this->assertNotEmpty($email);

        return $email;
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

    public function test_complete_workflow_sends_four_status_emails(): void
    {
        $admin = $this->user('admin');
        $staff = $this->user('staff');

        $email = $this->subscriberEmail();

        Mail::fake();

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
                ->assertSessionHasNoErrors();

            $this->assertSame(
                $status,
                $this->ticket->fresh()->status
            );
        }

        foreach ([
            'assigned',
            'in_progress',
            'resolved',
            'closed',
        ] as $status) {
            Mail::assertSent(
                TicketStatusMail::class,
                fn (TicketStatusMail $mail): bool =>
                    $mail->hasTo($email)
                    && $mail->ticketNumber ===
                        $this->ticket->ticket_number
                    && $mail->created === false
                    && $mail->status === $status
            );
        }

        Mail::assertSentCount(4);
    }

    public function test_reassignment_does_not_send_duplicate_email(): void
    {
        $admin = $this->user('admin');
        $first = $this->user('staff');
        $second = $this->user('staff');

        Mail::fake();

        $workflow = app(TicketWorkflowService::class);

        $workflow->assign($this->ticket, $first, $admin);

        Mail::assertSentCount(1);

        $workflow->assign($this->ticket, $second, $admin);

        $this->assertSame(
            'assigned',
            $this->ticket->fresh()->status
        );

        $this->assertSame(
            $second->id,
            $this->ticket->fresh()->assigned_user_id
        );

        Mail::assertSentCount(1);
    }

    public function test_invalid_transition_sends_no_additional_email(): void
    {
        $admin = $this->user('admin');
        $staff = $this->user('staff');

        Mail::fake();

        $workflow = app(TicketWorkflowService::class);

        $workflow->assign($this->ticket, $staff, $admin);

        Mail::assertSentCount(1);

        try {
            $workflow->transition(
                $this->ticket,
                'closed',
                $admin
            );

            $this->fail('Expected invalid status transition.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'status',
                $exception->errors()
            );
        }

        $this->assertSame(
            'assigned',
            $this->ticket->fresh()->status
        );

        Mail::assertSentCount(1);
    }

    public function test_unauthorized_transition_sends_no_additional_email(): void
    {
        $admin = $this->user('admin');
        $assignedStaff = $this->user('staff');
        $otherStaff = $this->user('staff');

        Mail::fake();

        $workflow = app(TicketWorkflowService::class);

        $workflow->assign(
            $this->ticket,
            $assignedStaff,
            $admin
        );

        Mail::assertSentCount(1);

        try {
            $workflow->transition(
                $this->ticket,
                'in_progress',
                $otherStaff
            );

            $this->fail('Expected authorization exception.');
        } catch (AuthorizationException $exception) {
            $this->assertNotEmpty(
                $exception->getMessage()
            );
        }

        $this->assertSame(
            'assigned',
            $this->ticket->fresh()->status
        );

        Mail::assertSentCount(1);
    }

    public function test_rolled_back_assignment_sends_no_email(): void
    {
        $admin = $this->user('admin');
        $staff = $this->user('staff');

        Mail::fake();

        try {
            DB::transaction(function () use (
                $admin,
                $staff
            ): void {
                app(TicketWorkflowService::class)->assign(
                    $this->ticket,
                    $staff,
                    $admin
                );

                Mail::assertNothingSent();

                throw new RuntimeException(
                    'Simulated workflow rollback'
                );
            });

            $this->fail('Expected workflow rollback.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Simulated workflow rollback',
                $exception->getMessage()
            );
        }

        $ticket = $this->ticket->fresh();

        $this->assertSame('open', $ticket->status);
        $this->assertNull($ticket->assigned_user_id);

        Mail::assertNothingSent();
    }
}