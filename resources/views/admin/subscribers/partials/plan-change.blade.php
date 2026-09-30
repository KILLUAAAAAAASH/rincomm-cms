@php
$planChangeErrors = $errors->has('requested_service_plan_id');

$planChangeReviewErrors = $errors->has('review_notes');

$planChangeReviewAction = old('plan_change_review_action');

$currentPlan = $activeSubscription?->servicePlan;

$previousPlanChangeRequests = $subscriber->planChangeRequests
    ->reject(fn ($request) => $request->status === 'pending');

$selectablePlans = $currentPlan
    ? $availablePlans->filter(
        fn ($plan) =>
            (float) $plan->speed_mbps !==
            (float) $currentPlan->speed_mbps
    )->values()
    : collect();

$canRequestPlanChange =
    $subscriber->status === 'active' &&
    $activeSubscription &&
    $currentPlan &&
    ! $pendingPlanChangeRequest &&
    $selectablePlans->isNotEmpty();

$planChangeUnavailableReason = match (true) {
    $subscriber->status !== 'active' =>
        'Requires an active subscriber.',

    ! $activeSubscription || ! $currentPlan =>
        'Requires an active subscription.',

    $pendingPlanChangeRequest !== null =>
        null,

    $selectablePlans->isEmpty() =>
        'No alternative upgrade or downgrade plan is available.',

    default => null,
};

$openPlanChangeWorkspace =
    ! $planChangeReviewErrors &&
    (
        $planChangeErrors ||
        request('feature') === 'plan-change'
    );
@endphp


