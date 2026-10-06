<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\CashPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CashPaymentController extends Controller
{
    public function __construct(
        private readonly CashPaymentService $cashPaymentService
    ) {}

    /**
     * Record an over-the-counter cash payment for an invoice.
     */
    public function store(
        Request $request,
        Invoice $invoice
    ): RedirectResponse {
        $validated = $request->validate(
            [
                'payment_token' => [
                    'required',
                    'uuid',
                ],
                'amount' => [
                    'required',
                    'regex:/^\d+(?:\.\d{1,2})?$/',
                    'gt:0',
                ],
                'remarks' => [
                    'nullable',
                    'string',
                    'max:1000',
                ],
            ],
            [
                'payment_token.required' =>
                    'The payment request token is missing.',
                'payment_token.uuid' =>
                    'The payment request token is invalid.',

                'amount.required' =>
                    'Please enter the cash payment amount.',
                'amount.regex' =>
                    'Payment amount must be a valid amount with up to two decimal places.',
                'amount.gt' =>
                    'Payment amount must be greater than zero.',

                'remarks.max' =>
                    'Payment remarks cannot exceed 1000 characters.',
            ]
        );

        $result = $this->cashPaymentService->record(
            invoice: $invoice,
            actor: $request->user(),
            paymentToken: $validated['payment_token'],
            amount: $validated['amount'],
            remarks: $validated['remarks'] ?? null,
            request: $request
        );

        $message = $result['idempotent']
            ? sprintf(
                'Cash payment %s was already recorded. Outstanding balance: PHP %s.',
                $result['payment']->payment_reference,
                number_format(
                    (float) $result['balance_amount'],
                    2
                )
            )
            : sprintf(
                'Cash payment %s recorded successfully. Outstanding balance: PHP %s.',
                $result['payment']->payment_reference,
                number_format(
                    (float) $result['balance_amount'],
                    2
                )
            );

        return redirect()
            ->route(
                'admin.invoices.show',
                $result['invoice']
            )
            ->with([
                'success' => $message,
                'recorded_payment_id' =>
                    $result['payment']->id,
            ]);
    }
}
