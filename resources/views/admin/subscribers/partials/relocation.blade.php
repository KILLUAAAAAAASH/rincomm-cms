@php
$relocationErrors = $errors->hasAny([
    'requested_service_area_id',
    'requested_installation_address',
]);

$reviewErrors = $errors->has('review_notes');
$reviewAction = old('relocation_action');

$openRelocationRequest = $subscriber->relocationRequests
    ->first(fn ($request) => in_array(
        $request->status,
        ['pending', 'approved'],
        true
    ));

$pendingRelocation =
    $openRelocationRequest?->status === 'pending'
        ? $openRelocationRequest
        : null;

$approvedRelocation =
    $openRelocationRequest?->status === 'approved'
        ? $openRelocationRequest
        : null;

$previousRelocationRequests = $subscriber->relocationRequests
    ->reject(fn ($request) => in_array(
        $request->status,
        ['pending', 'approved'],
        true
    ));

$canRequestRelocation =
    $subscriber->status === 'active' &&
    $activeSubscription &&
    ! $openRelocationRequest &&
    $serviceableAreas->isNotEmpty() &&
    filled($subscriber->installation_address);

$relocationStatusClasses = [
    'pending' =>
        'bg-amber-50 text-amber-700
         dark:bg-amber-950/40 dark:text-amber-300',

    'approved' =>
        'bg-blue-50 text-blue-700
         dark:bg-blue-950/40 dark:text-blue-300',

    'rejected' =>
        'bg-red-50 text-red-700
         dark:bg-red-950/40 dark:text-red-300',

    'cancelled' =>
        'bg-gray-100 text-gray-700
         dark:bg-neutral-800 dark:text-gray-300',

    'completed' =>
        'bg-green-50 text-green-700
         dark:bg-green-950/40 dark:text-green-300',
];

$relocationUnavailableReason = match (true) {
    $subscriber->status !== 'active' =>
        'Requires an active subscriber.',

    ! $activeSubscription =>
        'Requires an active subscription.',

    ! filled($subscriber->installation_address) =>
        'Current installation address is required.',

    $serviceableAreas->isEmpty() =>
        'No serviceable areas are available.',

    default => null,
};

$openRelocationWorkspace =
    $relocationErrors ||
    request('feature') === 'relocation-transfer';
@endphp


{{-- Compact relocation entry --}}
<div
    id="relocation-transfer"
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
            justify-between
            gap-3
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
                        data-lucide="map-pinned"
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
                    Relocation
                </p>


                @if ($pendingRelocation)

                <span
                    class="
                        inline-flex
                        items-center gap-1
                        rounded-[2px]
                        px-2 py-1
                        text-[11px] font-medium
                        {{ $relocationStatusClasses['pending'] }}
                    ">

                    <i
                        data-lucide="clock-3"
                        class="h-3 w-3"
                        aria-hidden="true">
                    </i>

                    Pending

                </span>

                @elseif ($approvedRelocation)

                <span
                    class="
                        inline-flex
                        items-center gap-1
                        rounded-[2px]
                        px-2 py-1
                        text-[11px] font-medium
                        {{ $relocationStatusClasses['approved'] }}
                    ">

                    <i
                        data-lucide="circle-check"
                        class="h-3 w-3"
                        aria-hidden="true">
                    </i>

                    Approved

                </span>

                @endif

            </div>


            @if (
                ! $canRequestRelocation &&
                ! $openRelocationRequest &&
                $relocationUnavailableReason
            )

            <p
                class="
                    mt-1 pl-10
                    text-xs text-gray-400
                    dark:text-gray-500
                ">
                {{ $relocationUnavailableReason }}
            </p>

            @endif

        </div>


        <button
            type="button"
            data-relocation-open
            aria-label="Open relocation"
            title="Open relocation"
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


