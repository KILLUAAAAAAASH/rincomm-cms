<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use InvalidArgumentException;

class InvoiceController extends Controller
{
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

        $totalCents = $this->decimalToCents(
            $invoice->total_amount
        );

        $paidCents = 0;

        foreach ($invoice->payments as $payment) {
            $paidCents += $this->decimalToCents(
                $payment->amount
            );
        }

        $outstandingCents = max(
            0,
            $totalCents - $paidCents
        );

        $amountPaid = $this->centsToDecimal(
            $paidCents
        );

        $outstandingBalance = $this->centsToDecimal(
            $outstandingCents
        );

        $canRecordCashPayment =
            $outstandingCents > 0
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
                'canRecordCashPayment',
                'paymentToken'
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
