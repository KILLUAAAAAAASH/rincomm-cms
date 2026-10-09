<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\GatewayPaymentInitiationService;
use App\Services\GatewayPaymentRecoveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GatewayPaymentController extends Controller
{
    public function __construct(
        private readonly GatewayPaymentInitiationService $gatewayPaymentInitiationService,
        private readonly GatewayPaymentRecoveryService $gatewayPaymentRecoveryService
    ) {}

    /**
     * Start a GCash payment for one invoice owned by the authenticated customer.
     */
    public function store(
        Request $request,
        string $invoice
    ): RedirectResponse {
        $validated = $request->validate([
            'payment_token' => [
                'required',
                'uuid',
            ],
        ]);

        $customer = $request
            ->user()
            ->customer;

        abort_if(
            $customer === null,
            404
        );

        /*
         * Ownership is enforced by resolving the invoice through the
         * authenticated customer's relationship. We deliberately do not
         * accept customer_id or amount from the browser.
         */
        $ownedInvoice = $customer
            ->invoices()
            ->whereKey($invoice)
            ->firstOrFail();

        $returnUrl = route(
            'customer.billing.show',
            $ownedInvoice
        );

        $result = $this
            ->gatewayPaymentInitiationService
            ->initiate(
                invoice: $ownedInvoice,
                actor: $request->user(),
                paymentToken:
                    $validated['payment_token'],
                returnUrl:
                    $returnUrl,
                request:
                    $request
            );

        return redirect()->away(
            $result['redirect_url']
        );
    }

    /**
     * Recover or reconcile the existing pending GCash payment.
     *
     * The browser supplies no amount, provider reference, customer ID,
     * payment status, or payment ID. The pending payment is resolved
     * exclusively from the authenticated customer's owned invoice.
     */
    public function recover(
        Request $request,
        string $invoice
    ): RedirectResponse {
        $customer = $request
            ->user()
            ->customer;

        abort_if(
            $customer === null,
            404
        );

        $ownedInvoice = $customer
            ->invoices()
            ->whereKey($invoice)
            ->firstOrFail();

        $returnUrl = route(
            'customer.billing.show',
            $ownedInvoice
        );

        $result = $this
            ->gatewayPaymentRecoveryService
            ->recover(
                invoice: $ownedInvoice,
                actor: $request->user(),
                returnUrl:
                    $returnUrl,
                request:
                    $request
            );

        if (
            $result['redirect_url']
            !== null
        ) {
            return redirect()->away(
                $result['redirect_url']
            );
        }

        if ($result['resolved']) {
            return redirect()
                ->route(
                    'customer.billing.show',
                    $ownedInvoice
                )
                ->with(
                    'success',
                    'GCash payment status updated successfully.'
                );
        }

        return redirect()
            ->route(
                'customer.billing.show',
                $ownedInvoice
            )
            ->with(
                'success',
                'GCash payment is still pending provider confirmation.'
            );
    }
}
