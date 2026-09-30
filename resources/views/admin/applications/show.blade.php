@extends('layouts.app')

@section('title', 'Application Details')

@section('page-title', 'Application Details')

@section('content')

@php
    $statusDisplay = match ($application->status) {
        'pending' => [
            'label' => 'Pending',
            'icon' => 'clock-3',
            'class' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300',
        ],
        'approved' => [
            'label' => 'Approved',
            'icon' => 'circle-check',
            'class' => 'bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-300',
        ],
        'rejected' => [
            'label' => 'Rejected',
            'icon' => 'triangle-alert',
            'class' => 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300',
        ],
        default => [
            'label' => 'Cancelled',
            'icon' => 'circle-minus',
            'class' => 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400',
        ],
    };

    $fullName = trim(
        $application->first_name . ' ' .
        ($application->middle_name ? $application->middle_name . ' ' : '') .
        $application->last_name
    );

    $serviceArea = implode(', ', array_filter([
        $application->serviceArea?->barangay,
        $application->serviceArea?->city_municipality,
        $application->serviceArea?->province,
        $application->serviceArea?->postal_code,
    ]));
@endphp


<div class="space-y-2">

    {{-- Back Navigation --}}
    <a
        href="{{ route('admin.applications.index') }}"
        class="
            inline-flex min-h-8
            items-center gap-1.5
            text-sm font-medium
            text-[#008080]
            transition hover:text-[#006666]
            focus:outline-none
            focus:ring-2 focus:ring-[#008080]/30
        "
    >
        <i
            data-lucide="arrow-left"
            class="h-4 w-4"
            aria-hidden="true"
        ></i>

        Applications
    </a>


    {{-- Success Message --}}
    @if (session('success'))
    <div
        role="status"
        aria-live="polite"
        class="
            flex items-start gap-2
            border border-green-200
            bg-green-50 px-3 py-2
            text-sm text-green-800
            dark:border-green-900
            dark:bg-green-950/30
            dark:text-green-200
        "
    >
        <i
            data-lucide="circle-check"
            class="mt-0.5 h-4 w-4 shrink-0"
            aria-hidden="true"
        ></i>

        <p>{{ session('success') }}</p>
    </div>
    @endif


    {{-- Error Message --}}
    @if (session('error'))
    <div
        role="alert"
        aria-live="assertive"
        class="
            flex items-start gap-2
            border border-red-200
            bg-red-50 px-3 py-2
            text-sm text-red-800
            dark:border-red-900
            dark:bg-red-950/30
            dark:text-red-200
        "
    >
        <i
            data-lucide="triangle-alert"
            class="mt-0.5 h-4 w-4 shrink-0"
            aria-hidden="true"
        ></i>

        <p>{{ session('error') }}</p>
    </div>
    @endif


    {{-- Application Workspace --}}
    <div
        class="
            border border-gray-200
            bg-white
            dark:border-neutral-800
            dark:bg-neutral-900
        "
    >

        {{-- Record Header --}}
        <header
            class="
                border-b border-gray-200
                px-4 py-3
                dark:border-neutral-800
            "
        >

            <div
                class="
                    flex flex-col gap-3
                    lg:flex-row lg:items-center lg:justify-between
                "
            >

                <div class="min-w-0">

                    <div class="flex flex-wrap items-center gap-2">

                        <h1
                            class="
                                break-words
                                text-lg font-semibold
                                text-gray-900
                                dark:text-white
                            "
                        >
                            {{ $fullName }}
                        </h1>

                        <span
                            class="
                                inline-flex items-center gap-1.5
                                px-2 py-1
                                text-xs font-medium
                                {{ $statusDisplay['class'] }}
                            "
                        >
                            <i
                                data-lucide="{{ $statusDisplay['icon'] }}"
                                class="h-3.5 w-3.5"
                                aria-hidden="true"
                            ></i>

                            {{ $statusDisplay['label'] }}
                        </span>

                    </div>

                    <p class="mt-0.5 break-all text-xs text-gray-500 dark:text-gray-400">
                        {{ $application->application_number }}
                    </p>

                </div>


                @if ($application->status === 'pending')
                <div class="flex flex-col gap-2 sm:flex-row">

                    <button
                        type="button"
                        data-application-reject
                        class="
                            inline-flex min-h-9
                            items-center justify-center gap-1.5
                            border border-red-300
                            px-3 py-2
                            text-sm font-medium text-red-700
                            transition hover:bg-red-50
                            focus:outline-none
                            focus:ring-2 focus:ring-red-300
                            dark:border-red-800
                            dark:text-red-300
                            dark:hover:bg-red-950/30
                        "
                    >
                        <i
                            data-lucide="triangle-alert"
                            class="h-4 w-4"
                            aria-hidden="true"
                        ></i>

                        Reject
                    </button>


                    <button
                        type="button"
                        data-application-approve
                        class="
                            inline-flex min-h-9
                            items-center justify-center gap-1.5
                            bg-[#008080] px-3 py-2
                            text-sm font-semibold text-white
                            transition hover:bg-[#006666]
                            focus:outline-none
                            focus:ring-2 focus:ring-[#008080]/30
                        "
                    >
                        <i
                            data-lucide="circle-check"
                            class="h-4 w-4"
                            aria-hidden="true"
                        ></i>

                        Approve
                    </button>

                </div>
                @endif

            </div>

        </header>


        {{-- Main Workspace --}}
        <div class="grid lg:grid-cols-3">

            {{-- Left Column --}}
            <div
                class="
                    lg:col-span-2
                    border-b border-gray-200
                    lg:border-b-0 lg:border-r
                    dark:border-neutral-800
                "
            >

                {{-- Applicant --}}
                <section
                    class="
                        border-b border-gray-200
                        px-4 py-3
                        dark:border-neutral-800
                    "
                >

                    <h2
                        class="
                            mb-3
                            text-xs font-semibold uppercase tracking-wide
                            text-gray-500 dark:text-gray-400
                        "
                    >
                        Applicant
                    </h2>


                    <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">

                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Full Name
                            </dt>

                            <dd class="mt-0.5 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $fullName }}
                            </dd>
                        </div>


                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Phone
                            </dt>

                            <dd class="mt-0.5 text-sm text-gray-700 dark:text-gray-300">
                                {{ $application->phone }}
                            </dd>
                        </div>


                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Email
                            </dt>

                            <dd class="mt-0.5 break-all text-sm text-gray-700 dark:text-gray-300">
                                {{ $application->email }}
                            </dd>
                        </div>


                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Portal Account
                            </dt>

                            <dd class="mt-0.5 break-all text-sm text-gray-700 dark:text-gray-300">
                                {{ $application->user?->email ?? 'Unavailable' }}
                            </dd>
                        </div>

                    </dl>

                </section>


                {{-- Installation and Billing --}}
                <section class="px-4 py-3">

                    <h2
                        class="
                            mb-3
                            text-xs font-semibold uppercase tracking-wide
                            text-gray-500 dark:text-gray-400
                        "
                    >
                        Installation & Billing
                    </h2>


                    <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">

                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Installation Address
                            </dt>

                            <dd
                                class="
                                    mt-0.5 whitespace-pre-line
                                    text-sm leading-5
                                    text-gray-700 dark:text-gray-300
                                "
                            >
                                {{ $application->installation_address }}
                            </dd>
                        </div>


                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Verified Service Area
                            </dt>

                            <dd
                                class="
                                    mt-0.5 text-sm leading-5
                                    text-gray-700 dark:text-gray-300
                                "
                            >
                                {{ $serviceArea !== '' ? $serviceArea : 'Unavailable' }}
                            </dd>
                        </div>


                        <div class="sm:col-span-2">

                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Billing Address
                            </dt>

                            <dd
                                class="
                                    mt-0.5 whitespace-pre-line
                                    text-sm leading-5
                                    text-gray-700 dark:text-gray-300
                                "
                            >
                                {{ $application->billing_address ?: 'Not provided' }}
                            </dd>

                        </div>

                    </dl>

                </section>

            </div>


            {{-- Right Column --}}
            <div>

                {{-- Selected Plan --}}
                <section
                    class="
                        border-b border-gray-200
                        px-4 py-3
                        dark:border-neutral-800
                    "
                >

                    <h2
                        class="
                            mb-3
                            text-xs font-semibold uppercase tracking-wide
                            text-gray-500 dark:text-gray-400
                        "
                    >
                        Selected Plan
                    </h2>


                    @if ($application->servicePlan)

                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $application->servicePlan->name }}
                    </p>


                    <dl class="mt-3 space-y-2">

                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Speed
                            </dt>

                            <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ number_format((float) $application->servicePlan->speed_mbps, 0) }} Mbps
                            </dd>
                        </div>


                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Monthly Fee
                            </dt>

                            <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                &#8369;{{ number_format((float) $application->servicePlan->monthly_fee, 2) }}
                            </dd>
                        </div>


                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Duration
                            </dt>

                            <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $application->servicePlan->duration_months }}
                                {{ $application->servicePlan->duration_months === 1 ? 'month' : 'months' }}
                            </dd>
                        </div>

                    </dl>

                    @else

                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Selected service plan is unavailable.
                    </p>

                    @endif

                </section>


                {{-- Review --}}
                <section class="px-4 py-3">

                    <h2
                        class="
                            mb-3
                            text-xs font-semibold uppercase tracking-wide
                            text-gray-500 dark:text-gray-400
                        "
                    >
                        Review
                    </h2>


                    <dl class="space-y-3">

                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Submitted
                            </dt>

                            <dd class="mt-0.5 text-sm text-gray-700 dark:text-gray-300">
                                {{ $application->submitted_at?->format('M d, Y h:i A') ?? 'Not submitted' }}
                            </dd>
                        </div>


                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Reviewed
                            </dt>

                            <dd class="mt-0.5 text-sm text-gray-700 dark:text-gray-300">
                                {{ $application->reviewed_at?->format('M d, Y h:i A') ?? 'Not reviewed yet' }}
                            </dd>
                        </div>


                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">
                                Reviewed By
                            </dt>

                            <dd class="mt-0.5 text-sm text-gray-700 dark:text-gray-300">
                                {{ $application->reviewer?->name ?? 'Not assigned' }}
                            </dd>
                        </div>


                        @if ($application->status === 'rejected' && $application->rejection_reason)

                        <div class="border-t border-gray-200 pt-3 dark:border-neutral-800">

                            <dt class="text-xs font-medium text-red-600 dark:text-red-400">
                                Rejection Reason
                            </dt>

                            <dd class="mt-0.5 text-sm leading-5 text-red-700 dark:text-red-300">
                                {{ $application->rejection_reason }}
                            </dd>

                        </div>

                        @endif

                    </dl>

                </section>

            </div>

        </div>

    </div>

