<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\CashPaymentService;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class CashPaymentEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_staff_can_view_cash_payment_controls_on_eligible_invoice(): void
    {
        $this->seed(DemoDataSeeder::class);

        $admin = $this->createActiveUser('admin');
        $staff = $this->createActiveUser('staff');
        $invoice = $this->createInvoice();

        $this->actingAs($admin)
            ->get(route('admin.invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Record Cash Payment')
            ->assertSee('Outstanding Balance');

        $this->actingAs($staff)
            ->get(route('admin.invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Record Cash Payment')
            ->assertSee('Outstanding Balance');
    }

    public function test_customer_and_technician_cannot_record_cash_payment(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customerUser = $this->createActiveUser('customer');
        $technician = $this->createActiveUser('technician');
        $invoice = $this->createInvoice();

        $payload = [
            'payment_token' => (string) Str::uuid(),
            'amount' => '500.00',
            'remarks' => 'Counter payment.',
        ];

        $this->actingAs($customerUser)
            ->post(
                route('admin.invoices.cash-payments.store', $invoice),
                $payload
            )
            ->assertForbidden();

        $this->actingAs($technician)
            ->post(
                route('admin.invoices.cash-payments.store', $invoice),
                $payload
            )
            ->assertForbidden();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_staff_can_record_partial_cash_payment(): void
    {
        $this->seed(DemoDataSeeder::class);

        $staff = $this->createActiveUser('staff');
        $invoice = $this->createInvoice(
            totalAmount: '1500.00'
        );

        $token = (string) Str::uuid();

        $response = $this
            ->actingAs($staff)
            ->post(
                route('admin.invoices.cash-payments.store', $invoice),
                [
                    'payment_token' => $token,
                    'amount' => '500.00',
                    'remarks' => '  Paid at the Rincomm counter.  ',
                ]
            );

        $payment = Payment::query()->firstOrFail();

        $response
            ->assertRedirect(
                route('admin.invoices.show', $invoice)
            )
            ->assertSessionHas('success');

        $this->assertSame(
            $invoice->id,
            $payment->invoice_id
        );

        $this->assertSame(
            $invoice->customer_id,
            $payment->customer_id
        );

        $this->assertSame(
            '500.00',
            $payment->amount
        );

        $this->assertSame(
            'cash',
            $payment->payment_method
        );

        $this->assertSame(
            'completed',
            $payment->payment_status
        );

        $this->assertNotNull(
            $payment->paid_at
        );

        $this->assertSame(
            'Paid at the Rincomm counter.',
            $payment->remarks
        );

        $this->assertStringStartsWith(
            'CASH-',
            $payment->payment_reference
        );

        $this->assertSame(
            'partially_paid',
            $invoice->fresh()->status
        );

        $this->assertDatabaseHas('activity_logs', [
            'actor_user_id' => $staff->id,
            'target_user_id' => $invoice->customer->user_id,
            'action' => 'billing.cash_payment_recorded',
        ]);
    }

    public function test_full_cash_payment_marks_invoice_paid(): void
    {
        $this->seed(DemoDataSeeder::class);

        $admin = $this->createActiveUser('admin');
        $invoice = $this->createInvoice(
            totalAmount: '1500.00'
        );

        $response = $this
            ->actingAs($admin)
            ->post(
                route('admin.invoices.cash-payments.store', $invoice),
                [
                    'payment_token' => (string) Str::uuid(),
                    'amount' => '1500.00',
                    'remarks' => null,
                ]
            );

        $response
            ->assertRedirect(
                route('admin.invoices.show', $invoice)
            )
            ->assertSessionHas('success');

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'amount' => '1500.00',
            'payment_method' => 'cash',
            'payment_status' => 'completed',
        ]);

        $this->assertSame(
            'paid',
            $invoice->fresh()->status
        );
    }

    public function test_cash_payment_cannot_exceed_current_outstanding_balance(): void
    {
        $this->seed(DemoDataSeeder::class);

        $staff = $this->createActiveUser('staff');
        $invoice = $this->createInvoice(
            totalAmount: '1500.00'
        );

        Payment::query()->create([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'payment_reference' => 'PAY-EXISTING-001',
            'amount' => '1000.00',
            'payment_method' => 'cash',
            'payment_status' => 'completed',
            'paid_at' => now(),
            'gateway_reference' => null,
            'remarks' => null,
        ]);

        $response = $this
            ->actingAs($staff)
            ->from(route('admin.invoices.show', $invoice))
            ->post(
                route('admin.invoices.cash-payments.store', $invoice),
                [
                    'payment_token' => (string) Str::uuid(),
                    'amount' => '600.00',
                    'remarks' => null,
                ]
            );

        $response
            ->assertRedirect(
                route('admin.invoices.show', $invoice)
            )
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount(
            'payments',
            1
        );

        $this->assertSame(
            'issued',
            $invoice->fresh()->status
        );
    }

    public function test_cash_payment_requires_positive_two_decimal_amount_and_valid_token(): void
    {
        $this->seed(DemoDataSeeder::class);

        $admin = $this->createActiveUser('admin');
        $invoice = $this->createInvoice();

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.invoices.show', $invoice))
            ->post(
                route('admin.invoices.cash-payments.store', $invoice),
                [
                    'payment_token' => 'not-a-uuid',
                    'amount' => '0.001',
                    'remarks' => str_repeat('a', 1001),
                ]
            );

        $response
            ->assertRedirect(
                route('admin.invoices.show', $invoice)
            )
            ->assertSessionHasErrors([
                'payment_token',
                'amount',
                'remarks',
            ]);

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_zero_cash_payment_is_rejected(): void
    {
        $this->seed(DemoDataSeeder::class);

        $admin = $this->createActiveUser('admin');
        $invoice = $this->createInvoice();

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.invoices.show', $invoice))
            ->post(
                route('admin.invoices.cash-payments.store', $invoice),
                [
                    'payment_token' => (string) Str::uuid(),
                    'amount' => '0.00',
                    'remarks' => null,
                ]
            );

        $response
            ->assertRedirect(
                route('admin.invoices.show', $invoice)
            )
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_draft_cancelled_and_paid_invoices_reject_new_cash_payment(): void
    {
        $this->seed(DemoDataSeeder::class);

        $admin = $this->createActiveUser('admin');

        foreach (
            ['draft', 'cancelled', 'paid']
            as $status
        ) {
            $invoice = $this->createInvoice(
                status: $status,
                invoiceNumber: 'INV-TEST-'.strtoupper($status),
                billingDate: now()
                    ->addDays(
                        match ($status) {
                            'draft' => 1,
                            'cancelled' => 2,
                            'paid' => 3,
                        }
                    )
                    ->toDateString()
            );

            $response = $this
                ->actingAs($admin)
                ->from(route('admin.invoices.show', $invoice))
                ->post(
                    route(
                        'admin.invoices.cash-payments.store',
                        $invoice
                    ),
                    [
                        'payment_token' => (string) Str::uuid(),
                        'amount' => '100.00',
                        'remarks' => null,
                    ]
                );

            $response
                ->assertRedirect(
                    route('admin.invoices.show', $invoice)
                )
                ->assertSessionHasErrors('payment');

            $this->assertDatabaseMissing('payments', [
                'invoice_id' => $invoice->id,
            ]);
        }
    }

    public function test_reposting_same_payment_token_is_idempotent(): void
    {
        $this->seed(DemoDataSeeder::class);

        $staff = $this->createActiveUser('staff');
        $invoice = $this->createInvoice(
            totalAmount: '1500.00'
        );

        $token = (string) Str::uuid();

        $payload = [
            'payment_token' => $token,
            'amount' => '500.00',
            'remarks' => 'Counter payment.',
        ];

        $firstResponse = $this
            ->actingAs($staff)
            ->post(
                route('admin.invoices.cash-payments.store', $invoice),
                $payload
            );

        $secondResponse = $this
            ->actingAs($staff)
            ->post(
                route('admin.invoices.cash-payments.store', $invoice),
                $payload
            );

        $firstResponse
            ->assertRedirect(
                route('admin.invoices.show', $invoice)
            )
            ->assertSessionHas('success');

        $secondResponse
            ->assertRedirect(
                route('admin.invoices.show', $invoice)
            )
            ->assertSessionHas('success');

        $this->assertDatabaseCount(
            'payments',
            1
        );

        $this->assertSame(
            'partially_paid',
            $invoice->fresh()->status
        );

        $this->assertSame(
            1,
            ActivityLog::query()
                ->where(
                    'action',
                    'billing.cash_payment_recorded'
                )
                ->count()
        );
    }

    public function test_cash_payment_rolls_back_when_cash_audit_log_cannot_be_recorded(): void
    {
        $this->seed(DemoDataSeeder::class);

        $staff = $this->createActiveUser('staff');
        $invoice = $this->createInvoice(
            totalAmount: '1500.00'
        );

        $this->mock(
            ActivityLogger::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('record')
                    ->once()
                    ->withArgs(
                        function (...$arguments): bool {
                            return ($arguments[0] ?? null)
                                === 'billing.invoice_status_updated';
                        }
                    )
                    ->andReturn(
                        new ActivityLog()
                    );

                $mock->shouldReceive('record')
                    ->once()
                    ->withArgs(
                        function (...$arguments): bool {
                            return ($arguments[0] ?? null)
                                === 'billing.cash_payment_recorded';
                        }
                    )
                    ->andReturnNull();
            }
        );

        $service = app(CashPaymentService::class);

        try {
            $service->record(
                invoice: $invoice,
                actor: $staff,
                paymentToken: (string) Str::uuid(),
                amount: '500.00',
                remarks: 'Counter payment.'
            );

            $this->fail(
                'Expected cash payment audit failure to abort the transaction.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Unable to record cash payment audit log.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount('payments', 0);

        $this->assertSame(
            'issued',
            $invoice->fresh()->status
        );
    }

    public function test_statement_shows_amount_paid_and_outstanding_balance(): void
    {
        $this->seed(DemoDataSeeder::class);

        $admin = $this->createActiveUser('admin');
        $invoice = $this->createInvoice(
            totalAmount: '1500.00'
        );

        Payment::query()->create([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'payment_reference' => 'PAY-DISPLAY-001',
            'amount' => '500.00',
            'payment_method' => 'cash',
            'payment_status' => 'completed',
            'paid_at' => now(),
            'gateway_reference' => null,
            'remarks' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Amount Paid')
            ->assertSee('Outstanding Balance')
            ->assertSee('500.00')
            ->assertSee('1,000.00');
    }

    private function createActiveUser(
        string $role
    ): User {
        $user = User::factory()->create();

        $user->forceFill([
            'role' => $role,
            'account_status' => 'active',
        ])->save();

        return $user->refresh();
    }

    private function createInvoice(
        string $status = 'issued',
        string $totalAmount = '1500.00',
        string $invoiceNumber = 'INV-TEST-0001',
        ?string $billingDate = null
    ): Invoice {
        $customer = Customer::query()
            ->firstOrFail();

        $subscription = Subscription::query()
            ->where(
                'customer_id',
                $customer->id
            )
            ->firstOrFail();

        return Invoice::query()->create([
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => $invoiceNumber,
            'billing_date' => $billingDate ?? now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'subtotal' => $totalAmount,
            'discount_amount' => '0.00',
            'adjustment_amount' => '0.00',
            'total_amount' => $totalAmount,
            'status' => $status,
            'disconnection_notice_date' => null,
            'remarks' => null,
        ]);
    }
}