{{-- Compact plan-change entry --}}
<div
    id="plan-change"
    class="
        hidden
        mt-4
        border-t border-gray-100
        pt-4
        dark:border-neutral-800
    ">

    <div
        class="
            flex items-center
            justify-between gap-3
        ">

        <div class="min-w-0">

            <div
                class="
                    flex flex-wrap
                    items-center gap-2
                ">

                <div
                    class="
                        flex h-8 w-8 shrink-0
                        items-center justify-center
                        rounded-[2px]
                        bg-[#008080]/10
                        text-[#008080]
                        dark:bg-[#008080]/20
                        dark:text-[#5EEAD4]
                    ">

                    <i
                        data-lucide="refresh-cw"
                        class="h-4 w-4"
                        aria-hidden="true">
                    </i>

                </div>


                <p
                    class="
                        text-sm font-semibold
                        text-gray-900
                        dark:text-white
                    ">
                    Plan Change
                </p>


                @if ($pendingPlanChangeRequest)

                <span
                    class="
                        inline-flex
                        items-center gap-1
                        rounded-[2px]
                        bg-amber-50
                        px-2 py-1
                        text-[11px] font-medium
                        text-amber-700
                        dark:bg-amber-950/40
                        dark:text-amber-300
                    ">

                    <i
                        data-lucide="clock-3"
                        class="h-3 w-3"
                        aria-hidden="true">
                    </i>

                    Pending

                </span>

                @endif

            </div>


            @if (
                ! $canRequestPlanChange &&
                ! $pendingPlanChangeRequest &&
                $planChangeUnavailableReason
            )

            <p
                class="
                    mt-1 pl-10
                    text-xs text-gray-400
                    dark:text-gray-500
                ">
                {{ $planChangeUnavailableReason }}
            </p>

            @endif

        </div>


        <button
            type="button"
            data-plan-change-open
            aria-label="Open plan change"
            title="Open plan change"
            class="
                inline-flex h-9 w-9 shrink-0
                items-center justify-center
                rounded-[2px]
                border border-gray-200
                text-gray-500
                transition
                hover:border-[#008080]/40
                hover:bg-[#008080]/5
                hover:text-[#008080]
                focus:outline-none
                focus:ring-2
                focus:ring-[#008080]/30
                dark:border-neutral-700
                dark:text-gray-400
                dark:hover:border-[#14B8A6]/40
                dark:hover:bg-[#008080]/10
                dark:hover:text-[#5EEAD4]
            ">

            <i
                data-lucide="arrow-up-right"
                class="h-4 w-4"
                aria-hidden="true">
            </i>

        </button>

    </div>

</div>


{{-- Plan-change workspace --}}
<div
    id="plan-change-workspace-modal"
    data-plan-change-modal
    data-open-on-load="{{ $openPlanChangeWorkspace ? 'true' : 'false' }}"
    class="
        fixed inset-0 z-50
        hidden items-center justify-center
        p-3 sm:p-4
    "
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
    aria-labelledby="plan-change-workspace-title">

    <div
        data-plan-change-overlay
        class="
            absolute inset-0
            bg-black/50
            backdrop-blur-[1px]
        ">
    </div>


    <div
        class="
            relative z-10
            max-h-[calc(100vh-1.5rem)]
            w-full max-w-xl
            overflow-y-auto
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
                sticky top-0 z-20
                flex items-start
                justify-between gap-3
                border-b border-gray-100
                bg-white/95
                px-4 py-4
                backdrop-blur
                dark:border-neutral-800
                dark:bg-neutral-900/95
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
                        data-lucide="refresh-cw"
                        class="h-4 w-4"
                        aria-hidden="true">
                    </i>

                </div>


                <div class="min-w-0">

                    <h2
                        id="plan-change-workspace-title"
                        class="
                            text-base font-semibold
                            text-gray-900
                            dark:text-white
                        ">
                        Plan Change
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


            <div class="flex shrink-0 items-center gap-1">

                @if ($previousPlanChangeRequests->isNotEmpty())

                <details
                    id="plan-change-more-menu"
                    class="relative">

                    <summary
                        aria-label="More plan change options"
                        title="More options"
                        style="list-style: none;"
                        class="
                            inline-flex h-9 w-9
                            cursor-pointer
                            items-center justify-center
                            rounded-[2px]
                            text-gray-500
                            transition
                            hover:bg-gray-100
                            hover:text-gray-900
                            dark:text-gray-400
                            dark:hover:bg-neutral-800
                            dark:hover:text-white
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
                            rounded-[2px]
                            border border-gray-200
                            bg-white
                            p-1.5
                            shadow-lg
                            dark:border-neutral-700
                            dark:bg-neutral-900
                        ">

                        <button
                            type="button"
                            data-plan-change-history-toggle
                            class="
                                flex w-full
                                items-center gap-2
                                rounded-[2px]
                                px-3 py-2
                                text-left
                                text-sm text-gray-700
                                transition
                                hover:bg-gray-50
                                dark:text-gray-200
                                dark:hover:bg-neutral-800
                            ">

                            <i
                                data-lucide="history"
                                class="h-4 w-4"
                                aria-hidden="true">
                            </i>

                            History

                        </button>

                    </div>

                </details>

                @endif


                <button
                    type="button"
                    data-plan-change-close
                    aria-label="Close plan change"
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


        <div class="p-4 sm:p-5">

            {{-- Current plan --}}
            @if ($currentPlan && $activeSubscription)

            <div
                class="
                    rounded-[2px]
                    border border-gray-200
                    bg-gray-50
                    px-3.5 py-3
                    dark:border-neutral-800
                    dark:bg-neutral-950
                ">

                <p
                    class="
                        text-[11px] font-medium
                        uppercase tracking-wide
                        text-gray-400
                    ">
                    Current Plan
                </p>


                <div
                    class="
                        mt-1 flex flex-wrap
                        items-end
                        justify-between gap-2
                    ">

                    <p
                        class="
                            text-sm font-semibold
                            text-gray-900
                            dark:text-white
                        ">
                        {{ $currentPlan->name }}
                    </p>


                    <p
                        class="
                            text-xs
                            text-gray-500
                            dark:text-gray-400
                        ">
                        {{ number_format(
                            (float) $currentPlan->speed_mbps,
                            0
                        ) }}
                        Mbps
                        &middot;
                        &#8369;{{ number_format(
                            (float) $currentPlan->monthly_fee,
                            2
                        ) }}/mo
                    </p>

                </div>

            </div>

            @endif


            {{-- Validation --}}
            @if ($planChangeErrors)

            <div
                role="alert"
                class="
                    mt-4
                    flex items-start gap-2
                    rounded-[2px]
                    border border-red-200
                    bg-red-50
                    px-3 py-2.5
                    text-sm text-red-700
                    dark:border-red-900
                    dark:bg-red-950/30
                    dark:text-red-300
                ">

                <i
                    data-lucide="triangle-alert"
                    class="mt-0.5 h-4 w-4 shrink-0"
                    aria-hidden="true">
                </i>

                Select a valid alternative plan.

            </div>

            @endif


            {{-- New request --}}
            @if ($canRequestPlanChange)

            <form
                method="POST"
                action="{{ route(
                    'admin.subscribers.plan-change-requests.store',
                    $subscriber
                ) }}"
                data-lock-submit
                class="mt-5">

                @csrf


                <label
                    for="requested_service_plan_id"
                    class="
                        text-sm font-medium
                        text-gray-800
                        dark:text-gray-200
                    ">
                    Requested Plan
                </label>


                <select
                    id="requested_service_plan_id"
                    name="requested_service_plan_id"
                    required
                    class="
                        mt-2 block min-h-11 w-full
                        rounded-[2px]
                        border bg-white
                        px-3 py-2.5
                        text-sm text-gray-900
                        outline-none transition
                        dark:bg-neutral-950
                        dark:text-white

                        @error('requested_service_plan_id')
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

                    <option value="">
                        Select plan
                    </option>


                    @foreach ($selectablePlans as $plan)

                    @php
                    $planDirection =
                        (float) $plan->speed_mbps >
                        (float) $currentPlan->speed_mbps
                            ? 'Upgrade'
                            : 'Downgrade';
                    @endphp


                    <option
    value="{{ $plan->id }}"
    @selected(
        (string) old(
            'requested_service_plan_id'
        ) ===
        (string) $plan->id
    )>
    {{ $plan->name }}
    -
    {{ number_format(
        (float) $plan->speed_mbps,
        0
    ) }} Mbps
    -
    &#8369;{{ number_format(
        (float) $plan->monthly_fee,
        2
    ) }}/month
    -
    {{ $planDirection }}
</option>

                    @endforeach

                </select>


                @error('requested_service_plan_id')

                <p
                    class="
                        mt-1.5
                        text-xs text-red-600
                        dark:text-red-400
                    ">
                    {{ $message }}
                </p>

                @enderror


                <div
                    class="
                        mt-5 flex
                        justify-end gap-2
                    ">

                    <button
                        type="button"
                        data-plan-change-close
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
                        data-loading-text="Recording..."
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
                            data-lucide="refresh-cw"
                            class="h-4 w-4"
                            aria-hidden="true">
                        </i>

                        Submit

                    </button>

                </div>

            </form>


            {{-- Pending request --}}
            @elseif ($pendingPlanChangeRequest)

            <div class="mt-5">

                <div
                    class="
                        flex items-center
                        justify-between gap-3
                    ">

                    <div>

                        <p
                            class="
                                text-xs font-medium
                                text-gray-400
                            ">
                            Current Request
                        </p>

                        <p
                            class="
                                mt-1
                                text-sm font-semibold
                                text-gray-900
                                dark:text-white
                            ">
                            {{ ucfirst(
                                $pendingPlanChangeRequest
                                    ->request_type
                            ) }}
                            awaiting review
                        </p>

                    </div>


                    <span
                        class="
                            inline-flex
                            items-center gap-1
                            rounded-[2px]
                            bg-amber-50
                            px-2.5 py-1
                            text-xs font-medium
                            text-amber-700
                            dark:bg-amber-950/40
                            dark:text-amber-300
                        ">

                        <i
                            data-lucide="clock-3"
                            class="h-3 w-3"
                            aria-hidden="true">
                        </i>

                        Pending

                    </span>

                </div>


                <div
                    class="
                        mt-4 grid gap-3
                        rounded-[2px]
                        border border-gray-200
                        p-4
                        sm:grid-cols-2
                        dark:border-neutral-800
                    ">

                    <div>

                        <p class="text-xs text-gray-400">
                            From
                        </p>

                        <p
                            class="
                                mt-1
                                text-sm font-medium
                                text-gray-800
                                dark:text-gray-200
                            ">
                            {{ $pendingPlanChangeRequest
                                ->currentPlan?->name
                                ?? 'Unavailable' }}
                        </p>

                    </div>


                    <div>

                        <p class="text-xs text-gray-400">
                            To
                        </p>

                        <p
                            class="
                                mt-1
                                text-sm font-semibold
                                text-gray-900
                                dark:text-white
                            ">
                            {{ $pendingPlanChangeRequest
                                ->requestedPlan?->name
                                ?? 'Unavailable' }}
                        </p>

                    </div>


                    @if (
                        $pendingPlanChangeRequest
                            ->requestedPlan
                    )

                    <div class="sm:col-span-2">

                        <p class="text-xs text-gray-400">
                            Requested Service
                        </p>

                        <p
                            class="
                                mt-1
                                text-sm text-gray-700
                                dark:text-gray-300
                            ">
                            {{ number_format(
                                (float)
                                $pendingPlanChangeRequest
                                    ->requestedPlan
                                    ->speed_mbps,
                                0
                            ) }}
                            Mbps
                            &middot;
                            &#8369;{{ number_format(
                                (float)
                                $pendingPlanChangeRequest
                                    ->requestedPlan
                                    ->monthly_fee,
                                2
                            ) }}/mo
                        </p>

                    </div>

                    @endif

                </div>


                <div
                    class="
                        mt-5 flex
                        justify-end gap-2
                    ">

                    <button
                        type="button"
                        data-plan-change-reject-open
                        class="
                            inline-flex min-h-10
                            items-center justify-center
                            gap-2 rounded-[2px]
                            border border-red-200
                            px-4 py-2
                            text-sm font-semibold
                            text-red-600
                            transition
                            hover:bg-red-50
                            dark:border-red-900
                            dark:text-red-400
                            dark:hover:bg-red-950/30
                        ">

                        <i
                            data-lucide="x"
                            class="h-4 w-4"
                            aria-hidden="true">
                        </i>

                        Reject

                    </button>


                    <button
                        type="button"
                        data-plan-change-approve-open
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
                            data-lucide="check"
                            class="h-4 w-4"
                            aria-hidden="true">
                        </i>

                        Approve

                    </button>

                </div>

            </div>


            {{-- Unavailable --}}
            @else

            <div
                class="
                    mt-5
                    rounded-[2px]
                    border border-gray-200
                    bg-gray-50
                    px-4 py-3
                    dark:border-neutral-800
                    dark:bg-neutral-950
                ">

                <div class="flex items-start gap-2">

                    <i
                        data-lucide="info"
                        class="
                            mt-0.5 h-4 w-4
                            shrink-0 text-gray-400
                        "
                        aria-hidden="true">
                    </i>

                    <p
                        class="
                            text-sm text-gray-600
                            dark:text-gray-300
                        ">
                        {{ $planChangeUnavailableReason
                            ?? 'Plan change is not available.' }}
                    </p>

                </div>

            </div>

            @endif


            {{-- History --}}
            @if ($previousPlanChangeRequests->isNotEmpty())

            <section
                data-plan-change-history-panel
                class="
                    mt-5 hidden
                    border-t border-gray-100
                    pt-5
                    dark:border-neutral-800
                ">

                <div
                    class="
                        flex items-center
                        justify-between gap-3
                    ">

                    <div>

                        <h3
                            class="
                                text-sm font-semibold
                                text-gray-900
                                dark:text-white
                            ">
                            Plan Change History
                        </h3>

                        <p
                            class="
                                mt-0.5
                                text-xs text-gray-400
                            ">
                            Previous requests
                        </p>

                    </div>


                    <button
                        type="button"
                        data-plan-change-history-hide
                        aria-label="Hide plan change history"
                        title="Hide history"
                        class="
                            inline-flex h-8 w-8
                            items-center justify-center
                            rounded-[2px]
                            text-gray-400
                            transition
                            hover:bg-gray-100
                            hover:text-gray-700
                            dark:hover:bg-neutral-800
                            dark:hover:text-gray-200
                        ">

                        <i
                            data-lucide="x"
                            class="h-4 w-4"
                            aria-hidden="true">
                        </i>

                    </button>

                </div>


                <div
                    class="
                        mt-3 max-h-72
                        space-y-2
                        overflow-y-auto pr-1
                    ">

                    @foreach (
                        $previousPlanChangeRequests
                        as $planChangeRequest
                    )

                    @php
                    $historyStatus = match (
                        $planChangeRequest->status
                    ) {
                        'approved' => [
                            'class' =>
                                'bg-green-50 text-green-700
                                 dark:bg-green-950/40
                                 dark:text-green-300',
                            'icon' => 'circle-check',
                        ],

                        'rejected' => [
                            'class' =>
                                'bg-red-50 text-red-700
                                 dark:bg-red-950/40
                                 dark:text-red-300',
                            'icon' => 'circle-x',
                        ],

                        'cancelled' => [
                            'class' =>
                                'bg-gray-100 text-gray-700
                                 dark:bg-neutral-800
                                 dark:text-gray-300',
                            'icon' => 'ban',
                        ],

                        default => [
                            'class' =>
                                'bg-gray-100 text-gray-700
                                 dark:bg-neutral-800
                                 dark:text-gray-300',
                            'icon' => 'circle-help',
                        ],
                    };
                    @endphp


                    <article
                        class="
                            rounded-[2px]
                            border border-gray-200
                            p-3
                            dark:border-neutral-800
                        ">

                        <div
                            class="
                                flex items-start
                                justify-between gap-3
                            ">

                            <div class="min-w-0">

                                <p
                                    class="
                                        text-sm font-medium
                                        text-gray-900
                                        dark:text-white
                                    ">
                                    {{ ucfirst(
                                        $planChangeRequest
                                            ->request_type
                                    ) }}
                                    &middot;
                                    {{ $planChangeRequest
                                        ->currentPlan?->name
                                        ?? 'Unavailable' }}
                                    &rarr;
                                    {{ $planChangeRequest
                                        ->requestedPlan?->name
                                        ?? 'Unavailable' }}
                                </p>

                                <p
                                    class="
                                        mt-0.5
                                        text-xs text-gray-400
                                    ">
                                    {{ $planChangeRequest
                                        ->created_at
                                        ?->format('M d, Y')
                                        ?? 'Unavailable' }}
                                </p>

                            </div>


                            <span
                                class="
                                    inline-flex shrink-0
                                    items-center gap-1
                                    rounded-[2px]
                                    px-2 py-1
                                    text-[11px] font-medium
                                    {{ $historyStatus['class'] }}
                                ">

                                <i
                                    data-lucide="{{
                                        $historyStatus['icon']
                                    }}"
                                    class="h-3 w-3"
                                    aria-hidden="true">
                                </i>

                                {{ ucfirst(
                                    $planChangeRequest->status
                                ) }}

                            </span>

                        </div>


                        @if ($planChangeRequest->review_notes)

                        <p
                            class="
                                mt-3
                                border-t border-gray-100
                                pt-2
                                text-xs text-gray-500
                                dark:border-neutral-800
                                dark:text-gray-400
                            ">
                            {{ $planChangeRequest->review_notes }}
                        </p>

                        @endif

                    </article>

                    @endforeach

                </div>

            </section>

            @endif

        </div>

    </div>

