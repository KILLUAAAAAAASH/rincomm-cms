@extends('layouts.app')

@section('title', $jobOrder->job_order_number . ' | Rincomm CMS')
@section('page-title', 'Job Order')

@section('secondary-navigation')
<nav
    class="flex min-w-max items-center gap-1 overflow-x-auto py-1.5"
    aria-label="Technician navigation">

    <a
        href="{{ route('technician.dashboard') }}"
        class="
                inline-flex min-h-8 items-center justify-center
                whitespace-nowrap  px-3 py-1.5
                text-xs font-medium transition sm:text-sm
                text-neutral-600
                hover:bg-[#008080]/10 hover:text-[#008080]
                dark:text-neutral-400
                dark:hover:bg-[#008080]/15 dark:hover:text-[#5EEAD4]
            ">
        Dashboard
    </a>

    <a
        href="{{ route('technician.job-orders.index') }}"
        class="
                inline-flex min-h-8 items-center justify-center
                whitespace-nowrap  bg-[#008080]
                px-3 py-1.5 text-xs font-medium text-white
                shadow-sm transition sm:text-sm
            ">
        Job Orders
    </a>

</nav>
@endsection

@section('content')

@php
$customerName = trim(
collect([
$jobOrder->customer?->first_name,
$jobOrder->customer?->middle_name,
$jobOrder->customer?->last_name,
])
->filter()
->implode(' ')
);

$jobTypeLabel = $jobOrder->job_type
? \Illuminate\Support\Str::headline($jobOrder->job_type)
: 'Type Not Set';

$statusClasses = match ($jobOrder->status) {
'assigned' =>
'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-950/40 dark:text-blue-300 dark:ring-blue-400/30',

'in_progress' =>
'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-400/30',

'completed' =>
'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-400/30',

'cancelled' =>
'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-950/40 dark:text-red-300 dark:ring-red-400/30',

default =>
'bg-neutral-100 text-neutral-700 ring-neutral-600/20 dark:bg-neutral-800 dark:text-neutral-300 dark:ring-neutral-500/30',
};
@endphp


<div class="mx-auto w-full max-w-6xl space-y-4">

    {{-- Back + heading --}}
    <div>
        <a
            href="{{ route('technician.job-orders.index') }}"
            class="
                    inline-flex min-h-9 items-center gap-1.5
                     px-2
                    text-sm font-medium
                    text-neutral-600
                    transition
                    hover:bg-[#008080]/10
                    hover:text-[#008080]
                    dark:text-neutral-400
                    dark:hover:bg-[#008080]/15
                    dark:hover:text-[#5EEAD4]
                ">

            <i
                data-lucide="arrow-left"
                class="h-4 w-4"
                aria-hidden="true">
            </i>

            Back to Job Orders

        </a>


        <div
            class="
                    mt-2 flex flex-col gap-3
                    sm:flex-row
                    sm:items-start
                    sm:justify-between
                ">

            <div class="min-w-0">

                <div class="flex flex-wrap items-center gap-2">

                    <h1
                        class="
                                text-xl font-bold
                                text-neutral-900
                                dark:text-white
                            ">
                        {{ $jobOrder->job_order_number }}
                    </h1>

                    <span
                        class="
                                inline-flex items-center

                                px-2.5 py-1
                                text-xs font-semibold
                                ring-1 ring-inset
                                {{ $statusClasses }}
                            ">

                        {{ \Illuminate\Support\Str::headline(
                                $jobOrder->status
                            ) }}

                    </span>

                </div>

                <p
                    class="
                            mt-1 text-sm
                            text-neutral-500
                            dark:text-neutral-400
                        ">
                    {{ $jobTypeLabel }}
                </p>

            </div>


            @if ($jobOrder->status === 'assigned')

            <form
                method="POST"
                action="{{ route(
                            'technician.job-orders.start',
                            $jobOrder
                        ) }}"
                data-lock-submit>

                @csrf
                @method('PATCH')

                <button
                    type="submit"
                    @disabled($jobOrder->job_type === null)
                    class="
                    inline-flex min-h-11 w-full
                    items-center justify-center gap-2

                    bg-[#008080]
                    px-4 py-2.5
                    text-sm font-semibold
                    text-white
                    transition
                    hover:bg-[#006f6f]
                    focus:outline-none
                    focus:ring-2
                    focus:ring-[#008080]/30
                    disabled:cursor-not-allowed
                    disabled:bg-neutral-300
                    disabled:text-neutral-500
                    dark:disabled:bg-neutral-700
                    dark:disabled:text-neutral-400
                    sm:w-auto
                    ">

                    <i
                        data-lucide="play"
                        class="h-4 w-4"
                        aria-hidden="true">
                    </i>

                    Start Job

                </button>

            </form>

            @endif

        </div>
    </div>


    {{-- Success --}}
    @if (session('success'))

    <div
        class="
                    flex items-start gap-3

                    border border-emerald-200
                    bg-emerald-50
                    px-4 py-3
                    text-sm text-emerald-800
                    dark:border-emerald-900
                    dark:bg-emerald-950/40
                    dark:text-emerald-300
                "
        role="status">

        <i
            data-lucide="circle-check"
            class="mt-0.5 h-4 w-4 shrink-0"
            aria-hidden="true">
        </i>

        <span>
            {{ session('success') }}
        </span>

    </div>

    @endif


    {{-- Workflow errors --}}
    @error('job_order')

    <div
        class="
                    flex items-start gap-3

                    border border-red-200
                    bg-red-50
                    px-4 py-3
                    text-sm text-red-700
                    dark:border-red-900
                    dark:bg-red-950/40
                    dark:text-red-300
                "
        role="alert">

        <i
            data-lucide="triangle-alert"
            class="mt-0.5 h-4 w-4 shrink-0"
            aria-hidden="true">
        </i>

        <span>{{ $message }}</span>

    </div>

    @enderror


    @if ($jobOrder->job_type === null)

    <div
        class="
                    flex items-start gap-3

                    border border-amber-200
                    bg-amber-50
                    px-4 py-3
                    text-sm text-amber-800
                    dark:border-amber-900
                    dark:bg-amber-950/40
                    dark:text-amber-300
                ">

        <i
            data-lucide="triangle-alert"
            class="mt-0.5 h-4 w-4 shrink-0"
            aria-hidden="true">
        </i>

        <div>
            <p class="font-semibold">
                Job type has not been assigned.
            </p>

            <p class="mt-0.5 text-xs leading-5">
                This Job Order cannot be started until an administrator
                or staff member assigns its field-work type.
            </p>
        </div>

    </div>

    @endif


    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_22rem]">

        {{-- Main column --}}
        <div class="space-y-4">

            {{-- Job information --}}
            <section
                class="
                        overflow-hidden
                        border border-neutral-200
                        bg-white
                        dark:border-neutral-800
                        dark:bg-neutral-900
                    ">

                <div
                    class="
                            border-b border-neutral-200
                            px-4 py-3
                            dark:border-neutral-800
                        ">

                    <h2
                        class="
                                text-sm font-semibold
                                text-neutral-900
                                dark:text-white
                            ">
                        Job Details
                    </h2>

                </div>


                <dl
                    class="
                            grid gap-x-6 gap-y-4
                            p-4
                            sm:grid-cols-2
                        ">

                    <div>
                        <dt
                            class="
                                    text-xs font-medium
                                    text-neutral-500
                                    dark:text-neutral-400
                                ">
                            Job Type
                        </dt>

                        <dd
                            class="
                                    mt-1 text-sm font-medium
                                    text-neutral-900
                                    dark:text-neutral-100
                                ">
                            {{ $jobTypeLabel }}
                        </dd>
                    </div>


                    <div>
                        <dt
                            class="
                                    text-xs font-medium
                                    text-neutral-500
                                    dark:text-neutral-400
                                ">
                            Schedule
                        </dt>

                        <dd
                            class="
                                    mt-1 text-sm
                                    text-neutral-900
                                    dark:text-neutral-100
                                ">

                            @if ($jobOrder->scheduled_date)

                            {{ $jobOrder->scheduled_date->format('M j, Y') }}

                            @if ($jobOrder->scheduled_time)
                            at
                            {{ \Carbon\Carbon::parse(
                                            $jobOrder->scheduled_time
                                        )->format('g:i A') }}
                            @endif

                            @else
                            Not scheduled
                            @endif

                        </dd>
                    </div>


                    <div>
                        <dt
                            class="
                                    text-xs font-medium
                                    text-neutral-500
                                    dark:text-neutral-400
                                ">
                            Service Request
                        </dt>

                        <dd
                            class="
                                    mt-1 text-sm
                                    text-neutral-900
                                    dark:text-neutral-100
                                ">
                            {{ $jobOrder->serviceRequest?->ticket_number
                                    ?: 'Not available'
                                }}
                        </dd>
                    </div>


                    <div>
                        <dt
                            class="
                                    text-xs font-medium
                                    text-neutral-500
                                    dark:text-neutral-400
                                ">
                            Request Type
                        </dt>

                        <dd
                            class="
                                    mt-1 text-sm
                                    text-neutral-900
                                    dark:text-neutral-100
                                ">
                            {{ $jobOrder->serviceRequest?->request_type
                                    ? \Illuminate\Support\Str::headline(
                                        $jobOrder->serviceRequest->request_type
                                    )
                                    : 'Not available'
                                }}
                        </dd>
                    </div>


                    @if ($jobOrder->started_at)

                    <div>
                        <dt
                            class="
                                        text-xs font-medium
                                        text-neutral-500
                                        dark:text-neutral-400
                                    ">
                            Started
                        </dt>

                        <dd
                            class="
                                        mt-1 text-sm
                                        text-neutral-900
                                        dark:text-neutral-100
                                    ">
                            {{ $jobOrder->started_at->format(
                                        'M j, Y g:i A'
                                    ) }}
                        </dd>
                    </div>

                    @endif


                    @if ($jobOrder->completed_at)

                    <div>
                        <dt
                            class="
                                        text-xs font-medium
                                        text-neutral-500
                                        dark:text-neutral-400
                                    ">
                            Completed
                        </dt>

                        <dd
                            class="
                                        mt-1 text-sm
                                        text-neutral-900
                                        dark:text-neutral-100
                                    ">
                            {{ $jobOrder->completed_at->format(
                                        'M j, Y g:i A'
                                    ) }}
                        </dd>
                    </div>

                    @endif

                </dl>


                @if ($jobOrder->description)

                <div
                    class="
                                border-t border-neutral-200
                                px-4 py-4
                                dark:border-neutral-800
                            ">

                    <p
                        class="
                                    text-xs font-medium
                                    text-neutral-500
                                    dark:text-neutral-400
                                ">
                        Work Description
                    </p>

                    <p
                        class="
                                    mt-1 whitespace-pre-line
                                    text-sm leading-6
                                    text-neutral-800
                                    dark:text-neutral-200
                                ">
                        {{ $jobOrder->description }}
                    </p>

                </div>

                @endif


                @if ($jobOrder->remarks)

                <div
                    class="
                                border-t border-neutral-200
                                px-4 py-4
                                dark:border-neutral-800
                            ">

                    <p
                        class="
                                    text-xs font-medium
                                    text-neutral-500
                                    dark:text-neutral-400
                                ">
                        Remarks
                    </p>

                    <p
                        class="
                                    mt-1 whitespace-pre-line
                                    text-sm leading-6
                                    text-neutral-800
                                    dark:text-neutral-200
                                ">
                        {{ $jobOrder->remarks }}
                    </p>

                </div>

                @endif

            </section>


            {{-- Customer --}}
            <section
                class="
                        overflow-hidden
                        border border-neutral-200
                        bg-white
                        dark:border-neutral-800
                        dark:bg-neutral-900
                    ">

                <div
                    class="
                            border-b border-neutral-200
                            px-4 py-3
                            dark:border-neutral-800
                        ">

                    <h2
                        class="
                                text-sm font-semibold
                                text-neutral-900
                                dark:text-white
                            ">
                        Customer & Location
                    </h2>

                </div>


                <div class="space-y-4 p-4">

                    <div class="flex items-start gap-3">

                        <i
                            data-lucide="user"
                            class="
                                    mt-0.5 h-4 w-4 shrink-0
                                    text-neutral-400
                                "
                            aria-hidden="true">
                        </i>

                        <div class="min-w-0">

                            <p
                                class="
                                        text-xs font-medium
                                        text-neutral-500
                                        dark:text-neutral-400
                                    ">
                                Customer
                            </p>

                            <p
                                class="
                                        mt-0.5 text-sm font-semibold
                                        text-neutral-900
                                        dark:text-white
                                    ">
                                {{ $customerName !== ''
                                        ? $customerName
                                        : 'Customer information unavailable'
                                    }}
                            </p>

                            @if ($jobOrder->customer?->customer_code)

                            <p
                                class="
                                            mt-0.5 text-xs
                                            text-neutral-500
                                            dark:text-neutral-400
                                        ">
                                {{ $jobOrder->customer->customer_code }}
                            </p>

                            @endif

                        </div>

                    </div>


                    @if ($jobOrder->customer?->phone)

                    <div class="flex items-start gap-3">

                        <i
                            data-lucide="phone"
                            class="
                                        mt-0.5 h-4 w-4 shrink-0
                                        text-neutral-400
                                    "
                            aria-hidden="true">
                        </i>

                        <div>

                            <p
                                class="
                                            text-xs font-medium
                                            text-neutral-500
                                            dark:text-neutral-400
                                        ">
                                Phone
                            </p>

                            <a
                                href="tel:{{ $jobOrder->customer->phone }}"
                                class="
                                            mt-0.5 inline-block
                                            text-sm font-medium
                                            text-[#008080]
                                            hover:underline
                                            dark:text-[#5EEAD4]
                                        ">
                                {{ $jobOrder->customer->phone }}
                            </a>

                        </div>

                    </div>

                    @endif


                    @if ($jobOrder->customer?->installation_address)

                    <div class="flex items-start gap-3">

                        <i
                            data-lucide="map-pin"
                            class="
                                        mt-0.5 h-4 w-4 shrink-0
                                        text-neutral-400
                                    "
                            aria-hidden="true">
                        </i>

                        <div>

                            <p
                                class="
                                            text-xs font-medium
                                            text-neutral-500
                                            dark:text-neutral-400
                                        ">
                                Installation Address
                            </p>

                            <p
                                class="
                                            mt-0.5 text-sm leading-6
                                            text-neutral-800
                                            dark:text-neutral-200
                                        ">
                                {{ $jobOrder->customer->installation_address }}
                            </p>

                        </div>

                    </div>

                    @elseif ($jobOrder->customer?->address)

                    <div class="flex items-start gap-3">

                        <i
                            data-lucide="map-pin"
                            class="
                                        mt-0.5 h-4 w-4 shrink-0
                                        text-neutral-400
                                    "
                            aria-hidden="true">
                        </i>

                        <div>

                            <p
                                class="
                                            text-xs font-medium
                                            text-neutral-500
                                            dark:text-neutral-400
                                        ">
                                Customer Address
                            </p>

                            <p
                                class="
                                            mt-0.5 text-sm leading-6
                                            text-neutral-800
                                            dark:text-neutral-200
                                        ">
                                {{ $jobOrder->customer->address }}
                            </p>

                        </div>

                    </div>

                    @endif

                </div>

            </section>


            {{-- Proof of work --}}
            <section
                class="
                        overflow-hidden
                        border border-neutral-200
                        bg-white
                        dark:border-neutral-800
                        dark:bg-neutral-900
                    ">

                <div
                    class="
                            flex items-center justify-between gap-3
                            border-b border-neutral-200
                            px-4 py-3
                            dark:border-neutral-800
                        ">

                    <div>
                        <h2
                            class="
                                    text-sm font-semibold
                                    text-neutral-900
                                    dark:text-white
                                ">
                            Proof of Work
                        </h2>

                        <p
                            class="
                                    mt-0.5 text-xs
                                    text-neutral-500
                                    dark:text-neutral-400
                                ">
                            {{ $jobOrder->proofs->count() }}
                            {{ \Illuminate\Support\Str::plural(
                                    'file',
                                    $jobOrder->proofs->count()
                                ) }}
                        </p>
                    </div>

                    <i
                        data-lucide="camera"
                        class="
                                h-4 w-4
                                text-neutral-400
                            "
                        aria-hidden="true">
                    </i>

                </div>


                @if ($jobOrder->status === 'in_progress')

                <form
                    method="POST"
                    action="{{ route(
                                'technician.job-orders.proofs.store',
                                $jobOrder
                            ) }}"
                    enctype="multipart/form-data"
                    class="
                                border-b border-neutral-200
                                p-4
                                dark:border-neutral-800
                            "
                    data-lock-submit>

                    @csrf

                    <div class="space-y-4">

                        <div>

                            <label
                                for="proof"
                                class="
                                            block text-sm font-semibold
                                            text-neutral-800
                                            dark:text-neutral-200
                                        ">
                                Add Proof File
                            </label>

                            <p
                                class="
                                            mt-0.5 text-xs
                                            text-neutral-500
                                            dark:text-neutral-400
                                        ">
                                PDF, JPG, JPEG, or PNG. Maximum 5 MB.
                            </p>

                            <input
                                id="proof"
                                name="proof"
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                required
                                class="
                                            mt-2 block w-full

                                            border
                                            bg-white
                                            px-3 py-2.5
                                            text-sm
                                            text-neutral-800
                                            file:mr-3

                                            file:border-0
                                            file:bg-[#008080]/10
                                            file:px-3
                                            file:py-1.5
                                            file:text-xs
                                            file:font-semibold
                                            file:text-[#008080]
                                            hover:file:bg-[#008080]/15
                                            dark:bg-neutral-950
                                            dark:text-neutral-200
                                            dark:file:bg-[#008080]/20
                                            dark:file:text-[#5EEAD4]

                                            {{ $errors->has('proof')
                                                ? 'border-red-500 ring-1 ring-red-500/20'
                                                : 'border-neutral-300 dark:border-neutral-700'
                                            }}
                                        ">

                            @error('proof')

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

                                {{ $message }}

                            </p>

                            @enderror

                        </div>


                        <div>

                            <label
                                for="notes"
                                class="
                                            block text-sm font-semibold
                                            text-neutral-800
                                            dark:text-neutral-200
                                        ">
                                Notes
                                <span
                                    class="
                                                font-normal
                                                text-neutral-400
                                            ">
                                    (optional)
                                </span>
                            </label>

                            <textarea
                                id="notes"
                                name="notes"
                                rows="3"
                                maxlength="1000"
                                placeholder="Describe what this proof shows..."
                                class="
                                            mt-2 block w-full
                                            resize-y
                                            border
                                            bg-white
                                            px-3 py-2.5
                                            text-sm
                                            text-neutral-900
                                            outline-none
                                            transition
                                            placeholder:text-neutral-400
                                            focus:border-[#008080]
                                            focus:ring-2
                                            focus:ring-[#008080]/20
                                            dark:bg-neutral-950
                                            dark:text-white
                                            dark:placeholder:text-neutral-500

                                            {{ $errors->has('notes')
                                                ? 'border-red-500 ring-1 ring-red-500/20'
                                                : 'border-neutral-300 dark:border-neutral-700'
                                            }}
                                        ">{{ old('notes') }}</textarea>

                            @error('notes')

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

                                {{ $message }}

                            </p>

                            @enderror

                        </div>


                        <button
                            type="submit"
                            class="
                                        inline-flex min-h-10 w-full
                                        items-center justify-center gap-2

                                        border border-[#008080]
                                        px-4 py-2
                                        text-sm font-semibold
                                        text-[#008080]
                                        transition
                                        hover:bg-[#008080]/10
                                        focus:outline-none
                                        focus:ring-2
                                        focus:ring-[#008080]/20
                                        dark:border-[#14B8A6]
                                        dark:text-[#5EEAD4]
                                        dark:hover:bg-[#008080]/15
                                        sm:w-auto
                                    ">

                            <i
                                data-lucide="upload"
                                class="h-4 w-4"
                                aria-hidden="true">
                            </i>

                            Upload Proof

                        </button>

                    </div>

                </form>

                @endif


                @if ($jobOrder->proofs->isEmpty())

                <div class="px-4 py-8 text-center">

                    <p
                        class="
                                    text-sm font-medium
                                    text-neutral-700
                                    dark:text-neutral-300
                                ">
                        No proof uploaded yet.
                    </p>

                    @if ($jobOrder->status === 'in_progress')

                    <p
                        class="
                                        mt-1 text-xs
                                        text-neutral-500
                                        dark:text-neutral-400
                                    ">
                        At least one proof file is required before completion.
                    </p>

                    @endif

                </div>

                @else

                <div
                    class="
                                divide-y divide-neutral-200
                                dark:divide-neutral-800
                            ">

                    @foreach ($jobOrder->proofs as $proof)

                    <div class="p-4">

                        <div
                            class="
                                            flex flex-col gap-3
                                            sm:flex-row
                                            sm:items-start
                                            sm:justify-between
                                        ">

                            <div class="flex min-w-0 items-start gap-3">

                                <div
                                    class="
                                                    flex h-9 w-9 shrink-0
                                                    items-center justify-center

                                                    bg-[#008080]/10
                                                    text-[#008080]
                                                    dark:bg-[#008080]/20
                                                    dark:text-[#5EEAD4]
                                                ">

                                    <i
                                        data-lucide="file-check"
                                        class="h-4 w-4"
                                        aria-hidden="true">
                                    </i>

                                </div>


                                <div class="min-w-0">

                                    <p
                                        class="
                                                        break-all
                                                        text-sm font-semibold
                                                        text-neutral-900
                                                        dark:text-white
                                                    ">
                                        {{ $proof->original_name }}
                                    </p>

                                    <p
                                        class="
                                                        mt-0.5 text-xs
                                                        text-neutral-500
                                                        dark:text-neutral-400
                                                    ">
                                        {{ number_format(
                                                        $proof->file_size / 1024,
                                                        1
                                                    ) }}
                                        KB
                                        ·
                                        {{ $proof->created_at?->format(
                                                        'M j, Y g:i A'
                                                    ) }}
                                    </p>

                                    @if ($proof->notes)

                                    <p
                                        class="
                                                            mt-2 whitespace-pre-line
                                                            text-sm leading-5
                                                            text-neutral-700
                                                            dark:text-neutral-300
                                                        ">
                                        {{ $proof->notes }}
                                    </p>

                                    @endif

                                </div>

                            </div>


                            <div
                                class="
                                                flex shrink-0
                                                flex-wrap gap-2
                                            ">

                                <a
                                    href="{{ route(
                                                    'technician.job-orders.proofs.show',
                                                    [$jobOrder, $proof]
                                                ) }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="
                                                    inline-flex min-h-9
                                                    items-center justify-center
                                                    gap-1.5
                                                    border border-neutral-200
                                                    px-3 py-2
                                                    text-xs font-semibold
                                                    text-neutral-700
                                                    transition
                                                    hover:border-[#008080]
                                                    hover:text-[#008080]
                                                    dark:border-neutral-700
                                                    dark:text-neutral-300
                                                    dark:hover:border-[#14B8A6]
                                                    dark:hover:text-[#5EEAD4]
                                                ">

                                    <i
                                        data-lucide="eye"
                                        class="h-3.5 w-3.5"
                                        aria-hidden="true">
                                    </i>

                                    View

                                </a>


                                <a
                                    href="{{ route(
                                                    'technician.job-orders.proofs.download',
                                                    [$jobOrder, $proof]
                                                ) }}"
                                    class="
                                                    inline-flex min-h-9
                                                    items-center justify-center
                                                    gap-1.5
                                                    border border-neutral-200
                                                    px-3 py-2
                                                    text-xs font-semibold
                                                    text-neutral-700
                                                    transition
                                                    hover:border-[#008080]
                                                    hover:text-[#008080]
                                                    dark:border-neutral-700
                                                    dark:text-neutral-300
                                                    dark:hover:border-[#14B8A6]
                                                    dark:hover:text-[#5EEAD4]
                                                ">

                                    <i
                                        data-lucide="download"
                                        class="h-3.5 w-3.5"
                                        aria-hidden="true">
                                    </i>

                                    Download

                                </a>


                                @if ($jobOrder->status === 'in_progress')

                                <form
                                    method="POST"
                                    action="{{ route(
                                                        'technician.job-orders.proofs.destroy',
                                                        [$jobOrder, $proof]
                                                    ) }}"
                                    data-lock-submit
                                    onsubmit="return confirm('Delete this proof-of-work file? This action cannot be undone.');">

                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="
                                                            inline-flex min-h-9
                                                            items-center justify-center
                                                            gap-1.5
                                                            border border-red-200
                                                            px-3 py-2
                                                            text-xs font-semibold
                                                            text-red-600
                                                            transition
                                                            hover:bg-red-50
                                                            focus:outline-none
                                                            focus:ring-2
                                                            focus:ring-red-500/20
                                                            dark:border-red-900
                                                            dark:text-red-400
                                                            dark:hover:bg-red-950/40
                                                        ">

                                        <i
                                            data-lucide="trash-2"
                                            class="h-3.5 w-3.5"
                                            aria-hidden="true">
                                        </i>

                                        Delete

                                    </button>

                                </form>

                                @endif

                            </div>

                        </div>

                    </div>

                    @endforeach

                </div>

                @endif

            </section>


            {{-- Notes --}}
            @if ($jobOrder->notes->isNotEmpty())

            <section
                class="
                            overflow-hidden
                            border border-neutral-200
                            bg-white
                            dark:border-neutral-800
                            dark:bg-neutral-900
                        ">

                <div
                    class="
                                border-b border-neutral-200
                                px-4 py-3
                                dark:border-neutral-800
                            ">

                    <h2
                        class="
                                    text-sm font-semibold
                                    text-neutral-900
                                    dark:text-white
                                ">
                        Job Notes
                    </h2>

                </div>


                <div
                    class="
                                divide-y divide-neutral-200
                                dark:divide-neutral-800
                            ">

                    @foreach ($jobOrder->notes as $note)

                    <div class="p-4">

                        <p
                            class="
                                            whitespace-pre-line
                                            text-sm leading-6
                                            text-neutral-800
                                            dark:text-neutral-200
                                        ">
                            {{ $note->note }}
                        </p>

                        <p
                            class="
                                            mt-2 text-xs
                                            text-neutral-500
                                            dark:text-neutral-400
                                        ">
                            {{ $note->user?->name ?: 'System User' }}

                            @if ($note->created_at)
                            ·
                            {{ $note->created_at->format(
                                                'M j, Y g:i A'
                                            ) }}
                            @endif
                        </p>

                    </div>

                    @endforeach

                </div>

            </section>

            @endif

        </div>


        {{-- Right workflow column --}}
        <aside class="space-y-4">

            {{-- Progress --}}
            <section
                class="

                        border border-neutral-200
                        bg-white
                        p-4
                        dark:border-neutral-800
                        dark:bg-neutral-900
                    ">

                <h2
                    class="
                            text-sm font-semibold
                            text-neutral-900
                            dark:text-white
                        ">
                    Work Progress
                </h2>


                <ol class="mt-4 space-y-4">

                    <li class="flex gap-3">

                        <div
                            class="
                                    flex h-7 w-7 shrink-0
                                    items-center justify-center

                                    bg-[#008080]
                                    text-white
                                ">
                            <i
                                data-lucide="check"
                                class="h-3.5 w-3.5"
                                aria-hidden="true">
                            </i>
                        </div>

                        <div>
                            <p
                                class="
                                        text-sm font-semibold
                                        text-neutral-900
                                        dark:text-white
                                    ">
                                Assigned
                            </p>

                            <p
                                class="
                                        mt-0.5 text-xs
                                        text-neutral-500
                                        dark:text-neutral-400
                                    ">
                                Job Order assigned to your account.
                            </p>
                        </div>

                    </li>


                    <li class="flex gap-3">

                        <div
                            class="
                                    flex h-7 w-7 shrink-0
                                    items-center justify-center


                                    {{ in_array(
                                        $jobOrder->status,
                                        ['in_progress', 'completed'],
                                        true
                                    )
                                        ? 'bg-[#008080] text-white'
                                        : 'bg-neutral-100 text-neutral-400 dark:bg-neutral-800'
                                    }}
                                ">

                            @if (
                            in_array(
                            $jobOrder->status,
                            ['in_progress', 'completed'],
                            true
                            )
                            )

                            <i
                                data-lucide="check"
                                class="h-3.5 w-3.5"
                                aria-hidden="true">
                            </i>

                            @else

                            <span class="text-xs font-bold">
                                2
                            </span>

                            @endif

                        </div>

                        <div>
                            <p
                                class="
                                        text-sm font-semibold
                                        text-neutral-900
                                        dark:text-white
                                    ">
                                Work In Progress
                            </p>

                            <p
                                class="
                                        mt-0.5 text-xs
                                        text-neutral-500
                                        dark:text-neutral-400
                                    ">
                                Start the field work and upload proof.
                            </p>
                        </div>

                    </li>


                    <li class="flex gap-3">

                        <div
                            class="
                                    flex h-7 w-7 shrink-0
                                    items-center justify-center


                                    {{ $jobOrder->status === 'completed'
                                        ? 'bg-[#008080] text-white'
                                        : 'bg-neutral-100 text-neutral-400 dark:bg-neutral-800'
                                    }}
                                ">

                            @if ($jobOrder->status === 'completed')

                            <i
                                data-lucide="check"
                                class="h-3.5 w-3.5"
                                aria-hidden="true">
                            </i>

                            @else

                            <span class="text-xs font-bold">
                                3
                            </span>

                            @endif

                        </div>

                        <div>
                            <p
                                class="
                                        text-sm font-semibold
                                        text-neutral-900
                                        dark:text-white
                                    ">
                                Completed
                            </p>

                            <p
                                class="
                                        mt-0.5 text-xs
                                        text-neutral-500
                                        dark:text-neutral-400
                                    ">
                                Completion report and proof retained.
                            </p>
                        </div>

                    </li>

                </ol>

            </section>


            {{-- Completion --}}
            @if ($jobOrder->status === 'in_progress')

            <section
                class="

                            border border-neutral-200
                            bg-white
                            p-4
                            dark:border-neutral-800
                            dark:bg-neutral-900
                        ">

                <h2
                    class="
                                text-sm font-semibold
                                text-neutral-900
                                dark:text-white
                            ">
                    Complete Job
                </h2>

                <p
                    class="
                                mt-1 text-xs leading-5
                                text-neutral-500
                                dark:text-neutral-400
                            ">
                    A completion report and at least one proof-of-work
                    file are required.
                </p>


                <form
                    method="POST"
                    action="{{ route(
                                'technician.job-orders.complete',
                                $jobOrder
                            ) }}"
                    class="mt-4"
                    data-lock-submit
                    onsubmit="return confirm('Mark this Job Order as completed? Proof files will become read-only.');">

                    @csrf
                    @method('PATCH')


                    <label
                        for="completion_report"
                        class="
                                    block text-sm font-semibold
                                    text-neutral-800
                                    dark:text-neutral-200
                                ">
                        Completion Report
                    </label>

                    <textarea
                        id="completion_report"
                        name="completion_report"
                        rows="6"
                        maxlength="5000"
                        required
                        placeholder="Describe the work performed, findings, and final result..."
                        class="
                                    mt-2 block w-full
                                    resize-y
                                    border
                                    bg-white
                                    px-3 py-2.5
                                    text-sm
                                    text-neutral-900
                                    outline-none
                                    transition
                                    placeholder:text-neutral-400
                                    focus:border-[#008080]
                                    focus:ring-2
                                    focus:ring-[#008080]/20
                                    dark:bg-neutral-950
                                    dark:text-white
                                    dark:placeholder:text-neutral-500

                                    {{ $errors->has('completion_report')
                                        ? 'border-red-500 ring-1 ring-red-500/20'
                                        : 'border-neutral-300 dark:border-neutral-700'
                                    }}
                                ">{{ old('completion_report') }}</textarea>


                    @error('completion_report')

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

                        {{ $message }}

                    </p>

                    @enderror


                    <button
                        type="submit"
                        class="
                                    mt-4 inline-flex min-h-11 w-full
                                    items-center justify-center gap-2

                                    bg-[#008080]
                                    px-4 py-2.5
                                    text-sm font-semibold
                                    text-white
                                    transition
                                    hover:bg-[#006f6f]
                                    focus:outline-none
                                    focus:ring-2
                                    focus:ring-[#008080]/30
                                ">

                        <i
                            data-lucide="circle-check"
                            class="h-4 w-4"
                            aria-hidden="true">
                        </i>

                        Complete Job

                    </button>

                </form>

            </section>

            @elseif ($jobOrder->status === 'completed')

            <section
                class="

                            border border-emerald-200
                            bg-emerald-50
                            p-4
                            dark:border-emerald-900
                            dark:bg-emerald-950/30
                        ">

                <div class="flex items-start gap-3">

                    <i
                        data-lucide="circle-check"
                        class="
                                    mt-0.5 h-5 w-5 shrink-0
                                    text-emerald-600
                                    dark:text-emerald-400
                                "
                        aria-hidden="true">
                    </i>

                    <div>

                        <h2
                            class="
                                        text-sm font-semibold
                                        text-emerald-900
                                        dark:text-emerald-200
                                    ">
                            Job Completed
                        </h2>

                        @if ($jobOrder->completed_at)

                        <p
                            class="
                                            mt-1 text-xs
                                            text-emerald-700
                                            dark:text-emerald-300
                                        ">
                            {{ $jobOrder->completed_at->format(
                                            'M j, Y g:i A'
                                        ) }}
                        </p>

                        @endif

                    </div>

                </div>


                @if ($jobOrder->completion_report)

                <div
                    class="
                                    mt-4 border-t
                                    border-emerald-200
                                    pt-4
                                    dark:border-emerald-900
                                ">

                    <p
                        class="
                                        text-xs font-semibold
                                        text-emerald-800
                                        dark:text-emerald-300
                                    ">
                        Completion Report
                    </p>

                    <p
                        class="
                                        mt-1 whitespace-pre-line
                                        text-sm leading-6
                                        text-emerald-900
                                        dark:text-emerald-100
                                    ">
                        {{ $jobOrder->completion_report }}
                    </p>

                </div>

                @endif

            </section>

            @endif

        </aside>

    </div>

</div>

@endsection