</div>


@if ($application->status === 'pending')

{{-- Approval Modal --}}
<div
    id="application-approve-modal"
    class="fixed inset-0 z-50 hidden items-center justify-center p-4"
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
    aria-labelledby="application-approve-title"
>
    <div
        data-application-approve-overlay
        class="absolute inset-0 bg-black/50"
    ></div>

    <div
        class="
            relative z-10 w-full max-w-md
            border border-gray-200
            bg-white p-5 shadow-xl
            dark:border-neutral-800
            dark:bg-neutral-900
        "
    >

        <div class="flex h-10 w-10 items-center justify-center bg-green-50 dark:bg-green-950/40">
            <i
                data-lucide="circle-check"
                class="h-5 w-5 text-green-600 dark:text-green-400"
                aria-hidden="true"
            ></i>
        </div>

        <h2
            id="application-approve-title"
            class="mt-3 text-base font-semibold text-gray-900 dark:text-white"
        >
            Approve Application?
        </h2>

        <p class="mt-2 text-sm leading-5 text-gray-500 dark:text-gray-400">
            You are about to approve
            <span class="font-medium text-gray-700 dark:text-gray-200">
                {{ $fullName }}
            </span>.
            This will create a pending customer record and pending subscription.
            Internet service will not be activated yet.
        </p>

        <form
            method="POST"
            action="{{ route('admin.applications.approve', $application) }}"
            data-lock-submit
            class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"
        >
            @csrf

            <button
                type="button"
                data-application-approve-cancel
                class="
                    min-h-10
                    border border-gray-300
                    px-4 py-2
                    text-sm font-medium text-gray-700
                    transition hover:bg-gray-50
                    focus:outline-none focus:ring-2 focus:ring-gray-300
                    dark:border-neutral-700
                    dark:text-gray-300
                    dark:hover:bg-neutral-800
                "
            >
                Cancel
            </button>

            <button
                type="submit"
                data-loading-text="Approving..."
                class="
                    inline-flex min-h-10
                    items-center justify-center gap-2
                    bg-[#008080] px-4 py-2
                    text-sm font-semibold text-white
                    transition hover:bg-[#006666]
                    focus:outline-none
                    focus:ring-2 focus:ring-[#008080]/30
                "
            >
                <i
                    data-lucide="circle-check"
                    class="h-4 w-4"
                    aria-hidden="true"
                ></i>

                Approve Application
            </button>

        </form>

    </div>
</div>


{{-- Rejection Modal --}}
<div
    id="application-reject-modal"
    class="fixed inset-0 z-50 hidden items-center justify-center p-4"
    data-open-on-error="{{ $errors->has('rejection_reason') ? 'true' : 'false' }}"
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
    aria-labelledby="application-reject-title"
>
    <div
        data-application-reject-overlay
        class="absolute inset-0 bg-black/50"
    ></div>

    <div
        class="
            relative z-10 w-full max-w-md
            border border-gray-200
            bg-white p-5 shadow-xl
            dark:border-neutral-800
            dark:bg-neutral-900
        "
    >

        <div class="flex h-10 w-10 items-center justify-center bg-red-50 dark:bg-red-950/40">
            <i
                data-lucide="triangle-alert"
                class="h-5 w-5 text-red-600 dark:text-red-400"
                aria-hidden="true"
            ></i>
        </div>

        <h2
            id="application-reject-title"
            class="mt-3 text-base font-semibold text-gray-900 dark:text-white"
        >
            Reject Application?
        </h2>

        <p class="mt-2 text-sm leading-5 text-gray-500 dark:text-gray-400">
            Rejecting
            <span class="font-medium text-gray-700 dark:text-gray-200">
                {{ $fullName }}
            </span>
            will close this application without creating customer or subscription records.
        </p>

        <form
            method="POST"
            action="{{ route('admin.applications.reject', $application) }}"
            data-lock-submit
            class="mt-5"
        >
            @csrf

            <label
                for="rejection_reason"
                class="text-sm font-medium text-gray-900 dark:text-white"
            >
                Rejection Reason
            </label>

            <textarea
                id="rejection_reason"
                name="rejection_reason"
                rows="4"
                maxlength="1000"
                required
                class="
                    mt-2 block w-full
                    border bg-white
                    px-3 py-2.5
                    text-sm text-gray-900
                    outline-none transition
                    placeholder:text-gray-400
                    focus:ring-2
                    dark:bg-neutral-950
                    dark:text-white

                    @error('rejection_reason')
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
                "
                placeholder="Explain why this application is being rejected."
            >{{ old('rejection_reason') }}</textarea>

            @error('rejection_reason')
            <p class="mt-1.5 flex items-start gap-1.5 text-xs text-red-600 dark:text-red-400">
                <i
                    data-lucide="triangle-alert"
                    class="mt-0.5 h-3.5 w-3.5 shrink-0"
                    aria-hidden="true"
                ></i>

                <span>{{ $message }}</span>
            </p>
            @enderror

            <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">

                <button
                    type="button"
                    data-application-reject-cancel
                    class="
                        min-h-10
                        border border-gray-300
                        px-4 py-2
                        text-sm font-medium text-gray-700
                        transition hover:bg-gray-50
                        focus:outline-none focus:ring-2 focus:ring-gray-300
                        dark:border-neutral-700
                        dark:text-gray-300
                        dark:hover:bg-neutral-800
                    "
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    data-loading-text="Rejecting..."
                    class="
                        inline-flex min-h-10
                        items-center justify-center gap-2
                        bg-red-600 px-4 py-2
                        text-sm font-semibold text-white
                        transition hover:bg-red-700
                        focus:outline-none
                        focus:ring-2 focus:ring-red-300
                        dark:focus:ring-red-900
                    "
                >
                    <i
                        data-lucide="triangle-alert"
                        class="h-4 w-4"
                        aria-hidden="true"
                    ></i>

                    Reject Application
                </button>

            </div>

        </form>

    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', () => {

        const approveModal = document.getElementById('application-approve-modal');
        const approveOpenButton = document.querySelector('[data-application-approve]');
        const approveCancelButton = document.querySelector('[data-application-approve-cancel]');
        const approveOverlay = document.querySelector('[data-application-approve-overlay]');

        const rejectModal = document.getElementById('application-reject-modal');
        const rejectOpenButton = document.querySelector('[data-application-reject]');
        const rejectCancelButton = document.querySelector('[data-application-reject-cancel]');
        const rejectOverlay = document.querySelector('[data-application-reject-overlay]');
        const rejectionReason = document.getElementById('rejection_reason');


        const openModal = (modal, focusTarget) => {

            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.setAttribute('aria-hidden', 'false');

            document.body.classList.add('overflow-hidden');

            focusTarget?.focus();
        };


        const closeModal = (modal, returnTarget) => {

            if (!modal) {
                return;
            }

            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modal.setAttribute('aria-hidden', 'true');

            document.body.classList.remove('overflow-hidden');

            returnTarget?.focus();
        };


        approveOpenButton?.addEventListener('click', () => {
            openModal(approveModal, approveCancelButton);
        });


        approveCancelButton?.addEventListener('click', () => {
            closeModal(approveModal, approveOpenButton);
        });


        approveOverlay?.addEventListener('click', () => {
            closeModal(approveModal, approveOpenButton);
        });


        rejectOpenButton?.addEventListener('click', () => {
            openModal(rejectModal, rejectionReason);
        });


        rejectCancelButton?.addEventListener('click', () => {
            closeModal(rejectModal, rejectOpenButton);
        });


        rejectOverlay?.addEventListener('click', () => {
            closeModal(rejectModal, rejectOpenButton);
        });


        document.addEventListener('keydown', (event) => {

            if (event.key !== 'Escape') {
                return;
            }

            if (
                approveModal?.getAttribute('aria-hidden') === 'false'
            ) {
                closeModal(approveModal, approveOpenButton);
                return;
            }

            if (
                rejectModal?.getAttribute('aria-hidden') === 'false'
            ) {
                closeModal(rejectModal, rejectOpenButton);
            }

        });


        if (
            rejectModal?.dataset.openOnError === 'true'
        ) {
            openModal(rejectModal, rejectionReason);
        }

    });
</script>

@endif

@endsection
