{{-- Module 5: Completed Payment History Ledger --}}
@php
    $invoice->payments->loadMissing('receipt');

    $receiptRouteName = auth()->user()?->role === 'customer'
        ? 'customer.billing.payments.receipt.show'
        : 'admin.invoices.payments.receipt.show';
@endphp
<section
    data-payment-ledger
    class="min-w-0 overflow-hidden rounded-md border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900 print:border-gray-300 print:bg-white"
>
    <div class="flex items-center gap-3 border-b border-neutral-200 px-5 py-4 dark:border-neutral-800">
        <i data-lucide="history" class="h-5 w-5 shrink-0 text-[#008080] dark:text-[#5EEAD4]" aria-hidden="true"></i>

        <div>
            <h3 class="text-sm font-semibold text-neutral-900 dark:text-white">
                Payment History
            </h3>

            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                Confirmed payments recorded for this invoice.
            </p>
        </div>
    </div>

    @if ($invoice->payments->isEmpty())
        <p class="px-5 py-6 text-sm text-neutral-500 dark:text-neutral-400">
            No completed payments have been recorded for this invoice.
        </p>
    @else
        <div class="divide-y divide-neutral-200 dark:divide-neutral-800">
            @foreach ($invoice->payments as $payment)
                <dl class="grid grid-cols-2 gap-x-4 gap-y-4 px-5 py-4 sm:grid-cols-3 xl:grid-cols-5">
                    <div class="min-w-0">
                        <dt class="text-xs text-neutral-500 dark:text-neutral-400">
                            Date
                        </dt>
                        <dd class="mt-1 text-sm text-neutral-900 dark:text-white">
                            {{ $payment->paid_at?->format('M d, Y h:i A') ?? '—' }}
                        </dd>
                    </div>

                    <div class="min-w-0">
                        <dt class="text-xs text-neutral-500 dark:text-neutral-400">
                            Reference
                        </dt>
                        <dd class="mt-1 break-all text-sm font-medium text-neutral-900 dark:text-white">
                            {{ $payment->payment_reference ?: '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-neutral-500 dark:text-neutral-400">
                            Method
                        </dt>
                        <dd class="mt-1 text-sm text-neutral-900 dark:text-white">
                            {{ $payment->payment_method === 'gcash' ? 'GCash' : ucfirst((string) $payment->payment_method) }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-neutral-500 dark:text-neutral-400">
                            Amount
                        </dt>
                        <dd class="mt-1 text-sm font-semibold text-neutral-900 dark:text-white">
                            &#8369;{{ number_format((float) $payment->amount, 2) }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-neutral-500 dark:text-neutral-400">
                            Status
                        </dt>
                        <dd class="mt-1">
                            <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                Completed
                            </span>
                        </dd>
                    </div>
                </dl>
                @if ($payment->receipt)
                    <div class="px-5 pb-4 print:hidden">
                        <a
                            href="{{ route($receiptRouteName, ['invoice' => $invoice->id, 'payment' => $payment->id]) }}"
                            class="inline-flex min-h-9 items-center justify-center rounded-md border border-[#008080] px-3 py-2 text-xs font-semibold text-[#008080] transition hover:bg-[#008080]/10 focus:outline-none focus:ring-2 focus:ring-[#008080]/30 dark:border-[#5EEAD4] dark:text-[#5EEAD4] dark:hover:bg-[#008080]/20"
                        >
                            View Receipt
                        </a>
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</section>