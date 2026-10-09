<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Tests\TestCase;

class CashPaymentStaleGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_stale_expired_gcash_attempt_is_reconciled_before_cash_payment(): void
    {
        $this->seed(DemoDataSeeder::class);

        $staff = $this->createActiveUser(
            'staff'
        );

        $invoice = $this->createInvoice();

        $gatewayPayment =
            $this->createPendingGatewayPayment(
                invoice: $invoice,
                gatewayReference:
                    'pi_cash_stale_001',
                hoursOld: 5
            );

        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive(
                    'retrievePaymentIntent'
                )
                    ->once()
                    ->with(
                        'pi_cash_stale_001'
                    )
                    ->andReturn([
                        'provider_intent_id' =>
                            'pi_cash_stale_001',
                        'status' =>
                            'awaiting_payment_method',
                        'amount_cents' =>
                            150000,
                        'currency' =>
                            'PHP',
                        'livemode' =>
                            false,
                        'payments' =>
                            [],
                    ]);
            }
        );

        $response = $this
            ->actingAs($staff)
            ->post(
                route(
                    'admin.invoices.cash-payments.store',
                    $invoice
                ),
                [
                    'payment_token' =>
                        (string) Str::uuid(),
                    'amount' =>
                        '500.00',
                    'remarks' =>
                        'Counter payment.',
                ]
            );

        $response
            ->assertRedirect(
                route(
                    'admin.invoices.show',
                    $invoice
                )
            )
            ->assertSessionHas('success');

        $this->assertSame(
            'cancelled',
            $gatewayPayment
                ->fresh()
                ->payment_status
        );

        $this->assertDatabaseHas(
            'payments',
            [
                'invoice_id' =>
                    $invoice->id,
                'payment_method' =>
                    'cash',
                'payment_status' =>
                    'completed',
                'amount' =>
                    '500.00',
            ]
        );

        $this->assertSame(
            'partially_paid',
            $invoice->fresh()->status
        );
    }

    public function test_stale_processing_gcash_attempt_still_blocks_cash_payment(): void
    {
        $this->seed(DemoDataSeeder::class);

        $staff = $this->createActiveUser(
            'staff'
        );

        $invoice = $this->createInvoice();

        $gatewayPayment =
            $this->createPendingGatewayPayment(
                invoice: $invoice,
                gatewayReference:
                    'pi_cash_processing_001',
                hoursOld: 6
            );

        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive(
                    'retrievePaymentIntent'
                )
                    ->once()
                    ->with(
                        'pi_cash_processing_001'
                    )
                    ->andReturn([
                        'provider_intent_id' =>
                            'pi_cash_processing_001',
                        'status' =>
                            'processing',
                        'amount_cents' =>
                            150000,
                        'currency' =>
                            'PHP',
                        'livemode' =>
                            false,
                        'payments' =>
                            [],
                    ]);
            }
        );

        $response = $this
            ->actingAs($staff)
            ->from(
                route(
                    'admin.invoices.show',
                    $invoice
                )
            )
            ->post(
                route(
                    'admin.invoices.cash-payments.store',
                    $invoice
                ),
                [
                    'payment_token' =>
                        (string) Str::uuid(),
                    'amount' =>
                        '500.00',
                    'remarks' =>
                        'Counter payment.',
                ]
            );

        $response
            ->assertRedirect(
                route(
                    'admin.invoices.show',
                    $invoice
                )
            )
            ->assertSessionHasErrors(
                'payment'
            );

        $this->assertSame(
            'pending',
            $gatewayPayment
                ->fresh()
                ->payment_status
        );

        $this->assertDatabaseMissing(
            'payments',
            [
                'invoice_id' =>
                    $invoice->id,
                'payment_method' =>
                    'cash',
            ]
        );

        $this->assertSame(
            'issued',
            $invoice->fresh()->status
        );
    }

    private function createActiveUser(
        string $role
    ): User {
        $user = User::factory()->create();

        $user->forceFill([
            'role' =>
                $role,
            'account_status' =>
                'active',
        ])->save();

        return $user->refresh();
    }

    private function createInvoice(): Invoice
    {
        $customer = Customer::query()
            ->firstOrFail();

        $subscription = Subscription::query()
            ->where(
                'customer_id',
                $customer->id
            )
            ->firstOrFail();

        return Invoice::query()->create([
            'customer_id' =>
                $customer->id,
            'subscription_id' =>
                $subscription->id,
            'invoice_number' =>
                'INV-CASH-STALE-0001',
            'billing_date' =>
                now()->toDateString(),
            'due_date' =>
                now()
                    ->addDays(10)
                    ->toDateString(),
            'subtotal' =>
                '1500.00',
            'discount_amount' =>
                '0.00',
            'adjustment_amount' =>
                '0.00',
            'total_amount' =>
                '1500.00',
            'status' =>
                'issued',
            'disconnection_notice_date' =>
                null,
            'remarks' =>
                null,
        ]);
    }

    private function createPendingGatewayPayment(
        Invoice $invoice,
        string $gatewayReference,
        int $hoursOld
    ): Payment {
        $payment = Payment::query()->create([
            'invoice_id' =>
                $invoice->id,
            'customer_id' =>
                $invoice->customer_id,
            'payment_reference' =>
                'GCASH-CASH-STALE-001',
            'amount' =>
                '1500.00',
            'payment_method' =>
                'gcash',
            'payment_status' =>
                'pending',
            'paid_at' =>
                null,
            'gateway_reference' =>
                $gatewayReference,
            'gateway_payment_reference' =>
                null,
            'remarks' =>
                null,
        ]);

        Payment::query()
            ->whereKey($payment->id)
            ->update([
                'created_at' =>
                    now()->subHours(
                        $hoursOld
                    ),
            ]);

        return $payment->fresh();
    }
}