{{-- Relocation workspace modal --}}
<div
    id="relocation-workspace-modal"
    data-relocation-modal
    data-open-on-load="{{ $openRelocationWorkspace ? 'true' : 'false' }}"
    class="
        fixed inset-0 z-50
        hidden items-center justify-center
        p-3 sm:p-4
    "
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
    aria-labelledby="relocation-workspace-title">

    <div
        data-relocation-overlay
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

        {{-- Modal header --}}
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
                        data-lucide="map-pinned"
                        class="h-4 w-4"
                        aria-hidden="true">
                    </i>

                </div>


                <div class="min-w-0">

                    <h2
                        id="relocation-workspace-title"
                        class="
                            text-base font-semibold
                            text-gray-900
                            dark:text-white
                        ">
                        Relocation
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

                @if ($previousRelocationRequests->isNotEmpty())

                <details
                    id="relocation-more-menu"
                    class="relative">

                    <summary
                        aria-label="More relocation options"
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
                            data-relocation-history-toggle
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
                    data-relocation-close
                    aria-label="Close relocation"
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

            {{-- Current address --}}
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
                    Current Installation Address
                </p>

                <p
                    class="
                        mt-1
                        whitespace-pre-line
                        text-sm font-medium
                        text-gray-800
                        dark:text-gray-200
                    ">
                    {{ $subscriber->installation_address
                        ?: $subscriber->address
                        ?: 'Not recorded' }}
                </p>

            </div>


            {{-- Request validation notice --}}
            @if ($relocationErrors)

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

                Check the highlighted relocation fields.

            </div>

            @endif


            {{-- New relocation --}}
            @if ($canRequestRelocation)

            <form
                method="POST"
                action="{{ route(
                    'admin.subscribers.relocation-requests.store',
                    $subscriber
                ) }}"
                data-lock-submit
                class="mt-5">

                @csrf


                <div>

                    <label
                        for="requested_service_area_id"
                        class="
                            text-sm font-medium
                            text-gray-800
                            dark:text-gray-200
                        ">
                        New Service Area
                    </label>


                    <select
                        id="requested_service_area_id"
                        name="requested_service_area_id"
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

                            @error('requested_service_area_id')
                                border-red-500
                                focus:border-red-500
                                focus:ring-2
                                focus:ring-red-200
                                dark:border-red-500
                                dark:focus:ring-red-950
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
                            Select service area
                        </option>


                        @foreach ($serviceableAreas as $serviceArea)

                        <option
                            value="{{ $serviceArea->id }}"
                            @selected(
                                (string) old(
                                    'requested_service_area_id'
                                ) ===
                                (string) $serviceArea->id
                            )>

                            {{ $serviceArea->barangay }},
                            {{ $serviceArea->city_municipality }},
                            {{ $serviceArea->province }}

                            @if ($serviceArea->postal_code)
                                - {{ $serviceArea->postal_code }}
                            @endif

                        </option>

                        @endforeach

                    </select>


                    @error('requested_service_area_id')

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
                        for="requested_installation_address"
                        class="
                            text-sm font-medium
                            text-gray-800
                            dark:text-gray-200
                        ">
                        New Installation Address
                    </label>


                    <textarea
                        id="requested_installation_address"
                        name="requested_installation_address"
                        rows="3"
                        maxlength="255"
                        required
                        placeholder="House number, street, purok, subdivision, or landmark"
                        class="
                            mt-2 block w-full
                            resize-none rounded-[2px]
                            border bg-white
                            px-3 py-2.5
                            text-sm text-gray-900
                            outline-none transition
                            placeholder:text-gray-400
                            dark:bg-neutral-950
                            dark:text-white
                            dark:placeholder:text-gray-500

                            @error('requested_installation_address')
                                border-red-500
                                focus:border-red-500
                                focus:ring-2
                                focus:ring-red-200
                                dark:border-red-500
                                dark:focus:ring-red-950
                            @else
                                border-gray-300
                                focus:border-[#008080]
                                focus:ring-2
                                focus:ring-[#008080]/20
                                dark:border-neutral-700
                                dark:focus:border-[#14B8A6]
                            @enderror
                        ">{{ old('requested_installation_address') }}</textarea>


                    @error('requested_installation_address')

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
                        data-relocation-close
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
                            gap-2
                            rounded-[2px]
                            bg-[#008080]
                            px-4 py-2
                            text-sm font-semibold
                            text-white
                            transition
                            hover:bg-[#006666]
                            focus:outline-none
                            focus:ring-2
                            focus:ring-[#008080]/30
                        ">

                        <i
                            data-lucide="map-pinned"
                            class="h-4 w-4"
                            aria-hidden="true">
                        </i>

                        Submit

                    </button>

                </div>

            </form>


            {{-- Pending request --}}
            @elseif ($pendingRelocation)

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
                            Relocation awaiting review
                        </p>

                    </div>


                    <span
                        class="
                            inline-flex
                            items-center gap-1
                            rounded-[2px]
                            px-2.5 py-1
                            text-xs font-medium
                            {{ $relocationStatusClasses['pending'] }}
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
                        mt-4 space-y-3
                        rounded-[2px]
                        border border-gray-200
                        p-4
                        dark:border-neutral-800
                    ">

                    <div>

                        <p
                            class="
                                text-xs text-gray-400
                            ">
                            Requested Address
                        </p>

                        <p
                            class="
                                mt-1 whitespace-pre-line
                                text-sm font-medium
                                text-gray-800
                                dark:text-gray-200
                            ">
                            {{ $pendingRelocation
                                ->requested_installation_address }}
                        </p>

                    </div>


                    <div>

                        <p class="text-xs text-gray-400">
                            Service Area
                        </p>

                        <p
                            class="
                                mt-1
                                text-sm text-gray-700
                                dark:text-gray-300
                            ">
                            {{ $pendingRelocation->requested_barangay }},
                            {{ $pendingRelocation
                                ->requested_city_municipality }},
                            {{ $pendingRelocation->requested_province }}

                            @if (
                                $pendingRelocation
                                    ->requested_postal_code
                            )
                                {{ $pendingRelocation
                                    ->requested_postal_code }}
                            @endif
                        </p>

                    </div>


                    <div>

                        <p class="text-xs text-gray-400">
                            Recorded
                        </p>

                        <p
                            class="
                                mt-1
                                text-sm text-gray-700
                                dark:text-gray-300
                            ">
                            {{ $pendingRelocation
                                ->created_at
                                ?->format('M d, Y g:i A')
                                ?? 'Unavailable' }}
                        </p>

                    </div>

                </div>


                <div
                    class="
                        mt-5 flex
                        justify-end gap-2
                    ">

                    <button
                        type="button"
                        data-relocation-reject-open
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
                        data-relocation-approve-open
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


            {{-- Approved request --}}
            @elseif ($approvedRelocation)

            <div class="mt-5">

                <div
                    class="
                        flex items-center
                        justify-between gap-3
                    ">

                    <div>

                        <p class="text-xs font-medium text-gray-400">
                            Current Request
                        </p>

                        <p
                            class="
                                mt-1
                                text-sm font-semibold
                                text-gray-900
                                dark:text-white
                            ">
                            Ready for completion
                        </p>

                    </div>


                    <span
                        class="
                            inline-flex
                            items-center gap-1
                            rounded-[2px]
                            px-2.5 py-1
                            text-xs font-medium
                            {{ $relocationStatusClasses['approved'] }}
                        ">

                        <i
                            data-lucide="circle-check"
                            class="h-3 w-3"
                            aria-hidden="true">
                        </i>

                        Approved

                    </span>

                </div>


                <div
                    class="
                        mt-4 space-y-3
                        rounded-[2px]
                        border border-gray-200
                        p-4
                        dark:border-neutral-800
                    ">

                    <div>

                        <p class="text-xs text-gray-400">
                            New Address
                        </p>

                        <p
                            class="
                                mt-1 whitespace-pre-line
                                text-sm font-medium
                                text-gray-800
                                dark:text-gray-200
                            ">
                            {{ $approvedRelocation
                                ->requested_installation_address }}
                        </p>

                    </div>


                    <div>

                        <p class="text-xs text-gray-400">
                            Service Area
                        </p>

                        <p
                            class="
                                mt-1
                                text-sm text-gray-700
                                dark:text-gray-300
                            ">
                            {{ $approvedRelocation->requested_barangay }},
                            {{ $approvedRelocation
                                ->requested_city_municipality }},
                            {{ $approvedRelocation->requested_province }}

                            @if (
                                $approvedRelocation
                                    ->requested_postal_code
                            )
                                {{ $approvedRelocation
                                    ->requested_postal_code }}
                            @endif
                        </p>

                    </div>


                    @if ($approvedRelocation->review_notes)

                    <div>

                        <p class="text-xs text-gray-400">
                            Review Notes
                        </p>

                        <p
                            class="
                                mt-1 whitespace-pre-line
                                text-sm text-gray-700
                                dark:text-gray-300
                            ">
                            {{ $approvedRelocation->review_notes }}
                        </p>

                    </div>

                    @endif

                </div>


                <div class="mt-5 flex justify-end">

                    <button
                        type="button"
                        data-relocation-complete-open
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
                            data-lucide="circle-check"
                            class="h-4 w-4"
                            aria-hidden="true">
                        </i>

                        Complete Relocation

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
                            shrink-0
                            text-gray-400
                        "
                        aria-hidden="true">
                    </i>

                    <p
                        class="
                            text-sm text-gray-600
                            dark:text-gray-300
                        ">
                        {{ $relocationUnavailableReason
                            ?? 'Relocation is not available.' }}
                    </p>

                </div>

            </div>

            @endif


            {{-- History, hidden until requested --}}
            @if ($previousRelocationRequests->isNotEmpty())

            <section
                data-relocation-history-panel
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
                            Relocation History
                        </h3>

                        <p
                            class="
                                mt-0.5
                                text-xs text-gray-400
                            ">
                            Previous relocation requests
                        </p>

                    </div>


                    <button
                        type="button"
                        data-relocation-history-hide
                        aria-label="Hide relocation history"
                        title="Hide history"
                        class="
                            inline-flex h-8 w-8
                            items-center justify-center
                            rounded-[2px]
                            text-gray-400
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
                        overflow-y-auto
                        pr-1
                    ">

                    @foreach (
                        $previousRelocationRequests
                        as $relocationRequest
                    )

                    @php
                    $historyClass =
                        $relocationStatusClasses[
                            $relocationRequest->status
                        ]
                        ??
                        'bg-gray-100 text-gray-700
                         dark:bg-neutral-800
                         dark:text-gray-300';
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
                                        truncate
                                        text-sm font-medium
                                        text-gray-900
                                        dark:text-white
                                    ">
                                    {{ $relocationRequest
                                        ->requested_barangay }},
                                    {{ $relocationRequest
                                        ->requested_city_municipality }}
                                </p>

                                <p
                                    class="
                                        mt-0.5
                                        text-xs text-gray-400
                                    ">
                                    {{ $relocationRequest
                                        ->created_at
                                        ?->format('M d, Y')
                                        ?? 'Unavailable' }}
                                </p>

                            </div>


                            <span
                                class="
                                    shrink-0
                                    rounded-[2px]
                                    px-2 py-1
                                    text-[11px] font-medium
                                    {{ $historyClass }}
                                ">
                                {{ ucfirst(
                                    $relocationRequest->status
                                ) }}
                            </span>

                        </div>


                        <div
                            class="
                                mt-3 grid gap-2
                                text-xs
                                sm:grid-cols-2
                            ">

                            <div>

                                <p class="text-gray-400">
                                    From
                                </p>

                                <p
                                    class="
                                        mt-0.5
                                        text-gray-600
                                        dark:text-gray-300
                                    ">
                                    {{ $relocationRequest
                                        ->current_installation_address }}
                                </p>

                            </div>


                            <div>

                                <p class="text-gray-400">
                                    To
                                </p>

                                <p
                                    class="
                                        mt-0.5
                                        text-gray-700
                                        dark:text-gray-200
                                    ">
                                    {{ $relocationRequest
                                        ->requested_installation_address }}
                                </p>

                            </div>

                        </div>


                        @if ($relocationRequest->review_notes)

                        <p
                            class="
                                mt-3
                                border-t border-gray-100
                                pt-2
                                text-xs text-gray-500
                                dark:border-neutral-800
                                dark:text-gray-400
                            ">
                            {{ $relocationRequest->review_notes }}
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


{{-- Approve relocation confirmation --}}
@if ($pendingRelocation)

<div
    id="relocation-approve-modal"
    data-relocation-modal
    data-open-on-error="{{
        $reviewErrors &&
        $reviewAction === 'approve'
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
    aria-labelledby="relocation-approve-title">

    <div
        data-relocation-approve-overlay
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
            id="relocation-approve-title"
            class="
                text-lg font-semibold
                text-gray-900
                dark:text-white
            ">
            Approve Relocation?
        </h2>


        <p
            class="
                mt-1
                text-sm text-gray-500
                dark:text-gray-400
            ">
            {{ $pendingRelocation
                ->requested_installation_address }}
        </p>


        <form
            method="POST"
            action="{{ route(
                'admin.subscribers.relocation-requests.approve',
                [$subscriber, $pendingRelocation]
            ) }}"
            data-lock-submit
            class="mt-4">

            @csrf
            @method('PATCH')

            <input
                type="hidden"
                name="relocation_action"
                value="approve">


            <label
                for="relocation_approval_notes"
                class="
                    text-sm font-medium
                    text-gray-800
                    dark:text-gray-200
                ">
                Review Notes
            </label>


            <textarea
                id="relocation_approval_notes"
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
                ">{{ $reviewAction === 'approve'
                    ? old('review_notes')
                    : '' }}</textarea>


            @if (
                $reviewErrors &&
                $reviewAction === 'approve'
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
                    data-relocation-approve-cancel
                    class="
                        min-h-10 rounded-[2px]
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

@endif


{{-- Reject relocation confirmation --}}
@if ($pendingRelocation)

<div
    id="relocation-reject-modal"
    data-relocation-modal
    data-open-on-error="{{
        $reviewErrors &&
        $reviewAction === 'reject'
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
    aria-labelledby="relocation-reject-title">

    <div
        data-relocation-reject-overlay
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
            id="relocation-reject-title"
            class="
                text-lg font-semibold
                text-gray-900
                dark:text-white
            ">
            Reject Relocation?
        </h2>


        <p
            class="
                mt-1
                text-sm text-gray-500
                dark:text-gray-400
            ">
            The current installation address will remain unchanged.
        </p>


        <form
            method="POST"
            action="{{ route(
                'admin.subscribers.relocation-requests.reject',
                [$subscriber, $pendingRelocation]
            ) }}"
            data-lock-submit
            class="mt-4">

            @csrf
            @method('PATCH')

            <input
                type="hidden"
                name="relocation_action"
                value="reject">


            <label
                for="relocation_rejection_reason"
                class="
                    text-sm font-medium
                    text-gray-800
                    dark:text-gray-200
                ">
                Rejection Reason
            </label>


            <textarea
                id="relocation_rejection_reason"
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
                        $reviewErrors &&
                        $reviewAction === 'reject'
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
                ">{{ $reviewAction === 'reject'
                    ? old('review_notes')
                    : '' }}</textarea>


            @if (
                $reviewErrors &&
                $reviewAction === 'reject'
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
                    data-relocation-reject-cancel
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


{{-- Complete relocation confirmation --}}
@if ($approvedRelocation)

<div
    id="relocation-complete-modal"
    data-relocation-modal
    class="
        fixed inset-0 z-[60]
        hidden items-center justify-center
        p-3 sm:p-4
    "
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
    aria-labelledby="relocation-complete-title">

    <div
        data-relocation-complete-overlay
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
            id="relocation-complete-title"
            class="
                text-lg font-semibold
                text-gray-900
                dark:text-white
            ">
            Complete Relocation?
        </h2>


        <p
            class="
                mt-1
                text-sm text-gray-500
                dark:text-gray-400
            ">
            Confirm only after the relocation work is finished.
        </p>


        <div
            class="
                mt-4
                rounded-[2px]
                border border-gray-200
                bg-gray-50
                p-3
                dark:border-neutral-800
                dark:bg-neutral-950
            ">

            <p class="text-xs text-gray-400">
                New Installation Address
            </p>

            <p
                class="
                    mt-1
                    text-sm font-semibold
                    text-gray-900
                    dark:text-white
                ">
                {{ $approvedRelocation
                    ->requested_installation_address }}
            </p>

        </div>


        <form
            method="POST"
            action="{{ route(
                'admin.subscribers.relocation-requests.complete',
                [$subscriber, $approvedRelocation]
            ) }}"
            data-lock-submit
            class="
                mt-5 flex
                justify-end gap-2
            ">

            @csrf
            @method('PATCH')


            <button
                type="button"
                data-relocation-complete-cancel
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
                data-loading-text="Completing..."
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
                    data-lucide="circle-check"
                    class="h-4 w-4"
                    aria-hidden="true">
                </i>

                Complete

            </button>

        </form>

    </div>

