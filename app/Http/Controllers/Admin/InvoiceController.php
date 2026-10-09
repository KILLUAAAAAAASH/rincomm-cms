<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceBalanceService $invoiceBalanceService
    ) {}

    /**
     * Display the billing and invoice register.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        $allowedStatuses = [
            'draft',
            'issued',
            'paid',
            'partially_paid',
            'overdue',
            'cancelled',
        ];

        if (
            $status !== ''
            && ! in_array($status, $allowedStatuses, true)
        ) {
            $status = '';
        }

        $invoices = Invoice::query()
            ->with([
                'customer:id,customer_code,first_name,last_name',
                'subscription.servicePlan:id,name,speed_mbps',
            ])
            ->when(
                $search !== '',
                function ($query) use ($search): void {
                    $query->where(function ($query) use ($search): void {
                        $query->where(
                            'invoice_number',
                            'like',
                            '%' . $search . '%'
                        )
                            ->orWhereHas(
                                'customer',
                                function ($query) use ($search): void {
                                    $query->where(
                                        'customer_code',
                                        'like',
                                        '%' . $search . '%'
                                    )
                                        ->orWhere(
                                            'first_name',
                                            'like',
                                            '%' . $search . '%'
                                        )
                                        ->orWhere(
                                            'last_name',
                                            'like',
                                            '%' . $search . '%'
                                        )
                                        ->orWhereRaw(
                                            "CONCAT(first_name, ' ', last_name) LIKE ?",
                                            ['%' . $search . '%']
                                        );
                                }
                            );
                    });
                }
            )
            ->when(
                $status !== '',
                fn($query) => $query->where('status', $status)
            )
            ->orderByDesc('billing_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view(
            'admin.invoices.index',
            compact(
                'invoices',
                'search',
                'status',
                'allowedStatuses'
            )
        );
    }

    /**
     * Display a single invoice as a digital statement of account.
     */
    public function show(Invoice $invoice): View
    {
        $invoice->load([
            'customer.user',
            'subscription.servicePlan',
            'items',
            'payments' => function ($query): void {
                $query
                    ->where('payment_status', 'completed')
                    ->orderBy('paid_at')
                    ->orderBy('id');
            },
        ]);

        $balance = $this->invoiceBalanceService->calculate(
            $invoice->total_amount,
            $invoice->payments->pluck('amount')
        );

        $amountPaid = $balance['paid_amount'];
        $outstandingBalance = $balance['balance_amount'];

        /*
         * Gateway attempts are intentionally loaded separately from the
         * completed-payment relationship above.
         *
         * Pending, failed, or cancelled gateway attempts remain visible
         * without affecting the invoice balance. Only provider-verified
         * completed payments may reduce the outstanding balance.
         */
        $gatewayPayments = $invoice
            ->payments()
            ->where(
                'payment_method',
                'gcash'
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get([
                'id',
                'payment_reference',
                'amount',
                'payment_method',
                'payment_status',
                'paid_at',
                'gateway_reference',
                'gateway_payment_reference',
                'created_at',
            ]);

        $canRecordCashPayment =
            $balance['balance_cents'] > 0
            && in_array(
                $invoice->status,
                [
                    'issued',
                    'partially_paid',
                    'overdue',
                ],
                true
            );

        $paymentToken = (string) Str::uuid();

        return view(
            'admin.invoices.show',
            compact(
                'invoice',
                'amountPaid',
                'outstandingBalance',
                'gatewayPayments',
                'canRecordCashPayment',
                'paymentToken'
            )
        );
    }
}
