<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __construct(
        private readonly InvoiceBalanceService $invoiceBalanceService
    ) {}

    /**
     * Display invoices owned by the authenticated customer.
     */
    public function index(Request $request): View
    {
        $customer = $request->user()->customer;

        abort_if(
            $customer === null,
            404
        );

        $invoices = $customer
            ->invoices()
            ->with([
                'subscription.servicePlan',
                'payments' => function ($query): void {
                    $query
                        ->where(
                            'payment_status',
                            'completed'
                        )
                        ->orderBy('paid_at')
                        ->orderBy('id');
                },
            ])
            ->orderByDesc('billing_date')
            ->orderByDesc('id')
            ->paginate(20);

        $balances = $invoices
            ->getCollection()
            ->mapWithKeys(
                function (Invoice $invoice): array {
                    return [
                        $invoice->id =>
                            $this
                                ->invoiceBalanceService
                                ->calculate(
                                    $invoice->total_amount,
                                    $invoice
                                        ->payments
                                        ->pluck('amount')
                                ),
                    ];
                }
            )
            ->all();

        return view(
            'customer.billing.index',
            compact(
                'customer',
                'invoices',
                'balances'
            )
        );
    }

    /**
     * Display one invoice owned by the authenticated customer.
     */
    public function show(
        Request $request,
        string $invoice
    ): View {
        $customer = $request->user()->customer;

        abort_if(
            $customer === null,
            404
        );

        $ownedInvoice = $customer
            ->invoices()
            ->with([
                'subscription.servicePlan',
                'items',
                'payments' => function ($query): void {
                    $query
                        ->where(
                            'payment_status',
                            'completed'
                        )
                        ->orderBy('paid_at')
                        ->orderBy('id');
                },
            ])
            ->whereKey($invoice)
            ->firstOrFail();

        $balance = $this
            ->invoiceBalanceService
            ->calculate(
                $ownedInvoice->total_amount,
                $ownedInvoice
                    ->payments
                    ->pluck('amount')
            );

        /*
         * Gateway attempts are loaded separately from the completed-payment
         * relationship above so pending, failed, or cancelled payments can
         * never reduce the displayed invoice balance.
         */
        $gatewayPayments = $ownedInvoice
            ->payments()
            ->where(
                'payment_method',
                'gcash'
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get([
                'id',
                'amount',
                'payment_status',
                'paid_at',
                'created_at',
            ]);

        $hasPendingGatewayPayment =
            $gatewayPayments->contains(
                'payment_status',
                'pending'
            );

        $paymentToken = (string) Str::uuid();

        return view(
            'customer.billing.show',
            [
                'customer' =>
                    $customer,
                'invoice' =>
                    $ownedInvoice,
                'balance' =>
                    $balance,
                'gatewayPayments' =>
                    $gatewayPayments,
                'hasPendingGatewayPayment' =>
                    $hasPendingGatewayPayment,
                'paymentToken' =>
                    $paymentToken,
            ]
        );
    }
}
