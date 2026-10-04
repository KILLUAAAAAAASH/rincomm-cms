@extends('layouts.app')

@section('title', 'Job Orders | Rincomm CMS')
@section('page-title', 'Job Orders')

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
$statusFilters = [
'' => 'All',
'assigned' => 'Assigned',
'in_progress' => 'In Progress',
'completed' => 'Completed',
'cancelled' => 'Cancelled',
];
@endphp


<div class="mx-auto w-full max-w-7xl space-y-4">

    {{-- Page heading --}}
    <div
        class="
                flex flex-col gap-3
                sm:flex-row sm:items-start sm:justify-between
            ">

        <div>
            <h1
                class="
                        text-lg font-bold
                        text-neutral-900
                        dark:text-white
                        sm:text-xl
                    ">
                Assigned Job Orders
            </h1>

            <p
                class="
                        mt-1 text-sm
                        text-neutral-500
                        dark:text-neutral-400
                    ">
                View and process field work assigned to you.
            </p>
        </div>


        <div
            class="
                    inline-flex w-fit items-center gap-2

                    border border-neutral-200
                    bg-white
                    px-3 py-2
                    dark:border-neutral-800
                    dark:bg-neutral-900
                ">

            <i
                data-lucide="badge-check"
                class="
                        h-4 w-4 shrink-0
                        text-[#008080]
                        dark:text-[#5EEAD4]
                    "
                aria-hidden="true">
            </i>

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
                            text-neutral-800
                            dark:text-neutral-100
                        ">
                    {{ $technician->technician_code }}
                </p>
            </div>

        </div>

    </div>


    {{-- Success message --}}
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


    {{-- Status filters --}}
    <div
        class="
                overflow-x-auto
                border-y border-neutral-200
                py-2
                dark:border-neutral-800

                sm:border
                sm:bg-white
                sm:px-3
                dark:sm:bg-neutral-900
            ">

        <div class="flex min-w-max items-center gap-1">

            @foreach ($statusFilters as $filterValue => $filterLabel)

            @php
            $isActiveFilter = $status === $filterValue;
            @endphp

            <a
                href="{{ route(
                            'technician.job-orders.index',
                            $filterValue !== ''
                                ? ['status' => $filterValue]
                                : []
                        ) }}"
                class="
                            inline-flex min-h-9
                            items-center justify-center
                            whitespace-nowrap

                            px-3 py-2
                            text-xs font-semibold
                            transition
                            sm:text-sm

                            {{ $isActiveFilter
                                ? 'bg-[#008080] text-white shadow-sm'
                                : 'text-neutral-600 hover:bg-[#008080]/10 hover:text-[#008080]
                                   dark:text-neutral-400 dark:hover:bg-[#008080]/15
                                   dark:hover:text-[#5EEAD4]'
                            }}
                        ">

                {{ $filterLabel }}

            </a>

            @endforeach

        </div>

    </div>


    {{-- Job Orders --}}
    @if ($jobOrders->isEmpty())

    <div
        class="

                    border border-dashed border-neutral-300
                    bg-white
                    px-5 py-12
                    text-center
                    dark:border-neutral-700
                    dark:bg-neutral-900
                ">

        <div
            class="
                        mx-auto flex h-11 w-11
                        items-center justify-center

                        bg-neutral-100
                        text-neutral-500
                        dark:bg-neutral-800
                        dark:text-neutral-400
                    ">

            <i
                data-lucide="clipboard-check"
                class="h-5 w-5"
                aria-hidden="true">
            </i>

        </div>

        <h2
            class="
                        mt-3 text-sm font-semibold
                        text-neutral-900
                        dark:text-white
                    ">
            No Job Orders Found
        </h2>

        <p
            class="
                        mx-auto mt-1 max-w-sm
                        text-xs leading-5
                        text-neutral-500
                        dark:text-neutral-400
                    ">

            @if ($status !== '')
            You currently have no
            {{ \Illuminate\Support\Str::headline($status) }}
            Job Orders.
            @else
            No Job Orders are currently assigned to your technician account.
            @endif

        </p>

    </div>

    @else

    {{-- Mobile cards --}}
    <div class="space-y-3 md:hidden">

        @foreach ($jobOrders as $jobOrder)

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

        $jobTypeLabel = $jobOrder->job_type
        ? \Illuminate\Support\Str::headline($jobOrder->job_type)
        : 'Type Not Set';
        @endphp


        <article
            class="
                            overflow-hidden
                            border border-neutral-200
                            bg-white
                            dark:border-neutral-800
                            dark:bg-neutral-900
                        ">

            <div class="p-4">

                <div
                    class="
                                    flex items-start
                                    justify-between gap-3
                                ">

                    <div class="min-w-0">

                        <p
                            class="
                                            text-xs font-medium
                                            text-[#008080]
                                            dark:text-[#5EEAD4]
                                        ">
                            {{ $jobOrder->job_order_number }}
                        </p>

                        <h2
                            class="
                                            mt-1 truncate
                                            text-base font-semibold
                                            text-neutral-900
                                            dark:text-white
                                        ">
                            {{ $jobTypeLabel }}
                        </h2>

                    </div>


                    <span
                        class="
                                        inline-flex shrink-0
                                        items-center
                                        px-2.5 py-1
                                        text-[11px] font-semibold
                                        ring-1 ring-inset
                                        {{ $statusClasses }}
                                    ">

                        {{ \Illuminate\Support\Str::headline(
                                        $jobOrder->status
                                    ) }}

                    </span>

                </div>


                <div class="mt-4 space-y-2.5">

                    <div class="flex items-start gap-2.5">

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
                                                text-[10px] font-medium
                                                uppercase tracking-wide
                                                text-neutral-400
                                            ">
                                Customer
                            </p>

                            <p
                                class="
                                                truncate text-sm font-medium
                                                text-neutral-800
                                                dark:text-neutral-200
                                            ">
                                {{ $customerName !== ''
                                                ? $customerName
                                                : 'Customer information unavailable'
                                            }}
                            </p>

                        </div>

                    </div>


                    <div class="flex items-start gap-2.5">

                        <i
                            data-lucide="calendar-clock"
                            class="
                                            mt-0.5 h-4 w-4 shrink-0
                                            text-neutral-400
                                        "
                            aria-hidden="true">
                        </i>

                        <div>

                            <p
                                class="
                                                text-[10px] font-medium
                                                uppercase tracking-wide
                                                text-neutral-400
                                            ">
                                Schedule
                            </p>

                            <p
                                class="
                                                text-sm
                                                text-neutral-700
                                                dark:text-neutral-300
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

                            </p>

                        </div>

                    </div>


                    @if ($jobOrder->customer?->installation_address)

                    <div class="flex items-start gap-2.5">

                        <i
                            data-lucide="map-pin"
                            class="
                                                mt-0.5 h-4 w-4 shrink-0
                                                text-neutral-400
                                            "
                            aria-hidden="true">
                        </i>

                        <div class="min-w-0">

                            <p
                                class="
                                                    text-[10px] font-medium
                                                    uppercase tracking-wide
                                                    text-neutral-400
                                                ">
                                Installation Address
                            </p>

                            <p
                                class="
                                                    line-clamp-2 text-sm
                                                    text-neutral-700
                                                    dark:text-neutral-300
                                                ">
                                {{ $jobOrder->customer->installation_address }}
                            </p>

                        </div>

                    </div>

                    @endif


                    @if ($jobOrder->serviceRequest?->ticket_number)

                    <div class="flex items-start gap-2.5">

                        <i
                            data-lucide="ticket"
                            class="
                                                mt-0.5 h-4 w-4 shrink-0
                                                text-neutral-400
                                            "
                            aria-hidden="true">
                        </i>

                        <div>

                            <p
                                class="
                                                    text-[10px] font-medium
                                                    uppercase tracking-wide
                                                    text-neutral-400
                                                ">
                                Service Request
                            </p>

                            <p
                                class="
                                                    text-sm
                                                    text-neutral-700
                                                    dark:text-neutral-300
                                                ">
                                {{ $jobOrder->serviceRequest->ticket_number }}
                            </p>

                        </div>

                    </div>

                    @endif

                </div>

            </div>


            <div
                class="
                                border-t border-neutral-200
                                bg-neutral-50
                                px-4 py-3
                                dark:border-neutral-800
                                dark:bg-neutral-950/50
                            ">

                <a
                    href="{{ route(
                                    'technician.job-orders.show',
                                    $jobOrder
                                ) }}"
                    class="
                                    inline-flex min-h-10 w-full
                                    items-center justify-center gap-2

                                    bg-[#008080]
                                    px-4 py-2
                                    text-sm font-semibold
                                    text-white
                                    transition
                                    hover:bg-[#006f6f]
                                    focus:outline-none
                                    focus:ring-2
                                    focus:ring-[#008080]/30
                                ">

                    View Job Order

                    <i
                        data-lucide="arrow-right"
                        class="h-4 w-4"
                        aria-hidden="true">
                    </i>

                </a>

            </div>

        </article>

        @endforeach

    </div>


    {{-- Desktop table --}}
    <div
        class="
                    hidden overflow-hidden
                    border border-neutral-200
                    bg-white
                    dark:border-neutral-800
                    dark:bg-neutral-900
                    md:block
                ">

        <div class="overflow-x-auto">

            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-800">

                <thead
                    class="
                                bg-neutral-50
                                dark:bg-neutral-950/60
                            ">

                    <tr>

                        <th
                            scope="col"
                            class="
                                        px-4 py-3 text-left
                                        text-xs font-semibold uppercase
                                        tracking-wide text-neutral-500
                                        dark:text-neutral-400
                                    ">
                            Job Order
                        </th>

                        <th
                            scope="col"
                            class="
                                        px-4 py-3 text-left
                                        text-xs font-semibold uppercase
                                        tracking-wide text-neutral-500
                                        dark:text-neutral-400
                                    ">
                            Customer
                        </th>

                        <th
                            scope="col"
                            class="
                                        px-4 py-3 text-left
                                        text-xs font-semibold uppercase
                                        tracking-wide text-neutral-500
                                        dark:text-neutral-400
                                    ">
                            Schedule
                        </th>

                        <th
                            scope="col"
                            class="
                                        px-4 py-3 text-left
                                        text-xs font-semibold uppercase
                                        tracking-wide text-neutral-500
                                        dark:text-neutral-400
                                    ">
                            Status
                        </th>

                        <th
                            scope="col"
                            class="px-4 py-3 text-right">
                            <span class="sr-only">
                                Actions
                            </span>
                        </th>

                    </tr>

                </thead>


                <tbody
                    class="
                                divide-y divide-neutral-200
                                dark:divide-neutral-800
                            ">

                    @foreach ($jobOrders as $jobOrder)

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


                    <tr
                        class="
                                        transition
                                        hover:bg-neutral-50
                                        dark:hover:bg-neutral-800/40
                                    ">

                        <td class="px-4 py-3">

                            <p
                                class="
                                                text-sm font-semibold
                                                text-neutral-900
                                                dark:text-white
                                            ">
                                {{ $jobOrder->job_order_number }}
                            </p>

                            <p
                                class="
                                                mt-0.5 text-xs
                                                text-neutral-500
                                                dark:text-neutral-400
                                            ">
                                {{ $jobOrder->job_type
                                                ? \Illuminate\Support\Str::headline(
                                                    $jobOrder->job_type
                                                )
                                                : 'Type Not Set'
                                            }}
                            </p>

                        </td>


                        <td class="px-4 py-3">

                            <p
                                class="
                                                max-w-52 truncate
                                                text-sm font-medium
                                                text-neutral-800
                                                dark:text-neutral-200
                                            ">
                                {{ $customerName !== ''
                                                ? $customerName
                                                : 'Unavailable'
                                            }}
                            </p>

                            @if ($jobOrder->customer?->phone)

                            <p
                                class="
                                                    mt-0.5 text-xs
                                                    text-neutral-500
                                                    dark:text-neutral-400
                                                ">
                                {{ $jobOrder->customer->phone }}
                            </p>

                            @endif

                        </td>


                        <td
                            class="
                                            whitespace-nowrap
                                            px-4 py-3
                                            text-sm text-neutral-700
                                            dark:text-neutral-300
                                        ">

                            @if ($jobOrder->scheduled_date)

                            <p>
                                {{ $jobOrder->scheduled_date->format('M j, Y') }}
                            </p>

                            <p
                                class="
                                                    mt-0.5 text-xs
                                                    text-neutral-500
                                                    dark:text-neutral-400
                                                ">

                                {{ $jobOrder->scheduled_time
                                                    ? \Carbon\Carbon::parse(
                                                        $jobOrder->scheduled_time
                                                    )->format('g:i A')
                                                    : 'Time not set'
                                                }}

                            </p>

                            @else
                            Not scheduled
                            @endif

                        </td>


                        <td class="px-4 py-3">

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

                        </td>


                        <td class="px-4 py-3 text-right">

                            <a
                                href="{{ route(
                                                'technician.job-orders.show',
                                                $jobOrder
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
                                                hover:bg-[#008080]/10
                                                hover:text-[#008080]
                                                focus:outline-none
                                                focus:ring-2
                                                focus:ring-[#008080]/20
                                                dark:border-neutral-700
                                                dark:text-neutral-300
                                                dark:hover:border-[#14B8A6]
                                                dark:hover:bg-[#008080]/15
                                                dark:hover:text-[#5EEAD4]
                                            ">

                                View

                                <i
                                    data-lucide="chevron-right"
                                    class="h-3.5 w-3.5"
                                    aria-hidden="true">
                                </i>

                            </a>

                        </td>

                    </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>


    {{-- Pagination --}}
    @if ($jobOrders->hasPages())

    <div>
        {{ $jobOrders->links() }}
    </div>

    @endif

    @endif

</div>

@endsection
