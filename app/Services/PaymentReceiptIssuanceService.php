<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentReceipt;
use App\Models\User;
use LogicException;

class PaymentReceiptIssuanceService
{
    /**
     * Issue exactly one electronic receipt per completed payment.
     *
     * Call this inside the existing payment transaction,
     * after locking the invoice and payment as appropriate.
     */
    public function issue(
        Payment $payment,
        ?User $actor = null
    ): PaymentReceipt {
        if (
            ! $payment->exists
            || $payment->payment_status !== 'completed'
            || $payment->paid_at === null
        ) {
            throw new LogicException(
                'Only completed, recorded payments can receive a receipt.'
            );
        }

        return PaymentReceipt::query()->firstOrCreate(
            [
                'payment_id' => $payment->getKey(),
            ],
            [
                'receipt_number' =>
                    'RIN-ER-' . str_pad(
                        (string) $payment->getKey(),
                        10,
                        '0',
                        STR_PAD_LEFT
                    ),
                'issued_at' => now(),
                'amount_paid' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'issued_by' => $actor?->name ?? 'System',
                'receipt_file' => null,
                'remarks' => null,
            ]
        );
    }
}