</div>


{{-- Approve plan change --}}
@if ($pendingPlanChangeRequest)

<div
    id="plan-change-approve-modal"
    data-plan-change-modal
    data-open-on-error="{{
        $planChangeReviewErrors &&
        $planChangeReviewAction === 'approve'
            ? 'true'
            : 'false'
    }}"
    class="
        fixed inset-0 z-[60]
        hidden items-center justify-center
        p-3 sm:p-4
    "
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
    aria-labelledby="plan-change-approve-title">

    <div
        data-plan-change-approve-overlay
        class="absolute inset-0 bg-black/50">
    </div>


    <div
        class="
            relative z-10
            w-full max-w-md
            rounded-[2px]
            bg-white p-5
            shadow-lg
            dark:bg-neutral-900
        ">

        <h2
            id="plan-change-approve-title"
            class="
                text-lg font-semibold
                text-gray-900
                dark:text-white
            ">
            Approve Plan Change?
        </h2>


        <p
            class="
                mt-1
                text-sm text-gray-500
                dark:text-gray-400
            ">
            {{ $pendingPlanChangeRequest
                ->currentPlan?->name
                ?? 'Current plan' }}
            &rarr;
            {{ $pendingPlanChangeRequest
                ->requestedPlan?->name
                ?? 'Requested plan' }}
        </p>


        <form
            method="POST"
            action="{{ route(
                'admin.subscribers.plan-change-requests.approve',
                [$subscriber, $pendingPlanChangeRequest]
            ) }}"
            data-lock-submit
            class="mt-4">

            @csrf
            @method('PATCH')

            <input
                type="hidden"
                name="plan_change_review_action"
                value="approve">


            <label
                for="plan_change_approve_notes"
                class="
                    text-sm font-medium
                    text-gray-800
                    dark:text-gray-200
                ">
                Review Notes
            </label>


            <textarea
                id="plan_change_approve_notes"
                name="review_notes"
                rows="3"
                maxlength="1000"
                placeholder="Optional"
                class="
                    mt-2 block w-full
                    resize-none rounded-[2px]
                    border border-gray-300
                    bg-white px-3 py-2.5
                    text-sm text-gray-900
                    outline-none transition
                    focus:border-[#008080]
                    focus:ring-2
                    focus:ring-[#008080]/20
                    dark:border-neutral-700
                    dark:bg-neutral-950
                    dark:text-white
                ">{{ $planChangeReviewAction === 'approve'
                    ? old('review_notes')
                    : '' }}</textarea>


            @if (
                $planChangeReviewAction === 'approve' &&
                $errors->has('review_notes')
            )

            <p
                class="
                    mt-1.5
                    text-xs text-red-600
                    dark:text-red-400
                ">
                {{ $errors->first('review_notes') }}
            </p>

            @endif


            <div
                class="
                    mt-5 flex
                    justify-end gap-2
                ">

                <button
                    type="button"
                    data-plan-change-approve-cancel
                    class="
                        min-h-10 rounded-[2px]
                        border border-gray-300
                        px-4 py-2
                        text-sm font-medium
                        text-gray-700
                        dark:border-neutral-700
                        dark:text-gray-300
                    ">
                    Cancel
                </button>


                <button
                    type="submit"
                    data-loading-text="Approving..."
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
                        data-lucide="check"
                        class="h-4 w-4"
                        aria-hidden="true">
                    </i>

                    Approve

                </button>

            </div>

        </form>

    </div>

