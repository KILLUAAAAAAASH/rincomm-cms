@extends('layouts.customer')

@section('title', 'Billing & Invoices - Rincomm Customer Portal')

@section('page-title', 'Billing & Payments')

@section('content')

    <div class="mb-6">

        <p
            class="
                text-[11px] font-semibold
                uppercase tracking-[0.12em]
                text-[#008080]
                dark:text-[#5EEAD4]
            ">
            Billing
        </p>

        <h2
            class="
                mt-1.5 text-2xl font-semibold
                tracking-tight
                text-neutral-950
                dark:text-white
            ">
            Billing &amp; Invoices
        </h2>

        <p
            class="
                mt-1.5 max-w-2xl
                text-sm leading-6
                text-neutral-500
                dark:text-neutral-400
            ">
            Review billing dates, invoice status, payments received, and outstanding balances.
        </p>

    </div>


    <section
        class="
            min-w-0 overflow-hidden rounded-md
            border border-neutral-200
            bg-white
            dark:border-neutral-800
            dark:bg-neutral-900
        ">

        <div
            class="
                flex items-center justify-between gap-4
                border-b border-neutral-200
                px-5 py-4
                dark:border-neutral-800
            ">

            <div class="min-w-0">

                <h3 class="text-sm font-semibold">
                    Your Invoices
                </h3>

                <p
                    class="
                        mt-0.5 text-xs
                        text-neutral-500
                        dark:text-neutral-400
                    ">
                    Statements issued to your subscriber account.
                </p>

            </div>


            <div
                class="
                    flex h-9 w-9 shrink-0
                    items-center justify-center
                    rounded-md
                    bg-[#008080]/10
                    text-[#008080]
                    dark:bg-[#008080]/20
                    dark:text-[#5EEAD4]
                ">

                <i
                    data-lucide="receipt-text"
                    class="h-4 w-4"
                    aria-hidden="true">
                </i>

            </div>

        </div>


        @if ($invoices->isEmpty())

            <div class="px-5 py-10 text-center">

                <i
                    data-lucide="receipt-text"
                    class="
                        mx-auto h-7 w-7
                        text-neutral-300
                        dark:text-neutral-600
                    "
                    aria-hidden="true">
                </i>

                <p class="mt-3 text-sm font-semibold">
                    No invoices available.
                </p>

                <p
                    class="
                        mt-1 text-xs
                        text-neutral-500
                        dark:text-neutral-400
                    ">
                    Your issued invoices will appear here.
                </p>

            </div>

        @else

            <div class="divide-y divide-neutral-200 dark:divide-neutral-800">

                @foreach ($invoices as $invoice)

                    @php
                        $invoiceBalance = $balances[$invoice->id];

                        $statusLabel = str($invoice->status)
                            ->replace('_', ' ')
                            ->title();

                        $statusClasses = match ($invoice->status) {
                            'paid' =>
                                'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300',

                            'overdue' =>
                                'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300',

                            'partially_paid' =>
                                'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300',

                            default =>
                                'border-neutral-200 bg-neutral-50 text-neutral-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300',
                        };
                    @endphp


                    <article
                        class="
                            min-w-0
                            px-5 py-5
                            transition
                            hover:bg-neutral-50/70
                            dark:hover:bg-neutral-950/30
                        ">

                        <div
                            class="
                                grid min-w-0 gap-5
                                lg:grid-cols-[minmax(0,1.35fr)_minmax(0,0.85fr)_minmax(0,0.85fr)]
                                lg:items-center
                            ">

                            {{-- Identity --}}
                            <div class="min-w-0">

                                <div
                                    class="
                                        flex min-w-0 flex-col gap-2
                                        sm:flex-row
                                        sm:items-center
                                        sm:justify-between
                                        lg:block
                                    ">

                                    <div class="min-w-0">

                                        <p
                                            class="
                                                text-[10px] font-semibold
                                                uppercase tracking-[0.1em]
                                                text-neutral-400
                                            ">
                                            Invoice
                                        </p>

                                        <p
                                            class="
                                                mt-1 break-all
                                                text-sm font-semibold
                                                text-neutral-950
                                                dark:text-white
                                            ">
                                            {{ $invoice->invoice_number }}
                                        </p>

                                    </div>


                                    <span
                                        class="
                                            inline-flex w-fit shrink-0
                                            items-center gap-1.5
                                            rounded-md
                                            border px-2 py-1
                                            text-xs font-semibold
                                            {{ $statusClasses }}
                                            lg:mt-3
                                        ">

                                        @if ($invoice->status === 'paid')

                                            <i
                                                data-lucide="circle-check"
                                                class="h-3.5 w-3.5"
                                                aria-hidden="true">
                                            </i>

                                        @elseif (in_array($invoice->status, ['overdue', 'partially_paid'], true))

                                            <i
                                                data-lucide="circle-alert"
                                                class="h-3.5 w-3.5"
                                                aria-hidden="true">
                                            </i>

                                        @endif

                                        {{ $statusLabel }}

                                    </span>

                                </div>

                            </div>


                            {{-- Dates --}}
                            <dl class="grid grid-cols-2 gap-4 lg:gap-5">

                                <div>

                                    <dt
                                        class="
                                            text-[10px] font-semibold
                                            uppercase tracking-[0.1em]
                                            text-neutral-400
                                        ">
                                        Billing Date
                                    </dt>

                                    <dd
                                        class="
                                            mt-1 text-sm font-medium
                                            text-neutral-700
                                            dark:text-neutral-300
                                        ">
                                        {{ $invoice->billing_date?->format('M d, Y') ?? '—' }}
                                    </dd>

                                </div>


                                <div>

                                    <dt
                                        class="
                                            text-[10px] font-semibold
                                            uppercase tracking-[0.1em]
                                            text-neutral-400
                                        ">
                                        Due Date
                                    </dt>

                                    <dd
                                        class="
                                            mt-1 text-sm font-medium
                                            text-neutral-700
                                            dark:text-neutral-300
                                        ">
                                        {{ $invoice->due_date?->format('M d, Y') ?? '—' }}
                                    </dd>

                                </div>

                            </dl>


                            {{-- Balance and action --}}
                            <div
                                class="
                                    min-w-0
                                    border-t border-neutral-200
                                    pt-4
                                    dark:border-neutral-800
                                    lg:border-t-0
                                    lg:border-l
                                    lg:pl-5
                                    lg:pt-0
                                ">

                                <div class="grid grid-cols-3 gap-3">

                                    <div class="min-w-0">

                                        <p
                                            class="
                                                text-[10px] font-semibold
                                                uppercase tracking-[0.08em]
                                                text-neutral-400
                                            ">
                                            Total
                                        </p>

                                        <p
                                            class="
                                                mt-1 break-words
                                                text-xs font-semibold
                                                text-neutral-700
                                                dark:text-neutral-300
                                                sm:text-sm
                                            ">
                                            ₱{{ number_format((float) $invoiceBalance['total_amount'], 2) }}
                                        </p>

                                    </div>


                                    <div class="min-w-0">

                                        <p
                                            class="
                                                text-[10px] font-semibold
                                                uppercase tracking-[0.08em]
                                                text-neutral-400
                                            ">
                                            Paid
                                        </p>

                                        <p
                                            class="
                                                mt-1 break-words
                                                text-xs font-semibold
                                                text-neutral-700
                                                dark:text-neutral-300
                                                sm:text-sm
                                            ">
                                            ₱{{ number_format((float) $invoiceBalance['paid_amount'], 2) }}
                                        </p>

                                    </div>


                                    <div class="min-w-0">

                                        <p
                                            class="
                                                text-[10px] font-semibold
                                                uppercase tracking-[0.08em]
                                                text-neutral-400
                                            ">
                                            Outstanding
                                        </p>

                                        <p
                                            class="
                                                mt-1 break-words
                                                text-xs font-semibold
                                                text-[#008080]
                                                dark:text-[#5EEAD4]
                                                sm:text-sm
                                            ">
                                            ₱{{ number_format((float) $invoiceBalance['balance_amount'], 2) }}
                                        </p>

                                    </div>

                                </div>


                                <a
                                    href="{{ route('customer.billing.show', $invoice) }}"
                                    class="
                                        mt-4 inline-flex min-h-9
                                        items-center gap-2
                                        rounded-md
                                        bg-[#008080]
                                        px-3 py-2
                                        text-xs font-semibold
                                        text-white
                                        transition
                                        hover:bg-[#006666]
                                        focus:outline-none
                                        focus:ring-2
                                        focus:ring-[#008080]/30
                                        focus:ring-offset-2
                                        dark:focus:ring-offset-neutral-900
                                    ">

                                    View invoice

                                    <i
                                        data-lucide="arrow-right"
                                        class="h-3.5 w-3.5"
                                        aria-hidden="true">
                                    </i>

                                </a>

                            </div>

                        </div>

                    </article>

                @endforeach

            </div>


            @if ($invoices->hasPages())

                <div
                    class="
                        border-t border-neutral-200
                        px-5 py-4
                        dark:border-neutral-800
                    ">
                    {{ $invoices->links() }}
                </div>

            @endif

        @endif

    </section>

@endsection
