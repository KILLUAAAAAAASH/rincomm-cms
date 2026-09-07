@extends('layouts.app')

@section('title', 'Subscriber Details')

@section('page-title', 'Subscriber Details')


{{-- Secondary Navigation --}}
@section('secondary-navigation')

<a
    href="{{ route('admin.subscribers.index') }}"
    class="
        inline-flex min-h-12
        items-center gap-2
        text-sm font-medium
        text-[#008080]
        transition
        hover:text-[#006666]
        focus:outline-none
        focus:ring-2
        focus:ring-[#008080]/30
    ">

    <i
        data-lucide="arrow-left"
        class="h-4 w-4"
        aria-hidden="true">
    </i>

    All Subscribers

</a>

@endsection


@section('content')

@php
$fullName = trim(
$subscriber->first_name . ' ' .
($subscriber->middle_name ? $subscriber->middle_name . ' ' : '') .
$subscriber->last_name
);

$subscriberStatus = match ($subscriber->status) {
'pending' => [
'label' => 'Pending',
'icon' => 'clock-3',
'class' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300',
],
'active' => [
'label' => 'Active',
'icon' => 'circle-check',
'class' => 'bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-300',
],
'inactive' => [
'label' => 'Inactive',
'icon' => 'circle-minus',
'class' => 'bg-gray-100 text-gray-700 dark:bg-neutral-800 dark:text-gray-300',
],
'suspended' => [
'label' => 'Suspended',
'icon' => 'pause-circle',
'class' => 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300',
],
'disconnected' => [
'label' => 'Disconnected',
'icon' => 'wifi-off',
'class' => 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300',
],
default => [
'label' => ucfirst($subscriber->status),
'icon' => 'circle-help',
'class' => 'bg-gray-100 text-gray-700 dark:bg-neutral-800 dark:text-gray-300',
],
};

$subscriptionStatus = match ($latestSubscription?->status) {
'pending' => [
'label' => 'Pending',
'icon' => 'clock-3',
'class' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300',
],
'active' => [
'label' => 'Active',
'icon' => 'circle-check',
'class' => 'bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-300',
],
'expired' => [
'label' => 'Expired',
'icon' => 'calendar-x',
'class' => 'bg-gray-100 text-gray-700 dark:bg-neutral-800 dark:text-gray-300',
],
'cancelled' => [
'label' => 'Cancelled',
'icon' => 'circle-x',
'class' => 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300',
],
default => null,
};

$accountStatus = match ($subscriber->user?->account_status) {
'active' => [
'label' => 'Active',
'icon' => 'circle-check',
'class' => 'bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-300',
],
'inactive' => [
'label' => 'Inactive',
'icon' => 'circle-minus',
'class' => 'bg-gray-100 text-gray-700 dark:bg-neutral-800 dark:text-gray-300',
],
default => [
'label' => $subscriber->user?->account_status
? ucfirst($subscriber->user->account_status)
: 'Unavailable',
'icon' => 'circle-help',
'class' => 'bg-gray-100 text-gray-700 dark:bg-neutral-800 dark:text-gray-300',
],
};

$location = implode(', ', array_filter([
$subscriber->city,
$subscriber->province,
$subscriber->postal_code,
]));

$statusLabels = [
'active' => 'Active',
'inactive' => 'Inactive',
'suspended' => 'Suspended',
'disconnected' => 'Disconnected',
];
@endphp


<div class="space-y-4">

    {{-- Success Feedback --}}
    @if (session('success'))

    <div
        role="status"
        aria-live="polite"
        class="
                flex items-start gap-2.5
                border border-green-200
                bg-green-50 px-4 py-3
                text-sm text-green-800
                dark:border-green-900
                dark:bg-green-950/30
                dark:text-green-200
            ">

        <i
            data-lucide="circle-check"
            class="mt-0.5 h-4 w-4 shrink-0"
            aria-hidden="true">
        </i>

        <p>
            {{ session('success') }}
        </p>

    </div>

    @endif


    {{-- Error Feedback --}}
    @if (session('error'))

    <div
        role="alert"
        class="
                flex items-start gap-2.5
                border border-red-200
                bg-red-50 px-4 py-3
                text-sm text-red-800
                dark:border-red-900
                dark:bg-red-950/30
                dark:text-red-200
            ">

        <i
            data-lucide="triangle-alert"
            class="mt-0.5 h-4 w-4 shrink-0"
            aria-hidden="true">
        </i>

        <p>
            {{ session('error') }}
        </p>

    </div>

    @endif


    {{-- Validation Feedback --}}
    @if ($errors->any())

    <div
        role="alert"
        class="
                flex items-start gap-2.5
                border border-red-200
                bg-red-50 px-4 py-3
                text-sm text-red-800
                dark:border-red-900
                dark:bg-red-950/30
                dark:text-red-200
            ">

        <i
            data-lucide="triangle-alert"
            class="mt-0.5 h-4 w-4 shrink-0"
            aria-hidden="true">
        </i>

        <p>
            Please correct the status-change information and try again.
        </p>

    </div>

    @endif


    {{-- Main Subscriber Surface --}}
    <div
        class="
            border border-gray-200
            bg-white shadow-sm
            dark:border-neutral-800
            dark:bg-neutral-900
        ">

        {{-- Subscriber Header --}}
        <div
            class="
                flex flex-col gap-4
                border-b border-gray-200
                px-4 py-3
                sm:flex-row
                sm:items-start
                sm:justify-between
                dark:border-neutral-800
            ">

            <div class="min-w-0">

                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                    {{ $fullName }}
                </h1>

                <p class="mt-0.5 text-xs font-medium text-[#008080] dark:text-[#5EEAD4]">
                    {{ $subscriber->customer_code }}
                </p>

            </div>


            <div
                class="
                    flex flex-wrap items-center gap-2
                    sm:justify-end
                ">

                {{-- Subscriber Status --}}
                <span
                    class="
                        inline-flex w-fit shrink-0
                        items-center gap-1.5
                        px-2.5 py-1
                        text-xs font-medium
                        {{ $subscriberStatus['class'] }}
                    ">

                    <i
                        data-lucide="{{ $subscriberStatus['icon'] }}"
                        class="h-3.5 w-3.5"
                        aria-hidden="true">
                    </i>

                    {{ $subscriberStatus['label'] }}

                </span>


                {{-- Manage Status --}}
                @if (count($allowedStatusTransitions) > 0)

                <button
                    type="button"
                    data-subscriber-status-open
                    class="
                            inline-flex min-h-10
                            items-center justify-center gap-2
                            border border-[#008080]/40
                            px-3 py-2
                            text-sm font-semibold
                            text-[#008080]
                            transition
                            hover:bg-[#008080]/5
                            focus:outline-none
                            focus:ring-2
                            focus:ring-[#008080]/30
                            dark:border-[#14B8A6]/50
                            dark:text-[#5EEAD4]
                            dark:hover:bg-[#008080]/10
                        ">

                    <i
                        data-lucide="settings-2"
                        class="h-4 w-4"
                        aria-hidden="true">
                    </i>

                    Manage Status

                </button>

                @endif

            </div>

        </div>


        {{-- Manual Status Restriction --}}
        @if ($subscriber->status === 'pending')

        <div
            class="
                    flex items-start gap-2.5
                    border-b border-amber-200
                    bg-amber-50 px-4 py-3
                    text-sm text-amber-800
                    dark:border-amber-900
                    dark:bg-amber-950/20
                    dark:text-amber-200
                ">

            <i
                data-lucide="info"
                class="mt-0.5 h-4 w-4 shrink-0"
                aria-hidden="true">
            </i>

            <p>
                This subscriber is pending installation and service activation.
                Status cannot be changed manually from this screen yet.
            </p>

        </div>

        @elseif ($subscriber->status === 'disconnected')

        <div
            class="
                    flex items-start gap-2.5
                    border-b border-gray-200
                    bg-gray-50 px-4 py-3
                    text-sm text-gray-700
                    dark:border-neutral-800
                    dark:bg-neutral-950/50
                    dark:text-gray-300
                ">

            <i
                data-lucide="info"
                class="mt-0.5 h-4 w-4 shrink-0"
                aria-hidden="true">
            </i>

            <p>
                This subscriber is disconnected. Reconnection must use the proper
                reconnection and service workflow rather than a direct status change.
            </p>

        </div>

        @endif


        <div class="grid lg:grid-cols-3">

            {{-- Main Subscriber Information --}}
            <div
                class="
                    lg:col-span-2
                    lg:border-r
                    lg:border-gray-200
                    lg:dark:border-neutral-800
                ">

                {{-- Subscriber Information --}}
                <section
                    class="
                        border-b border-gray-200
                        p-4
                        dark:border-neutral-800
                    ">

                    <h2 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">
                        SUBSCRIBER INFORMATION
                    </h2>


                    <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">

                        <div>

                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Full Name
                            </dt>

                            <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $fullName }}
                            </dd>

                        </div>


                        <div>

                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Customer Code
                            </dt>

                            <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $subscriber->customer_code }}
                            </dd>

                        </div>


                        <div>

                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Email
                            </dt>

                            <dd class="mt-1 break-all text-sm text-gray-700 dark:text-gray-300">
                                {{ $subscriber->email ?: 'Not recorded' }}
                            </dd>

                        </div>


                        <div>

                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Phone
                            </dt>

                            <dd class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                {{ $subscriber->phone ?: 'Not recorded' }}
                            </dd>

                        </div>


                        <div>

                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Location
                            </dt>

                            <dd class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                {{ $location !== '' ? $location : 'Not specified' }}
                            </dd>

                        </div>


                        <div>

                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Subscriber Since
                            </dt>

                            <dd class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                {{ $subscriber->created_at?->format('M d, Y') ?? 'Not available' }}
                            </dd>

                        </div>

                    </dl>

                </section>


                {{-- Service and Billing Address --}}
                <section class="p-4">

                    <h2 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">
                        SERVICE & BILLING ADDRESS
                    </h2>


                    <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">

                        <div>

                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Installation Address
                            </dt>

                            <dd
                                class="
                                    mt-1 whitespace-pre-line
                                    text-sm leading-5
                                    text-gray-700
                                    dark:text-gray-300
                                ">

                                {{ $subscriber->installation_address
                                    ?: $subscriber->address
                                    ?: 'Not recorded'
                                }}

                            </dd>

                        </div>


                        <div>

                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Billing Address
                            </dt>

                            <dd
                                class="
                                    mt-1 whitespace-pre-line
                                    text-sm leading-5
                                    text-gray-700
                                    dark:text-gray-300
                                ">

                                {{ $subscriber->billing_address ?: 'Not provided' }}

                            </dd>

                        </div>

                    </dl>

                </section>

            </div>


            {{-- Portal Account and Subscription --}}
            <div>

                {{-- Portal Account --}}
                <section
                    class="
                        border-b border-gray-200
                        p-4
                        dark:border-neutral-800
                    ">

                    <h2 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">
                        PORTAL ACCOUNT
                    </h2>


                    @if ($subscriber->user)

                    <dl class="space-y-3">

                        <div>

                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Account Email
                            </dt>

                            <dd class="mt-1 break-all text-sm text-gray-700 dark:text-gray-300">
                                {{ $subscriber->user->email }}
                            </dd>

                        </div>


                        <div>

                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Role
                            </dt>

                            <dd class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                {{ ucfirst($subscriber->user->role) }}
                            </dd>

                        </div>


                        <div>

                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Account Status
                            </dt>

                            <dd class="mt-1">

                                <span
                                    class="
                                            inline-flex items-center gap-1.5
                                            px-2.5 py-1
                                            text-xs font-medium
                                            {{ $accountStatus['class'] }}
                                        ">

                                    <i
                                        data-lucide="{{ $accountStatus['icon'] }}"
                                        class="h-3.5 w-3.5"
                                        aria-hidden="true">
                                    </i>

                                    {{ $accountStatus['label'] }}

                                </span>

                            </dd>

                        </div>


                        <div>

                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Account Created
                            </dt>

                            <dd class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                {{ $subscriber->user->created_at?->format('M d, Y') ?? 'Not available' }}
                            </dd>

                        </div>

                    </dl>


                    @else

                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        No linked portal account is available.
                    </p>

                    @endif

                </section>


                {{-- Latest Subscription --}}
                <section class="p-4">

                    <h2 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">
                        LATEST SUBSCRIPTION
                    </h2>


                    @if ($latestSubscription)

                    <div class="flex items-start justify-between gap-3">

                        <div class="min-w-0">

                            <p class="text-base font-semibold text-gray-900 dark:text-white">
                                {{ $latestSubscription->servicePlan?->name ?? 'Plan unavailable' }}
                            </p>


                            @if ($latestSubscription->servicePlan)

                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">

                                {{ number_format(
                                            (float) $latestSubscription->servicePlan->speed_mbps,
                                            0
                                        ) }}
                                Mbps

                            </p>

                            @endif

                        </div>


                        @if ($subscriptionStatus)

                        <span
                            class="
                                        inline-flex shrink-0
                                        items-center gap-1.5
                                        px-2.5 py-1
                                        text-xs font-medium
                                        {{ $subscriptionStatus['class'] }}
                                    ">

                            <i
                                data-lucide="{{ $subscriptionStatus['icon'] }}"
                                class="h-3.5 w-3.5"
                                aria-hidden="true">
                            </i>

                            {{ $subscriptionStatus['label'] }}

                        </span>

                        @endif

                    </div>


                    <dl class="mt-4 space-y-3">

                        <div class="flex items-start justify-between gap-4">

                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Monthly Fee
                            </dt>

                            <dd class="text-right text-sm font-medium text-gray-900 dark:text-white">

                                @if ($latestSubscription->servicePlan)

                                &#8369;{{ number_format(
                                            (float) $latestSubscription->servicePlan->monthly_fee,
                                            2
                                        ) }}

                                @else

                                Not available

                                @endif

                            </dd>

                        </div>


                        <div class="flex items-start justify-between gap-4">

                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Start Date
                            </dt>

                            <dd class="text-right text-sm text-gray-700 dark:text-gray-300">
                                {{ $latestSubscription->start_date?->format('M d, Y') ?? 'Not started' }}
                            </dd>

                        </div>


                        <div class="flex items-start justify-between gap-4">

                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                End Date
                            </dt>

                            <dd class="text-right text-sm text-gray-700 dark:text-gray-300">
                                {{ $latestSubscription->end_date?->format('M d, Y') ?? 'Not set' }}
                            </dd>

                        </div>


                        <div class="flex items-start justify-between gap-4">

                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Lock-in
                            </dt>

                            <dd class="text-right text-sm text-gray-700 dark:text-gray-300">

                                @if ($latestSubscription->lock_in_months)

                                {{ $latestSubscription->lock_in_months }}

                                {{ $latestSubscription->lock_in_months === 1
                                            ? 'month'
                                            : 'months'
                                        }}

                                @else

                                Not set

                                @endif

                            </dd>

                        </div>

                    </dl>


                    @else

                    <div
                        class="
                                border border-dashed border-gray-300
                                px-4 py-5
                                text-center
                                dark:border-neutral-700
                            ">

                        <i
                            data-lucide="wifi"
                            class="mx-auto h-5 w-5 text-gray-400"
                            aria-hidden="true">
                        </i>

                        <p class="mt-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                            No subscription found
                        </p>

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Subscription information will appear here when available.
                        </p>

                    </div>

                    @endif

                </section>

            </div>

        </div>

    </div>

</div>


{{-- Manage Subscriber Status Modal --}}
@if (count($allowedStatusTransitions) > 0)

<div
    id="subscriber-status-modal"
    class="
            fixed inset-0 z-50
            hidden items-center justify-center
            p-4
        "
    data-open-on-error="{{ $errors->has('status') || $errors->has('reason') ? 'true' : 'false' }}"
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
    aria-labelledby="subscriber-status-modal-title">

    {{-- Overlay --}}
    <div
        data-subscriber-status-overlay
        class="absolute inset-0 bg-black/50">
    </div>


    {{-- Dialog --}}
    <div
        class="
                relative z-10
                max-h-[90vh] w-full max-w-lg
                overflow-y-auto
                bg-white p-5 shadow-xl
                dark:bg-neutral-900
                sm:p-6
            ">

        <div
            class="
                    flex h-11 w-11
                    items-center justify-center
                    bg-[#008080]/10
                    text-[#008080]
                    dark:bg-[#008080]/20
                    dark:text-[#5EEAD4]
                ">

            <i
                data-lucide="settings-2"
                class="h-5 w-5"
                aria-hidden="true">
            </i>

        </div>


        <h2
            id="subscriber-status-modal-title"
            class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">

            Manage Subscriber Status

        </h2>


        <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">

            Change the subscriber lifecycle status for

            <span class="font-medium text-gray-800 dark:text-gray-200">
                {{ $fullName }}
            </span>.

            The reason will be stored in the subscriber status history.

        </p>


        {{-- Current Status --}}
        <div
            class="
                    mt-5 flex items-center justify-between gap-4
                    border border-gray-200
                    bg-gray-50 px-3 py-3
                    dark:border-neutral-800
                    dark:bg-neutral-950
                ">

            <span class="text-sm text-gray-500 dark:text-gray-400">
                Current Status
            </span>


            <span
                class="
                        inline-flex items-center gap-1.5
                        px-2.5 py-1
                        text-xs font-medium
                        {{ $subscriberStatus['class'] }}
                    ">

                <i
                    data-lucide="{{ $subscriberStatus['icon'] }}"
                    class="h-3.5 w-3.5"
                    aria-hidden="true">
                </i>

                {{ $subscriberStatus['label'] }}

            </span>

        </div>


        <form
            method="POST"
            action="{{ route('admin.subscribers.status', $subscriber) }}"
            data-lock-submit
            class="mt-5">

            @csrf
            @method('PATCH')


            {{-- New Status --}}
            <div>

                <label
                    for="subscriber_status"
                    class="text-sm font-medium text-gray-900 dark:text-white">

                    New Status

                </label>


                <select
                    id="subscriber_status"
                    name="status"
                    required
                    class="
                            mt-2 block min-h-11 w-full
                            border bg-white px-3 py-2.5
                            text-sm text-gray-900
                            outline-none transition
                            focus:ring-2
                            dark:bg-neutral-950
                            dark:text-white

                            @error('status')
                                border-red-500
                                focus:border-red-500
                                focus:ring-red-200
                                dark:border-red-500
                                dark:focus:ring-red-950
                            @else
                                border-gray-300
                                focus:border-[#008080]
                                focus:ring-[#008080]/20
                                dark:border-neutral-700
                                dark:focus:border-[#14B8A6]
                            @enderror
                        ">

                    <option value="">
                        Select a new status
                    </option>


                    @foreach ($allowedStatusTransitions as $transition)

                    <option
                        value="{{ $transition }}"
                        @selected(old('status')===$transition)>

                        {{ $statusLabels[$transition] ?? ucfirst($transition) }}

                    </option>

                    @endforeach

                </select>


                @error('status')

                <p
                    class="
                                mt-1.5 flex items-start gap-1.5
                                text-xs text-red-600
                                dark:text-red-400
                            ">

                    <i
                        data-lucide="triangle-alert"
                        class="mt-0.5 h-3.5 w-3.5 shrink-0"
                        aria-hidden="true">
                    </i>

                    <span>
                        {{ $message }}
                    </span>

                </p>

                @enderror

            </div>


            {{-- Reason --}}
            <div class="mt-5">

                <label
                    for="subscriber_status_reason"
                    class="text-sm font-medium text-gray-900 dark:text-white">

                    Reason

                    <span class="text-red-600 dark:text-red-400">
                        *
                    </span>

                </label>


                <textarea
                    id="subscriber_status_reason"
                    name="reason"
                    rows="4"
                    maxlength="1000"
                    required
                    placeholder="Explain why this subscriber status is being changed."
                    class="
                            mt-2 block w-full
                            border bg-white px-3 py-2.5
                            text-sm text-gray-900
                            outline-none transition
                            placeholder:text-gray-400
                            focus:ring-2
                            dark:bg-neutral-950
                            dark:text-white

                            @error('reason')
                                border-red-500
                                focus:border-red-500
                                focus:ring-red-200
                                dark:border-red-500
                                dark:focus:ring-red-950
                            @else
                                border-gray-300
                                focus:border-[#008080]
                                focus:ring-[#008080]/20
                                dark:border-neutral-700
                                dark:focus:border-[#14B8A6]
                            @enderror
                        ">{{ old('reason') }}</textarea>


                <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                    This reason becomes part of the subscriber's status history.
                </p>


                @error('reason')

                <p
                    class="
                                mt-1.5 flex items-start gap-1.5
                                text-xs text-red-600
                                dark:text-red-400
                            ">

                    <i
                        data-lucide="triangle-alert"
                        class="mt-0.5 h-3.5 w-3.5 shrink-0"
                        aria-hidden="true">
                    </i>

                    <span>
                        {{ $message }}
                    </span>

                </p>

                @enderror

            </div>


            {{-- Actions --}}
            <div
                class="
                        mt-6 flex flex-col-reverse gap-2
                        sm:flex-row
                        sm:justify-end
                    ">

                <button
                    type="button"
                    data-subscriber-status-cancel
                    class="
                            min-h-11
                            border border-gray-300
                            px-4 py-2.5
                            text-sm font-medium
                            text-gray-700
                            transition
                            hover:bg-gray-50
                            focus:outline-none
                            focus:ring-2
                            focus:ring-gray-300
                            dark:border-neutral-700
                            dark:text-gray-300
                            dark:hover:bg-neutral-800
                        ">

                    Cancel

                </button>


                <button
                    type="submit"
                    data-loading-text="Updating..."
                    class="
                            inline-flex min-h-11
                            items-center justify-center gap-2
                            bg-[#008080] px-4 py-2.5
                            text-sm font-semibold
                            text-white
                            transition
                            hover:bg-[#006666]
                            focus:outline-none
                            focus:ring-2
                            focus:ring-[#008080]/30
                        ">

                    <i
                        data-lucide="save"
                        class="h-4 w-4"
                        aria-hidden="true">
                    </i>

                    Confirm Status Change

                </button>

            </div>

        </form>

    </div>

</div>


<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('subscriber-status-modal');
        const openButton = document.querySelector('[data-subscriber-status-open]');
        const cancelButton = document.querySelector('[data-subscriber-status-cancel]');
        const overlay = document.querySelector('[data-subscriber-status-overlay]');
        const statusField = document.getElementById('subscriber_status');

        if (
            !modal ||
            !openButton ||
            !cancelButton ||
            !overlay ||
            !statusField
        ) {
            return;
        }

        const openModal = () => {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.setAttribute('aria-hidden', 'false');

            document.body.classList.add('overflow-hidden');

            statusField.focus();
        };

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modal.setAttribute('aria-hidden', 'true');

            document.body.classList.remove('overflow-hidden');

            openButton.focus();
        };

        openButton.addEventListener('click', openModal);
        cancelButton.addEventListener('click', closeModal);
        overlay.addEventListener('click', closeModal);

        document.addEventListener('keydown', (event) => {
            if (
                event.key === 'Escape' &&
                modal.getAttribute('aria-hidden') === 'false'
            ) {
                closeModal();
            }
        });

        if (modal.dataset.openOnError === 'true') {
            openModal();
        }
    });
</script>

@endif

@endsection