</div>

@endif


<script>
    document.addEventListener('DOMContentLoaded', () => {
        const workspaceModal =
            document.getElementById(
                'relocation-workspace-modal'
            );

        const relocationOpenButton =
            document.querySelector(
                '[data-relocation-open]'
            );

        const relocationCloseButtons =
            document.querySelectorAll(
                '[data-relocation-close]'
            );

        const relocationOverlay =
            document.querySelector(
                '[data-relocation-overlay]'
            );


        const approveModal =
            document.getElementById(
                'relocation-approve-modal'
            );

        const rejectModal =
            document.getElementById(
                'relocation-reject-modal'
            );

        const completeModal =
            document.getElementById(
                'relocation-complete-modal'
            );


        const allRelocationModals =
            Array.from(
                document.querySelectorAll(
                    '[data-relocation-modal]'
                )
            );


        const anyModalOpen = () => {
            return allRelocationModals.some(
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


        relocationOpenButton?.addEventListener(
            'click',
            () => {
                openModal(
                    workspaceModal,
                    document.getElementById(
                        'requested_service_area_id'
                    )
                );
            }
        );


        relocationCloseButtons.forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        closeModal(
                            workspaceModal,
                            relocationOpenButton
                        );
                    }
                );
            }
        );


        relocationOverlay?.addEventListener(
            'click',
            () => {
                closeModal(
                    workspaceModal,
                    relocationOpenButton
                );
            }
        );


        const historyToggle =
            document.querySelector(
                '[data-relocation-history-toggle]'
            );

        const historyHide =
            document.querySelector(
                '[data-relocation-history-hide]'
            );

        const historyPanel =
            document.querySelector(
                '[data-relocation-history-panel]'
            );

        const moreMenu =
            document.getElementById(
                'relocation-more-menu'
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
                '[data-relocation-approve-open]'
            );

        const approveCancel =
            document.querySelector(
                '[data-relocation-approve-cancel]'
            );

        const approveOverlay =
            document.querySelector(
                '[data-relocation-approve-overlay]'
            );


        approveOpen?.addEventListener(
            'click',
            () => {
                closeModal(workspaceModal);

                openModal(
                    approveModal,
                    document.getElementById(
                        'relocation_approval_notes'
                    )
                );
            }
        );


        approveCancel?.addEventListener(
            'click',
            () => {
                closeModal(approveModal);
            }
        );


        approveOverlay?.addEventListener(
            'click',
            () => {
                closeModal(approveModal);
            }
        );


        const rejectOpen =
            document.querySelector(
                '[data-relocation-reject-open]'
            );

        const rejectCancel =
            document.querySelector(
                '[data-relocation-reject-cancel]'
            );

        const rejectOverlay =
            document.querySelector(
                '[data-relocation-reject-overlay]'
            );


        rejectOpen?.addEventListener(
            'click',
            () => {
                closeModal(workspaceModal);

                openModal(
                    rejectModal,
                    document.getElementById(
                        'relocation_rejection_reason'
                    )
                );
            }
        );


        rejectCancel?.addEventListener(
            'click',
            () => {
                closeModal(rejectModal);
            }
        );


        rejectOverlay?.addEventListener(
            'click',
            () => {
                closeModal(rejectModal);
            }
        );


        const completeOpen =
            document.querySelector(
                '[data-relocation-complete-open]'
            );

        const completeCancel =
            document.querySelector(
                '[data-relocation-complete-cancel]'
            );

        const completeOverlay =
            document.querySelector(
                '[data-relocation-complete-overlay]'
            );


        completeOpen?.addEventListener(
            'click',
            () => {
                closeModal(workspaceModal);
                openModal(completeModal);
            }
        );


        completeCancel?.addEventListener(
            'click',
            () => {
                closeModal(completeModal);
            }
        );


        completeOverlay?.addEventListener(
            'click',
            () => {
                closeModal(completeModal);
            }
        );


        allRelocationModals.forEach(
            (modal) => {
                if (
                    modal.dataset.openOnError ===
                    'true'
                ) {
                    openModal(modal);
                }
            }
        );


        if (
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

                const openModalElement =
                    [...allRelocationModals]
                        .reverse()
                        .find(
                            (modal) =>
                                modal.getAttribute(
                                    'aria-hidden'
                                ) === 'false'
                        );

                if (openModalElement) {
                    closeModal(
                        openModalElement
                    );
                }
            }
        );
    });
</script>
