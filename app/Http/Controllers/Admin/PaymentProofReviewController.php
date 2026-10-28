<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PaymentProofSubmission;
use App\Services\PaymentProofReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentProofReviewController extends Controller
{
    public function __construct(private readonly PaymentProofReviewService $reviews) {}

    public function download(Invoice $invoice, PaymentProofSubmission $submission): StreamedResponse
    {
        abort_unless((int) $submission->invoice_id === (int) $invoice->id, 404);
        abort_unless((int) $submission->customer_id === (int) $invoice->customer_id, 404);

        $path = $submission->proof_path;
        abort_unless(is_string($path) && str_starts_with($path, 'payment-proofs/') && ! str_contains($path, '..'), 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        abort_unless(in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'], true), 404);

        return Storage::disk('local')->download(
            $path,
            'payment-proof-'.$submission->id.'.'.$ext,
            ['X-Content-Type-Options' => 'nosniff']
        );
    }

    public function review(Request $request, Invoice $invoice, PaymentProofSubmission $submission): RedirectResponse
    {
        abort_unless((int) $submission->invoice_id === (int) $invoice->id, 404);
        abort_unless((int) $submission->customer_id === (int) $invoice->customer_id, 404);

        $validated = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'review_notes' => ['required', 'string', 'min:10', 'max:1000'],
        ]);
        if (mb_strlen(trim($validated['review_notes'])) < 10) {
            throw ValidationException::withMessages([
                'review_notes' => 'Provide at least 10 meaningful characters explaining the decision.',
            ]);
        }
        if ($validated['decision'] === 'approve' && ! $request->boolean('confirmed_received')) {
            throw ValidationException::withMessages([
                'review' => 'Independently confirm receipt of funds before approval.',
            ]);
        }

        $this->reviews->review(
            invoice: $invoice,
            submission: $submission,
            reviewer: $request->user(),
            decision: $validated['decision'],
            notes: trim($validated['review_notes']),
            request: $request
        );

        return redirect()->route('admin.invoices.show', $invoice)->with(
            'success',
            $validated['decision'] === 'approve'
                ? 'Verified transfer recorded. Invoice balance and receipt have been updated.'
                : 'Payment proof rejected. No payment was posted.'
        );
    }
}
