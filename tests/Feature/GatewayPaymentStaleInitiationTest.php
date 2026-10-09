<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\GatewayPaymentInitiationService;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Mockery\MockInterface;
use Tests\TestCase;

class GatewayPaymentStaleInitiationTest extends TestCase
{
    use RefreshDatabase;

    public function test_stale_expired_pending_attempt_is_reconciled_before_new_gcash_payment(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $invoice = $this->createInvoice(
            $customer
        );

        $stalePayment =
            $this->createPendingPayment(
                invoice: $invoice,
                gatewayReference:
                    'pi_stale_expired_001',
                hoursOld: 5
            );

        $token = (string) Str::uuid();

        $newReference =
            $this->paymentReference($token);

        $this->mock(
            PaymentGateway::class,
            function (MockInterface $mock) use (
                $invoice,
                $newReference
            ): void {
                $mock->shouldReceive(
                    'retrievePaymentIntent'
                )
                    ->once()
                    ->with(
                        'pi_stale_expired_001'
                    )
                    ->andReturn([
                        'provider_intent_id' =>
                            'pi_stale_expired_001',
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

                $mock->shouldReceive(
                    'initiatePayment'
                )
                    ->once()
                    ->with(
                        150000,
                        'gcash',
                        'Rincomm invoice '
                            . $invoice->invoice_number,
                        'https://rincomm.test/return',
                        $newReference
                    )
                    ->andReturn([
                        'provider_intent_id' =>
                            'pi_new_001',
                        'redirect_url' =>
                            'https://checkout.paymongo.test/gcash/new',
                        'status' =>
                            'awaiting_next_action',
                    ]);
            }
        );

        $result = app(
            GatewayPaymentInitiationService::class
        )->initiate(
            invoice: $invoice,
            actor: $customer->user,
            paymentToken: $token,
            returnUrl:
                'https://rincomm.test/return'
        );

        $this->assertSame(
            'cancelled',
            $stalePayment
                ->fresh()
                ->payment_status
        );

        $this->assertSame(
            'pending',
            $result['payment']
                ->payment_status
        );

        $this->assertSame(
            'pi_new_001',
            $result['payment']
                ->gateway_reference
        );

        $this->assertDatabaseCount(
            'payments',
            2
        );
    }

    public function test_stale_processing_attempt_still_blocks_new_gcash_payment(): void
    {
        $this->seed(DemoDataSeeder::class);

        $customer = Customer::query()
            ->with('user')
            ->firstOrFail();

        $invoice = $this->createInvoice(
            $customer
        );

        $stalePayment =
            $this->createPendingPayment(
                invoice: $invoice,
                gatewayReference:
                    'pi_processing_001',
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
                        'pi_processing_001'
                    )
                    ->andReturn([
                        'provider_intent_id' =>
                            'pi_processing_001',
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

                $mock->shouldReceive(
                    'initiatePayment'
                )->never();
            }
        );

        try {
            app(
                GatewayPaymentInitiationService::class
            )->initiate(
                invoice: $invoice,
                actor: $customer->user,
                paymentToken:
                    (string) Str::uuid(),
                returnUrl:
                    'https://rincomm.test/return'
            );

            $this->fail(
                'Expected unresolved processing GCash payment to continue blocking initiation.'
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'payment',
                $exception->errors()
            );
        }

        $this->assertSame(
            'pending',
            $stalePayment
                ->fresh()
                ->payment_status
        );

        $this->assertDatabaseCount(
            'payments',
            1
        );
    }

    private function createInvoice(
        Customer $customer
    ): Invoice {
        $servicePlanId = Subscription::query()
            ->value('service_plan_id');

        $subscription =
            Subscription::query()->create([
                'customer_id' =>
                    $customer->id,
                'service_plan_id' =>
                    $servicePlanId,
                'start_date' =>
                    now()->toDateString(),
                'end_date' =>
                    now()
                        ->addMonths(12)
                        ->toDateString(),
                'lock_in_months' =>
                    12,
                'discount_amount' =>
                    '0.00',
                'is_custom_plan' =>
                    false,
                'status' =>
                    'active',
            ]);

        return Invoice::query()->create([
            'customer_id' =>
                $customer->id,
            'subscription_id' =>
                $subscription->id,
            'invoice_number' =>
                'INV-STALE-INIT-0001',
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

    private function createPendingPayment(
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
                'GCASH-STALE-EXISTING-001',
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

    private function paymentReference(
        string $token
    ): string {
        return 'GCASH-' . strtoupper(
            str_replace(
                '-',
                '',
                $token
            )
        );
    }
}
