@extends('layouts.app')

@section('title', 'Statement of Account')

@section('page-title', 'Statement of Account')

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

$invoiceStatus = $statusMap[$invoice->status]
?? [
'label' => ucfirst(str_replace('_', ' ', $invoice->status)),
'icon' => 'circle',
'class' =>
'border-gray-200 bg-gray-50 text-gray-700
dark:border-neutral-700 dark:bg-neutral-800 dark:text-gray-300',
];

$customer = $invoice->customer;
$subscription = $invoice->subscription;
$servicePlan = $subscription?->servicePlan;

$subscriberName = trim(
collect([
$customer?->first_name,
$customer?->middle_name,
$customer?->last_name,
])
->filter()
->implode(' ')
);

$billingAddress = trim(
(string) (
$customer?->billing_address
?: $customer?->address
)
);

$locality = collect([
$customer?->city,
$customer?->province,
$customer?->postal_code,
])
->filter()
->implode(', ');
@endphp

<div class="space-y-1.5">

    {{-- Compact page controls --}}
    <div
        class="
            rincomm-soa-controls
            flex flex-col gap-2
            sm:flex-row
            sm:items-center
            sm:justify-between
            print:hidden
        ">

        <div class="min-w-0">

            <a
                href="{{ route('admin.invoices.index') }}"
                class="
                    inline-flex items-center gap-1.5
                    text-xs font-medium
                    text-gray-500
                    transition
                    hover:text-[#008080]
                    dark:text-gray-400
                    dark:hover:text-[#5EEAD4]
                ">

                <i
                    data-lucide="arrow-left"
                    class="h-3.5 w-3.5"
                    aria-hidden="true">
                </i>

                Billing & Invoicing

            </a>

            <div
                class="
                    mt-0.5
                    flex flex-wrap
                    items-baseline gap-x-2 gap-y-0.5
                ">

                <h1
                    class="
                        text-lg font-semibold
                        tracking-tight
                        text-gray-900
                        dark:text-white
                    ">
                    Statement of Account
                </h1>

                <p
                    class="
                        text-xs text-gray-500
                        dark:text-gray-400
                    ">
                    {{ $invoice->invoice_number }}
                </p>

            </div>

        </div>


        <button
            type="button"
            onclick="window.print()"
            class="
                inline-flex min-h-9
                shrink-0
                items-center justify-center
                gap-2
                border border-gray-300
                bg-white
                px-3 py-1.5
                text-xs font-semibold
                text-gray-700
                transition
                hover:border-[#008080]/40
                hover:bg-gray-50
                hover:text-[#008080]
                focus:outline-none
                focus:ring-2
                focus:ring-[#008080]/20
                dark:border-neutral-700
                dark:bg-neutral-900
                dark:text-gray-200
                dark:hover:bg-neutral-800
                dark:hover:text-[#5EEAD4]
            ">

            <i
                data-lucide="printer"
                class="h-3.5 w-3.5"
                aria-hidden="true">
            </i>

            Print SOA

        </button>

    </div>


    {{-- Digital SOA --}}
    <section
        class="
            border border-gray-200
            bg-white
            dark:border-neutral-800
            dark:bg-neutral-900
            print:border-0
            print:bg-white
            print:text-black
        ">

        {{-- SOA header --}}
        <header
            class="
                border-b border-gray-200
                px-4 py-3
                dark:border-neutral-800
                sm:px-5
                lg:py-2.5
                print:border-gray-300
            ">

            <div
                class="
                    flex flex-col gap-3
                    sm:flex-row
                    sm:items-start
                    sm:justify-between
                ">

                <div>

                    <p
                        class="
                            text-base font-bold
                            tracking-tight
                            text-[#008080]
                            dark:text-[#5EEAD4]
                            print:text-black
                        ">
                        {{ config('app.name') }}
                    </p>

                    <p
                        class="
                            mt-0.5
                            text-[11px] text-gray-500
                            dark:text-gray-400
                            print:text-gray-600
                        ">
                        Internet Service Statement of Account
                    </p>

                </div>


                <div class="sm:text-right">

                    <p
                        class="
                            text-[10px] font-semibold
                            uppercase tracking-[0.08em]
                            text-gray-500
                            dark:text-gray-400
                            print:text-gray-600
                        ">
                        Invoice Number
                    </p>

                    <p
                        class="
                            mt-0.5
                            text-sm font-semibold
                            text-gray-900
                            dark:text-white
                            print:text-black
                        ">
                        {{ $invoice->invoice_number }}
                    </p>

                    <span
                        class="
                            mt-1.5 inline-flex
                            items-center gap-1.5
                            border px-2 py-0.5
                            text-xs font-medium
                            {{ $invoiceStatus['class'] }}
                            print:border-gray-400
                            print:bg-white
                            print:text-black
                        ">

                        <i
                            data-lucide="{{ $invoiceStatus['icon'] }}"
                            class="h-3 w-3 print:hidden"
                            aria-hidden="true">
                        </i>

                        {{ $invoiceStatus['label'] }}

                    </span>

                </div>

            </div>

        </header>


        {{-- Subscriber and billing metadata --}}
        <div
            class="
                grid gap-0
                border-b border-gray-200
                dark:border-neutral-800
                md:grid-cols-2
                print:grid-cols-2
                print:border-gray-300
            ">

            {{-- Subscriber --}}
            <div
                class="
                    px-4 py-3
                    sm:px-5
                    lg:py-2.5
                    md:border-r
                    md:border-gray-200
                    dark:md:border-neutral-800
                    print:border-r
                    print:border-gray-300
                ">

                <h2
                    class="
                        text-[10px] font-semibold
                        uppercase tracking-[0.08em]
                        text-gray-500
                        dark:text-gray-400
                        print:text-gray-600
                    ">
                    Bill To
                </h2>

                <p
                    class="
                        mt-1.5
                        text-sm font-semibold
                        text-gray-900
                        dark:text-white
                        print:text-black
                    ">
                    {{ $subscriberName !== ''
                        ? $subscriberName
                        : 'Subscriber' }}
                </p>

                <p
                    class="
                        mt-0.5
                        text-[11px] font-medium
                        text-gray-500
                        dark:text-gray-400
                        print:text-gray-600
                    ">
                    {{ $customer?->customer_code ?? 'No customer code' }}
                </p>


                <div
                    class="
                        mt-2 space-y-0.5
                        text-[11px] leading-4
                        text-gray-600
                        dark:text-gray-300
                        print:text-gray-700
                    ">

                    @if ($billingAddress !== '')

                    <p>
                        {{ $billingAddress }}
                    </p>

                    @endif

                    @if ($locality !== '')

                    <p>
                        {{ $locality }}
                    </p>

                    @endif

                    @if ($customer?->email)

                    <p>
                        {{ $customer->email }}
                    </p>

                    @endif

                    @if ($customer?->phone)

                    <p>
                        {{ $customer->phone }}
                    </p>

                    @endif

                </div>

            </div>


            {{-- Billing details --}}
            <div
                class="
                    px-4 py-3
                    sm:px-5
                    lg:py-2.5
                ">

                <h2
                    class="
                        text-[10px] font-semibold
                        uppercase tracking-[0.08em]
                        text-gray-500
                        dark:text-gray-400
                        print:text-gray-600
                    ">
                    Billing Details
                </h2>

                <dl
                    class="
                        mt-1.5
                        grid grid-cols-2
                        gap-x-4 gap-y-2
                        text-[11px]
                    ">

                    <div>

                        <dt
                            class="
                                text-gray-500
                                dark:text-gray-400
                                print:text-gray-600
                            ">
                            Billing Date
                        </dt>

                        <dd
                            class="
                                mt-0.5 font-medium
                                text-gray-900
                                dark:text-white
                                print:text-black
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
                                print:text-gray-600
                            ">
                            Due Date
                        </dt>

                        <dd
                            class="
                                mt-0.5 font-medium
                                text-gray-900
                                dark:text-white
                                print:text-black
                            ">
                            {{ $invoice->due_date?->format('M d, Y')
                                ?? 'Not set' }}
                        </dd>

                    </div>


                    <div>

                        <dt
                            class="
                                text-gray-500
                                dark:text-gray-400
                                print:text-gray-600
                            ">
                            Service Plan
                        </dt>

                        <dd
                            class="
                                mt-0.5 font-medium
                                text-gray-900
                                dark:text-white
                                print:text-black
                            ">
                            {{ $servicePlan?->name ?? 'Unavailable' }}
                        </dd>

                    </div>


                    <div>

                        <dt
                            class="
                                text-gray-500
                                dark:text-gray-400
                                print:text-gray-600
                            ">
                            Speed
                        </dt>

                        <dd
                            class="
                                mt-0.5 font-medium
                                text-gray-900
                                dark:text-white
                                print:text-black
                            ">
                            @if ($servicePlan)
                            {{ number_format(
                                    (float) $servicePlan->speed_mbps,
                                    0
                                ) }}
                            Mbps
                            @else
                            Unavailable
                            @endif
                        </dd>

                    </div>


                    @if ($invoice->disconnection_notice_date)

                    <div>

                        <dt
                            class="
                                text-gray-500
                                dark:text-gray-400
                                print:text-gray-600
                            ">
                            Notice Date
                        </dt>

                        <dd
                            class="
                                mt-0.5 font-medium
                                text-gray-900
                                dark:text-white
                                print:text-black
                            ">
                            {{ $invoice
                                ->disconnection_notice_date
                                ->format('M d, Y') }}
                        </dd>

                    </div>


                    <div>

                        <dt
                            class="
                                text-gray-500
                                dark:text-gray-400
                                print:text-gray-600
                            ">
                            Notice State
                        </dt>

                        <dd
                            class="
                                mt-0.5
                                inline-flex items-center gap-1.5
                                font-medium
                                {{ $invoice->disconnection_notice_triggered_at
                                    ? 'text-red-700 dark:text-red-300'
                                    : 'text-gray-700 dark:text-gray-200' }}
                                print:text-black
                            ">

                            @if ($invoice->disconnection_notice_triggered_at)

                            <i
                                data-lucide="circle-alert"
                                class="h-3 w-3 print:hidden"
                                aria-hidden="true">
                            </i>

                            Triggered

                            @else

                            <i
                                data-lucide="calendar-clock"
                                class="h-3 w-3 print:hidden"
                                aria-hidden="true">
                            </i>

                            Scheduled

                            @endif

                        </dd>

                    </div>

                    @endif

                </dl>


                @if (
                $invoice->disconnection_notice_date
                && ! $invoice->disconnection_notice_triggered_at
                )

                <p
                    class="
                        mt-2
                        text-[10px] leading-4
                        text-gray-500
                        dark:text-gray-400
                        print:text-gray-600
                    ">
                    The notice becomes actionable only if an outstanding
                    balance remains on the notice date.
                </p>

                @endif

            </div>

        </div>


        {{-- Invoice items --}}
        <div class="overflow-x-auto">

            <table class="w-full min-w-[640px]">

                <thead
                    class="
                        border-b border-gray-200
                        bg-gray-50
                        dark:border-neutral-800
                        dark:bg-neutral-950
                        print:border-gray-300
                        print:bg-gray-100
                    ">

                    <tr>

                        <th
                            class="
                                px-4 py-2
                                text-left
                                text-[10px] font-semibold
                                uppercase tracking-[0.08em]
                                text-gray-500
                                dark:text-gray-400
                                sm:px-5
                                print:text-gray-600
                            ">
                            Description
                        </th>

                        <th
                            class="
                                w-24
                                px-3 py-2
                                text-right
                                text-[10px] font-semibold
                                uppercase tracking-[0.08em]
                                text-gray-500
                                dark:text-gray-400
                                print:text-gray-600
                            ">
                            Qty
                        </th>

                        <th
                            class="
                                w-32
                                px-3 py-2
                                text-right
                                text-[10px] font-semibold
                                uppercase tracking-[0.08em]
                                text-gray-500
                                dark:text-gray-400
                                print:text-gray-600
                            ">
                            Unit Price
                        </th>

                        <th
                            class="
                                w-32
                                px-4 py-2
                                text-right
                                text-[10px] font-semibold
                                uppercase tracking-[0.08em]
                                text-gray-500
                                dark:text-gray-400
                                sm:px-5
                                print:text-gray-600
                            ">
                            Amount
                        </th>

                    </tr>

                </thead>


                <tbody
                    class="
                        divide-y divide-gray-100
                        dark:divide-neutral-800
                        print:divide-gray-200
                    ">

                    @forelse ($invoice->items as $item)

                    <tr>

                        <td
                            class="
                                px-4 py-2
                                text-sm text-gray-800
                                dark:text-gray-200
                                sm:px-5
                                print:text-black
                            ">
                            {{ $item->description }}
                        </td>

                        <td
                            class="
                                px-3 py-2
                                text-right
                                text-sm text-gray-600
                                dark:text-gray-300
                                print:text-gray-700
                            ">
                            {{ number_format(
                                (float) $item->quantity,
                                2
                            ) }}
                        </td>

                        <td
                            class="
                                px-3 py-2
                                text-right
                                text-sm text-gray-600
                                dark:text-gray-300
                                print:text-gray-700
                            ">
                            &#8369;{{ number_format(
                                (float) $item->unit_price,
                                2
                            ) }}
                        </td>

                        <td
                            class="
                                px-4 py-2
                                text-right
                                text-sm font-medium
                                text-gray-900
                                dark:text-white
                                sm:px-5
                                print:text-black
                            ">
                            &#8369;{{ number_format(
                                (float) $item->amount,
                                2
                            ) }}
                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td
                            colspan="4"
                            class="
                                px-4 py-6
                                text-center
                                text-sm text-gray-500
                                dark:text-gray-400
                                print:text-gray-600
                            ">
                            No invoice line items are available.
                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- Totals --}}
        <div
            class="
                border-t border-gray-200
                px-4 py-3
                dark:border-neutral-800
                sm:px-5
                lg:py-2.5
                print:border-gray-300
            ">

            <div class="ml-auto max-w-sm">

                <dl class="space-y-1.5 text-sm">

                    <div
                        class="
                            flex items-center
                            justify-between gap-4
                        ">

                        <dt
                            class="
                                text-gray-500
                                dark:text-gray-400
                                print:text-gray-600
                            ">
                            Subtotal
                        </dt>

                        <dd
                            class="
                                font-medium
                                text-gray-900
                                dark:text-white
                                print:text-black
                            ">
                            &#8369;{{ number_format(
                                (float) $invoice->subtotal,
                                2
                            ) }}
                        </dd>

                    </div>


                    <div
                        class="
                            flex items-center
                            justify-between gap-4
                        ">

                        <dt
                            class="
                                text-gray-500
                                dark:text-gray-400
                                print:text-gray-600
                            ">
                            Discount
                        </dt>

                        <dd
                            class="
                                font-medium
                                text-gray-900
                                dark:text-white
                                print:text-black
                            ">
                            {{ (float) $invoice->discount_amount > 0 ? '-' : '' }}&#8369;{{ number_format(
                                (float) $invoice->discount_amount,
                                2
                            ) }}
                        </dd>

                    </div>


                    @if ((float) $invoice->adjustment_amount !== 0.0)

                    <div
                        class="
                            flex items-center
                            justify-between gap-4
                        ">

                        <dt
                            class="
                                text-gray-500
                                dark:text-gray-400
                                print:text-gray-600
                            ">
                            Adjustment
                        </dt>

                        <dd
                            class="
                                font-medium
                                text-gray-900
                                dark:text-white
                                print:text-black
                            ">
                            &#8369;{{ number_format(
                                (float) $invoice->adjustment_amount,
                                2
                            ) }}
                        </dd>

                    </div>

                    @endif


                    <div
                        class="
                            flex items-center
                            justify-between gap-4
                            border-t border-gray-200
                            pt-2
                            dark:border-neutral-700
                            print:border-gray-300
                        ">

                        <dt
                            class="
                                font-semibold
                                text-gray-900
                                dark:text-white
                                print:text-black
                            ">
                            Total Amount
                        </dt>

                        <dd
                            class="
                                text-base font-bold
                                text-[#008080]
                                dark:text-[#5EEAD4]
                                print:text-black
                            ">
                            &#8369;{{ number_format(
                                (float) $invoice->total_amount,
                                2
                            ) }}
                        </dd>

                    </div>

                </dl>

            </div>

        </div>


        {{-- Formal disconnection notice --}}
        @if ($invoice->disconnection_notice_triggered_at)

        <section
            class="
                border-t border-gray-300
                px-4 py-3
                dark:border-neutral-700
                sm:px-5
                print:border-gray-400
            ">

            <div
                class="
                    flex flex-col gap-2
                    sm:flex-row
                    sm:items-start
                    sm:justify-between
                ">

                <div>

                    <p
                        class="
                            text-[10px] font-semibold
                            uppercase tracking-[0.12em]
                            text-gray-500
                            dark:text-gray-400
                            print:text-gray-600
                        ">
                        Account Notice
                    </p>

                    <h2
                        class="
                            mt-0.5
                            text-sm font-bold
                            tracking-tight
                            text-gray-900
                            dark:text-white
                            print:text-black
                        ">
                        Disconnection Notice
                    </h2>

                </div>


                <span
                    class="
                        inline-flex w-fit
                        items-center gap-1.5
                        border border-red-200
                        bg-red-50
                        px-2 py-0.5
                        text-[11px] font-semibold
                        text-red-700
                        dark:border-red-900
                        dark:bg-red-950/30
                        dark:text-red-300
                        print:border-gray-400
                        print:bg-white
                        print:text-black
                    ">

                    <i
                        data-lucide="circle-alert"
                        class="h-3 w-3 print:hidden"
                        aria-hidden="true">
                    </i>

                    Triggered

                </span>

            </div>


            <dl
                class="
                    mt-2
                    grid gap-x-6 gap-y-1.5
                    text-[11px]
                    sm:grid-cols-4
                ">

                <div>

                    <dt
                        class="
                            text-gray-500
                            dark:text-gray-400
                            print:text-gray-600
                        ">
                        Account
                    </dt>

                    <dd
                        class="
                            mt-0.5 font-medium
                            text-gray-900
                            dark:text-white
                            print:text-black
                        ">
                        {{ $customer?->customer_code ?? 'Unavailable' }}
                    </dd>

                </div>


                <div>

                    <dt
                        class="
                            text-gray-500
                            dark:text-gray-400
                            print:text-gray-600
                        ">
                        Invoice
                    </dt>

                    <dd
                        class="
                            mt-0.5 font-medium
                            text-gray-900
                            dark:text-white
                            print:text-black
                        ">
                        {{ $invoice->invoice_number }}
                    </dd>

                </div>


                <div>

                    <dt
                        class="
                            text-gray-500
                            dark:text-gray-400
                            print:text-gray-600
                        ">
                        Notice Date
                    </dt>

                    <dd
                        class="
                            mt-0.5 font-medium
                            text-gray-900
                            dark:text-white
                            print:text-black
                        ">
                        {{ $invoice
                            ->disconnection_notice_date
                            ?->format('M d, Y')
                            ?? 'Not set' }}
                    </dd>

                </div>


                <div>

                    <dt
                        class="
                            text-gray-500
                            dark:text-gray-400
                            print:text-gray-600
                        ">
                        Triggered On
                    </dt>

                    <dd
                        class="
                            mt-0.5 font-medium
                            text-gray-900
                            dark:text-white
                            print:text-black
                        ">
                        {{ $invoice
                            ->disconnection_notice_triggered_at
                            ->format('M d, Y') }}
                    </dd>

                </div>

            </dl>


            <p
                class="
                    mt-2
                    max-w-4xl
                    text-[11px] leading-4
                    text-gray-600
                    dark:text-gray-300
                    print:text-gray-700
                ">
                This notice was generated because an outstanding balance
                remained when the configured notice date was reached.
                If payment has already been completed, refer to the current
                invoice status and official payment record. Service
                disconnection, when required, is processed through the
                applicable service workflow.
            </p>

        </section>

        @endif


        {{-- Remarks --}}
        @if ($invoice->remarks)

        <footer
            class="
                border-t border-gray-200
                bg-gray-50
                px-4 py-2.5
                text-[11px] text-gray-600
                dark:border-neutral-800
                dark:bg-neutral-950
                dark:text-gray-400
                sm:px-5
                print:border-gray-300
                print:bg-white
                print:text-gray-700
            ">

            <span class="font-semibold">
                Remarks:
            </span>

            {{ $invoice->remarks }}

        </footer>

        @endif

    </section>

</div>

@endsection