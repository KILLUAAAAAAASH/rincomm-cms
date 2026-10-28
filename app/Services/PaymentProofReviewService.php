<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentProofSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PaymentProofReviewService
{
    public function __construct(
        private readonly MoneyService $money,
        private readonly InvoiceBalanceService $balances,
        private readonly InvoiceLifecycleService $lifecycle,
        private readonly ActivityLogger $activity,
        private readonly PaymentReceiptIssuanceService $receipts
    ) {}

    public function review(
        Invoice $invoice,
        PaymentProofSubmission $submission,
        User $reviewer,
        string $decision,
        string $notes,
        ?Request $request = null
    ): void {
        // Lock order: invoice -> proof -> payment rows, matching existing billing transactions.
        DB::transaction(function () use ($invoice, $submission, $reviewer, $decision, $notes, $request): void {
            $lockedInvoice = Invoice::query()
                ->with('customer.user')
                ->whereKey($invoice->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $proof = PaymentProofSubmission::query()
                ->whereKey($submission->getKey())
                ->where('invoice_id', $lockedInvoice->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($proof->status !== 'pending') {
                throw ValidationException::withMessages(['review' => 'This proof has already been reviewed.']);
            }
            if ((int) $proof->customer_id !== (int) $lockedInvoice->customer_id) {
                abort(404);
            }

            if ($decision === 'reject') {
                $proof->update([
                    'status' => 'rejected',
                    'reviewed_by_user_id' => $reviewer->id,
                    'reviewed_at' => now(),
                    'review_notes' => $notes,
                ]);
                $action = 'billing.payment_proof_rejected';
            } elseif ($decision === 'approve') {
                if (! in_array($lockedInvoice->status, ['issued', 'partially_paid', 'overdue'], true)) {
                    throw ValidationException::withMessages(['review' => 'Invoice is not open for payments.']);
                }
                if (! in_array($proof->payment_method, ['gcash', 'bank_transfer', 'other'], true)) {
                    throw ValidationException::withMessages(['review' => 'Unsupported manual payment method.']);
                }
                if (! is_string($proof->proof_path) ||
                    ! str_starts_with($proof->proof_path, 'payment-proofs/') ||
                    str_contains($proof->proof_path, '..') ||
                    ! Storage::disk('local')->exists($proof->proof_path)) {
                    throw ValidationException::withMessages(['review' => 'Stored proof is missing or unsafe.']);
                }

                // An unresolved gateway attempt can complete later. Never double-post funds.
                if ($lockedInvoice->payments()
                    ->where('payment_method', 'gcash')
                    ->where('payment_status', 'pending')
                    ->lockForUpdate()->exists()) {
                    throw ValidationException::withMessages(['review' => 'Resolve the pending GCash checkout first.']);
                }

                if ($proof->payment_method === 'gcash' && $lockedInvoice->payments()
                    ->where('payment_method', 'gcash')
                    ->where('payment_status', 'completed')
                    ->where(function ($query) use ($proof): void {
                        $query->where('gateway_payment_reference', $proof->transaction_reference)
                            ->orWhere('gateway_reference', $proof->transaction_reference)
                            ->orWhere('payment_reference', $proof->transaction_reference);
                    })->exists()) {
                    throw ValidationException::withMessages(['review' => 'The matching GCash transaction is already recorded.']);
                }

                // Reusing an external transfer reference for a previously approved proof is unsafe.
                if (PaymentProofSubmission::query()
                    ->where('payment_method', $proof->payment_method)
                    ->where('transaction_reference', $proof->transaction_reference)
                    ->where('status', 'approved')
                    ->whereKeyNot($proof->id)
                    ->exists()) {
                    throw ValidationException::withMessages(['review' => 'This transaction reference has already been approved.']);
                }

                $completed = $lockedInvoice->payments()
                    ->where('payment_status', 'completed')
                    ->lockForUpdate()->get(['amount']);
                $balance = $this->balances->calculate(
                    $lockedInvoice->total_amount,
                    $completed->pluck('amount')
                );
                $amountCents = $this->money->toCents($proof->amount);
                if ($amountCents <= 0 || $amountCents > $balance['balance_cents']) {
                    throw ValidationException::withMessages(['review' => 'Proof amount exceeds the current outstanding balance.']);
                }

                $paymentReference = 'PROOF-'.$proof->id;
                if (Payment::query()->where('payment_reference', $paymentReference)->exists()) {
                    throw ValidationException::withMessages(['review' => 'Payment for this proof already exists.']);
                }

                // This action requires independent verification of money RECEIVED by staff.
                // A customer's screenshot alone cannot reach this code path.
                $payment = Payment::query()->create([
                    'invoice_id' => $lockedInvoice->id,
                    'customer_id' => $lockedInvoice->customer_id,
                    'payment_reference' => $paymentReference,
                    'amount' => $this->money->toDecimal($amountCents),
                    'payment_method' => $proof->payment_method,
                    'payment_status' => 'completed',
                    'paid_at' => now(),
                    'gateway_reference' => null,
                    'gateway_payment_reference' => null,
                    'remarks' => 'Externally verified manual transfer '.$proof->transaction_reference.' (proof #'.$proof->id.'). '.$notes,
                ]);

                $proof->update([
                    'status' => 'approved',
                    'reviewed_by_user_id' => $reviewer->id,
                    'reviewed_at' => now(),
                    'review_notes' => $notes,
                    'payment_id' => $payment->id,
                ]);

                $this->lifecycle->sync(invoice: $lockedInvoice, actor: $reviewer, request: $request);
                $this->receipts->issue($payment, $reviewer);
                $action = 'billing.payment_proof_approved';
            } else {
                throw ValidationException::withMessages(['review' => 'Invalid decision.']);
            }

            $log = $this->activity->record(
                action: $action,
                actor: $reviewer,
                target: $lockedInvoice->customer?->user,
                description: sprintf('Payment proof #%d %s for invoice %s.', $proof->id, $decision, $lockedInvoice->invoice_number),
                metadata: [
                    'invoice_id' => $lockedInvoice->id,
                    'submission_id' => $proof->id,
                    'status' => $proof->status,
                    'payment_id' => $proof->payment_id,
                    'payment_method' => $proof->payment_method,
                    'transaction_reference' => $proof->transaction_reference,
                ],
                request: $request
            );
            if ($log === null) {
                throw new RuntimeException('Unable to record proof review audit log; transaction rolled back.');
            }
        });
    }
}
