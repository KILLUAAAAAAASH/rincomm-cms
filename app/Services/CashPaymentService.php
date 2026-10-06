<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class CashPaymentService
{
    public function __construct(
        private readonly InvoiceLifecycleService $invoiceLifecycleService,
        private readonly ActivityLogger $activityLogger
    ) {}

    /**
     * Record one completed over-the-counter cash payment.
     *
     * Lock order must remain:
     * Invoice -> Payment
     *
     * @return array{
     *     payment: Payment,
     *     invoice: Invoice,
     *     paid_amount: string,
     *     balance_amount: string,
     *     idempotent: bool
     * }
     */
    public function record(
        Invoice $invoice,
        User $actor,
        string $paymentToken,
        string $amount,
        ?string $remarks = null,
        ?Request $request = null
    ): array {
        $amountCents = $this->decimalToCents($amount);

        if ($amountCents <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than zero.',
            ]);
        }

        $normalizedAmount = $this->centsToDecimal(
            $amountCents
        );

        $normalizedRemarks = $remarks !== null
            ? trim($remarks)
            : null;

        if ($normalizedRemarks === '') {
            $normalizedRemarks = null;
        }

        $paymentReference = $this->paymentReference(
            $paymentToken
        );

        return DB::transaction(function () use (
            $invoice,
            $actor,
            $paymentReference,
            $normalizedAmount,
            $amountCents,
            $normalizedRemarks,
            $request
        ): array {
            $lockedInvoice = Invoice::query()
                ->with('customer.user')
                ->whereKey($invoice->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Idempotency:
             *
             * The browser-generated UUID token is converted into a stable,
             * globally unique payment reference. Because the invoice row is
             * locked first, a duplicate submission for the same invoice is
             * serialized before checking the payment row.
             */
            $existingPayment = Payment::query()
                ->where(
                    'payment_reference',
                    $paymentReference
                )
                ->lockForUpdate()
                ->first();

            if ($existingPayment !== null) {
                if (
                    (int) $existingPayment->invoice_id
                        !== (int) $lockedInvoice->id
                    || (int) $existingPayment->customer_id
                        !== (int) $lockedInvoice->customer_id
                    || $existingPayment->payment_method !== 'cash'
                    || $existingPayment->payment_status !== 'completed'
                    || $this->decimalToCents(
                        $existingPayment->amount
                    ) !== $amountCents
                ) {
                    throw ValidationException::withMessages([
                        'payment_token' => 'This payment request token has already been used for a different payment.',
                    ]);
                }

                $lifecycle = $this
                    ->invoiceLifecycleService
                    ->sync(
                        invoice: $lockedInvoice,
                        actor: $actor,
                        request: $request
                    );

                return [
                    'payment' => $existingPayment,
                    'invoice' => $lifecycle['invoice'],
                    'paid_amount' =>
                        $lifecycle['paid_amount'],
                    'balance_amount' =>
                        $lifecycle['balance_amount'],
                    'idempotent' => true,
                ];
            }

            if (
                ! in_array(
                    $lockedInvoice->status,
                    [
                        'issued',
                        'partially_paid',
                        'overdue',
                    ],
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'payment' => 'Cash payments can only be recorded for issued, partially paid, or overdue invoices.',
                ]);
            }

            /*
             * Only completed payments represent received funds.
             * Existing rows are locked before the balance is calculated.
             */
            $completedPayments = $lockedInvoice
                ->payments()
                ->where(
                    'payment_status',
                    'completed'
                )
                ->lockForUpdate()
                ->get([
                    'id',
                    'amount',
                ]);

            $paidCents = 0;

            foreach ($completedPayments as $payment) {
                $paidCents += $this->decimalToCents(
                    $payment->amount
                );
            }

            $totalCents = $this->decimalToCents(
                $lockedInvoice->total_amount
            );

            $balanceCents = max(
                0,
                $totalCents - $paidCents
            );

            if ($balanceCents === 0) {
                throw ValidationException::withMessages([
                    'payment' => 'This invoice no longer has an outstanding balance.',
                ]);
            }

            if ($amountCents > $balanceCents) {
                throw ValidationException::withMessages([
                    'amount' => sprintf(
                        'Payment cannot exceed the outstanding balance of PHP %s.',
                        number_format(
                            $balanceCents / 100,
                            2,
                            '.',
                            ','
                        )
                    ),
                ]);
            }

            $statusBeforePayment =
                $lockedInvoice->status;

            $payment = Payment::query()->create([
                'invoice_id' =>
                    $lockedInvoice->id,
                'customer_id' =>
                    $lockedInvoice->customer_id,
                'payment_reference' =>
                    $paymentReference,
                'amount' =>
                    $normalizedAmount,
                'payment_method' =>
                    'cash',
                'payment_status' =>
                    'completed',
                'paid_at' =>
                    now(),
                'gateway_reference' =>
                    null,
                'remarks' =>
                    $normalizedRemarks,
            ]);

            $lifecycle = $this
                ->invoiceLifecycleService
                ->sync(
                    invoice: $lockedInvoice,
                    actor: $actor,
                    request: $request
                );

            $activityLog = $this->activityLogger->record(
                action: 'billing.cash_payment_recorded',
                actor: $actor,
                target: $lockedInvoice
                    ->customer
                    ?->user,
                description: sprintf(
                    'Cash payment %s recorded for invoice %s.',
                    $payment->payment_reference,
                    $lockedInvoice->invoice_number
                ),
                metadata: [
                    'payment_id' =>
                        $payment->id,
                    'payment_reference' =>
                        $payment->payment_reference,
                    'invoice_id' =>
                        $lockedInvoice->id,
                    'invoice_number' =>
                        $lockedInvoice->invoice_number,
                    'customer_id' =>
                        $lockedInvoice->customer_id,
                    'payment_method' =>
                        'cash',
                    'amount' =>
                        $normalizedAmount,
                    'balance_before' =>
                        $this->centsToDecimal(
                            $balanceCents
                        ),
                    'balance_after' =>
                        $lifecycle['balance_amount'],
                    'invoice_status_before' =>
                        $statusBeforePayment,
                    'invoice_status_after' =>
                        $lifecycle['new_status'],
                ],
                request: $request
            );

            if ($activityLog === null) {
                throw new RuntimeException(
                    'Unable to record cash payment audit log.'
                );
            }

            return [
                'payment' => $payment->fresh(),
                'invoice' => $lifecycle['invoice'],
                'paid_amount' =>
                    $lifecycle['paid_amount'],
                'balance_amount' =>
                    $lifecycle['balance_amount'],
                'idempotent' => false,
            ];
        });
    }

    private function paymentReference(
        string $paymentToken
    ): string {
        return 'CASH-'.strtoupper(
            str_replace(
                '-',
                '',
                $paymentToken
            )
        );
    }

    private function decimalToCents(
        string|int|float|null $amount
    ): int {
        $value = trim(
            (string) ($amount ?? '0')
        );

        if (
            ! preg_match(
                '/^\d+(?:\.\d{1,2})?$/',
                $value
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid monetary amount.'
            );
        }

        [$whole, $fraction] = array_pad(
            explode(
                '.',
                $value,
                2
            ),
            2,
            ''
        );

        $fraction = str_pad(
            $fraction,
            2,
            '0'
        );

        return ((int) $whole * 100)
            + (int) substr(
                $fraction,
                0,
                2
            );
    }

    private function centsToDecimal(
        int $cents
    ): string {
        if ($cents < 0) {
            throw new InvalidArgumentException(
                'Monetary amount cannot be negative.'
            );
        }

        return sprintf(
            '%d.%02d',
            intdiv(
                $cents,
                100
            ),
            $cents % 100
        );
    }
}