</div>


{{-- Reject plan change --}}
<div
    id="plan-change-reject-modal"
    data-plan-change-modal
    data-open-on-error="{{
        $planChangeReviewErrors &&
        $planChangeReviewAction === 'reject'
            ? 'true'
            : 'false'
    }}"
    class="
        fixed inset-0 z-[60]
        hidden items-center justify-center
        p-3 sm:p-4
    "
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
    aria-labelledby="plan-change-reject-title">

    <div
        data-plan-change-reject-overlay
        class="absolute inset-0 bg-black/50">
    </div>


    <div
        class="
            relative z-10
            w-full max-w-md
            rounded-[2px]
            bg-white p-5
            shadow-lg
            dark:bg-neutral-900
        ">

        <h2
            id="plan-change-reject-title"
            class="
                text-lg font-semibold
                text-gray-900
                dark:text-white
            ">
            Reject Plan Change?
        </h2>


        <p
            class="
                mt-1
                text-sm text-gray-500
                dark:text-gray-400
            ">
            The subscriber's current plan will remain unchanged.
        </p>


        <form
            method="POST"
            action="{{ route(
                'admin.subscribers.plan-change-requests.reject',
                [$subscriber, $pendingPlanChangeRequest]
            ) }}"
            data-lock-submit
            class="mt-4">

            @csrf
            @method('PATCH')

            <input
                type="hidden"
                name="plan_change_review_action"
                value="reject">


            <label
                for="plan_change_reject_notes"
                class="
                    text-sm font-medium
                    text-gray-800
                    dark:text-gray-200
                ">
                Rejection Reason
            </label>


            <textarea
                id="plan_change_reject_notes"
                name="review_notes"
                rows="3"
                maxlength="1000"
                required
                class="
                    mt-2 block w-full
                    resize-none rounded-[2px]
                    border bg-white
                    px-3 py-2.5
                    text-sm text-gray-900
                    outline-none
                    dark:bg-neutral-950
                    dark:text-white

                    @if (
                        $planChangeReviewAction === 'reject' &&
                        $errors->has('review_notes')
                    )
                        border-red-500
                        focus:border-red-500
                        focus:ring-2
                        focus:ring-red-200
                    @else
                        border-gray-300
                        focus:border-red-500
                        focus:ring-2
                        focus:ring-red-100
                        dark:border-neutral-700
                    @endif
                ">{{ $planChangeReviewAction === 'reject'
                    ? old('review_notes')
                    : '' }}</textarea>


            @if (
                $planChangeReviewAction === 'reject' &&
                $errors->has('review_notes')
            )

            <p
                class="
                    mt-1.5
                    text-xs text-red-600
                    dark:text-red-400
                ">
                {{ $errors->first('review_notes') }}
            </p>

            @endif


            <div
                class="
                    mt-5 flex
                    justify-end gap-2
                ">

                <button
                    type="button"
                    data-plan-change-reject-cancel
                    class="
                        min-h-10 rounded-[2px]
                        border border-gray-300
                        px-4 py-2
                        text-sm font-medium
                        text-gray-700
                        dark:border-neutral-700
                        dark:text-gray-300
                    ">
                    Cancel
                </button>


                <button
                    type="submit"
                    data-loading-text="Rejecting..."
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
                        data-lucide="x"
                        class="h-4 w-4"
                        aria-hidden="true">
                    </i>

                    Reject

                </button>

            </div>

        </form>

    </div>

