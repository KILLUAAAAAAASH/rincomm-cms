@extends('layouts.app')

@section('title', 'Billing & Invoicing')

@section('page-title', 'Billing & Invoicing')

@section('content')

@php
    $statusMap = [
        'draft' => [
            'label' => 'Draft',
            'icon' => 'file-pen-line',
            'class' =>
                'border-gray-200 bg-gray-50 text-gray-700
                 dark:border-neutral-700 dark:bg-neutral-800 dark:text-gray-300',
        ],
        'issued' => [
            'label' => 'Issued',
            'icon' => 'send',
            'class' =>
                'border-blue-200 bg-blue-50 text-blue-700
                 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-300',
        ],
        'paid' => [
            'label' => 'Paid',
            'icon' => 'circle-check',
            'class' =>
                'border-green-200 bg-green-50 text-green-700
                 dark:border-green-900 dark:bg-green-950/30 dark:text-green-300',
        ],
        'partially_paid' => [
            'label' => 'Partially Paid',
            'icon' => 'circle-dot',
            'class' =>
                'border-amber-200 bg-amber-50 text-amber-700
                 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300',
        ],
        'overdue' => [
            'label' => 'Overdue',
            'icon' => 'clock-alert',
            'class' =>
                'border-red-200 bg-red-50 text-red-700
                 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300',
        ],
        'cancelled' => [
            'label' => 'Cancelled',
            'icon' => 'ban',
            'class' =>
                'border-gray-200 bg-gray-50 text-gray-600
                 dark:border-neutral-700 dark:bg-neutral-800 dark:text-gray-400',
        ],
    ];
@endphp

