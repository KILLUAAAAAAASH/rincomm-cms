@extends('layouts.app')

@section('title', 'Subscriber Details')

@section('page-title', 'Subscriber Details')

@php
$allowedFeatureKeys = [
    'customer-information',
    'customer-account-status',
    'customer-documents',
    'relocation-transfer',
    'plan-change',
];

$requestedFeature = request('feature');

$featureKey = in_array($requestedFeature, $allowedFeatureKeys, true)
    ? $requestedFeature
    : null;
@endphp


{{-- Secondary navigation --}}
@section('secondary-navigation')

<a
    href="{{ route('admin.subscribers.index', $featureKey ? ['feature' => $featureKey] : []) }}"
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
        aria-hidden="true"></i>

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
'icon' => 'triangle-alert',
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
'icon' => 'circle-minus',
'class' => 'bg-gray-100 text-gray-700 dark:bg-neutral-800 dark:text-gray-300',
],
'cancelled' => [
'label' => 'Cancelled',
'icon' => 'triangle-alert',
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

$hasSubscriptionErrors =
    $errors->hasAny([
        'discount_amount',
        'start_date',
        'lock_in_months',
    ]) ||
    (
        $errors->has('reason') &&
        (
            old('discount_amount') !== null ||
            old('start_date') !== null ||
            old('lock_in_months') !== null
        )
    );

$hasStatusErrors =
    $errors->has('status') ||
    (
        ! $hasSubscriptionErrors &&
        $errors->has('reason') &&
        old('status') !== null
    );

$hasDocumentErrors = $errors->hasAny([
    'document_type',
    'document',
    'notes',
]);
@endphp


<div class="space-y-2">

    {{-- Success feedback --}}
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
            aria-hidden="true"></i>

        <p>
            {{ session('success') }}
        </p>
    </div>

    @endif


    {{-- Error feedback --}}
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
            aria-hidden="true"></i>

        <p>
            {{ session('error') }}
        </p>
    </div>

    @endif


    {{-- Subscriber status validation feedback --}}
    @if ($hasStatusErrors)

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
            aria-hidden="true"></i>

        <p>
            Please correct the status-change information and try again.
        </p>
    </div>

    @endif


    @php
    $displayPlan = $latestSubscription?->servicePlan;

    $baseMonthlyFee = $displayPlan
        ? (float) $displayPlan->monthly_fee
        : 0;

    $promotionalDiscount = $latestSubscription
        ? (float) $latestSubscription->discount_amount
        : 0;

    $effectiveMonthlyFee = max(
        0,
        $baseMonthlyFee - $promotionalDiscount
    );

    $displayInstallationAddress =
        $subscriber->installation_address
        ?: $subscriber->address
        ?: null;

    $lockInStatus = $latestSubscription
        ? $latestSubscription->lockInStatus()
        : null;

    $remainingLockInDays = $latestSubscription
        ? $latestSubscription->remainingLockInDays()
        : 0;

    $lockInStatusMeta = match ($lockInStatus) {
        'active' => [
            'label' => 'Active',
            'class' =>
                'border-amber-200 bg-amber-50 text-amber-700
                 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300',
        ],

        'completed' => [
            'label' => 'Completed',
            'class' =>
                'border-green-200 bg-green-50 text-green-700
                 dark:border-green-900 dark:bg-green-950/30 dark:text-green-300',
        ],

        'not_started' => [
            'label' => 'Not Started',
            'class' =>
                'border-blue-200 bg-blue-50 text-blue-700
                 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-300',
        ],

        default => [
            'label' => 'Not Set',
            'class' =>
                'border-gray-200 bg-gray-50 text-gray-600
                 dark:border-neutral-700 dark:bg-neutral-800 dark:text-gray-300',
        ],
    };

    $openRelocationQuickAction =
        $subscriber->relocationRequests->first(
            fn ($request) =>
                in_array(
                    $request->status,
                    ['pending', 'approved'],
                    true
                )
        );
    @endphp


    {{-- Subscriber header --}}
    <section
        class="
            border border-gray-200
            bg-white
            px-4 py-3
            dark:border-neutral-800
            dark:bg-neutral-900
            sm:px-5
        ">

        <div
            class="
                flex items-start
                justify-between gap-4
            ">

            <div class="min-w-0">

                <div
                    class="
                        flex flex-wrap
                        items-center gap-2
                    ">

                    <h1
                        class="
                            truncate
                            text-xl font-semibold
                            tracking-tight
                            text-gray-900
                            dark:text-white
                            sm:text-2xl
                        ">
                        {{ $fullName }}
                    </h1>


                    <span
                        class="
                            inline-flex
                            items-center gap-1.5
                            border px-2 py-0.5
                            text-xs font-medium
                            {{ $subscriberStatus['class'] }}
                        ">

                        <i
                            data-lucide="{{ $subscriberStatus['icon'] }}"
                            class="h-3 w-3"
                            aria-hidden="true">
                        </i>

                        {{ $subscriberStatus['label'] }}

                    </span>

                </div>


                <p
                    class="
                        mt-1
                        text-xs font-semibold
                        tracking-wide
                        text-[#008080]
                        dark:text-[#5EEAD4]
                    ">
                    {{ $subscriber->customer_code }}
                </p>

            </div>


            <details class="relative shrink-0">

                <summary
                    aria-label="Subscriber options"
                    title="More options"
                    style="list-style: none;"
                    class="
                        inline-flex h-9 w-9
                        cursor-pointer
                        items-center justify-center
                        border border-gray-200
                        text-gray-500
                        transition
                        hover:bg-gray-50
                        hover:text-gray-900
                        focus:outline-none
                        focus:ring-2
                        focus:ring-[#008080]/20
                        dark:border-neutral-700
                        dark:text-gray-400
                        dark:hover:bg-neutral-800
                        dark:hover:text-white
                        [&::-webkit-details-marker]:hidden
                    ">

                    <span
                        class="text-xl font-semibold leading-none"
                        aria-hidden="true">
                        &#8942;
                    </span>

                </summary>


                <div
                    class="
                        absolute right-0 z-40
                        mt-1 w-48
                        border border-gray-200
                        bg-white
                        p-1
                        shadow-lg
                        dark:border-neutral-700
                        dark:bg-neutral-900
                    ">

                    <a
                        href="{{ route(
                            'admin.subscribers.edit',
                            $subscriber
                        ) }}"
                        class="
                            flex items-center gap-2.5
                            px-3 py-2
                            text-sm text-gray-700
                            transition
                            hover:bg-gray-50
                            dark:text-gray-200
                            dark:hover:bg-neutral-800
                        ">

                        <i
                            data-lucide="pencil"
                            class="h-4 w-4 shrink-0"
                            aria-hidden="true">
                        </i>

                        Edit Profile

                    </a>


                    @if (count($allowedStatusTransitions) > 0)

                    <button
                        type="button"
                        data-subscriber-status-open
                        class="
                            flex w-full
                            items-center gap-2.5
                            px-3 py-2
                            text-left
                            text-sm text-gray-700
                            transition
                            hover:bg-gray-50
                            dark:text-gray-200
                            dark:hover:bg-neutral-800
                        ">

                        <i
                            data-lucide="settings-2"
                            class="h-4 w-4 shrink-0"
                            aria-hidden="true">
                        </i>

                        Manage Status

                    </button>

                    @endif

                </div>

            </details>

        </div>

    </section>


    {{-- Subscriber lifecycle notice --}}
    @if ($subscriber->status === 'pending')

    <div
        class="
            flex items-start gap-2
            border border-amber-200
            bg-amber-50
            px-3 py-2.5
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
            Pending installation and service activation.
            Manual lifecycle changes are unavailable until activation.
        </p>

    </div>

    @elseif ($subscriber->status === 'disconnected')

    <div
        class="
            flex items-start gap-2
            border border-gray-200
            bg-gray-50
            px-3 py-2.5
            text-sm text-gray-700
            dark:border-neutral-800
            dark:bg-neutral-950
            dark:text-gray-300
        ">

        <i
            data-lucide="info"
            class="mt-0.5 h-4 w-4 shrink-0"
            aria-hidden="true">
        </i>

        <p>
            This subscriber is disconnected.
            Reconnection must use the proper service workflow.
        </p>

    </div>

    @endif


    {{-- Summary and primary workflow entry points --}}
    <section
        aria-label="Subscriber summary"
        class="
            grid gap-2
            sm:grid-cols-2
            xl:grid-cols-4
        ">

        {{-- Current plan --}}
        <div
            class="
                flex min-w-0 flex-col
                border border-gray-200
                bg-white
                px-3.5 py-3
                dark:border-neutral-800
                dark:bg-neutral-900
            ">

            <div
                class="
                    flex items-start
                    justify-between gap-3
                ">

                <div class="min-w-0">

                    <p
                        class="
                            text-[10px] font-semibold
                            uppercase tracking-[0.08em]
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Current Plan
                    </p>


                    <p
                        class="
                            mt-2 truncate
                            text-base font-semibold
                            text-gray-900
                            dark:text-white
                        ">
                        {{ $displayPlan?->name
                            ?? 'No plan' }}
                    </p>


                    @if ($displayPlan)

                    <p
                        class="
                            mt-0.5
                            text-xs text-gray-500
                            dark:text-gray-400
                        ">
                        {{ number_format(
                            (float) $displayPlan->speed_mbps,
                            0
                        ) }}
                        Mbps
                    </p>

                    @else

                    <p
                        class="
                            mt-0.5
                            text-xs text-gray-500
                            dark:text-gray-400
                        ">
                        Subscription unavailable
                    </p>

                    @endif

                </div>


                <i
                    data-lucide="cable"
                    class="
                        h-4 w-4 shrink-0
                        text-[#008080]
                        dark:text-[#5EEAD4]
                    "
                    aria-hidden="true">
                </i>

            </div>


            <button
                type="button"
                data-plan-change-open
                class="
                    mt-3 flex w-full
                    items-center justify-between
                    border-t border-gray-100
                    pt-2.5
                    text-left
                    text-xs font-semibold
                    text-[#008080]
                    transition
                    hover:text-[#006666]
                    focus:outline-none
                    focus:text-[#006666]
                    dark:border-neutral-800
                    dark:text-[#5EEAD4]
                    dark:hover:text-teal-300
                ">

                <span>
                    Change Plan
                </span>

                <i
                    data-lucide="arrow-right"
                    class="h-3.5 w-3.5"
                    aria-hidden="true">
                </i>

            </button>

        </div>


        {{-- Effective monthly fee --}}
        <div
            class="
                flex min-w-0 flex-col
                border border-gray-200
                bg-white
                px-3.5 py-3
                dark:border-neutral-800
                dark:bg-neutral-900
            ">

            <div
                class="
                    flex items-start
                    justify-between gap-3
                ">

                <div class="min-w-0">

                    <p
                        class="
                            text-[10px] font-semibold
                            uppercase tracking-[0.08em]
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Effective Fee
                    </p>


                    <p
                        class="
                            mt-2
                            text-base font-semibold
                            text-gray-900
                            dark:text-white
                        ">

                        @if ($displayPlan)

                        &#8369;{{ number_format(
                            $effectiveMonthlyFee,
                            2
                        ) }}

                        @else

                        Not available

                        @endif

                    </p>


                    @if (
                        $displayPlan &&
                        $promotionalDiscount > 0
                    )

                    <p
                        class="
                            mt-0.5
                            text-xs text-gray-500
                            dark:text-gray-400
                        ">
                        &#8369;{{ number_format(
                            $promotionalDiscount,
                            2
                        ) }}
                        discount
                    </p>

                    @else

                    <p
                        class="
                            mt-0.5
                            text-xs text-gray-500
                            dark:text-gray-400
                        ">
                        Monthly
                    </p>

                    @endif

                </div>


                <i
                    data-lucide="percent"
                    class="
                        h-4 w-4 shrink-0
                        text-[#008080]
                        dark:text-[#5EEAD4]
                    "
                    aria-hidden="true">
                </i>

            </div>


            @if ($latestSubscription)

            <button
                type="button"
                data-subscription-workspace-open
                class="
                    mt-3 flex w-full
                    items-center justify-between
                    border-t border-gray-100
                    pt-2.5
                    text-left
                    text-xs font-semibold
                    text-[#008080]
                    transition
                    hover:text-[#006666]
                    focus:outline-none
                    focus:text-[#006666]
                    dark:border-neutral-800
                    dark:text-[#5EEAD4]
                    dark:hover:text-teal-300
                ">

                <span>
                    Manage Subscription
                </span>

                <i
                    data-lucide="arrow-right"
                    class="h-3.5 w-3.5"
                    aria-hidden="true">
                </i>

            </button>

            @else

            <div
                class="
                    mt-3
                    border-t border-gray-100
                    pt-2.5
                    text-xs text-gray-400
                    dark:border-neutral-800
                    dark:text-gray-500
                ">
                No subscription to manage
            </div>

            @endif

        </div>


        {{-- Service address --}}
        <div
            class="
                flex min-w-0 flex-col
                border border-gray-200
                bg-white
                px-3.5 py-3
                dark:border-neutral-800
                dark:bg-neutral-900
            ">

            <div
                class="
                    flex items-start
                    justify-between gap-3
                ">

                <div class="min-w-0">

                    <p
                        class="
                            text-[10px] font-semibold
                            uppercase tracking-[0.08em]
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Service Address
                    </p>


                    <p
                        class="
                            mt-2 truncate
                            text-base font-semibold
                            text-gray-900
                            dark:text-white
                        "
                        title="{{ $displayInstallationAddress }}">
                        {{ $displayInstallationAddress
                            ?: 'Not recorded' }}
                    </p>


                    <p
                        class="
                            mt-0.5 truncate
                            text-xs text-gray-500
                            dark:text-gray-400
                        ">
                        {{ $location !== ''
                            ? $location
                            : 'Location unavailable' }}
                    </p>

                </div>


                <i
                    data-lucide="map-pin"
                    class="
                        h-4 w-4 shrink-0
                        text-[#008080]
                        dark:text-[#5EEAD4]
                    "
                    aria-hidden="true">
                </i>

            </div>


            <button
                type="button"
                data-relocation-open
                class="
                    mt-3 flex w-full
                    items-center justify-between
                    border-t border-gray-100
                    pt-2.5
                    text-left
                    text-xs font-semibold
                    text-[#008080]
                    transition
                    hover:text-[#006666]
                    focus:outline-none
                    focus:text-[#006666]
                    dark:border-neutral-800
                    dark:text-[#5EEAD4]
                    dark:hover:text-teal-300
                ">

                <span>
                    Manage Relocation
                </span>

                <i
                    data-lucide="arrow-right"
                    class="h-3.5 w-3.5"
                    aria-hidden="true">
                </i>

            </button>

        </div>


        {{-- Documents --}}
        <div
            class="
                flex min-w-0 flex-col
                border border-gray-200
                bg-white
                px-3.5 py-3
                dark:border-neutral-800
                dark:bg-neutral-900
            ">

            <div
                class="
                    flex items-start
                    justify-between gap-3
                ">

                <div class="min-w-0">

                    <p
                        class="
                            text-[10px] font-semibold
                            uppercase tracking-[0.08em]
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Documents
                    </p>


                    <p
                        class="
                            mt-2
                            text-base font-semibold
                            text-gray-900
                            dark:text-white
                        ">
                        {{ $subscriber->documents->count() }}
                    </p>


                    <p
                        class="
                            mt-0.5
                            text-xs text-gray-500
                            dark:text-gray-400
                        ">
                        {{ $subscriber->documents->count() === 1
                            ? 'document'
                            : 'documents' }}
                    </p>

                </div>


                <i
                    data-lucide="file-text"
                    class="
                        h-4 w-4 shrink-0
                        text-[#008080]
                        dark:text-[#5EEAD4]
                    "
                    aria-hidden="true">
                </i>

            </div>


            <button
                type="button"
                data-document-workspace-open
                class="
                    mt-3 flex w-full
                    items-center justify-between
                    border-t border-gray-100
                    pt-2.5
                    text-left
                    text-xs font-semibold
                    text-[#008080]
                    transition
                    hover:text-[#006666]
                    focus:outline-none
                    focus:text-[#006666]
                    dark:border-neutral-800
                    dark:text-[#5EEAD4]
                    dark:hover:text-teal-300
                ">

                <span>
                    Manage Documents
                </span>

                <i
                    data-lucide="arrow-right"
                    class="h-3.5 w-3.5"
                    aria-hidden="true">
                </i>

            </button>

        </div>

    </section>

    {{-- Primary workspace --}}
    <div class="space-y-2">

        {{-- Dense customer information row --}}
        <div
            class="
                grid items-start gap-2
                lg:grid-cols-[minmax(0,1.6fr)_minmax(280px,0.8fr)]
            ">

            {{-- Customer profile --}}
            <section
                id="customer-information"
                class="
                    border border-gray-200
                    bg-white
                    dark:border-neutral-800
                    dark:bg-neutral-900
                ">

                <div
                    class="
                        border-b border-gray-200
                        px-3 py-2.5
                        dark:border-neutral-800
                    ">

                    <h2
                        class="
                            text-sm font-semibold
                            text-gray-900
                            dark:text-white
                        ">
                        Customer Profile
                    </h2>

                </div>


                <dl
                    class="
                        grid gap-x-6
                        px-3 py-3
                        sm:grid-cols-2
                    ">

                    <div
                        class="
                            border-b border-gray-100
                            py-2
                            dark:border-neutral-800
                        ">

                        <dt
                            class="
                                text-[10px] font-semibold
                                uppercase tracking-wide
                                text-gray-500
                                dark:text-gray-400
                            ">
                            Full Name
                        </dt>

                        <dd
                            class="
                                mt-0.5
                                text-sm font-medium
                                text-gray-900
                                dark:text-white
                            ">
                            {{ $fullName }}
                        </dd>

                    </div>


                    <div
                        class="
                            border-b border-gray-100
                            py-2
                            dark:border-neutral-800
                        ">

                        <dt
                            class="
                                text-[10px] font-semibold
                                uppercase tracking-wide
                                text-gray-500
                                dark:text-gray-400
                            ">
                            Phone
                        </dt>

                        <dd
                            class="
                                mt-0.5
                                text-sm text-gray-700
                                dark:text-gray-300
                            ">
                            {{ $subscriber->phone ?: 'Not recorded' }}
                        </dd>

                    </div>


                    <div
                        class="
                            border-b border-gray-100
                            py-2
                            dark:border-neutral-800
                        ">

                        <dt
                            class="
                                text-[10px] font-semibold
                                uppercase tracking-wide
                                text-gray-500
                                dark:text-gray-400
                            ">
                            Email
                        </dt>

                        <dd
                            class="
                                mt-0.5 break-all
                                text-sm text-gray-700
                                dark:text-gray-300
                            ">
                            {{ $subscriber->email ?: 'Not recorded' }}
                        </dd>

                    </div>


                    <div
                        class="
                            border-b border-gray-100
                            py-2
                            dark:border-neutral-800
                        ">

                        <dt
                            class="
                                text-[10px] font-semibold
                                uppercase tracking-wide
                                text-gray-500
                                dark:text-gray-400
                            ">
                            Location
                        </dt>

                        <dd
                            class="
                                mt-0.5
                                text-sm text-gray-700
                                dark:text-gray-300
                            ">
                            {{ $location !== ''
                                ? $location
                                : 'Not specified' }}
                        </dd>

                    </div>


                    <div class="py-2">

                        <dt
                            class="
                                text-[10px] font-semibold
                                uppercase tracking-wide
                                text-gray-500
                                dark:text-gray-400
                            ">
                            Installation Address
                        </dt>

                        <dd
                            class="
                                mt-0.5
                                text-sm leading-5
                                text-gray-700
                                dark:text-gray-300
                            ">
                            {{ $displayInstallationAddress
                                ?: 'Not recorded' }}
                        </dd>

                    </div>


                    <div class="py-2">

                        <dt
                            class="
                                text-[10px] font-semibold
                                uppercase tracking-wide
                                text-gray-500
                                dark:text-gray-400
                            ">
                            Billing Address
                        </dt>

                        <dd
                            class="
                                mt-0.5
                                text-sm leading-5
                                text-gray-700
                                dark:text-gray-300
                            ">
                            {{ $subscriber->billing_address
                                ?: 'Not provided' }}
                        </dd>

                    </div>

                </dl>

            </section>


            {{-- Portal account --}}
            <section
                id="customer-account-status"
                class="
                    border border-gray-200
                    bg-white
                    dark:border-neutral-800
                    dark:bg-neutral-900
                ">

                <div
                    class="
                        border-b border-gray-200
                        px-3 py-2.5
                        dark:border-neutral-800
                    ">

                    <h2
                        class="
                            text-sm font-semibold
                            text-gray-900
                            dark:text-white
                        ">
                        Portal Account
                    </h2>

                </div>


                @if ($subscriber->user)

                <dl class="px-3 py-2">

                    <div
                        class="
                            border-b border-gray-100
                            py-2
                            dark:border-neutral-800
                        ">

                        <dt
                            class="
                                text-[10px] font-semibold
                                uppercase tracking-wide
                                text-gray-500
                                dark:text-gray-400
                            ">
                            Login Email
                        </dt>

                        <dd
                            class="
                                mt-0.5 break-all
                                text-sm text-gray-700
                                dark:text-gray-300
                            ">
                            {{ $subscriber->user->email }}
                        </dd>

                    </div>


                    <div
                        class="
                            border-b border-gray-100
                            py-2
                            dark:border-neutral-800
                        ">

                        <dt
                            class="
                                text-[10px] font-semibold
                                uppercase tracking-wide
                                text-gray-500
                                dark:text-gray-400
                            ">
                            Role
                        </dt>

                        <dd
                            class="
                                mt-0.5
                                text-sm text-gray-700
                                dark:text-gray-300
                            ">
                            {{ ucfirst($subscriber->user->role) }}
                        </dd>

                    </div>


                    <div
                        class="
                            border-b border-gray-100
                            py-2
                            dark:border-neutral-800
                        ">

                        <dt
                            class="
                                text-[10px] font-semibold
                                uppercase tracking-wide
                                text-gray-500
                                dark:text-gray-400
                            ">
                            Account Status
                        </dt>

                        <dd class="mt-1">

                            <span
                                class="
                                    inline-flex items-center
                                    gap-1.5 border
                                    px-2 py-0.5
                                    text-xs font-medium
                                    {{ $accountStatus['class'] }}
                                ">

                                <i
                                    data-lucide="{{ $accountStatus['icon'] }}"
                                    class="h-3 w-3"
                                    aria-hidden="true">
                                </i>

                                {{ $accountStatus['label'] }}

                            </span>

                        </dd>

                    </div>


                    <div class="py-2">

                        <dt
                            class="
                                text-[10px] font-semibold
                                uppercase tracking-wide
                                text-gray-500
                                dark:text-gray-400
                            ">
                            Account Created
                        </dt>

                        <dd
                            class="
                                mt-0.5
                                text-sm text-gray-700
                                dark:text-gray-300
                            ">
                            {{ $subscriber->user->created_at
                                ?->format('M d, Y')
                                ?? 'Not available' }}
                        </dd>

                    </div>

                </dl>

                @else

                <div class="px-3 py-4">

                    <p
                        class="
                            text-sm text-gray-500
                            dark:text-gray-400
                        ">
                        No linked portal account is available.
                    </p>

                </div>

                @endif

            </section>

        </div>

        {{-- Current subscription --}}
        <section
            class="
                border border-gray-200
                bg-white
                dark:border-neutral-800
                dark:bg-neutral-900
            ">

            <div
                class="
                    flex items-start
                    justify-between gap-4
                    border-b border-gray-200
                    px-3 py-2.5
                    dark:border-neutral-800
                ">

                <div class="min-w-0">

                    <h2
                        class="
                            text-sm font-semibold
                            text-gray-900
                            dark:text-white
                        ">
                        Current Subscription
                    </h2>

                    @if ($latestSubscription)

                    <div
                        class="
                            mt-1 flex flex-wrap
                            items-center gap-x-2 gap-y-1
                        ">

                        <span
                            class="
                                text-sm font-semibold
                                text-gray-900
                                dark:text-white
                            ">
                            {{ $displayPlan?->name
                                ?? 'Plan unavailable' }}
                        </span>

                        @if ($displayPlan)

                        <span
                            class="
                                text-xs text-gray-400
                                dark:text-gray-500
                            "
                            aria-hidden="true">
                            |
                        </span>

                        <span
                            class="
                                text-xs text-gray-500
                                dark:text-gray-400
                            ">
                            {{ number_format(
                                (float) $displayPlan->speed_mbps,
                                0
                            ) }}
                            Mbps
                        </span>

                        @endif

                    </div>

                    @endif

                </div>


                @if ($subscriptionStatus)

                <span
                    class="
                        inline-flex shrink-0
                        items-center gap-1.5
                        border px-2 py-0.5
                        text-xs font-medium
                        {{ $subscriptionStatus['class'] }}
                    ">

                    <i
                        data-lucide="{{ $subscriptionStatus['icon'] }}"
                        class="h-3 w-3"
                        aria-hidden="true">
                    </i>

                    {{ $subscriptionStatus['label'] }}

                </span>

                @endif

            </div>


            @if ($latestSubscription)

            <dl
                class="
                    grid
                    sm:grid-cols-2
                    xl:grid-cols-4
                ">

                {{-- Monthly fee --}}
                <div
                    class="
                        border-b border-gray-100
                        px-3 py-2.5
                        sm:border-r
                        dark:border-neutral-800
                    ">

                    <dt
                        class="
                            text-[10px] font-semibold
                            uppercase tracking-[0.08em]
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Monthly Fee
                    </dt>

                    <dd
                        class="
                            mt-1
                            text-sm font-medium
                            text-gray-900
                            dark:text-white
                        ">

                        @if ($displayPlan)

                        &#8369;{{ number_format(
                            $baseMonthlyFee,
                            2
                        ) }}

                        @else

                        Not available

                        @endif

                    </dd>

                </div>


                {{-- Discount --}}
                <div
                    class="
                        border-b border-gray-100
                        px-3 py-2.5
                        xl:border-r
                        dark:border-neutral-800
                    ">

                    <dt
                        class="
                            text-[10px] font-semibold
                            uppercase tracking-[0.08em]
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Discount
                    </dt>

                    <dd
                        class="
                            mt-1
                            text-sm text-gray-700
                            dark:text-gray-300
                        ">
                        &#8369;{{ number_format(
                            $promotionalDiscount,
                            2
                        ) }}
                    </dd>

                </div>


                {{-- Effective fee --}}
                <div
                    class="
                        border-b border-gray-100
                        px-3 py-2.5
                        sm:border-r
                        dark:border-neutral-800
                    ">

                    <dt
                        class="
                            text-[10px] font-semibold
                            uppercase tracking-[0.08em]
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Effective Fee
                    </dt>

                    <dd
                        class="
                            mt-1
                            text-sm font-semibold
                            text-[#008080]
                            dark:text-[#5EEAD4]
                        ">
                        &#8369;{{ number_format(
                            $effectiveMonthlyFee,
                            2
                        ) }}
                    </dd>

                </div>


                {{-- Lock-in period --}}
                <div
                    class="
                        border-b border-gray-100
                        px-3 py-2.5
                        dark:border-neutral-800
                    ">

                    <dt
                        class="
                            text-[10px] font-semibold
                            uppercase tracking-[0.08em]
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Lock-in
                    </dt>

                    <dd
                        class="
                            mt-1
                            text-sm text-gray-700
                            dark:text-gray-300
                        ">

                        @if ($latestSubscription->lock_in_months)

                        {{ $latestSubscription->lock_in_months }}
                        {{ $latestSubscription->lock_in_months === 1
                            ? 'month'
                            : 'months' }}

                        @else

                        Not set

                        @endif

                    </dd>

                </div>


                {{-- Start date --}}
                <div
                    class="
                        px-3 py-2.5
                        sm:border-r
                        dark:border-neutral-800
                    ">

                    <dt
                        class="
                            text-[10px] font-semibold
                            uppercase tracking-[0.08em]
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Start Date
                    </dt>

                    <dd
                        class="
                            mt-1
                            text-sm text-gray-700
                            dark:text-gray-300
                        ">
                        {{ $latestSubscription->start_date
                            ?->format('M d, Y')
                            ?? 'Not started' }}
                    </dd>

                </div>


                {{-- End date --}}
                <div
                    class="
                        px-3 py-2.5
                        xl:border-r
                        dark:border-neutral-800
                    ">

                    <dt
                        class="
                            text-[10px] font-semibold
                            uppercase tracking-[0.08em]
                            text-gray-500
                            dark:text-gray-400
                        ">
                        End Date
                    </dt>

                    <dd
                        class="
                            mt-1
                            text-sm text-gray-700
                            dark:text-gray-300
                        ">
                        {{ $latestSubscription->end_date
                            ?->format('M d, Y')
                            ?? 'Not set' }}
                    </dd>

                </div>


                {{-- Lock-in status --}}
                <div
                    class="
                        px-3 py-2.5
                        sm:border-r
                        dark:border-neutral-800
                    ">

                    <dt
                        class="
                            text-[10px] font-semibold
                            uppercase tracking-[0.08em]
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Lock-in Status
                    </dt>

                    <dd class="mt-1">

                        <span
                            class="
                                inline-flex
                                border px-2 py-0.5
                                text-xs font-medium
                                {{ $lockInStatusMeta['class'] }}
                            ">
                            {{ $lockInStatusMeta['label'] }}
                        </span>

                    </dd>

                </div>


                {{-- Remaining --}}
                <div class="px-3 py-2.5">

                    <dt
                        class="
                            text-[10px] font-semibold
                            uppercase tracking-[0.08em]
                            text-gray-500
                            dark:text-gray-400
                        ">

                        @if ($lockInStatus === 'completed')
                            Completed
                        @else
                            Remaining
                        @endif

                    </dt>

                    <dd
                        class="
                            mt-1
                            text-sm text-gray-700
                            dark:text-gray-300
                        ">

                        @if ($lockInStatus === 'active')

                        {{ number_format(
                            $remainingLockInDays
                        ) }}
                        {{ $remainingLockInDays === 1
                            ? 'day'
                            : 'days' }}

                        @elseif ($lockInStatus === 'completed')

                        {{ $latestSubscription->end_date
                            ?->format('M d, Y')
                            ?? 'Completed' }}

                        @else

                        Not available

                        @endif

                    </dd>

                </div>

            </dl>

            @else

            <div
                class="
                    px-3 py-4
                    text-sm text-gray-500
                    dark:text-gray-400
                ">
                No subscription found.
            </div>

            @endif

        </section>

    </div>

    {{-- Subscription management workspace --}}
    @if ($latestSubscription)

    <div
        id="subscription-workspace-modal"
        data-subscription-modal
        data-open-on-error="{{ $hasSubscriptionErrors ? 'true' : 'false' }}"
        class="
            fixed inset-0 z-50
            hidden items-center justify-center
            p-3 sm:p-4
        "
        aria-hidden="true"
        role="dialog"
        aria-modal="true"
        aria-labelledby="subscription-workspace-title">

        <div
            data-subscription-workspace-overlay
            class="
                absolute inset-0
                bg-black/50
            ">
        </div>


        <div
            class="
                relative z-10
                max-h-[calc(100vh-1.5rem)]
                w-full max-w-2xl
                overflow-y-auto
                border border-gray-200
                bg-white
                shadow-lg
                dark:border-neutral-800
                dark:bg-neutral-900
            ">

            {{-- Modal header --}}
            <div
                class="
                    sticky top-0 z-10
                    flex items-start
                    justify-between gap-4
                    border-b border-gray-200
                    bg-white
                    px-4 py-3
                    dark:border-neutral-800
                    dark:bg-neutral-900
                    sm:px-5
                ">

                <div>

                    <h2
                        id="subscription-workspace-title"
                        class="
                            text-base font-semibold
                            text-gray-900
                            dark:text-white
                        ">
                        Manage Subscription
                    </h2>

                    <p
                        class="
                            mt-0.5
                            text-xs text-gray-500
                            dark:text-gray-400
                        ">
                        {{ $fullName }}
                        &middot;
                        {{ $subscriber->customer_code }}
                    </p>

                </div>


                <button
                    type="button"
                    data-subscription-workspace-close
                    aria-label="Close subscription"
                    title="Close"
                    class="
                        inline-flex h-8 w-8
                        items-center justify-center
                        border border-gray-200
                        text-gray-500
                        transition
                        hover:bg-gray-50
                        hover:text-gray-900
                        dark:border-neutral-700
                        dark:text-gray-400
                        dark:hover:bg-neutral-800
                        dark:hover:text-white
                    ">

                    <i
                        data-lucide="x"
                        class="h-4 w-4"
                        aria-hidden="true">
                    </i>

                </button>

            </div>


            <div class="p-4 sm:p-5">

                {{-- Subscription identity --}}
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
                                text-xs font-medium
                                uppercase tracking-wide
                                text-gray-400
                            ">
                            Subscription
                        </p>

                        <p
                            class="
                                mt-1
                                text-lg font-semibold
                                text-gray-900
                                dark:text-white
                            ">
                            {{ $displayPlan?->name
                                ?? 'Plan unavailable' }}
                        </p>

                        @if ($displayPlan)

                        <p
                            class="
                                mt-1
                                text-sm text-gray-500
                                dark:text-gray-400
                            ">
                            {{ number_format(
                                (float) $displayPlan->speed_mbps,
                                0
                            ) }}
                            Mbps
                            &middot;
                            &#8369;{{ number_format(
                                $baseMonthlyFee,
                                2
                            ) }}/month
                        </p>

                        @endif

                    </div>


                    @if ($subscriptionStatus)

                    <span
                        class="
                            inline-flex w-fit
                            items-center gap-1.5
                            border px-2 py-1
                            text-xs font-medium
                            {{ $subscriptionStatus['class'] }}
                        ">

                        <i
                            data-lucide="{{ $subscriptionStatus['icon'] }}"
                            class="h-3 w-3"
                            aria-hidden="true">
                        </i>

                        {{ $subscriptionStatus['label'] }}

                    </span>

                    @endif

                </div>


                {{-- Details --}}
                <dl
                    class="
                        mt-5 grid
                        gap-x-6 gap-y-3
                        border-y border-gray-200
                        py-4
                        sm:grid-cols-2
                        dark:border-neutral-800
                    ">

                    <div>

                        <dt
                            class="
                                text-xs text-gray-500
                                dark:text-gray-400
                            ">
                            Plan Type
                        </dt>

                        <dd
                            class="
                                mt-1
                                text-sm text-gray-800
                                dark:text-gray-200
                            ">
                            {{ $latestSubscription->is_custom_plan
                                ? 'Custom Plan'
                                : 'Standard Plan' }}
                        </dd>

                    </div>


                    <div>

                        <dt
                            class="
                                text-xs text-gray-500
                                dark:text-gray-400
                            ">
                            Promotional Discount
                        </dt>

                        <dd
                            class="
                                mt-1
                                text-sm text-gray-800
                                dark:text-gray-200
                            ">
                            &#8369;{{ number_format(
                                $promotionalDiscount,
                                2
                            ) }}
                        </dd>

                    </div>


                    <div>

                        <dt
                            class="
                                text-xs text-gray-500
                                dark:text-gray-400
                            ">
                            Effective Monthly Fee
                        </dt>

                        <dd
                            class="
                                mt-1
                                text-sm font-semibold
                                text-[#008080]
                                dark:text-[#5EEAD4]
                            ">
                            &#8369;{{ number_format(
                                $effectiveMonthlyFee,
                                2
                            ) }}
                        </dd>

                    </div>


                    <div>

                        <dt
                            class="
                                text-xs text-gray-500
                                dark:text-gray-400
                            ">
                            Lock-in Status
                        </dt>

                        <dd class="mt-1">

                            <span
                                class="
                                    inline-flex
                                    border px-2 py-0.5
                                    text-xs font-medium
                                    {{ $lockInStatusMeta['class'] }}
                                ">
                                {{ $lockInStatusMeta['label'] }}
                            </span>

                        </dd>

                    </div>


                    <div>

                        <dt
                            class="
                                text-xs text-gray-500
                                dark:text-gray-400
                            ">
                            Start Date
                        </dt>

                        <dd
                            class="
                                mt-1
                                text-sm text-gray-800
                                dark:text-gray-200
                            ">
                            {{ $latestSubscription->start_date
                                ?->format('M d, Y')
                                ?? 'Not started' }}
                        </dd>

                    </div>


                    <div>

                        <dt
                            class="
                                text-xs text-gray-500
                                dark:text-gray-400
                            ">
                            End Date
                        </dt>

                        <dd
                            class="
                                mt-1
                                text-sm text-gray-800
                                dark:text-gray-200
                            ">
                            {{ $latestSubscription->end_date
                                ?->format('M d, Y')
                                ?? 'Not set' }}
                        </dd>

                    </div>


                    <div>

                        <dt
                            class="
                                text-xs text-gray-500
                                dark:text-gray-400
                            ">
                            Lock-in Period
                        </dt>

                        <dd
                            class="
                                mt-1
                                text-sm text-gray-800
                                dark:text-gray-200
                            ">

                            @if ($latestSubscription->lock_in_months)

                            {{ $latestSubscription->lock_in_months }}
                            {{ $latestSubscription->lock_in_months === 1
                                ? 'month'
                                : 'months' }}

                            @else

                            Not set

                            @endif

                        </dd>

                    </div>


                    @if ($lockInStatus === 'active')

                    <div>

                        <dt
                            class="
                                text-xs text-gray-500
                                dark:text-gray-400
                            ">
                            Remaining
                        </dt>

                        <dd
                            class="
                                mt-1
                                text-sm text-gray-800
                                dark:text-gray-200
                            ">
                            {{ number_format(
                                $remainingLockInDays
                            ) }}
                            {{ $remainingLockInDays === 1
                                ? 'day'
                                : 'days' }}
                        </dd>

                    </div>

                    @endif

                </dl>


                {{-- Promotional discount management --}}
                @if ($latestSubscription->status === 'active')

                <section
                    class="
                        mt-5
                        border border-gray-200
                        dark:border-neutral-800
                    ">

                    <div
                        class="
                            border-b border-gray-200
                            px-4 py-3
                            dark:border-neutral-800
                        ">

                        <h3
                            class="
                                text-sm font-semibold
                                text-gray-900
                                dark:text-white
                            ">
                            Promotional Discount
                        </h3>

                    </div>


                    <form
                        method="POST"
                        action="{{ route(
                            'admin.subscribers.subscriptions.discount',
                            [$subscriber, $latestSubscription]
                        ) }}"
                        data-lock-submit
                        class="p-4">

                        @csrf
                        @method('PATCH')


                        <div
                            class="
                                grid gap-4
                                sm:grid-cols-2
                            ">

                            <div>

                                <label
                                    for="subscription_discount_amount"
                                    class="
                                        block
                                        text-xs font-medium
                                        text-gray-700
                                        dark:text-gray-300
                                    ">
                                    Discount Amount
                                </label>


                                <div class="relative mt-1.5">

                                    <span
                                        class="
                                            pointer-events-none
                                            absolute inset-y-0 left-3
                                            flex items-center
                                            text-sm text-gray-500
                                            dark:text-gray-400
                                        ">
                                        &#8369;
                                    </span>


                                    <input
                                        id="subscription_discount_amount"
                                        name="discount_amount"
                                        type="number"
                                        required
                                        min="0"
                                        max="{{ number_format(
                                            $baseMonthlyFee,
                                            2,
                                            '.',
                                            ''
                                        ) }}"
                                        step="0.01"
                                        value="{{ old(
                                            'discount_amount',
                                            number_format(
                                                $promotionalDiscount,
                                                2,
                                                '.',
                                                ''
                                            )
                                        ) }}"
                                        class="
                                            block min-h-10 w-full
                                            border border-gray-300
                                            bg-white
                                            py-2 pl-8 pr-3
                                            text-sm text-gray-900
                                            outline-none transition
                                            focus:border-[#008080]
                                            focus:ring-2
                                            focus:ring-[#008080]/20
                                            dark:border-neutral-700
                                            dark:bg-neutral-950
                                            dark:text-white
                                        ">

                                </div>


                                @error('discount_amount')

                                <p
                                    class="
                                        mt-1
                                        text-xs text-red-600
                                        dark:text-red-400
                                    ">
                                    {{ $message }}
                                </p>

                                @enderror

                            </div>


                            <div>

                                <label
                                    for="subscription_discount_reason"
                                    class="
                                        block
                                        text-xs font-medium
                                        text-gray-700
                                        dark:text-gray-300
                                    ">
                                    Reason
                                </label>


                                <textarea
                                    id="subscription_discount_reason"
                                    name="reason"
                                    rows="2"
                                    required
                                    maxlength="1000"
                                    placeholder="Reason for the promotional discount."
                                    class="
                                        mt-1.5 block w-full
                                        resize-none
                                        border border-gray-300
                                        bg-white
                                        px-3 py-2
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
                                    ">{{ old('reason') }}</textarea>


                                @if (
                                    $hasSubscriptionErrors &&
                                    $errors->has('reason')
                                )

                                <p
                                    class="
                                        mt-1
                                        text-xs text-red-600
                                        dark:text-red-400
                                    ">
                                    {{ $errors->first('reason') }}
                                </p>

                                @endif

                            </div>

                        </div>


                        <div
                            class="
                                mt-4 flex
                                justify-end
                            ">

                            <button
                                type="submit"
                                data-loading-text="Updating..."
                                class="
                                    inline-flex min-h-10
                                    items-center justify-center
                                    gap-2
                                    bg-[#008080]
                                    px-4 py-2
                                    text-sm font-medium
                                    text-white
                                    transition
                                    hover:bg-[#006666]
                                ">

                                <i
                                    data-lucide="percent"
                                    class="h-4 w-4"
                                    aria-hidden="true">
                                </i>

                                Update Discount

                            </button>

                        </div>

                    </form>

                </section>

                @endif


                {{-- Pending subscription activation --}}
                @if (
                    $latestSubscription->status === 'pending' &&
                    $subscriber->status === 'pending'
                )

                <section
                    class="
                        mt-5
                        border border-gray-200
                        dark:border-neutral-800
                    ">

                    <div
                        class="
                            border-b border-gray-200
                            px-4 py-3
                            dark:border-neutral-800
                        ">

                        <h3
                            class="
                                text-sm font-semibold
                                text-gray-900
                                dark:text-white
                            ">
                            Activate Subscription
                        </h3>

                    </div>


                    <form
                        method="POST"
                        action="{{ route(
                            'admin.subscribers.subscriptions.activate',
                            [$subscriber, $latestSubscription]
                        ) }}"
                        data-lock-submit
                        class="p-4">

                        @csrf
                        @method('PATCH')


                        <div
                            class="
                                grid gap-4
                                sm:grid-cols-2
                            ">

                            <div>

                                <label
                                    for="subscription_start_date"
                                    class="
                                        block
                                        text-xs font-medium
                                        text-gray-700
                                        dark:text-gray-300
                                    ">
                                    Start Date
                                </label>


                                <input
                                    id="subscription_start_date"
                                    name="start_date"
                                    type="date"
                                    required
                                    value="{{ old(
                                        'start_date',
                                        now()->format('Y-m-d')
                                    ) }}"
                                    class="
                                        mt-1.5 block
                                        min-h-10 w-full
                                        border border-gray-300
                                        bg-white
                                        px-3 py-2
                                        text-sm text-gray-900
                                        outline-none
                                        focus:border-[#008080]
                                        focus:ring-2
                                        focus:ring-[#008080]/20
                                        dark:border-neutral-700
                                        dark:bg-neutral-950
                                        dark:text-white
                                    ">


                                @error('start_date')

                                <p
                                    class="
                                        mt-1
                                        text-xs text-red-600
                                        dark:text-red-400
                                    ">
                                    {{ $message }}
                                </p>

                                @enderror

                            </div>


                            <div>

                                <label
                                    for="subscription_lock_in_months"
                                    class="
                                        block
                                        text-xs font-medium
                                        text-gray-700
                                        dark:text-gray-300
                                    ">
                                    Lock-in Period
                                </label>


                                <div class="relative mt-1.5">

                                    <input
                                        id="subscription_lock_in_months"
                                        name="lock_in_months"
                                        type="number"
                                        required
                                        min="1"
                                        max="120"
                                        step="1"
                                        value="{{ old(
                                            'lock_in_months',
                                            $latestSubscription->lock_in_months
                                                ?: 12
                                        ) }}"
                                        class="
                                            block min-h-10 w-full
                                            border border-gray-300
                                            bg-white
                                            px-3 py-2 pr-20
                                            text-sm text-gray-900
                                            outline-none
                                            focus:border-[#008080]
                                            focus:ring-2
                                            focus:ring-[#008080]/20
                                            dark:border-neutral-700
                                            dark:bg-neutral-950
                                            dark:text-white
                                        ">


                                    <span
                                        class="
                                            pointer-events-none
                                            absolute inset-y-0 right-3
                                            flex items-center
                                            text-xs text-gray-500
                                            dark:text-gray-400
                                        ">
                                        months
                                    </span>

                                </div>


                                @error('lock_in_months')

                                <p
                                    class="
                                        mt-1
                                        text-xs text-red-600
                                        dark:text-red-400
                                    ">
                                    {{ $message }}
                                </p>

                                @enderror

                            </div>

                        </div>


                        <div class="mt-4">

                            <label
                                for="subscription_activation_reason"
                                class="
                                    block
                                    text-xs font-medium
                                    text-gray-700
                                    dark:text-gray-300
                                ">
                                Activation Reason
                            </label>


                            <textarea
                                id="subscription_activation_reason"
                                name="reason"
                                rows="2"
                                required
                                maxlength="1000"
                                placeholder="Reason for service activation."
                                class="
                                    mt-1.5 block w-full
                                    resize-none
                                    border border-gray-300
                                    bg-white
                                    px-3 py-2
                                    text-sm text-gray-900
                                    outline-none
                                    placeholder:text-gray-400
                                    focus:border-[#008080]
                                    focus:ring-2
                                    focus:ring-[#008080]/20
                                    dark:border-neutral-700
                                    dark:bg-neutral-950
                                    dark:text-white
                                    dark:placeholder:text-gray-500
                                ">{{ old('reason') }}</textarea>


                            @if (
                                $hasSubscriptionErrors &&
                                $errors->has('reason')
                            )

                            <p
                                class="
                                    mt-1
                                    text-xs text-red-600
                                    dark:text-red-400
                                ">
                                {{ $errors->first('reason') }}
                            </p>

                            @endif

                        </div>


                        <div
                            class="
                                mt-4 flex
                                justify-end
                            ">

                            <button
                                type="submit"
                                data-loading-text="Activating..."
                                class="
                                    inline-flex min-h-10
                                    items-center justify-center
                                    gap-2
                                    bg-[#008080]
                                    px-4 py-2
                                    text-sm font-medium
                                    text-white
                                    transition
                                    hover:bg-[#006666]
                                ">

                                <i
                                    data-lucide="circle-check"
                                    class="h-4 w-4"
                                    aria-hidden="true">
                                </i>

                                Activate Subscription

                            </button>

                        </div>

                    </form>

                </section>

                @endif

            </div>

        </div>

    </div>


    {{-- Subscription workspace behavior --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal =
                document.getElementById(
                    'subscription-workspace-modal'
                );

            const openButton =
                document.querySelector(
                    '[data-subscription-workspace-open]'
                );

            const closeButton =
                document.querySelector(
                    '[data-subscription-workspace-close]'
                );

            const overlay =
                document.querySelector(
                    '[data-subscription-workspace-overlay]'
                );


            const openModal = () => {
                if (!modal) {
                    return;
                }

                modal.classList.remove('hidden');
                modal.classList.add('flex');

                modal.setAttribute(
                    'aria-hidden',
                    'false'
                );

                document.body.classList.add(
                    'overflow-hidden'
                );
            };


            const closeModal = () => {
                if (!modal) {
                    return;
                }

                modal.classList.add('hidden');
                modal.classList.remove('flex');

                modal.setAttribute(
                    'aria-hidden',
                    'true'
                );

                document.body.classList.remove(
                    'overflow-hidden'
                );

                openButton?.focus();
            };


            openButton?.addEventListener(
                'click',
                openModal
            );

            closeButton?.addEventListener(
                'click',
                closeModal
            );

            overlay?.addEventListener(
                'click',
                closeModal
            );


            if (
                modal?.dataset.openOnError ===
                'true'
            ) {
                openModal();
            }


            document.addEventListener(
                'keydown',
                (event) => {
                    if (
                        event.key === 'Escape' &&
                        modal?.getAttribute(
                            'aria-hidden'
                        ) === 'false'
                    ) {
                        closeModal();
                    }
                }
            );
        });
    </script>

    @endif


    {{-- Keep existing Plan Change and Relocation workflows --}}
    @include('admin.subscribers.partials.plan-change')
    @include('admin.subscribers.partials.relocation')

</div>

{{-- Customer documents workspace --}}
<div
    id="customer-document-workspace-modal"
    data-document-workspace-modal
    data-open-on-load="{{
        request('feature') === 'customer-documents' &&
        ! $hasDocumentErrors
            ? 'true'
            : 'false'
    }}"
    class="
        fixed inset-0 z-50
        hidden items-center justify-center
        p-3 sm:p-4
    "
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
    aria-labelledby="customer-document-workspace-title">

    <div
        data-document-workspace-overlay
        class="
            absolute inset-0
            bg-black/50
            backdrop-blur-[1px]
        ">
    </div>


    <div
        class="
            relative z-10
            flex
            max-h-[calc(100vh-1.5rem)]
            w-full max-w-2xl
            flex-col
            overflow-hidden
            rounded-[2px]
            border border-gray-200
            bg-white
            shadow-lg
            dark:border-neutral-800
            dark:bg-neutral-900
            sm:max-h-[calc(100vh-2rem)]
        ">

        {{-- Header --}}
        <div
            class="
                flex shrink-0
                items-center justify-between
                gap-3
                border-b border-gray-100
                px-4 py-4
                dark:border-neutral-800
                sm:px-5
            ">

            <div
                class="
                    flex min-w-0
                    items-center gap-3
                ">

                <div
                    class="
                        flex h-9 w-9 shrink-0
                        items-center justify-center
                        rounded-[2px]
                        bg-[#008080]/10
                        text-[#008080]
                        dark:bg-[#008080]/20
                        dark:text-[#5EEAD4]
                    ">

                    <i
                        data-lucide="file-text"
                        class="h-4 w-4"
                        aria-hidden="true">
                    </i>

                </div>


                <div class="min-w-0">

                    <h2
                        id="customer-document-workspace-title"
                        class="
                            text-base font-semibold
                            text-gray-900
                            dark:text-white
                        ">
                        Customer Documents
                    </h2>

                    <p
                        class="
                            mt-0.5 truncate
                            text-xs text-gray-500
                            dark:text-gray-400
                        ">
                        {{ $fullName }}
                        &middot;
                        {{ $subscriber->customer_code }}
                    </p>

                </div>

            </div>


            <div
                class="
                    flex shrink-0
                    items-center gap-1
                ">

                {{-- One primary document action --}}
                <button
                    type="button"
                    data-document-upload-open
                    aria-label="Upload document"
                    title="Upload document"
                    class="
                        inline-flex h-9 w-9
                        items-center justify-center
                        rounded-[2px]
                        bg-[#008080]
                        text-white
                        transition
                        hover:bg-[#006666]
                        focus:outline-none
                        focus:ring-2
                        focus:ring-[#008080]/30
                    ">

                    <i
                        data-lucide="plus"
                        class="h-4 w-4"
                        aria-hidden="true">
                    </i>

                </button>


                <button
                    type="button"
                    data-document-workspace-close
                    aria-label="Close customer documents"
                    title="Close"
                    class="
                        inline-flex h-9 w-9
                        items-center justify-center
                        rounded-[2px]
                        text-gray-500
                        transition
                        hover:bg-gray-100
                        hover:text-gray-900
                        dark:text-gray-400
                        dark:hover:bg-neutral-800
                        dark:hover:text-white
                    ">

                    <i
                        data-lucide="x"
                        class="h-4 w-4"
                        aria-hidden="true">
                    </i>

                </button>

            </div>

        </div>


        {{-- Documents --}}
        <div
            class="
                min-h-0 flex-1
                overflow-y-auto
                p-4 sm:p-5
            ">

            @if ($subscriber->documents->isEmpty())

            <div
                class="
                    flex min-h-48
                    flex-col
                    items-center justify-center
                    text-center
                ">

                <div
                    class="
                        flex h-11 w-11
                        items-center justify-center
                        rounded-[2px]
                        bg-gray-100
                        text-gray-400
                        dark:bg-neutral-800
                        dark:text-gray-500
                    ">

                    <i
                        data-lucide="file-text"
                        class="h-5 w-5"
                        aria-hidden="true">
                    </i>

                </div>


                <p
                    class="
                        mt-3
                        text-sm font-medium
                        text-gray-700
                        dark:text-gray-300
                    ">
                    No documents yet
                </p>

            </div>

            @else

            <div class="space-y-2">

                @foreach ($subscriber->documents as $document)

                @php
                $fileIcon = str_starts_with(
                    (string) $document->mime_type,
                    'image/'
                )
                    ? 'image'
                    : 'file-text';

                $fileSize =
                    $document->file_size >= 1048576
                        ? number_format(
                            $document->file_size / 1048576,
                            2
                        ) . ' MB'
                        : number_format(
                            $document->file_size / 1024,
                            1
                        ) . ' KB';
                @endphp


                <article
                    class="
                        rounded-[2px]
                        border border-gray-200
                        bg-white
                        p-3
                        dark:border-neutral-800
                        dark:bg-neutral-900
                    ">

                    <div
                        class="
                            flex items-start
                            gap-3
                        ">

                        <div
                            class="
                                flex h-9 w-9 shrink-0
                                items-center justify-center
                                rounded-[2px]
                                bg-[#008080]/10
                                text-[#008080]
                                dark:bg-[#008080]/20
                                dark:text-[#5EEAD4]
                            ">

                            <i
                                data-lucide="{{ $fileIcon }}"
                                class="h-4 w-4"
                                aria-hidden="true">
                            </i>

                        </div>


                        <div class="min-w-0 flex-1">

                            <p
                                class="
                                    truncate
                                    text-sm font-semibold
                                    text-gray-900
                                    dark:text-white
                                "
                                title="{{ $document->document_type }}">
                                {{ $document->document_type }}
                            </p>


                            <p
                                class="
                                    mt-0.5 truncate
                                    text-xs text-gray-500
                                    dark:text-gray-400
                                "
                                title="{{ $document->original_name }}">
                                {{ $document->original_name }}
                            </p>


                            <p
                                class="
                                    mt-1
                                    text-[11px] text-gray-400
                                    dark:text-gray-500
                                ">
                                {{ $fileSize }}

                                &middot;

                                {{ $document->created_at
                                    ?->format('M d, Y')
                                    ?? 'Unavailable' }}

                                @if ($document->uploader)

                                &middot;

                                {{ $document->uploader->name }}

                                @endif
                            </p>


                            @if ($document->notes)

                            <p
                                class="
                                    mt-2 truncate
                                    text-xs text-gray-500
                                    dark:text-gray-400
                                "
                                title="{{ $document->notes }}">
                                {{ $document->notes }}
                            </p>

                            @endif

                        </div>


                        {{-- One actions menu --}}
                        <details class="relative shrink-0">

                            <summary
                                aria-label="Document options"
                                title="More options"
                                style="list-style: none;"
                                class="
                                    inline-flex h-8 w-8
                                    cursor-pointer
                                    items-center justify-center
                                    rounded-[2px]
                                    text-gray-400
                                    transition
                                    hover:bg-gray-100
                                    hover:text-gray-800
                                    dark:hover:bg-neutral-800
                                    dark:hover:text-gray-200
                                    [&::-webkit-details-marker]:hidden
                                ">

                                <i
                                    data-lucide="ellipsis-vertical"
                                    class="h-4 w-4"
                                    aria-hidden="true">
                                </i>

                            </summary>


                            <div
                                class="
                                    absolute right-0 z-30
                                    mt-2 w-40
                                    overflow-hidden
                                    rounded-[2px]
                                    border border-gray-200
                                    bg-white
                                    p-1.5
                                    shadow-lg
                                    dark:border-neutral-700
                                    dark:bg-neutral-900
                                ">

                                <a
                                    href="{{ route(
                                        'admin.subscribers.documents.show',
                                        [$subscriber, $document]
                                    ) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="
                                        flex items-center gap-2
                                        rounded-[2px]
                                        px-3 py-2
                                        text-sm text-gray-700
                                        transition
                                        hover:bg-gray-50
                                        dark:text-gray-200
                                        dark:hover:bg-neutral-800
                                    ">

                                    <i
                                        data-lucide="eye"
                                        class="h-4 w-4"
                                        aria-hidden="true">
                                    </i>

                                    View

                                </a>


                                <a
                                    href="{{ route(
                                        'admin.subscribers.documents.download',
                                        [$subscriber, $document]
                                    ) }}"
                                    class="
                                        flex items-center gap-2
                                        rounded-[2px]
                                        px-3 py-2
                                        text-sm text-gray-700
                                        transition
                                        hover:bg-gray-50
                                        dark:text-gray-200
                                        dark:hover:bg-neutral-800
                                    ">

                                    <i
                                        data-lucide="download"
                                        class="h-4 w-4"
                                        aria-hidden="true">
                                    </i>

                                    Download

                                </a>


                                <div
                                    class="
                                        my-1
                                        border-t border-gray-100
                                        dark:border-neutral-800
                                    ">
                                </div>


                                <button
                                    type="button"
                                    data-document-delete
                                    data-document-delete-url="{{ route(
                                        'admin.subscribers.documents.destroy',
                                        [$subscriber, $document]
                                    ) }}"
                                    data-document-delete-name="{{ $document->original_name }}"
                                    data-document-delete-type="{{ $document->document_type }}"
                                    class="
                                        flex w-full
                                        items-center gap-2
                                        rounded-[2px]
                                        px-3 py-2
                                        text-left
                                        text-sm text-red-600
                                        transition
                                        hover:bg-red-50
                                        dark:text-red-400
                                        dark:hover:bg-red-950/30
                                    ">

                                    <i
                                        data-lucide="trash-2"
                                        class="h-4 w-4"
                                        aria-hidden="true">
                                    </i>

                                    Delete

                                </button>

                            </div>

                        </details>

                    </div>

                </article>

                @endforeach

            </div>

            @endif

        </div>

    </div>

</div>


{{-- Upload customer document modal --}}
<div
    id="customer-document-upload-modal"
    class="
        fixed inset-0 z-[60]
        hidden items-center justify-center
        p-3 sm:p-4
    "
    data-open-on-error="{{ $hasDocumentErrors ? 'true' : 'false' }}"
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
    aria-labelledby="customer-document-upload-title">

    <div
        data-document-upload-overlay
        class="absolute inset-0 bg-black/50">
    </div>


    <div
        class="
            relative z-10
            max-h-[calc(100vh-1.5rem)]
            w-full max-w-lg
            overflow-y-auto
            rounded-[2px]
            border border-gray-200
            bg-white
            p-5
            shadow-lg
            dark:border-neutral-800
            dark:bg-neutral-900
        ">

        <div
            class="
                flex items-center gap-3
            ">

            <div
                class="
                    flex h-9 w-9 shrink-0
                    items-center justify-center
                    rounded-[2px]
                    bg-[#008080]/10
                    text-[#008080]
                    dark:bg-[#008080]/20
                    dark:text-[#5EEAD4]
                ">

                <i
                    data-lucide="upload"
                    class="h-4 w-4"
                    aria-hidden="true">
                </i>

            </div>


            <div>

                <h2
                    id="customer-document-upload-title"
                    class="
                        text-lg font-semibold
                        text-gray-900
                        dark:text-white
                    ">
                    Upload Document
                </h2>

                <p
                    class="
                        mt-0.5
                        text-xs text-gray-500
                        dark:text-gray-400
                    ">
                    {{ $fullName }}
                </p>

            </div>

        </div>


        <form
            method="POST"
            action="{{ route(
                'admin.subscribers.documents.store',
                $subscriber
            ) }}"
            enctype="multipart/form-data"
            data-lock-submit
            class="mt-5">

            @csrf


            <div>

                <label
                    for="document_type"
                    class="
                        text-sm font-medium
                        text-gray-800
                        dark:text-gray-200
                    ">
                    Document Type
                </label>


                <input
                    id="document_type"
                    name="document_type"
                    type="text"
                    value="{{ old('document_type') }}"
                    maxlength="100"
                    required
                    placeholder="Example: Valid ID"
                    class="
                        mt-2 block min-h-11 w-full
                        rounded-[2px]
                        border bg-white
                        px-3 py-2.5
                        text-sm text-gray-900
                        outline-none transition
                        placeholder:text-gray-400
                        dark:bg-neutral-950
                        dark:text-white

                        @error('document_type')
                            border-red-500
                            focus:border-red-500
                            focus:ring-2
                            focus:ring-red-200
                            dark:border-red-500
                        @else
                            border-gray-300
                            focus:border-[#008080]
                            focus:ring-2
                            focus:ring-[#008080]/20
                            dark:border-neutral-700
                            dark:focus:border-[#14B8A6]
                        @enderror
                    ">


                @error('document_type')

                <p
                    class="
                        mt-1.5
                        text-xs text-red-600
                        dark:text-red-400
                    ">
                    {{ $message }}
                </p>

                @enderror

            </div>


            <div class="mt-4">

                <label
                    for="customer_document"
                    class="
                        text-sm font-medium
                        text-gray-800
                        dark:text-gray-200
                    ">
                    File
                </label>


                <input
                    id="customer_document"
                    name="document"
                    type="file"
                    required
                    accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                    class="
                        mt-2 block w-full
                        rounded-[2px]
                        border bg-white
                        text-sm text-gray-700
                        file:mr-4
                        file:border-0
                        file:bg-gray-100
                        file:px-4
                        file:py-3
                        file:text-sm
                        file:font-medium
                        file:text-gray-700
                        hover:file:bg-gray-200
                        dark:bg-neutral-950
                        dark:text-gray-300
                        dark:file:bg-neutral-800
                        dark:file:text-gray-300

                        @error('document')
                            border-red-500
                        @else
                            border-gray-300
                            dark:border-neutral-700
                        @enderror
                    ">


                <p
                    class="
                        mt-1.5
                        text-xs text-gray-400
                    ">
                    PDF, JPG, JPEG or PNG. Maximum 5 MB.
                </p>


                @error('document')

                <p
                    class="
                        mt-1.5
                        text-xs text-red-600
                        dark:text-red-400
                    ">
                    {{ $message }}
                </p>

                @enderror

            </div>


            <div class="mt-4">

                <label
                    for="document_notes"
                    class="
                        text-sm font-medium
                        text-gray-800
                        dark:text-gray-200
                    ">
                    Notes
                    <span
                        class="
                            font-normal text-gray-400
                        ">
                        (optional)
                    </span>
                </label>


                <textarea
                    id="document_notes"
                    name="notes"
                    rows="3"
                    maxlength="1000"
                    class="
                        mt-2 block w-full
                        resize-none rounded-[2px]
                        border bg-white
                        px-3 py-2.5
                        text-sm text-gray-900
                        outline-none transition
                        dark:bg-neutral-950
                        dark:text-white

                        @error('notes')
                            border-red-500
                            focus:border-red-500
                            focus:ring-2
                            focus:ring-red-200
                        @else
                            border-gray-300
                            focus:border-[#008080]
                            focus:ring-2
                            focus:ring-[#008080]/20
                            dark:border-neutral-700
                        @enderror
                    ">{{ old('notes') }}</textarea>


                @error('notes')

                <p
                    class="
                        mt-1.5
                        text-xs text-red-600
                        dark:text-red-400
                    ">
                    {{ $message }}
                </p>

                @enderror

            </div>


            <div
                class="
                    mt-5 flex
                    justify-end gap-2
                ">

                <button
                    type="button"
                    data-document-upload-cancel
                    class="
                        min-h-10
                        rounded-[2px]
                        border border-gray-300
                        px-4 py-2
                        text-sm font-medium
                        text-gray-700
                        transition
                        hover:bg-gray-50
                        dark:border-neutral-700
                        dark:text-gray-300
                        dark:hover:bg-neutral-800
                    ">
                    Cancel
                </button>


                <button
                    type="submit"
                    data-loading-text="Uploading..."
                    class="
                        inline-flex min-h-10
                        items-center justify-center
                        gap-2 rounded-[2px]
                        bg-[#008080]
                        px-4 py-2
                        text-sm font-semibold
                        text-white
                        transition
                        hover:bg-[#006666]
                    ">

                    <i
                        data-lucide="upload"
                        class="h-4 w-4"
                        aria-hidden="true">
                    </i>

                    Upload

                </button>

            </div>

        </form>

    </div>

</div>


{{-- Delete customer document confirmation --}}
<div
    id="customer-document-delete-modal"
    class="
        fixed inset-0 z-[60]
        hidden items-center justify-center
        p-3 sm:p-4
    "
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
    aria-labelledby="customer-document-delete-title">

    <div
        data-document-delete-overlay
        class="absolute inset-0 bg-black/50">
    </div>


    <div
        class="
            relative z-10
            w-full max-w-md
            rounded-[2px]
            border border-gray-200
            bg-white
            p-5
            shadow-lg
            dark:border-neutral-800
            dark:bg-neutral-900
        ">

        <h2
            id="customer-document-delete-title"
            class="
                text-lg font-semibold
                text-gray-900
                dark:text-white
            ">
            Delete Document?
        </h2>


        <p
            class="
                mt-2
                text-sm leading-6
                text-gray-500
                dark:text-gray-400
            ">

            Delete

            <span
                data-document-delete-type-text
                class="
                    font-medium
                    text-gray-800
                    dark:text-gray-200
                ">
            </span>

            <span
                data-document-delete-name-text
                class="
                    font-medium
                    text-gray-800
                    dark:text-gray-200
                ">
            </span>?

        </p>


        <form
            id="customer-document-delete-form"
            method="POST"
            data-lock-submit
            class="
                mt-5 flex
                justify-end gap-2
            ">

            @csrf
            @method('DELETE')


            <button
                type="button"
                data-document-delete-cancel
                class="
                    min-h-10
                    rounded-[2px]
                    border border-gray-300
                    px-4 py-2
                    text-sm font-medium
                    text-gray-700
                    transition
                    hover:bg-gray-50
                    dark:border-neutral-700
                    dark:text-gray-300
                    dark:hover:bg-neutral-800
                ">
                Cancel
            </button>


            <button
                type="submit"
                data-loading-text="Deleting..."
                class="
                    inline-flex min-h-10
                    items-center justify-center
                    gap-2 rounded-[2px]
                    bg-red-600
                    px-4 py-2
                    text-sm font-semibold
                    text-white
                    transition
                    hover:bg-red-700
                ">

                <i
                    data-lucide="trash-2"
                    class="h-4 w-4"
                    aria-hidden="true">
                </i>

                Delete

            </button>

        </form>

    </div>

</div>


{{-- Documents workspace behavior --}}
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const workspaceModal =
            document.getElementById(
                'customer-document-workspace-modal'
            );

        const workspaceOpenButton =
            document.querySelector(
                '[data-document-workspace-open]'
            );

        const workspaceCloseButton =
            document.querySelector(
                '[data-document-workspace-close]'
            );

        const workspaceOverlay =
            document.querySelector(
                '[data-document-workspace-overlay]'
            );


        const uploadModal =
            document.getElementById(
                'customer-document-upload-modal'
            );

        const uploadOpenButton =
            document.querySelector(
                '[data-document-upload-open]'
            );

        const uploadCancelButton =
            document.querySelector(
                '[data-document-upload-cancel]'
            );

        const uploadOverlay =
            document.querySelector(
                '[data-document-upload-overlay]'
            );


        const deleteModal =
            document.getElementById(
                'customer-document-delete-modal'
            );

        const deleteButtons =
            document.querySelectorAll(
                '[data-document-delete]'
            );

        const deleteCancelButton =
            document.querySelector(
                '[data-document-delete-cancel]'
            );

        const deleteOverlay =
            document.querySelector(
                '[data-document-delete-overlay]'
            );


        let reopenWorkspaceAfterUpload = false;
        let reopenWorkspaceAfterDelete = false;


        const openWorkspace = () => {
            if (!workspaceModal) {
                return;
            }

            workspaceModal.classList.remove(
                'hidden'
            );

            workspaceModal.classList.add(
                'flex'
            );

            workspaceModal.setAttribute(
                'aria-hidden',
                'false'
            );

            document.body.classList.add(
                'overflow-hidden'
            );
        };


        const closeWorkspace = () => {
            if (!workspaceModal) {
                return;
            }

            workspaceModal.classList.add(
                'hidden'
            );

            workspaceModal.classList.remove(
                'flex'
            );

            workspaceModal.setAttribute(
                'aria-hidden',
                'true'
            );

            document.body.classList.remove(
                'overflow-hidden'
            );
        };


        workspaceOpenButton?.addEventListener(
            'click',
            () => {
                openWorkspace();
            }
        );


        workspaceCloseButton?.addEventListener(
            'click',
            () => {
                closeWorkspace();
                workspaceOpenButton?.focus();
            }
        );


        workspaceOverlay?.addEventListener(
            'click',
            () => {
                closeWorkspace();
                workspaceOpenButton?.focus();
            }
        );


        /*
         * The existing page script still owns the
         * Upload and Delete confirmation modals.
         * We only hide/reopen this workspace around them.
         */
        uploadOpenButton?.addEventListener(
            'click',
            () => {
                reopenWorkspaceAfterUpload = true;
                closeWorkspace();
            }
        );


        const restoreAfterUpload = () => {
            if (!reopenWorkspaceAfterUpload) {
                return;
            }

            reopenWorkspaceAfterUpload = false;

            window.setTimeout(
                openWorkspace,
                0
            );
        };


        uploadCancelButton?.addEventListener(
            'click',
            restoreAfterUpload
        );

        uploadOverlay?.addEventListener(
            'click',
            restoreAfterUpload
        );


        deleteButtons.forEach((button) => {
            button.addEventListener(
                'click',
                () => {
                    reopenWorkspaceAfterDelete = true;
                    closeWorkspace();
                }
            );
        });


        const restoreAfterDelete = () => {
            if (!reopenWorkspaceAfterDelete) {
                return;
            }

            reopenWorkspaceAfterDelete = false;

            window.setTimeout(
                openWorkspace,
                0
            );
        };


        deleteCancelButton?.addEventListener(
            'click',
            restoreAfterDelete
        );

        deleteOverlay?.addEventListener(
            'click',
            restoreAfterDelete
        );


        if (
            workspaceModal?.dataset.openOnLoad ===
            'true'
        ) {
            openWorkspace();
        }


        document.addEventListener(
            'keydown',
            (event) => {
                if (
                    event.key !== 'Escape' ||
                    ! workspaceModal
                ) {
                    return;
                }

                const childModalOpen =
                    uploadModal?.getAttribute(
                        'aria-hidden'
                    ) === 'false' ||
                    deleteModal?.getAttribute(
                        'aria-hidden'
                    ) === 'false';

                if (childModalOpen) {
                    return;
                }

                if (
                    workspaceModal.getAttribute(
                        'aria-hidden'
                    ) === 'false'
                ) {
                    closeWorkspace();
                    workspaceOpenButton?.focus();
                }
            }
        );
    });
</script>

{{-- Manage subscriber status modal --}}
@if (count($allowedStatusTransitions) > 0)

<div
    id="subscriber-status-modal"
    class="
            fixed inset-0 z-50
            hidden items-center justify-center
            p-4
        "
    data-open-on-error="{{ $hasStatusErrors ? 'true' : 'false' }}"
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
    aria-labelledby="subscriber-status-modal-title">

    <div
        data-subscriber-status-overlay
        class="absolute inset-0 bg-black/50"></div>


    <div
        class="
                relative z-10
                max-h-[90vh] w-full max-w-lg
                overflow-y-auto
                bg-white p-5 shadow-lg
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
                aria-hidden="true"></i>
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


        {{-- Current status --}}
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
                    aria-hidden="true"></i>

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


            {{-- New status --}}
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
                        aria-hidden="true"></i>

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
                        aria-hidden="true"></i>

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
                            bg-[#008080]
                            px-4 py-2.5
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
                        aria-hidden="true"></i>

                    Confirm Status Change
                </button>

            </div>

        </form>

    </div>

</div>

@endif


<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Subscriber status modal
        const statusModal = document.getElementById('subscriber-status-modal');
        const statusOpenButton = document.querySelector('[data-subscriber-status-open]');
        const statusCancelButton = document.querySelector('[data-subscriber-status-cancel]');
        const statusOverlay = document.querySelector('[data-subscriber-status-overlay]');
        const statusField = document.getElementById('subscriber_status');

        const openStatusModal = () => {
            if (!statusModal) {
                return;
            }

            statusModal.classList.remove('hidden');
            statusModal.classList.add('flex');
            statusModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');

            statusField?.focus();
        };

        const closeStatusModal = () => {
            if (!statusModal) {
                return;
            }

            statusModal.classList.add('hidden');
            statusModal.classList.remove('flex');
            statusModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');

            statusOpenButton?.focus();
        };

        statusOpenButton?.addEventListener('click', openStatusModal);
        statusCancelButton?.addEventListener('click', closeStatusModal);
        statusOverlay?.addEventListener('click', closeStatusModal);

        if (statusModal?.dataset.openOnError === 'true') {
            openStatusModal();
        }


        // Customer document upload modal
        const uploadModal = document.getElementById('customer-document-upload-modal');
        const uploadOpenButton = document.querySelector('[data-document-upload-open]');
        const uploadCancelButton = document.querySelector('[data-document-upload-cancel]');
        const uploadOverlay = document.querySelector('[data-document-upload-overlay]');
        const documentTypeField = document.getElementById('document_type');

        const openUploadModal = () => {
            if (!uploadModal) {
                return;
            }

            uploadModal.classList.remove('hidden');
            uploadModal.classList.add('flex');
            uploadModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');

            documentTypeField?.focus();
        };

        const closeUploadModal = () => {
            if (!uploadModal) {
                return;
            }

            uploadModal.classList.add('hidden');
            uploadModal.classList.remove('flex');
            uploadModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');

            uploadOpenButton?.focus();
        };

        uploadOpenButton?.addEventListener('click', openUploadModal);
        uploadCancelButton?.addEventListener('click', closeUploadModal);
        uploadOverlay?.addEventListener('click', closeUploadModal);

        if (uploadModal?.dataset.openOnError === 'true') {
            openUploadModal();
        }


        // Customer document delete modal
        const deleteModal = document.getElementById('customer-document-delete-modal');
        const deleteOverlay = document.querySelector('[data-document-delete-overlay]');
        const deleteCancelButton = document.querySelector('[data-document-delete-cancel]');
        const deleteForm = document.getElementById('customer-document-delete-form');
        const deleteName = document.querySelector('[data-document-delete-name-text]');
        const deleteType = document.querySelector('[data-document-delete-type-text]');
        const deleteButtons = document.querySelectorAll('[data-document-delete]');

        const closeDeleteModal = () => {
            if (!deleteModal) {
                return;
            }

            deleteModal.classList.add('hidden');
            deleteModal.classList.remove('flex');
            deleteModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');
        };

        deleteButtons.forEach((button) => {
            button.addEventListener('click', () => {
                if (!deleteModal || !deleteForm) {
                    return;
                }

                deleteForm.action = button.dataset.documentDeleteUrl || '';

                if (deleteName) {
                    deleteName.textContent =
                        button.dataset.documentDeleteName || 'this file';
                }

                if (deleteType) {
                    deleteType.textContent =
                        button.dataset.documentDeleteType || 'Customer';
                }

                deleteModal.classList.remove('hidden');
                deleteModal.classList.add('flex');
                deleteModal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');

                deleteCancelButton?.focus();
            });
        });

        deleteCancelButton?.addEventListener('click', closeDeleteModal);
        deleteOverlay?.addEventListener('click', closeDeleteModal);


        // Close open modal with Escape
        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            if (
                deleteModal &&
                deleteModal.getAttribute('aria-hidden') === 'false'
            ) {
                closeDeleteModal();
                return;
            }

            if (
                uploadModal &&
                uploadModal.getAttribute('aria-hidden') === 'false'
            ) {
                closeUploadModal();
                return;
            }

            if (
                statusModal &&
                statusModal.getAttribute('aria-hidden') === 'false'
            ) {
                closeStatusModal();
            }
        });
    });
</script>

@endsection




