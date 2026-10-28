@extends('layouts.customer')

@section('title', $invoice->invoice_number . ' - Rincomm Customer Portal')

@section('page-title', 'Invoice Details')

@section('header-actions')

    <a
        href="{{ route('customer.billing.index') }}"
        class="
            inline-flex min-h-9
            items-center gap-2
            rounded-md
            border border-neutral-200
            bg-white
            px-3 py-2
            text-xs font-semibold
            text-neutral-700
            transition
            hover:border-[#008080]
            hover:text-[#008080]
            focus:outline-none
            focus:ring-2
            focus:ring-[#008080]/20
            dark:border-neutral-700
            dark:bg-neutral-900
            dark:text-neutral-300
            dark:hover:border-[#008080]
            dark:hover:text-[#5EEAD4]
        ">

        <i
            data-lucide="arrow-left"
            class="h-4 w-4"
            aria-hidden="true">
        </i>

        <span class="hidden sm:inline">
            Back to Billing
        </span>

    </a>

@endsection


@section('content')

    @php
        $invoiceStatusLabel = str($invoice->status)
            ->replace('_', ' ')
            ->title();

        $invoiceStatusClasses = match ($invoice->status) {
            'paid' =>
                'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300',

            'overdue' =>
                'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300',

            'partially_paid' =>
                'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300',

            default =>
                'border-neutral-200 bg-neutral-50 text-neutral-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300',
        };

        // Invoices created before plan snapshots were introduced can have
        // the billed plan name in their recorded recurring-charge item.
        // Never substitute the subscription's current plan for a past bill.
        $billedPlanName = trim((string) $invoice->billed_service_plan_name);

        if ($billedPlanName === '') {
            $legacyPlanNames = $invoice->items
                ->pluck('description')
                ->filter(fn ($description) => is_string($description)
                    && str_starts_with($description, 'Monthly internet service - '))
                ->map(fn ($description) => trim(substr(
                    $description,
                    strlen('Monthly internet service - ')
                )))
                ->filter(fn ($name) => $name !== '')
                ->unique()
                ->values();

            $billedPlanName = $legacyPlanNames->count() === 1
                ? $legacyPlanNames->first()
                : 'Not recorded';
        }
    @endphp


    {{-- Page heading --}}
    <div class="mb-6 min-w-0">

        <p
            class="
                text-[11px] font-semibold
                uppercase tracking-[0.12em]
                text-[#008080]
                dark:text-[#5EEAD4]
            ">
            Billing Statement
        </p>

        <div
            class="
                mt-1.5 flex flex-col gap-3
                sm:flex-row
                sm:items-start
                sm:justify-between
            ">

            <div class="min-w-0">

                <h2
                    class="
                        break-all
                        text-xl font-semibold
                        tracking-tight
                        text-neutral-950
                        dark:text-white
                        sm:text-2xl
                    ">
                    {{ $invoice->invoice_number }}
                </h2>

                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                    Review charges, payment status, and your current outstanding balance.
                </p>

            </div>


            <span
                class="
                    inline-flex w-fit shrink-0
                    items-center gap-1.5
                    rounded-md
                    border px-2.5 py-1.5
                    text-xs font-semibold
                    {{ $invoiceStatusClasses }}
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

                {{ $invoiceStatusLabel }}

            </span>

        </div>

    </div>


    @if ($errors->any())

        <div
            role="alert"
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

            <span>
                {{ $errors->first() }}
            </span>

        </div>

    @endif


    <div
        class="
            grid min-w-0 gap-5
            xl:grid-cols-[minmax(0,1fr)_320px]
        ">

        {{-- Main invoice content --}}
        <div class="min-w-0 space-y-5">

            {{-- Invoice overview --}}
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
                        flex items-center gap-3
                        border-b border-neutral-200
                        px-5 py-4
                        dark:border-neutral-800
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
                            data-lucide="receipt-text"
                            class="h-4 w-4"
                            aria-hidden="true">
                        </i>

                    </div>


                    <div class="min-w-0">

                        <h3 class="text-sm font-semibold">
                            Invoice Overview
                        </h3>

                        <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                            Statement and service information.
                        </p>

                    </div>

                </div>


                <dl
                    class="
                        grid
                        sm:grid-cols-2
                        lg:grid-cols-3
                    ">

                    <div
                        class="
                            min-w-0
                            border-b border-neutral-200
                            px-5 py-4
                            dark:border-neutral-800
                            sm:border-r
                            lg:border-b
                        ">

                        <dt
                            class="
                                text-[10px] font-semibold
                                uppercase tracking-[0.1em]
                                text-neutral-400
                            ">
                            Invoice Number
                        </dt>

                        <dd class="mt-1.5 break-all text-sm font-semibold">
                            {{ $invoice->invoice_number }}
                        </dd>

                    </div>


                    <div
                        class="
                            border-b border-neutral-200
                            px-5 py-4
                            dark:border-neutral-800
                            lg:border-r
                        ">

                        <dt
                            class="
                                text-[10px] font-semibold
                                uppercase tracking-[0.1em]
                                text-neutral-400
                            ">
                            Billing Date
                        </dt>

                        <dd class="mt-1.5 text-sm font-medium">
                            {{ $invoice->billing_date?->format('M d, Y') ?? '—' }}
                        </dd>

                    </div>


                    <div
                        class="
                            border-b border-neutral-200
                            px-5 py-4
                            dark:border-neutral-800
                            sm:border-r
                            lg:border-r-0
                        ">

                        <dt
                            class="
                                text-[10px] font-semibold
                                uppercase tracking-[0.1em]
                                text-neutral-400
                            ">
                            Due Date
                        </dt>

                        <dd class="mt-1.5 text-sm font-medium">
                            {{ $invoice->due_date?->format('M d, Y') ?? '—' }}
                        </dd>

                    </div>


                    <div
                        class="
                            border-b border-neutral-200
                            px-5 py-4
                            dark:border-neutral-800
                            lg:border-b-0 lg:border-r
                        ">

                        <dt
                            class="
                                text-[10px] font-semibold
                                uppercase tracking-[0.1em]
                                text-neutral-400
                            ">
                            Service Plan
                        </dt>

                        <dd class="mt-1.5 break-words text-sm font-medium">
                            {{ $billedPlanName }}
                        </dd>

                    </div>


                    <div
                        class="
                            border-b border-neutral-200
                            px-5 py-4
                            dark:border-neutral-800
                            sm:border-r sm:border-b-0
                            lg:border-r
                        ">

                        <dt
                            class="
                                text-[10px] font-semibold
                                uppercase tracking-[0.1em]
                                text-neutral-400
                            ">
                            Customer
                        </dt>

                        <dd class="mt-1.5 break-words text-sm font-medium">
                            {{ $customer->first_name }} {{ $customer->last_name }}
                        </dd>

                    </div>


                    <div class="px-5 py-4">

                        <dt
                            class="
                                text-[10px] font-semibold
                                uppercase tracking-[0.1em]
                                text-neutral-400
                            ">
                            Status
                        </dt>

                        <dd class="mt-1.5 text-sm font-semibold">
                            {{ $invoiceStatusLabel }}
                        </dd>

                    </div>

                </dl>

            </section>


            {{-- Charges --}}
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

                    <div>

                        <h3 class="text-sm font-semibold">
                            Charges
                        </h3>

                        <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                            Services included in this billing statement.
                        </p>

                    </div>

                    <i
                        data-lucide="file-text"
                        class="h-5 w-5 shrink-0 text-neutral-400"
                        aria-hidden="true">
                    </i>

                </div>


                @if ($invoice->items->isEmpty())

                    <div class="px-5 py-6 text-sm text-neutral-500 dark:text-neutral-400">
                        No charge items are available.
                    </div>

                @else

                    <div class="divide-y divide-neutral-200 dark:divide-neutral-800">

                        @foreach ($invoice->items as $item)

                            <div
                                class="
                                    flex min-w-0 flex-col gap-1.5
                                    px-5 py-4
                                    sm:flex-row
                                    sm:items-start
                                    sm:justify-between
                                    sm:gap-6
                                ">

                                <p class="min-w-0 break-words text-sm text-neutral-800 dark:text-neutral-200">
                                    {{ $item->description }}
                                </p>

                                <p class="shrink-0 text-sm font-semibold text-neutral-950 dark:text-white">
                                    &#8369;{{ number_format((float) $item->amount, 2) }}
                                </p>

                            </div>

                        @endforeach

                    </div>

                @endif

            </section>


            @include('shared.payment-ledger')

            {{-- Gateway activity --}}
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
                        flex items-start justify-between gap-4
                        border-b border-neutral-200
                        px-5 py-4
                        dark:border-neutral-800
                    ">

                    <div>

                        <h3 class="text-sm font-semibold">
                            GCash payment activity
                        </h3>

                        <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                            Online payment attempts recorded for this invoice.
                        </p>

                    </div>

                    <i
                        data-lucide="credit-card"
                        class="mt-0.5 h-5 w-5 shrink-0 text-neutral-400"
                        aria-hidden="true">
                    </i>

                </div>


                @if ($gatewayPayments->isEmpty())

                    <div class="px-5 py-7 text-sm text-neutral-500 dark:text-neutral-400">
                        No GCash payment attempts have been recorded for this invoice.
                    </div>

                @else

                    <div class="divide-y divide-neutral-200 dark:divide-neutral-800">

                        @foreach ($gatewayPayments as $gatewayPayment)

                            @php
                                $gatewayStatusClasses = match ($gatewayPayment->payment_status) {
                                    'completed' =>
                                        'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300',

                                    'failed' =>
                                        'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300',

                                    'cancelled' =>
                                        'border-neutral-200 bg-neutral-50 text-neutral-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300',

                                    default =>
                                        'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300',
                                };

                                $confirmationLabel = match ($gatewayPayment->payment_status) {
                                    'completed' => $gatewayPayment->paid_at?->format('M d, Y h:i A') ?? 'Confirmed',
                                    'failed' => 'Payment failed',
                                    'cancelled' => 'Payment cancelled',
                                    default => 'Awaiting provider confirmation',
                                };
                            @endphp


                            <div
                                class="
                                    grid min-w-0 gap-4
                                    px-5 py-4
                                    sm:grid-cols-2
                                    lg:grid-cols-[minmax(0,1.05fr)_minmax(0,0.65fr)_minmax(0,0.7fr)_minmax(0,1.4fr)]
                                    lg:items-center
                                ">

                                <div class="min-w-0">

                                    <p
                                        class="
                                            text-[10px] font-semibold
                                            uppercase tracking-[0.1em]
                                            text-neutral-400
                                        ">
                                        Started
                                    </p>

                                    <p class="mt-1 break-words text-sm font-medium">
                                        {{ $gatewayPayment->created_at?->format('M d, Y h:i A') ?? '—' }}
                                    </p>

                                </div>


                                <div>

                                    <p
                                        class="
                                            text-[10px] font-semibold
                                            uppercase tracking-[0.1em]
                                            text-neutral-400
                                        ">
                                        Amount
                                    </p>

                                    <p class="mt-1 text-sm font-semibold">
                                        &#8369;{{ number_format((float) $gatewayPayment->amount, 2) }}
                                    </p>

                                </div>


                                <div>

                                    <p
                                        class="
                                            text-[10px] font-semibold
                                            uppercase tracking-[0.1em]
                                            text-neutral-400
                                        ">
                                        Status
                                    </p>

                                    <span
                                        class="
                                            mt-1 inline-flex
                                            rounded-md
                                            border px-2 py-1
                                            text-xs font-semibold
                                            {{ $gatewayStatusClasses }}
                                        ">
                                        {{ str($gatewayPayment->payment_status)->title() }}
                                    </span>

                                </div>


                                <div class="min-w-0">

                                    <p
                                        class="
                                            text-[10px] font-semibold
                                            uppercase tracking-[0.1em]
                                            text-neutral-400
                                        ">
                                        Confirmation
                                    </p>

                                    <p class="mt-1 break-words text-sm text-neutral-600 dark:text-neutral-300">
                                        {{ $confirmationLabel }}
                                    </p>

                                </div>

                            </div>

                        @endforeach

                    </div>

                @endif

            </section>

        </div>


        {{-- Billing summary --}}
        <aside class="min-w-0">

            <section
                class="
                    overflow-hidden rounded-md
                    border border-neutral-200
                    bg-white
                    dark:border-neutral-800
                    dark:bg-neutral-900
                    xl:sticky
                    xl:top-[88px]
                ">

                <div
                    class="
                        flex items-center gap-3
                        border-b border-neutral-200
                        px-5 py-4
                        dark:border-neutral-800
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


                    <div>

                        <h3 class="text-sm font-semibold">
                            Billing Summary
                        </h3>

                        <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                            Current invoice balance.
                        </p>

                    </div>

                </div>


                <dl class="divide-y divide-neutral-200 dark:divide-neutral-800">

                    <div class="flex items-center justify-between gap-4 px-5 py-3.5">

                        <dt class="text-sm text-neutral-500 dark:text-neutral-400">
                            Total
                        </dt>

                        <dd class="shrink-0 text-sm font-semibold">
                            &#8369;{{ number_format((float) $balance['total_amount'], 2) }}
                        </dd>

                    </div>


                    <div class="flex items-center justify-between gap-4 px-5 py-3.5">

                        <dt class="text-sm text-neutral-500 dark:text-neutral-400">
                            Paid
                        </dt>

                        <dd class="shrink-0 text-sm font-semibold">
                            &#8369;{{ number_format((float) $balance['paid_amount'], 2) }}
                        </dd>

                    </div>


                    <div class="px-5 py-4">

                        <dt
                            class="
                                text-[10px] font-semibold
                                uppercase tracking-[0.1em]
                                text-neutral-400
                            ">
                            Outstanding
                        </dt>

                        <dd
                            class="
                                mt-1 text-2xl font-semibold
                                tracking-tight
                                text-[#008080]
                                dark:text-[#5EEAD4]
                            ">
                            &#8369;{{ number_format((float) $balance['balance_amount'], 2) }}
                        </dd>

                    </div>

                </dl>


                <div
                    class="
                        border-t border-neutral-200
                        px-5 py-5
                        dark:border-neutral-800
                    ">

                    {{-- Feature 4: payment proof is independent of verified gateway payments. --}}
                    <section class="mb-6 rounded-lg border border-neutral-200 p-4 dark:border-neutral-800" aria-labelledby="proof-heading">
                        <h3 id="proof-heading" class="text-sm font-semibold">Submit proof of payment</h3>
                        <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                            Only upload a receipt for a transfer you have already made. Uploading does not pay this invoice or confirm receipt of funds.
                        </p>

                        @if ($paymentProofs->isNotEmpty())
                            <ul class="mt-3 space-y-2 text-xs" aria-label="Submitted payment proofs">
                                @foreach ($paymentProofs as $submittedProof)
                                    <li class="rounded-md border border-neutral-200 p-2 dark:border-neutral-700">
                                        {{ $submittedProof->transaction_reference }}
                                        &middot; &#8369;{{ number_format((float) $submittedProof->amount, 2) }}
                                        &middot; {{ $submittedProof->status === 'pending' ? 'Pending staff review' : ucfirst($submittedProof->status) }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if ($hasPendingPaymentProof)
                            <p class="mt-3 text-sm text-amber-700 dark:text-amber-300" role="status">A proof is awaiting staff review. Do not submit it again.</p>
                        @elseif ($hasPendingGatewayPayment)
                            <p class="mt-3 text-sm text-amber-700 dark:text-amber-300">Resolve your pending GCash gateway payment before submitting other proof.</p>
                        @elseif ($balance['balance_cents'] > 0 && in_array($invoice->status, ['issued', 'partially_paid', 'overdue'], true))
                            <form method="POST" action="{{ route('customer.billing.payment-proofs.store', $invoice) }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                                @csrf
                                <div>
                                    <label for="proof-method" class="block text-xs font-medium">Payment method</label>
                                    <select id="proof-method" name="payment_method" required class="mt-1 w-full rounded-md border border-neutral-300 bg-white p-2 text-sm text-neutral-900 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white">
                                        <option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>Bank transfer</option>
                                        <option value="gcash" @selected(old('payment_method') === 'gcash')>Manual GCash transfer (not online checkout)</option>
                                        <option value="other" @selected(old('payment_method') === 'other')>Other transfer</option>
                                    </select>
                                    @error('payment_method') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="proof-reference" class="block text-xs font-medium">Transaction reference</label>
                                    <input id="proof-reference" name="transaction_reference" required minlength="5" maxlength="100" value="{{ old('transaction_reference') }}" class="mt-1 w-full rounded-md border border-neutral-300 bg-white p-2 text-sm text-neutral-900 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white">
                                    @error('transaction_reference') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="proof-amount" class="block text-xs font-medium">Amount already paid (PHP)</label>
                                    <input id="proof-amount" name="amount" type="number" min="0.01" step="0.01" required value="{{ old('amount') }}" class="mt-1 w-full rounded-md border border-neutral-300 bg-white p-2 text-sm text-neutral-900 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white">
                                    @error('amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="proof-file" class="block text-xs font-medium">Proof image or PDF (max 5 MB)</label>
                                    <input id="proof-file" name="proof" type="file" accept="image/jpeg,image/png,application/pdf" required class="mt-1 block w-full text-xs">
                                    @error('proof') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <button type="submit" class="rounded-md bg-[#008080] px-4 py-2 text-sm font-semibold text-white hover:bg-[#006666]">Submit for staff review</button>
                            </form>
                        @else
                            <p class="mt-3 text-xs text-neutral-500">No outstanding balance is available for proof submission.</p>
                        @endif
                    </section>
                    @if ($hasPendingGatewayPayment)

                        <div
                            class="
                                rounded-md
                                border border-amber-200
                                bg-amber-50
                                px-4 py-4
                                dark:border-amber-900
                                dark:bg-amber-950/25
                            ">

                            <div class="flex items-start gap-3">

                                <i
                                    data-lucide="circle-alert"
                                    class="
                                        mt-0.5 h-4 w-4 shrink-0
                                        text-amber-600
                                        dark:text-amber-400
                                    "
                                    aria-hidden="true">
                                </i>


                                <div class="min-w-0">

                                    <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                                        GCash payment pending
                                    </p>

                                    <p class="mt-1 text-xs leading-5 text-amber-700 dark:text-amber-400">
                                        This payment is awaiting verified provider confirmation. Another online or cash payment cannot be started until it is resolved.
                                    </p>


                                    <form
                                        method="POST"
                                        action="{{ route('customer.billing.gateway-payments.recover', $invoice) }}"
                                        data-lock-submit
                                        class="mt-3">

                                        @csrf

                                        <button
                                            type="submit"
                                            class="
                                                inline-flex min-h-9 w-full
                                                items-center justify-center gap-2
                                                rounded-md
                                                border border-amber-300
                                                bg-white
                                                px-3 py-2
                                                text-xs font-semibold
                                                text-amber-800
                                                transition
                                                hover:border-[#008080]
                                                hover:text-[#008080]
                                                focus:outline-none
                                                focus:ring-2
                                                focus:ring-[#008080]/20
                                                dark:border-amber-800
                                                dark:bg-neutral-900
                                                dark:text-amber-300
                                                dark:hover:border-[#008080]
                                                dark:hover:text-[#5EEAD4]
                                            ">

                                            <i
                                                data-lucide="refresh-cw"
                                                class="h-4 w-4"
                                                aria-hidden="true">
                                            </i>

                                            Check / Continue GCash Payment

                                        </button>

                                    </form>

                                </div>

                            </div>

                        </div>

                    @elseif (
                        $balance['balance_cents'] > 0
                        && in_array(
                            $invoice->status,
                            ['issued', 'partially_paid', 'overdue'],
                            true
                        )
                    )

                        <div>

                            <p class="text-sm font-semibold">
                                Pay with GCash
                            </p>

                            <p class="mt-1 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                The full outstanding balance will be processed securely through the payment provider.
                            </p>


                            <div
                                class="
                                    mt-4 flex items-center justify-between gap-4
                                    border-y border-neutral-200
                                    py-3
                                    dark:border-neutral-800
                                ">

                                <span class="text-xs text-neutral-500 dark:text-neutral-400">
                                    Amount to pay
                                </span>

                                <span class="shrink-0 text-sm font-semibold">
                                    &#8369;{{ number_format((float) $balance['balance_amount'], 2) }}
                                </span>

                            </div>


                            <form
                                method="POST"
                                action="{{ route('customer.billing.gateway-payments.store', $invoice) }}"
                                data-lock-submit
                                class="mt-4">

                                @csrf

                                <input
                                    type="hidden"
                                    name="payment_token"
                                    value="{{ $paymentToken }}">


                                <button
                                    type="submit"
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

                                    <i
                                        data-lucide="credit-card"
                                        class="h-4 w-4"
                                        aria-hidden="true">
                                    </i>

                                    Pay Online with GCash

                                </button>

                            </form>


                            <p class="mt-3 text-[11px] leading-5 text-neutral-500 dark:text-neutral-400">
                                Payment is recorded as completed only after verified provider confirmation.
                            </p>

                        </div>

                    @else

                        <div class="flex items-start gap-2.5">

                            <i
                                data-lucide="info"
                                class="mt-0.5 h-4 w-4 shrink-0 text-neutral-400"
                                aria-hidden="true">
                            </i>

                            <p class="text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                Online payment is not available for this invoice.
                            </p>

                        </div>

                    @endif

                </div>

            </section>

        </aside>

    </div>

@endsection