</div>

@endif


<script>
    document.addEventListener('DOMContentLoaded', () => {
        const workspaceModal =
            document.getElementById(
                'plan-change-workspace-modal'
            );

        const openButton =
            document.querySelector(
                '[data-plan-change-open]'
            );

        const closeButtons =
            document.querySelectorAll(
                '[data-plan-change-close]'
            );

        const workspaceOverlay =
            document.querySelector(
                '[data-plan-change-overlay]'
            );


        const approveModal =
            document.getElementById(
                'plan-change-approve-modal'
            );

        const rejectModal =
            document.getElementById(
                'plan-change-reject-modal'
            );


        const allModals = Array.from(
            document.querySelectorAll(
                '[data-plan-change-modal]'
            )
        );


        const anyModalOpen = () => {
            return allModals.some(
                (modal) =>
                    modal.getAttribute(
                        'aria-hidden'
                    ) === 'false'
            );
        };


        const openModal = (
            modal,
            focusTarget = null
        ) => {
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

            window.setTimeout(() => {
                focusTarget?.focus();
            }, 0);
        };


        const closeModal = (
            modal,
            restoreFocus = null
        ) => {
            if (!modal) {
                return;
            }

            modal.classList.add('hidden');
            modal.classList.remove('flex');

            modal.setAttribute(
                'aria-hidden',
                'true'
            );

            if (!anyModalOpen()) {
                document.body.classList.remove(
                    'overflow-hidden'
                );
            }

            restoreFocus?.focus();
        };


        const reopenWorkspace = () => {
            openModal(workspaceModal);
        };


        openButton?.addEventListener(
            'click',
            () => {
                openModal(
                    workspaceModal,
                    document.getElementById(
                        'requested_service_plan_id'
                    )
                );
            }
        );


        closeButtons.forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        closeModal(
                            workspaceModal,
                            openButton
                        );
                    }
                );
            }
        );


        workspaceOverlay?.addEventListener(
            'click',
            () => {
                closeModal(
                    workspaceModal,
                    openButton
                );
            }
        );


        const historyToggle =
            document.querySelector(
                '[data-plan-change-history-toggle]'
            );

        const historyHide =
            document.querySelector(
                '[data-plan-change-history-hide]'
            );

        const historyPanel =
            document.querySelector(
                '[data-plan-change-history-panel]'
            );

        const moreMenu =
            document.getElementById(
                'plan-change-more-menu'
            );


        historyToggle?.addEventListener(
            'click',
            () => {
                historyPanel?.classList.remove(
                    'hidden'
                );

                moreMenu?.removeAttribute('open');

                window.setTimeout(() => {
                    historyPanel?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest',
                    });
                }, 0);
            }
        );


        historyHide?.addEventListener(
            'click',
            () => {
                historyPanel?.classList.add(
                    'hidden'
                );
            }
        );


        const approveOpen =
            document.querySelector(
                '[data-plan-change-approve-open]'
            );

        const approveCancel =
            document.querySelector(
                '[data-plan-change-approve-cancel]'
            );

        const approveOverlay =
            document.querySelector(
                '[data-plan-change-approve-overlay]'
            );


        approveOpen?.addEventListener(
            'click',
            () => {
                closeModal(workspaceModal);

                openModal(
                    approveModal,
                    document.getElementById(
                        'plan_change_approve_notes'
                    )
                );
            }
        );


        const closeApprove = () => {
            closeModal(approveModal);
            reopenWorkspace();
        };


        approveCancel?.addEventListener(
            'click',
            closeApprove
        );

        approveOverlay?.addEventListener(
            'click',
            closeApprove
        );


        const rejectOpen =
            document.querySelector(
                '[data-plan-change-reject-open]'
            );

        const rejectCancel =
            document.querySelector(
                '[data-plan-change-reject-cancel]'
            );

        const rejectOverlay =
            document.querySelector(
                '[data-plan-change-reject-overlay]'
            );


        rejectOpen?.addEventListener(
            'click',
            () => {
                closeModal(workspaceModal);

                openModal(
                    rejectModal,
                    document.getElementById(
                        'plan_change_reject_notes'
                    )
                );
            }
        );


        const closeReject = () => {
            closeModal(rejectModal);
            reopenWorkspace();
        };


        rejectCancel?.addEventListener(
            'click',
            closeReject
        );

        rejectOverlay?.addEventListener(
            'click',
            closeReject
        );


        const reviewErrorModal = allModals.find(
            (modal) =>
                modal.dataset.openOnError === 'true'
        );


        if (reviewErrorModal) {
            openModal(reviewErrorModal);
        } else if (
            workspaceModal?.dataset.openOnLoad ===
            'true'
        ) {
            openModal(workspaceModal);
        }


        document.addEventListener(
            'keydown',
            (event) => {
                if (event.key !== 'Escape') {
                    return;
                }

                if (
                    approveModal?.getAttribute(
                        'aria-hidden'
                    ) === 'false'
                ) {
                    closeApprove();
                    return;
                }

                if (
                    rejectModal?.getAttribute(
                        'aria-hidden'
                    ) === 'false'
                ) {
                    closeReject();
                    return;
                }

                if (
                    workspaceModal?.getAttribute(
                        'aria-hidden'
                    ) === 'false'
                ) {
                    closeModal(
                        workspaceModal,
                        openButton
                    );
                }
            }
        );
    });
</script>