<div class="space-y-2">

    {{-- Page heading --}}
    <div
        class="
            flex flex-col gap-3
            sm:flex-row
            sm:items-center
            sm:justify-between
        ">

        <div>

            <h1
                class="
                    text-xl font-semibold
                    tracking-tight
                    text-gray-900
                    dark:text-white
                ">
                Billing & Invoicing
            </h1>

            <p
                class="
                    mt-0.5
                    text-xs text-gray-500
                    dark:text-gray-400
                ">
                Review subscriber invoices, billing dates, balances, and digital statements of account.
            </p>

        </div>

    </div>


    {{-- Operational note --}}
    <div
        class="
            flex items-start gap-2
            border border-gray-200
            bg-gray-50
            px-3 py-2.5
            text-xs text-gray-600
            dark:border-neutral-800
            dark:bg-neutral-950
            dark:text-gray-400
        ">

        <i
            data-lucide="info"
            class="
                mt-0.5 h-3.5 w-3.5
                shrink-0
                text-[#008080]
                dark:text-[#5EEAD4]
            "
            aria-hidden="true">
        </i>

        <p>
            Recurring invoices are generated from active subscriptions using the configured billing cycle.
            Open an invoice to review its complete digital statement of account.
        </p>

    </div>


    {{-- Search and filter toolbar --}}
    <form
        method="GET"
        action="{{ route('admin.invoices.index') }}"
        class="
            border border-gray-200
            bg-white
            p-3
            dark:border-neutral-800
            dark:bg-neutral-900
        ">

        <div
            class="
                flex flex-col gap-2
                md:flex-row
                md:items-center
            ">

            {{-- Search --}}
            <div class="relative min-w-0 flex-1">

                <label
                    for="invoice-search"
                    class="sr-only">
                    Search invoices
                </label>

                <i
                    data-lucide="search"
                    class="
                        pointer-events-none
                        absolute left-3 top-1/2
                        h-4 w-4
                        -translate-y-1/2
                        text-gray-400
                        dark:text-gray-500
                    "
                    aria-hidden="true">
                </i>

                <input
                    id="invoice-search"
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Search invoice number, customer code, or subscriber name"
                    autocomplete="off"
                    class="
                        min-h-10 w-full
                        border border-gray-300
                        bg-white
                        py-2 pl-10 pr-3
                        text-sm text-gray-900
                        outline-none transition
                        placeholder:text-gray-400
                        focus:border-[#008080]
                        focus:ring-2
                        focus:ring-[#008080]/20
                        dark:border-neutral-700
                        dark:bg-neutral-950
                        dark:text-white
                        dark:placeholder:text-gray-500
                    ">

            </div>


            {{-- Status filter --}}
            <div class="md:w-48 md:shrink-0">

                <label
                    for="invoice-status"
                    class="sr-only">
                    Filter invoices by status
                </label>

                <select
                    id="invoice-status"
                    name="status"
                    class="
                        min-h-10 w-full
                        border border-gray-300
                        bg-white
                        px-3 py-2
                        text-sm text-gray-800
                        outline-none transition
                        focus:border-[#008080]
                        focus:ring-2
                        focus:ring-[#008080]/20
                        dark:border-neutral-700
                        dark:bg-neutral-950
                        dark:text-gray-100
                    ">

                    <option value="">
                        All Status
                    </option>

                    @foreach ($allowedStatuses as $invoiceStatus)

                    <option
                        value="{{ $invoiceStatus }}"
                        @selected($status === $invoiceStatus)>
                        {{ $statusMap[$invoiceStatus]['label']
                            ?? ucfirst(str_replace('_', ' ', $invoiceStatus)) }}
                    </option>

                    @endforeach

                </select>

            </div>


            {{-- Apply --}}
            <button
                type="submit"
                class="
                    inline-flex min-h-10
                    shrink-0
                    items-center justify-center
                    gap-2
                    bg-[#008080]
                    px-4 py-2
                    text-sm font-semibold
                    text-white
                    transition
                    hover:bg-[#006666]
                    focus:outline-none
                    focus:ring-2
                    focus:ring-[#008080]/20
                ">

                <i
                    data-lucide="list-filter"
                    class="h-4 w-4"
                    aria-hidden="true">
                </i>

                Apply

            </button>


            @if ($search !== '' || $status !== '')

            <a
                href="{{ route('admin.invoices.index') }}"
                class="
                    inline-flex min-h-10
                    shrink-0
                    items-center justify-center
                    gap-2
                    border border-gray-300
                    bg-white
                    px-3 py-2
                    text-sm font-medium
                    text-gray-700
                    transition
                    hover:bg-gray-50
                    focus:outline-none
                    focus:ring-2
                    focus:ring-[#008080]/20
                    dark:border-neutral-700
                    dark:bg-neutral-950
                    dark:text-gray-200
                    dark:hover:bg-neutral-800
                ">

                <i
                    data-lucide="x"
                    class="h-4 w-4"
                    aria-hidden="true">
                </i>

                Clear

            </a>

            @endif

        </div>

    </form>


    @if ($invoices->isEmpty())

    {{-- Empty state --}}
    <div
        class="
            border border-dashed
            border-gray-300
            bg-white
            px-4 py-10
            text-center
            dark:border-neutral-700
            dark:bg-neutral-900
        ">

        <i
            data-lucide="receipt-text"
            class="
                mx-auto h-5 w-5
                text-gray-400
            "
            aria-hidden="true">
        </i>

        <h2
            class="
                mt-3
                text-sm font-semibold
                text-gray-900
                dark:text-white
            ">
            {{ $search !== '' || $status !== ''
                ? 'No matching invoices'
                : 'No invoices yet' }}
        </h2>

        <p
            class="
                mx-auto mt-1
                max-w-md
                text-xs text-gray-500
                dark:text-gray-400
            ">
            @if ($search !== '' || $status !== '')
                Try changing the search term or invoice status filter.
            @else
                Recurring invoices will appear here when eligible subscriber billing cycles are generated.
            @endif
        </p>

        @if ($search !== '' || $status !== '')

        <a
            href="{{ route('admin.invoices.index') }}"
            class="
                mt-4 inline-flex min-h-9
                items-center justify-center
                gap-2
                border border-[#008080]
                px-3 py-2
                text-sm font-medium
                text-[#008080]
                transition
                hover:bg-[#008080]/5
                dark:text-[#5EEAD4]
            ">

            <i
                data-lucide="rotate-ccw"
                class="h-4 w-4"
                aria-hidden="true">
            </i>

            Reset Filters

        </a>

        @endif

    </div>

    @else


    {{-- Desktop invoice table --}}
    <div
        class="
            hidden
            border border-gray-200
            bg-white
            dark:border-neutral-800
            dark:bg-neutral-900
            lg:block
        ">

        <table class="w-full table-fixed">

            <thead
                class="
                    border-b border-gray-200
                    bg-gray-50
                    dark:border-neutral-800
                    dark:bg-neutral-950
                ">

                <tr>

                    <th
                        class="
                            w-[19%]
                            px-3 py-2.5
                            text-left
                            text-[10px] font-semibold
                            uppercase tracking-[0.08em]
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Invoice
                    </th>

                    <th
                        class="
                            w-[24%]
                            px-3 py-2.5
                            text-left
                            text-[10px] font-semibold
                            uppercase tracking-[0.08em]
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Subscriber
                    </th>

                    <th
                        class="
                            w-[15%]
                            px-3 py-2.5
                            text-left
                            text-[10px] font-semibold
                            uppercase tracking-[0.08em]
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Plan
                    </th>

                    <th
                        class="
                            w-[12%]
                            px-3 py-2.5
                            text-left
                            text-[10px] font-semibold
                            uppercase tracking-[0.08em]
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Billing Date
                    </th>

                    <th
                        class="
                            w-[12%]
                            px-3 py-2.5
                            text-left
                            text-[10px] font-semibold
                            uppercase tracking-[0.08em]
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Due Date
                    </th>

                    <th
                        class="
                            w-[10%]
                            px-3 py-2.5
                            text-right
                            text-[10px] font-semibold
                            uppercase tracking-[0.08em]
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Amount
                    </th>

                    <th
                        class="
                            w-[8%]
                            px-3 py-2.5
                            text-left
                            text-[10px] font-semibold
                            uppercase tracking-[0.08em]
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Status
                    </th>

                </tr>

            </thead>


            <tbody
                class="
                    divide-y divide-gray-100
                    dark:divide-neutral-800
                ">

                @foreach ($invoices as $invoice)

                @php
                    $invoiceStatus = $statusMap[$invoice->status]
                        ?? [
                            'label' => ucfirst(str_replace('_', ' ', $invoice->status)),
                            'icon' => 'circle',
                            'class' =>
                                'border-gray-200 bg-gray-50 text-gray-700
                                 dark:border-neutral-700 dark:bg-neutral-800 dark:text-gray-300',
                        ];

                    $subscriberName = trim(
                        ($invoice->customer?->first_name ?? '')
                        . ' '
                        . ($invoice->customer?->last_name ?? '')
                    );
                @endphp

                <tr
                    class="
                        transition
                        hover:bg-gray-50/70
                        dark:hover:bg-neutral-800/40
                    ">

                    {{-- Invoice --}}
                    <td class="px-3 py-3">

                        <a
                            href="{{ route('admin.invoices.show', $invoice) }}"
                            class="
                                block min-w-0
                                focus:outline-none
                            ">

                            <p
                                class="
                                    truncate
                                    text-sm font-semibold
                                    text-[#008080]
                                    hover:underline
                                    dark:text-[#5EEAD4]
                                "
                                title="{{ $invoice->invoice_number }}">
                                {{ $invoice->invoice_number }}
                            </p>

                            <p
                                class="
                                    mt-0.5
                                    text-xs text-gray-500
                                    dark:text-gray-400
                                ">
                                SOA #{{ $invoice->id }}
                            </p>

                        </a>

                    </td>


                    {{-- Subscriber --}}
                    <td class="px-3 py-3">

                        <div class="min-w-0">

                            <p
                                class="
                                    truncate
                                    text-sm font-medium
                                    text-gray-900
                                    dark:text-white
                                "
                                title="{{ $subscriberName }}">
                                {{ $subscriberName !== ''
                                    ? $subscriberName
                                    : 'Subscriber' }}
                            </p>

                            <p
                                class="
                                    mt-0.5 truncate
                                    text-xs text-gray-500
                                    dark:text-gray-400
                                ">
                                {{ $invoice->customer?->customer_code
                                    ?? 'No customer code' }}
                            </p>

                        </div>

                    </td>


                    {{-- Plan --}}
                    <td class="px-3 py-3">

                        <p
                            class="
                                truncate
                                text-sm text-gray-700
                                dark:text-gray-200
                            ">
                            {{ $invoice->subscription?->servicePlan?->name
                                ?? 'Unavailable' }}
                        </p>

                        @if ($invoice->subscription?->servicePlan)

                        <p
                            class="
                                mt-0.5
                                text-xs text-gray-500
                                dark:text-gray-400
                            ">
                            {{ number_format(
                                (float) $invoice->subscription->servicePlan->speed_mbps,
                                0
                            ) }}
                            Mbps
                        </p>

                        @endif

                    </td>


                    {{-- Billing date --}}
                    <td
                        class="
                            px-3 py-3
                            text-sm text-gray-600
                            dark:text-gray-300
                        ">
                        {{ $invoice->billing_date?->format('M d, Y')
                            ?? 'Not set' }}
                    </td>


                    {{-- Due date --}}
                    <td
                        class="
                            px-3 py-3
                            text-sm text-gray-600
                            dark:text-gray-300
                        ">
                        {{ $invoice->due_date?->format('M d, Y')
                            ?? 'Not set' }}
                    </td>


                    {{-- Amount --}}
                    <td
                        class="
                            px-3 py-3
                            text-right
                            text-sm font-semibold
                            text-gray-900
                            dark:text-white
                        ">
                        &#8369;{{ number_format(
                            (float) $invoice->total_amount,
                            2
                        ) }}
                    </td>


                    {{-- Status --}}
                    <td class="px-3 py-3">

                        <span
                            class="
                                inline-flex
                                items-center gap-1.5
                                border px-2 py-0.5
                                text-xs font-medium
                                {{ $invoiceStatus['class'] }}
                            ">

                            <i
                                data-lucide="{{ $invoiceStatus['icon'] }}"
                                class="h-3 w-3"
                                aria-hidden="true">
                            </i>

                            <span class="whitespace-nowrap">
                                {{ $invoiceStatus['label'] }}
                            </span>

                        </span>

                    </td>

                </tr>

                @endforeach

            </tbody>

        </table>

    </div>


    {{-- Mobile and tablet invoice list --}}
    <div class="space-y-2 lg:hidden">

        @foreach ($invoices as $invoice)

        @php
            $invoiceStatus = $statusMap[$invoice->status]
                ?? [
                    'label' => ucfirst(str_replace('_', ' ', $invoice->status)),
                    'icon' => 'circle',
                    'class' =>
                        'border-gray-200 bg-gray-50 text-gray-700
                         dark:border-neutral-700 dark:bg-neutral-800 dark:text-gray-300',
                ];

            $subscriberName = trim(
                ($invoice->customer?->first_name ?? '')
                . ' '
                . ($invoice->customer?->last_name ?? '')
            );
        @endphp

        <article
            class="
                border border-gray-200
                bg-white
                p-3
                dark:border-neutral-800
                dark:bg-neutral-900
            ">

            <div
                class="
                    flex items-start
                    justify-between gap-3
                ">

                <div class="min-w-0">

                    <a
                        href="{{ route('admin.invoices.show', $invoice) }}"
                        class="
                            block truncate
                            text-sm font-semibold
                            text-[#008080]
                            hover:underline
                            dark:text-[#5EEAD4]
                        ">
                        {{ $invoice->invoice_number }}
                    </a>

                    <p
                        class="
                            mt-0.5 truncate
                            text-xs text-gray-500
                            dark:text-gray-400
                        ">
                        {{ $invoice->customer?->customer_code ?? 'No customer code' }}
                        <span aria-hidden="true">·</span>
                        {{ $subscriberName !== ''
                            ? $subscriberName
                            : 'Subscriber' }}
                    </p>

                </div>

                <span
                    class="
                        inline-flex shrink-0
                        items-center gap-1.5
                        border px-2 py-0.5
                        text-xs font-medium
                        {{ $invoiceStatus['class'] }}
                    ">

                    <i
                        data-lucide="{{ $invoiceStatus['icon'] }}"
                        class="h-3 w-3"
                        aria-hidden="true">
                    </i>

                    {{ $invoiceStatus['label'] }}

                </span>

            </div>


            <dl
                class="
                    mt-3 grid grid-cols-2
                    gap-x-4 gap-y-3
                    border-t border-gray-100
                    pt-3
                    text-xs
                    dark:border-neutral-800
                ">

                <div>

                    <dt
                        class="
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Plan
                    </dt>

                    <dd
                        class="
                            mt-0.5 font-medium
                            text-gray-800
                            dark:text-gray-200
                        ">
                        {{ $invoice->subscription?->servicePlan?->name
                            ?? 'Unavailable' }}
                    </dd>

                </div>


                <div>

                    <dt
                        class="
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Amount
                    </dt>

                    <dd
                        class="
                            mt-0.5 font-semibold
                            text-gray-900
                            dark:text-white
                        ">
                        &#8369;{{ number_format(
                            (float) $invoice->total_amount,
                            2
                        ) }}
                    </dd>

                </div>


                <div>

                    <dt
                        class="
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Billing Date
                    </dt>

                    <dd
                        class="
                            mt-0.5
                            text-gray-800
                            dark:text-gray-200
                        ">
                        {{ $invoice->billing_date?->format('M d, Y')
                            ?? 'Not set' }}
                    </dd>

                </div>


                <div>

                    <dt
                        class="
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Due Date
                    </dt>

                    <dd
                        class="
                            mt-0.5
                            text-gray-800
                            dark:text-gray-200
                        ">
                        {{ $invoice->due_date?->format('M d, Y')
                            ?? 'Not set' }}
                    </dd>

                </div>

            </dl>


            <div
                class="
                    mt-3
                    border-t border-gray-100
                    pt-3
                    dark:border-neutral-800
                ">

                <a
                    href="{{ route('admin.invoices.show', $invoice) }}"
                    class="
                        inline-flex min-h-9
                        w-full items-center
                        justify-center gap-2
                        border border-gray-300
                        bg-white
                        px-3 py-2
                        text-sm font-medium
                        text-gray-700
                        transition
                        hover:border-[#008080]/40
                        hover:bg-gray-50
                        hover:text-[#008080]
                        focus:outline-none
                        focus:ring-2
                        focus:ring-[#008080]/20
                        dark:border-neutral-700
                        dark:bg-neutral-950
                        dark:text-gray-200
                        dark:hover:bg-neutral-800
                        dark:hover:text-[#5EEAD4]
                    ">

                    <i
                        data-lucide="receipt-text"
                        class="h-4 w-4"
                        aria-hidden="true">
                    </i>

                    View Statement

                </a>

            </div>

        </article>

        @endforeach

    </div>


    {{-- Pagination --}}
    @if ($invoices->hasPages())

    <div
        class="
            border border-gray-200
            bg-white
            px-3 py-3
            dark:border-neutral-800
            dark:bg-neutral-900
        ">
        {{ $invoices->links() }}
    </div>

    @endif

    @endif

</div>

@endsection
