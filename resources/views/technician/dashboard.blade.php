@extends('layouts.app')

@section('title', 'Technician Dashboard | Rincomm CMS')
@section('page-title', 'Technician Dashboard')

@section('content')

@php
$formatCustomerName = function ($customer) {
if (! $customer) {
return 'Customer information unavailable';
}

$name = trim(
collect([
$customer->first_name,
$customer->middle_name,
$customer->last_name,
])
->filter()
->implode(' ')
);

return $name !== ''
? $name
: 'Customer information unavailable';
};
@endphp


<div class="mx-auto w-full max-w-7xl space-y-4">

    {{-- Header --}}
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
                        text-xs font-semibold uppercase
                        tracking-wide
                        text-[#008080]
                        dark:text-[#5EEAD4]
                    ">
                Field Operations
            </p>

            <h1
                class="
                        mt-1 text-xl font-bold
                        text-neutral-900
                        dark:text-white
                        sm:text-2xl
                    ">
                Welcome, {{ auth()->user()->name }}
            </h1>

            <p
                class="
                        mt-1 text-sm
                        text-neutral-500
                        dark:text-neutral-400
                    ">
                Review your assigned work and continue active field jobs.
            </p>

        </div>


        <div
            class="
                    inline-flex w-fit
                    items-center gap-3

                    border border-neutral-200
                    bg-white
                    px-4 py-3
                    dark:border-neutral-800
                    dark:bg-neutral-900
                ">

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
                    data-lucide="badge-check"
                    class="h-4 w-4"
                    aria-hidden="true">
                </i>

            </div>

            <div class="min-w-0">

                <p
                    class="
                            text-[10px] font-medium uppercase
                            tracking-wide
                            text-neutral-500
                            dark:text-neutral-400
                        ">
                    Technician
                </p>

                <p
                    class="
                            truncate text-sm font-semibold
                            text-neutral-900
                            dark:text-white
                        ">
                    {{ $technician->technician_code }}
                </p>

                @if ($technician->specialization)

                <p
                    class="
                                mt-0.5 max-w-56 truncate
                                text-xs text-neutral-500
                                dark:text-neutral-400
                            ">
                    {{ $technician->specialization }}
                </p>

                @endif

            </div>

        </div>

    </div>


    {{-- Summary --}}
    <div class="grid grid-cols-3 gap-2 sm:gap-3">

        <a
            href="{{ route(
                    'technician.job-orders.index',
                    ['status' => 'assigned']
                ) }}"
            class="

                    border border-neutral-200
                    bg-white
                    p-3
                    transition
                    hover:border-[#008080]
                    hover:bg-[#008080]/5
                    focus:outline-none
                    focus:ring-2
                    focus:ring-[#008080]/20
                    dark:border-neutral-800
                    dark:bg-neutral-900
                    dark:hover:border-[#14B8A6]
                    dark:hover:bg-[#008080]/10
                    sm:p-4
                ">

            <div
                class="
                        flex items-center gap-2
                        text-neutral-500
                        dark:text-neutral-400
                    ">

                <i
                    data-lucide="clipboard-list"
                    class="h-4 w-4 shrink-0"
                    aria-hidden="true">
                </i>

                <span
                    class="
                            truncate text-[10px]
                            font-semibold uppercase
                            tracking-wide
                            sm:text-xs
                        ">
                    Assigned
                </span>

            </div>

            <p
                class="
                        mt-2 text-2xl font-bold
                        text-neutral-900
                        dark:text-white
                    ">
                {{ $assignedCount }}
            </p>

        </a>


        <a
            href="{{ route(
                    'technician.job-orders.index',
                    ['status' => 'in_progress']
                ) }}"
            class="

                    border border-neutral-200
                    bg-white
                    p-3
                    transition
                    hover:border-[#008080]
                    hover:bg-[#008080]/5
                    focus:outline-none
                    focus:ring-2
                    focus:ring-[#008080]/20
                    dark:border-neutral-800
                    dark:bg-neutral-900
                    dark:hover:border-[#14B8A6]
                    dark:hover:bg-[#008080]/10
                    sm:p-4
                ">

            <div
                class="
                        flex items-center gap-2
                        text-neutral-500
                        dark:text-neutral-400
                    ">

                <i
                    data-lucide="wrench"
                    class="h-4 w-4 shrink-0"
                    aria-hidden="true">
                </i>

                <span
                    class="
                            truncate text-[10px]
                            font-semibold uppercase
                            tracking-wide
                            sm:text-xs
                        ">
                    In Progress
                </span>

            </div>

            <p
                class="
                        mt-2 text-2xl font-bold
                        text-neutral-900
                        dark:text-white
                    ">
                {{ $inProgressCount }}
            </p>

        </a>


        <a
            href="{{ route(
                    'technician.job-orders.index',
                    ['status' => 'completed']
                ) }}"
            class="

                    border border-neutral-200
                    bg-white
                    p-3
                    transition
                    hover:border-[#008080]
                    hover:bg-[#008080]/5
                    focus:outline-none
                    focus:ring-2
                    focus:ring-[#008080]/20
                    dark:border-neutral-800
                    dark:bg-neutral-900
                    dark:hover:border-[#14B8A6]
                    dark:hover:bg-[#008080]/10
                    sm:p-4
                ">

            <div
                class="
                        flex items-center gap-2
                        text-neutral-500
                        dark:text-neutral-400
                    ">

                <i
                    data-lucide="circle-check"
                    class="h-4 w-4 shrink-0"
                    aria-hidden="true">
                </i>

                <span
                    class="
                            truncate text-[10px]
                            font-semibold uppercase
                            tracking-wide
                            sm:text-xs
                        ">
                    Completed
                </span>

            </div>

            <p
                class="
                        mt-2 text-2xl font-bold
                        text-neutral-900
                        dark:text-white
                    ">
                {{ $completedCount }}
            </p>

        </a>

    </div>


    {{-- Active work --}}
    @if ($activeJobOrder)

    <section
        class="
                    overflow-hidden
                    border border-amber-200
                    bg-white
                    dark:border-amber-900
                    dark:bg-neutral-900
                ">

        <div
            class="
                        flex items-center justify-between gap-3
                        border-b border-amber-200
                        bg-amber-50
                        px-4 py-3
                        dark:border-amber-900
                        dark:bg-amber-950/30
                    ">

            <div class="flex items-center gap-2">

                <i
                    data-lucide="activity"
                    class="
                                h-4 w-4
                                text-amber-600
                                dark:text-amber-400
                            "
                    aria-hidden="true">
                </i>

                <h2
                    class="
                                text-sm font-semibold
                                text-amber-900
                                dark:text-amber-200
                            ">
                    Active Job
                </h2>

            </div>

            <span
                class="

                            bg-amber-100
                            px-2.5 py-1
                            text-[11px] font-semibold
                            text-amber-700
                            dark:bg-amber-900/50
                            dark:text-amber-300
                        ">
                In Progress
            </span>

        </div>


        <div class="p-4">

            <div
                class="
                            flex flex-col gap-4
                            sm:flex-row
                            sm:items-start
                            sm:justify-between
                        ">

                <div class="min-w-0">

                    <p
                        class="
                                    text-xs font-semibold
                                    text-[#008080]
                                    dark:text-[#5EEAD4]
                                ">
                        {{ $activeJobOrder->job_order_number }}
                    </p>

                    <h3
                        class="
                                    mt-1 text-base font-semibold
                                    text-neutral-900
                                    dark:text-white
                                ">
                        {{ $activeJobOrder->job_type
                                    ? \Illuminate\Support\Str::headline(
                                        $activeJobOrder->job_type
                                    )
                                    : 'Type Not Set'
                                }}
                    </h3>

                    <p
                        class="
                                    mt-2 text-sm
                                    text-neutral-700
                                    dark:text-neutral-300
                                ">
                        {{ $formatCustomerName(
                                    $activeJobOrder->customer
                                ) }}
                    </p>


                    @if ($activeJobOrder->customer?->installation_address)

                    <p
                        class="
                                        mt-1 line-clamp-2
                                        text-xs leading-5
                                        text-neutral-500
                                        dark:text-neutral-400
                                    ">
                        {{ $activeJobOrder->customer->installation_address }}
                    </p>

                    @endif

                </div>


                <a
                    href="{{ route(
                                'technician.job-orders.show',
                                $activeJobOrder
                            ) }}"
                    class="
                                inline-flex min-h-11
                                w-full items-center
                                justify-center gap-2

                                bg-[#008080]
                                px-4 py-2.5
                                text-sm font-semibold
                                text-white
                                transition
                                hover:bg-[#006f6f]
                                focus:outline-none
                                focus:ring-2
                                focus:ring-[#008080]/30
                                sm:w-auto
                            ">

                    Continue Job

                    <i
                        data-lucide="arrow-right"
                        class="h-4 w-4"
                        aria-hidden="true">
                    </i>

                </a>

            </div>

        </div>

    </section>

    @endif


    {{-- Assigned work --}}
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
                    flex items-center
                    justify-between gap-3
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
                    Assigned Work
                </h2>

                <p
                    class="
                            mt-0.5 text-xs
                            text-neutral-500
                            dark:text-neutral-400
                        ">
                    Your next assigned Job Orders.
                </p>

            </div>


            <a
                href="{{ route('technician.job-orders.index') }}"
                class="
                        inline-flex min-h-9
                        shrink-0 items-center gap-1
                         px-2
                        text-xs font-semibold
                        text-[#008080]
                        transition
                        hover:bg-[#008080]/10
                        dark:text-[#5EEAD4]
                        dark:hover:bg-[#008080]/15
                    ">

                View All

                <i
                    data-lucide="chevron-right"
                    class="h-3.5 w-3.5"
                    aria-hidden="true">
                </i>

            </a>

        </div>


        @if ($upcomingJobOrders->isEmpty())

        <div class="px-4 py-10 text-center">

            <div
                class="
                            mx-auto flex h-10 w-10
                            items-center justify-center

                            bg-neutral-100
                            text-neutral-400
                            dark:bg-neutral-800
                            dark:text-neutral-500
                        ">

                <i
                    data-lucide="clipboard-check"
                    class="h-5 w-5"
                    aria-hidden="true">
                </i>

            </div>

            <p
                class="
                            mt-3 text-sm font-semibold
                            text-neutral-800
                            dark:text-neutral-200
                        ">
                No assigned jobs
            </p>

            <p
                class="
                            mt-1 text-xs
                            text-neutral-500
                            dark:text-neutral-400
                        ">
                You have no Job Orders waiting to be started.
            </p>

        </div>

        @else

        <div
            class="
                        divide-y divide-neutral-200
                        dark:divide-neutral-800
                    ">

            @foreach ($upcomingJobOrders as $jobOrder)

            <a
                href="{{ route(
                                'technician.job-orders.show',
                                $jobOrder
                            ) }}"
                class="
                                block p-4
                                transition
                                hover:bg-neutral-50
                                focus:outline-none
                                focus:ring-2
                                focus:ring-inset
                                focus:ring-[#008080]/20
                                dark:hover:bg-neutral-800/40
                            ">

                <div
                    class="
                                    flex items-start
                                    justify-between gap-3
                                ">

                    <div class="min-w-0">

                        <div
                            class="
                                            flex flex-wrap
                                            items-center gap-2
                                        ">

                            <p
                                class="
                                                text-sm font-semibold
                                                text-neutral-900
                                                dark:text-white
                                            ">
                                {{ $jobOrder->job_order_number }}
                            </p>

                            <span
                                class="
                                                inline-flex

                                                bg-blue-50
                                                px-2 py-0.5
                                                text-[10px]
                                                font-semibold
                                                text-blue-700
                                                ring-1 ring-inset
                                                ring-blue-600/20
                                                dark:bg-blue-950/40
                                                dark:text-blue-300
                                                dark:ring-blue-400/30
                                            ">
                                Assigned
                            </span>

                        </div>


                        <p
                            class="
                                            mt-1 text-sm font-medium
                                            text-neutral-700
                                            dark:text-neutral-300
                                        ">
                            {{ $jobOrder->job_type
                                            ? \Illuminate\Support\Str::headline(
                                                $jobOrder->job_type
                                            )
                                            : 'Type Not Set'
                                        }}
                        </p>

                        <p
                            class="
                                            mt-1 truncate
                                            text-xs
                                            text-neutral-500
                                            dark:text-neutral-400
                                        ">
                            {{ $formatCustomerName(
                                            $jobOrder->customer
                                        ) }}
                        </p>


                        <div
                            class="
                                            mt-2 flex
                                            flex-wrap items-center
                                            gap-x-3 gap-y-1
                                            text-xs
                                            text-neutral-500
                                            dark:text-neutral-400
                                        ">

                            <span
                                class="
                                                inline-flex
                                                items-center gap-1.5
                                            ">

                                <i
                                    data-lucide="calendar"
                                    class="h-3.5 w-3.5"
                                    aria-hidden="true">
                                </i>

                                {{ $jobOrder->scheduled_date
                                                ? $jobOrder->scheduled_date->format(
                                                    'M j, Y'
                                                )
                                                : 'Not scheduled'
                                            }}

                            </span>


                            @if ($jobOrder->scheduled_time)

                            <span
                                class="
                                                    inline-flex
                                                    items-center gap-1.5
                                                ">

                                <i
                                    data-lucide="clock"
                                    class="h-3.5 w-3.5"
                                    aria-hidden="true">
                                </i>

                                {{ \Carbon\Carbon::parse(
                                                    $jobOrder->scheduled_time
                                                )->format('g:i A') }}

                            </span>

                            @endif

                        </div>

                    </div>


                    <i
                        data-lucide="chevron-right"
                        class="
                                        mt-1 h-4 w-4 shrink-0
                                        text-neutral-400
                                    "
                        aria-hidden="true">
                    </i>

                </div>

            </a>

            @endforeach

        </div>

        @endif

    </section>


    {{-- Primary shortcut --}}
    <a
        href="{{ route('technician.job-orders.index') }}"
        class="
                inline-flex min-h-11 w-full
                items-center justify-center gap-2

                border border-[#008080]
                px-4 py-2.5
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
            data-lucide="clipboard-list"
            class="h-4 w-4"
            aria-hidden="true">
        </i>

        Open Job Orders

    </a>

</div>

@endsection
