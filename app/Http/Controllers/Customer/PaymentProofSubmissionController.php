<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\PaymentProofSubmission;
use App\Services\InvoiceBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentProofSubmissionController extends Controller
{
    public function __construct(private readonly InvoiceBalanceService $balances) {}

    public function store(Request $request, string $invoice): RedirectResponse
    {
        $customer = $request->user()?->customer;
        abort_if($customer === null, 404);

        // Never trust a customer_id, invoice_id, paid status or amount due from the browser.
        $ownedInvoice = $customer->invoices()->whereKey($invoice)->firstOrFail();

        $validated = $request->validate([
            'payment_method' => ['required', 'in:bank_transfer,gcash,other'],
            'transaction_reference' => ['required', 'string', 'min:5', 'max:100'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        if (! in_array($ownedInvoice->status, ['issued', 'partially_paid', 'overdue'], true)) {
            throw ValidationException::withMessages(['proof' => 'This invoice is not open for payment proof submission.']);
        }

        if ($ownedInvoice->payments()->where('payment_method', 'gcash')->where('payment_status', 'pending')->exists()) {
            throw ValidationException::withMessages(['proof' => 'A GCash gateway payment is pending. Resolve it first.']);
        }

        if (PaymentProofSubmission::query()->where('invoice_id', $ownedInvoice->id)->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages(['proof' => 'A payment proof is already awaiting staff review.']);
        }

        $completed = $ownedInvoice->payments()->where('payment_status', 'completed')->pluck('amount');
        $balance = $this->balances->calculate($ownedInvoice->total_amount, $completed);
        $submittedCents = (int) round(((float) $validated['amount']) * 100);
        if ($submittedCents < 1 || $submittedCents > (int) $balance['balance_cents']) {
            throw ValidationException::withMessages(['amount' => 'Amount must not exceed the outstanding invoice balance.']);
        }

        // Laravel's 'local' disk is private; no public storage URL is created.
        $path = $request->file('proof')->store('payment-proofs', 'local');
        if (! is_string($path) || $path === '') {
            throw ValidationException::withMessages(['proof' => 'Upload failed. Please try again.']);
        }

        try {
            PaymentProofSubmission::query()->create([
                'invoice_id' => $ownedInvoice->id,
                'customer_id' => $customer->id,
                'payment_method' => $validated['payment_method'],
                'transaction_reference' => $validated['transaction_reference'],
                'amount' => $validated['amount'],
                'proof_path' => $path,
                'status' => 'pending',
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        // A proof upload is NOT a completed Payment. Staff review follows in Stage 2.
        return redirect()->route('customer.billing.show', $ownedInvoice)
            ->with('success', 'Proof submitted for review. Your invoice remains unpaid until payment is verified.');
    }
}
