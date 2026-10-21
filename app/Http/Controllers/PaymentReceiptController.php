<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PaymentReceiptController extends Controller
{
    /**
     * Display an existing receipt for an authorized user.
     *
     * The route must also be protected by the existing
     * authentication, active-account, and role middleware.
     */
    public function show(
        Request $request,
        string $invoice,
        string $payment
    ): View {
        $user = $request->user();

        abort_if($user === null, 403);

        $role = (string) $user->role;

        abort_unless(
            in_array(
                $role,
                ['admin', 'staff', 'customer'],
                true
            ),
            403
        );

        $record = Payment::query()
            ->with([
                'receipt',
                'invoice.customer.user',
            ])
            ->whereKey($payment)
            ->where('invoice_id', $invoice)
            ->where('payment_status', 'completed')
            ->whereHas('receipt')
            ->firstOrFail();

        abort_unless(
            $record->invoice !== null
            && (int) $record->customer_id
                === (int) $record->invoice->customer_id,
            404
        );

        if ($role === 'customer') {
            $customer = $user->customer;

            abort_unless(
                $customer !== null
                && (int) $customer->id
                    === (int) $record->customer_id,
                404
            );
        }

        return view(
            'shared.receipts.show',
            [
                'invoice' => $record->invoice,
                'payment' => $record,
                'receipt' => $record->receipt,
            ]
        );
    }
}