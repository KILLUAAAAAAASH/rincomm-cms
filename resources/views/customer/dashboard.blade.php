@extends('layouts.customer')

@section('title', 'Overview - Rincomm Customer Portal')

@section('page-title', 'Overview')

@section('content')

    @php
        $hasInvoice = $latestInvoice !== null && $latestInvoiceBalance !== null;

        $hasOutstandingBalance = $hasInvoice
            && $latestInvoiceBalance['balance_cents'] > 0;

        $invoiceStatusLabel = $hasInvoice
            ? str($latestInvoice->status)->replace('_', ' ')->title()
            : null;

        $subscriptionStatusLabel = $subscription !== null
            ? str($subscription->status)->replace('_', ' ')->title()
            : null;
    @endphp


    @if (session('success'))

        <div
            role="status"
            aria-live="polite"
            class="
                mb-5 flex items-start gap-3
                rounded-md
                border border-emerald-200
                bg-emerald-50
                px-4 py-3
                text-sm text-emerald-800
                dark:border-emerald-900
                dark:bg-emerald-950/30
                dark:text-emerald-300
            ">

            <i
                data-lucide="circle-check"
                class="mt-0.5 h-4 w-4 shrink-0"
                aria-hidden="true">
            </i>

            <span>{{ session('success') }}</span>

        </div>

    @endif


    @if (session('error'))

        <div
            role="alert"
            aria-live="assertive"
            class="
                mb-5 flex items-start gap-3
                rounded-md
                border border-red-200
                bg-red-50
                px-4 py-3
                text-sm text-red-800
                dark:border-red-900
                dark:bg-red-950/30
                dark:text-red-300
            ">

            <i
                data-lucide="triangle-alert"
                class="mt-0.5 h-4 w-4 shrink-0"
                aria-hidden="true">
            </i>

            <span>{{ session('error') }}</span>

        </div>

    @endif


    <div class="mb-6">

        <h2
            class="
                text-2xl font-semibold
                tracking-tight
                text-neutral-950
                dark:text-white
                sm:text-[28px]
            ">

            @if ($customer !== null)
                Welcome back, {{ $customer->first_name }}.
            @else
                Welcome to Rincomm.
            @endif

        </h2>

        <p class="mt-1.5 text-sm text-neutral-500 dark:text-neutral-400">

            @if ($customer !== null)
                Here's your internet connection and billing at a glance.
            @else
                Complete your service application to start your Rincomm subscription.
            @endif

        </p>

    </div>


    @if ($customer === null)

        <section
            class="
                max-w-3xl overflow-hidden rounded-md
                border border-neutral-200
                bg-white
                dark:border-neutral-800
                dark:bg-neutral-900
            ">

            <div
                class="
                    flex items-start gap-4
                    border-b border-neutral-200
                    px-5 py-5
                    dark:border-neutral-800
                ">

                <div
                    class="
                        flex h-10 w-10 shrink-0
                        items-center justify-center
                        rounded-md
                        bg-[#008080]/10
                        text-[#008080]
                        dark:bg-[#008080]/20
                        dark:text-[#5EEAD4]
                    ">

                    <i
                        data-lucide="user-check"
                        class="h-5 w-5"
                        aria-hidden="true">
                    </i>

                </div>

                <div>

                    <h3 class="text-base font-semibold">
                        Subscriber Profile Pending
                    </h3>

                    <p class="mt-1 max-w-xl text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                        Your portal account is active, but no approved subscriber service is linked to it yet.
                    </p>

                </div>

            </div>


            <div class="px-5 py-5">

                <p class="max-w-2xl text-sm leading-6 text-neutral-600 dark:text-neutral-300">
                    Submit your service application first. Subscription and billing information will appear here after the application is approved and your service progresses through the installation process.
                </p>

                <a
                    href="{{ route('customer.application.create') }}"
                    class="
                        mt-5 inline-flex min-h-10
                        items-center gap-2
                        rounded-md
                        bg-[#008080]
                        px-4 py-2
                        text-sm font-semibold
                        text-white
                        transition
                        hover:bg-[#006666]
                        focus:outline-none
                        focus:ring-2
                        focus:ring-[#008080]/30
                        focus:ring-offset-2
                        dark:focus:ring-offset-neutral-900
                    ">

                    <i
                        data-lucide="clipboard-list"
                        class="h-4 w-4"
                        aria-hidden="true">
                    </i>

                    Service Application

                </a>

            </div>

        </section>

    @else

        <div
            class="
                grid gap-5
                xl:grid-cols-[minmax(0,1.65fr)_minmax(300px,0.75fr)]
            ">

            {{-- Primary connection card --}}
            <section
                class="
                    relative overflow-hidden rounded-md
                    border border-neutral-200
                    bg-[#008080]/10
                    text-neutral-900
                    shadow-sm
                    dark:border-neutral-800
                    dark:bg-[#008080]/20
                    dark:text-neutral-100
                ">

                <div class="px-5 py-5 sm:px-6 sm:py-6">

                    <div
                        class="
                            flex flex-col gap-6
                            sm:flex-row
                            sm:items-start
                            sm:justify-between
                        ">

                        <div class="min-w-0">

                            <div class="flex items-center gap-2.5">

                                <div
                                    class="
                                        flex h-8 w-8 shrink-0
                                        items-center justify-center
                                        rounded-md
                                        bg-[#008080]/10
                                        text-[#008080]
                                        dark:bg-[#008080]/20
                                        dark:text-[#5EEAD4]
                                    ">

                                    <i
                                        data-lucide="wifi"
                                        class="h-4 w-4"
                                        aria-hidden="true">
                                    </i>

                                </div>

                                <p
                                    class="
                                        text-[11px] font-semibold
                                        uppercase tracking-[0.14em]
                                        text-neutral-500
                                        dark:text-neutral-400
                                    ">
                                    Your Home Connection
                                </p>

                            </div>


                            <div class="mt-7">

                                <h3
                                    class="
                                        max-w-xl
                                        text-2xl font-semibold
                                        tracking-tight
                                        text-neutral-950
                                        dark:text-white
                                        sm:text-[30px]
                                    ">
                                    {{ $subscription?->servicePlan?->name ?? 'No active internet package' }}
                                </h3>


                                @if ($subscription?->servicePlan)

                                    <div class="mt-2 flex items-end gap-2">

                                        <span
                                            class="
                                                text-4xl font-semibold
                                                tracking-tight
                                                text-[#008080]
                                                sm:text-[42px]
                                            ">
                                            {{ number_format(
                                                (float) $subscription->servicePlan->speed_mbps,
                                                0
                                            ) }}
                                        </span>

                                        <span
                                            class="
                                                pb-1 text-sm font-medium
                                                text-neutral-500
                                                dark:text-neutral-400
                                            ">
                                            Mbps
                                        </span>

                                    </div>

                                @else

                                    <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
                                        Plan information is not currently available.
                                    </p>

                                @endif

                            </div>

                        </div>


                        <div
                            class="
                                flex h-16 w-16 shrink-0
                                items-center justify-center
                                rounded-md
                                border border-[#008080]/20
                                bg-[#008080]/10
                                text-[#008080]
                                dark:border-[#008080]/30
                                dark:bg-[#008080]/20
                                dark:text-[#5EEAD4]
                                sm:h-[72px]
                                sm:w-[72px]
                            ">

                            <i
                                data-lucide="router"
                                class="h-8 w-8"
                                aria-hidden="true">
                            </i>

                        </div>

                    </div>


                    <div
                        class="
                            mt-8 grid gap-4
                            border-t border-neutral-200
                            pt-4
                            dark:border-neutral-800
                            sm:grid-cols-3
                        ">

                        <div>

                            <p
                                class="
                                    text-[10px] font-semibold
                                    uppercase tracking-[0.12em]
                                    text-neutral-600
                                    dark:text-neutral-400
                                ">
                                Customer
                            </p>

                            <p class="mt-1 text-md font-semibold text-neutral-900 dark:text-white">
                                {{ $customer->customer_code }}
                            </p>

                        </div>


                        <div>

                            <p
                                class="
                                    text-[10px] font-semibold
                                    uppercase tracking-[0.12em]
                                    text-neutral-600
                                    dark:text-neutral-400
                                ">
                                Monthly Fee
                            </p>

                            <p class="mt-1 text-md font-semibold text-neutral-900 dark:text-white">

                                @if ($subscription?->servicePlan)
                                    ₱{{ number_format(
                                        (float) $subscription->servicePlan->monthly_fee,
                                        2
                                    ) }}
                                @else
                                    —
                                @endif

                            </p>

                        </div>


                        <div>

                            <p
                                class="
                                    text-[10px] font-semibold
                                    uppercase tracking-[0.12em]
                                    text-neutral-600
                                    dark:text-neutral-400
                                ">
                                Service Status
                            </p>

                            <div class="mt-1 flex items-center gap-1.5">

                                <i
                                    data-lucide="{{ $subscription?->status === 'active' ? 'circle-check' : 'circle-alert' }}"
                                    class="
                                        h-3.5 w-3.5
                                        {{ $subscription?->status === 'active'
                                            ? 'text-[#008080]'
                                            : 'text-amber-500'
                                        }}
                                    "
                                    aria-hidden="true">
                                </i>

                                <span class="text-md font-semibold text-neutral-900 dark:text-white">
                                    {{ $subscriptionStatusLabel ?? 'Unavailable' }}
                                </span>

                            </div>

                        </div>

                    </div>

                </div>

            </section>


            {{-- Billing summary --}}
            <section
                class="
                    flex flex-col overflow-hidden rounded-md
                    border border-neutral-200
                    bg-white
                    dark:border-neutral-800
                    dark:bg-neutral-900
                ">

                <div class="px-5 py-5">

                    <div class="flex items-center justify-between gap-3">

                        <div>

                            <p
                                class="
                                    text-[11px] font-semibold
                                    uppercase tracking-[0.12em]
                                    text-neutral-400
                                ">
                                Your Next Payment
                            </p>

                            <h3 class="mt-1 text-sm font-semibold">
                                Billing Summary
                            </h3>

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
                                data-lucide="credit-card"
                                class="h-4 w-4"
                                aria-hidden="true">
                            </i>

                        </div>

                    </div>


                    @if ($hasInvoice)

                        <div class="mt-7">

                            <p
                                class="
                                    text-3xl font-semibold
                                    tracking-tight
                                    text-neutral-950
                                    dark:text-white
                                ">
                                ₱{{ number_format(
                                    (float) $latestInvoiceBalance['balance_amount'],
                                    2
                                ) }}
                            </p>

                            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                {{ $hasOutstandingBalance ? 'Outstanding balance' : 'No outstanding balance' }}
                            </p>

                        </div>


                        <dl class="mt-6 space-y-3">

                            <div class="flex items-center justify-between gap-4">

                                <dt class="text-sm text-neutral-500 dark:text-neutral-400">
                                    Due date
                                </dt>

                                <dd class="text-sm font-medium">
                                    {{ $latestInvoice->due_date?->format('M d, Y') ?? '—' }}
                                </dd>

                            </div>


                            <div class="flex items-center justify-between gap-4">

                                <dt class="text-sm text-neutral-500 dark:text-neutral-400">
                                    Status
                                </dt>

                                <dd class="flex items-center gap-1.5 text-sm font-semibold">

                                    <i
                                        data-lucide="{{ $hasOutstandingBalance ? 'circle-alert' : 'circle-check' }}"
                                        class="
                                            h-3.5 w-3.5
                                            {{ $hasOutstandingBalance
                                                ? 'text-amber-500'
                                                : 'text-[#008080]'
                                            }}
                                        "
                                        aria-hidden="true">
                                    </i>

                                    {{ $invoiceStatusLabel }}

                                </dd>

                            </div>


                            <div class="flex items-center justify-between gap-4">

                                <dt class="text-sm text-neutral-500 dark:text-neutral-400">
                                    Invoice
                                </dt>

                                <dd class="text-sm font-medium">
                                    {{ $latestInvoice->invoice_number }}
                                </dd>

                            </div>

                        </dl>

                    @else

                        <div class="mt-7">

                            <p class="text-lg font-semibold text-neutral-900 dark:text-white">
                                No payment due
                            </p>

                            <p class="mt-2 text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                                No billing statement has been issued for this account yet.
                            </p>

                        </div>

                    @endif

                </div>


                <div
                    class="
                        mt-auto
                        border-t border-neutral-200
                        px-5 py-4
                        dark:border-neutral-800
                    ">

                    <a
                        href="{{ route('customer.billing.index') }}"
                        class="
                            inline-flex min-h-10 w-full
                            items-center justify-center gap-2
                            rounded-md
                            bg-[#008080]
                            px-4 py-2
                            text-sm font-semibold
                            text-white
                            transition
                            hover:bg-[#006666]
                            focus:outline-none
                            focus:ring-2
                            focus:ring-[#008080]/30
                            focus:ring-offset-2
                            dark:focus:ring-offset-neutral-900
                        ">

                        <span>Billing &amp; Invoices</span>

                        <i
                            data-lucide="arrow-right"
                            class="h-4 w-4"
                            aria-hidden="true">
                        </i>

                    </a>

                </div>

            </section>

        </div>


        <div
            class="
                mt-5 grid gap-5
                xl:grid-cols-[minmax(0,1.65fr)_minmax(300px,0.75fr)]
            ">

            {{-- Latest statement --}}
            <section
                class="
                    overflow-hidden rounded-md
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

                    <div>

                        <h3 class="text-sm font-semibold">
                            Latest Statement
                        </h3>

                        <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                            Your most recent billing statement.
                        </p>

                    </div>

                    <i
                        data-lucide="receipt-text"
                        class="h-5 w-5 text-neutral-400"
                        aria-hidden="true">
                    </i>

                </div>


                @if ($hasInvoice)

                    <div class="px-5 py-5">

                        <div
                            class="
                                grid min-w-0 gap-5
                                lg:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)_minmax(0,0.85fr)]
                                lg:items-center
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
                                    {{ $latestInvoice->invoice_number }}
                                </p>


                                <div class="mt-3 flex items-center gap-1.5">

                                    <i
                                        data-lucide="{{ $hasOutstandingBalance ? 'circle-alert' : 'circle-check' }}"
                                        class="
                                            h-3.5 w-3.5
                                            {{ $hasOutstandingBalance
                                                ? 'text-amber-500'
                                                : 'text-[#008080]'
                                            }}
                                        "
                                        aria-hidden="true">
                                    </i>

                                    <span class="text-xs font-semibold text-neutral-700 dark:text-neutral-300">
                                        {{ $invoiceStatusLabel }}
                                    </span>

                                </div>

                            </div>


                            <dl class="grid grid-cols-2 gap-4">

                                <div>

                                    <dt
                                        class="
                                            text-[10px] font-semibold
                                            uppercase tracking-[0.1em]
                                            text-neutral-400
                                        ">
                                        Billing Date
                                    </dt>

                                    <dd class="mt-1 text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                        {{ $latestInvoice->billing_date?->format('M d, Y') ?? '—' }}
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

                                    <dd class="mt-1 text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                        {{ $latestInvoice->due_date?->format('M d, Y') ?? '—' }}
                                    </dd>

                                </div>

                            </dl>


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

                                <p
                                    class="
                                        text-[10px] font-semibold
                                        uppercase tracking-[0.1em]
                                        text-neutral-400
                                    ">
                                    Outstanding
                                </p>

                                <p
                                    class="
                                        mt-1 text-lg font-semibold
                                        tracking-tight
                                        text-[#008080]
                                    ">
                                    ₱{{ number_format(
                                        (float) $latestInvoiceBalance['balance_amount'],
                                        2
                                    ) }}
                                </p>


                                <a
                                    href="{{ route('customer.billing.show', $latestInvoice) }}"
                                    class="
                                        mt-3 inline-flex min-h-9
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

                    </div>

                @else

                    <div class="px-5 py-8 text-center">

                        <i
                            data-lucide="file-text"
                            class="mx-auto h-6 w-6 text-neutral-300 dark:text-neutral-600"
                            aria-hidden="true">
                        </i>

                        <p class="mt-3 text-sm font-medium">
                            No statements yet
                        </p>

                        <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                            Your issued invoices will appear here.
                        </p>

                    </div>

                @endif

            </section>


            {{-- Quick actions --}}
            <section
                class="
                    overflow-hidden rounded-md
                    border border-neutral-200
                    bg-white
                    dark:border-neutral-800
                    dark:bg-neutral-900
                ">

                <div
                    class="
                        border-b border-neutral-200
                        px-5 py-4
                        dark:border-neutral-800
                    ">

                    <h3 class="text-sm font-semibold">
                        Quick Actions
                    </h3>

                    <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                        Common account tasks.
                    </p>

                </div>


                <div class="divide-y divide-neutral-200 dark:divide-neutral-800">

                    <a
                        href="{{ route('customer.billing.index') }}"
                        class="
                            flex items-center gap-3
                            px-5 py-4
                            transition
                            hover:bg-[#008080]/5
                            dark:hover:bg-[#008080]/10
                        ">

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
                                data-lucide="credit-card"
                                class="h-4 w-4"
                                aria-hidden="true">
                            </i>

                        </div>


                        <div class="min-w-0 flex-1">

                            <p class="text-sm font-semibold">
                                Billing &amp; Payments
                            </p>

                            <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                                View invoices and payment status
                            </p>

                        </div>

                        <i
                            data-lucide="chevron-right"
                            class="h-4 w-4 shrink-0 text-neutral-400"
                            aria-hidden="true">
                        </i>

                    </a>


                    <a
                        href="{{ route('profile.edit') }}"
                        class="
                            flex items-center gap-3
                            px-5 py-4
                            transition
                            hover:bg-[#008080]/5
                            dark:hover:bg-[#008080]/10
                        ">

                        <div
                            class="
                                flex h-9 w-9 shrink-0
                                items-center justify-center
                                rounded-md
                                bg-neutral-100
                                text-neutral-600
                                dark:bg-neutral-800
                                dark:text-neutral-300
                            ">

                            <i
                                data-lucide="user"
                                class="h-4 w-4"
                                aria-hidden="true">
                            </i>

                        </div>


                        <div class="min-w-0 flex-1">

                            <p class="text-sm font-semibold">
                                My Profile
                            </p>

                            <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                                Manage account information
                            </p>

                        </div>

                        <i
                            data-lucide="chevron-right"
                            class="h-4 w-4 shrink-0 text-neutral-400"
                            aria-hidden="true">
                        </i>

                    </a>

                </div>

            </section>

        </div>

    @endif

@endsection
