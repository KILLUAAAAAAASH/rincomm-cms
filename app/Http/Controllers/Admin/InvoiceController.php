<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
        ]);

        return view(
            'admin.invoices.show',
            compact('invoice')
        );
    }
}
