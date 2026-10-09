<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceBalanceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly InvoiceBalanceService $invoiceBalanceService
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $customer = $user->customer;

        if ($customer === null) {
            return view(
                'customer.dashboard',
                [
                    'customer' => null,
                    'subscription' => null,
                    'latestInvoice' => null,
                    'latestInvoiceBalance' => null,
                ]
            );
        }

        $subscription = $customer
            ->subscriptions()
            ->with('servicePlan')
            ->where('status', 'active')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        if ($subscription === null) {
            $subscription = $customer
                ->subscriptions()
                ->with('servicePlan')
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->first();
        }

        $latestInvoice = $customer
            ->invoices()
            ->with([
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
            ->first();

        $latestInvoiceBalance = null;

        if ($latestInvoice instanceof Invoice) {
            $latestInvoiceBalance = $this
                ->invoiceBalanceService
                ->calculate(
                    $latestInvoice->total_amount,
                    $latestInvoice
                        ->payments
                        ->pluck('amount')
                );
        }

        return view(
            'customer.dashboard',
            [
                'customer' =>
                    $customer,
                'subscription' =>
                    $subscription,
                'latestInvoice' =>
                    $latestInvoice,
                'latestInvoiceBalance' =>
                    $latestInvoiceBalance,
            ]
        );
    }
}
