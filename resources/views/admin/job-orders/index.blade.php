@extends('layouts.app')

@section('title', 'Job Orders')
@section('page-title', 'Job Orders')

@section('content')

@php
$statusMap = [
    'pending' => [
        'label' => 'Pending',
        'icon' => 'clock-3',
        'class' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300',
    ],
    'assigned' => [
        'label' => 'Assigned',
        'icon' => 'user-check',
        'class' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300',
    ],
    'in_progress' => [
        'label' => 'In Progress',
        'icon' => 'loader-circle',
        'class' => 'bg-cyan-50 text-cyan-700 dark:bg-cyan-950/40 dark:text-cyan-300',
    ],
    'completed' => [
        'label' => 'Completed',
        'icon' => 'circle-check',
        'class' => 'bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-300',
    ],
    'cancelled' => [
        'label' => 'Cancelled',
        'icon' => 'circle-x',
        'class' => 'bg-gray-100 text-gray-600 dark:bg-neutral-800 dark:text-gray-300',
    ],
];
@endphp

<div class="mx-auto max-w-[1600px] space-y-4">

    <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">

        <div class="flex items-center gap-2">

            <div
                class="
                    flex h-9 w-9 shrink-0 items-center justify-center
                    border border-[#008080]/15
                    bg-[#008080]/10 text-[#008080]
                    dark:border-[#14B8A6]/20
                    dark:bg-[#008080]/20
                    dark:text-[#5EEAD4]
                ">
                <i data-lucide="clipboard-list" class="h-4.5 w-4.5"></i>
            </div>

            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white sm:text-2xl">
                    Job Orders
                </h1>

                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                    Create and monitor field work orders.
                </p>
            </div>

        </div>

        <a
            href="{{ route('admin.job-orders.create') }}"
            class="
                inline-flex min-h-10 items-center justify-center gap-2
                bg-[#008080] px-4 py-2
                text-sm font-semibold text-white
                transition hover:bg-[#006666]
                focus:outline-none focus:ring-2 focus:ring-[#008080]/30
            ">
            <i data-lucide="plus" class="h-4 w-4"></i>
            Create Job Order
        </a>

    </section>


    @if (session('success'))

    <div
        class="
            border border-green-200 bg-green-50
            px-4 py-3 text-sm text-green-800
            dark:border-green-900
            dark:bg-green-950/40
            dark:text-green-300
        ">
        {{ session('success') }}
    </div>

    @endif


    <form
        method="GET"
        action="{{ route('admin.job-orders.index') }}"
        class="
            border border-gray-200 bg-white p-3
            dark:border-neutral-800 dark:bg-neutral-900
        ">

        <div class="flex flex-col gap-2 lg:flex-row lg:items-center">

            <div class="relative min-w-0 flex-1">

                <label for="job-order-search" class="sr-only">
                    Search Job Orders
                </label>

                <i
                    data-lucide="search"
                    class="
                        pointer-events-none absolute left-3 top-1/2
                        h-4 w-4 -translate-y-1/2
                        text-gray-400
                    ">
                </i>

                <input
                    id="job-order-search"
                    type="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search JO number, customer code, name, email, or phone"
                    class="
                        min-h-10 w-full
                        border border-gray-300 bg-white
                        py-2 pl-10 pr-3 text-sm
                        text-gray-900 outline-none
                        focus:border-[#008080]
                        focus:ring-2 focus:ring-[#008080]/20
                        dark:border-neutral-700
                        dark:bg-neutral-950
                        dark:text-white
                    ">

            </div>


            <select
                name="job_type"
                aria-label="Filter by Job Order type"
                class="
                    min-h-10 border border-gray-300
                    bg-white px-3 py-2 text-sm
                    text-gray-800 outline-none
                    focus:border-[#008080]
                    focus:ring-2 focus:ring-[#008080]/20
                    dark:border-neutral-700
                    dark:bg-neutral-950
                    dark:text-gray-100
                    lg:w-52
                ">

                <option value="">All Job Types</option>

                @foreach ($jobTypes as $typeValue => $typeLabel)
                    <option
                        value="{{ $typeValue }}"
                        @selected($jobType === $typeValue)>
                        {{ $typeLabel }}
                    </option>
                @endforeach

            </select>


            <select
                name="status"
                aria-label="Filter by Job Order status"
                class="
                    min-h-10 border border-gray-300
                    bg-white px-3 py-2 text-sm
                    text-gray-800 outline-none
                    focus:border-[#008080]
                    focus:ring-2 focus:ring-[#008080]/20
                    dark:border-neutral-700
                    dark:bg-neutral-950
                    dark:text-gray-100
                    lg:w-44
                ">

                <option value="">All Status</option>

                @foreach ($statuses as $statusValue)
                    <option
                        value="{{ $statusValue }}"
                        @selected($status === $statusValue)>
                        {{ \Illuminate\Support\Str::headline($statusValue) }}
                    </option>
                @endforeach

            </select>


            <button
                type="submit"
                class="
                    inline-flex min-h-10 items-center justify-center gap-2
                    bg-[#008080] px-4 py-2
                    text-sm font-semibold text-white
                    hover:bg-[#006666]
                ">
                <i data-lucide="list-filter" class="h-4 w-4"></i>
                Filter
            </button>


            @if ($search !== '' || $status !== '' || $jobType !== '')

            <a
                href="{{ route('admin.job-orders.index') }}"
                aria-label="Clear Job Order filters"
                title="Clear filters"
                class="
                    inline-flex h-10 w-10 items-center justify-center
                    border border-gray-300
                    text-gray-500
                    hover:bg-gray-50
                    dark:border-neutral-700
                    dark:text-gray-400
                    dark:hover:bg-neutral-800
                ">
                <i data-lucide="x" class="h-4 w-4"></i>
            </a>

            @endif

        </div>

    </form>


    @if ($jobOrders->isEmpty())

    <section
        class="
            border border-dashed border-gray-300
            bg-white px-6 py-14 text-center
            dark:border-neutral-700
            dark:bg-neutral-900
        ">

        <div
            class="
                mx-auto flex h-12 w-12 items-center justify-center
                border border-gray-200 bg-gray-100
                text-gray-400
                dark:border-neutral-700
                dark:bg-neutral-800
                dark:text-gray-500
            ">
            <i data-lucide="clipboard-list" class="h-5 w-5"></i>
        </div>

        <h2 class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">
            No Job Orders found
        </h2>

        <p class="mx-auto mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">
            @if ($search !== '' || $status !== '' || $jobType !== '')
                Try changing your search or filters.
            @else
                Create the first field Job Order to begin.
            @endif
        </p>

    </section>

    @else

    <section
        class="
            hidden border border-gray-200 bg-white
            dark:border-neutral-800 dark:bg-neutral-900
            lg:block
        ">

        <div class="border-b border-gray-100 px-4 py-3 dark:border-neutral-800">

            <div class="flex items-center justify-between gap-4">

                <div>
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
                        Field Job Orders
                    </h2>

                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        Pending orders are ready for scheduling and assignment.
                    </p>
                </div>

                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ number_format($jobOrders->total()) }}
                    {{ $jobOrders->total() === 1 ? 'record' : 'records' }}
                </span>

            </div>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[1000px]">

                <thead class="bg-gray-50/80 dark:bg-neutral-800/60">
                    <tr>

                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                            JO Number
                        </th>

                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                            Subscriber
                        </th>

                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                            Job Type
                        </th>

                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                            Status
                        </th>

                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                            Technician
                        </th>

                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                            Schedule
                        </th>

                        <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                            Action
                        </th>

                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-neutral-800">

                    @foreach ($jobOrders as $jobOrder)

                    @php
                    $display = $statusMap[$jobOrder->status] ?? [
                        'label' => \Illuminate\Support\Str::headline($jobOrder->status),
                        'icon' => 'circle-help',
                        'class' => 'bg-gray-100 text-gray-600 dark:bg-neutral-800 dark:text-gray-300',
                    ];

                    $customerName = trim(implode(' ', array_filter([
                        $jobOrder->customer?->first_name,
                        $jobOrder->customer?->middle_name,
                        $jobOrder->customer?->last_name,
                    ])));
                    @endphp

                    <tr class="hover:bg-[#008080]/[0.035] dark:hover:bg-[#008080]/[0.07]">

                        <td class="px-4 py-3.5">
                            <a
                                href="{{ route('admin.job-orders.show', $jobOrder) }}"
                                class="text-sm font-semibold text-[#008080] hover:underline dark:text-[#5EEAD4]">
                                {{ $jobOrder->job_order_number }}
                            </a>
                        </td>

                        <td class="px-4 py-3.5">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $customerName ?: 'Unknown subscriber' }}
                            </p>

                            <p class="mt-0.5 text-xs text-gray-400">
                                {{ $jobOrder->customer?->customer_code }}
                            </p>
                        </td>

                        <td class="px-4 py-3.5 text-sm text-gray-700 dark:text-gray-300">
                            {{ $jobTypes[$jobOrder->job_type] ?? \Illuminate\Support\Str::headline($jobOrder->job_type) }}
                        </td>

                        <td class="px-4 py-3.5">
                            <span
                                class="
                                    inline-flex items-center gap-1.5
                                    rounded-full px-2.5 py-1
                                    text-xs font-medium
                                    {{ $display['class'] }}
                                ">
                                <i data-lucide="{{ $display['icon'] }}" class="h-3 w-3"></i>
                                {{ $display['label'] }}
                            </span>
                        </td>

                        <td class="px-4 py-3.5 text-sm text-gray-600 dark:text-gray-300">
                            {{ $jobOrder->technician?->technician_code ?? 'Unassigned' }}
                        </td>

                        <td class="px-4 py-3.5 text-sm text-gray-600 dark:text-gray-300">
                            @if ($jobOrder->scheduled_date)
                                {{ $jobOrder->scheduled_date->format('M d, Y') }}
                                @if ($jobOrder->scheduled_time)
                                    <span class="text-gray-400">
                                        {{ \Illuminate\Support\Carbon::parse($jobOrder->scheduled_time)->format('g:i A') }}
                                    </span>
                                @endif
                            @else
                                Not scheduled
                            @endif
                        </td>

                        <td class="px-4 py-3.5 text-right">
                            <a
                                href="{{ route('admin.job-orders.show', $jobOrder) }}"
                                class="
                                    inline-flex h-8 items-center justify-center gap-1.5
                                    border border-gray-300 px-3
                                    text-xs font-semibold text-gray-700
                                    hover:border-[#008080]
                                    hover:text-[#008080]
                                    dark:border-neutral-700
                                    dark:text-gray-300
                                ">
                                <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                View
                            </a>
                        </td>

                    </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </section>


    <section class="grid gap-3 lg:hidden">

        @foreach ($jobOrders as $jobOrder)

        @php
        $display = $statusMap[$jobOrder->status] ?? [
            'label' => \Illuminate\Support\Str::headline($jobOrder->status),
            'icon' => 'circle-help',
            'class' => 'bg-gray-100 text-gray-600 dark:bg-neutral-800 dark:text-gray-300',
        ];

        $customerName = trim(implode(' ', array_filter([
            $jobOrder->customer?->first_name,
            $jobOrder->customer?->middle_name,
            $jobOrder->customer?->last_name,
        ])));
        @endphp

        <article
            class="
                border border-gray-200 bg-white p-4
                dark:border-neutral-800 dark:bg-neutral-900
            ">

            <div class="flex items-start justify-between gap-3">

                <div class="min-w-0">

                    <a
                        href="{{ route('admin.job-orders.show', $jobOrder) }}"
                        class="text-sm font-semibold text-[#008080] dark:text-[#5EEAD4]">
                        {{ $jobOrder->job_order_number }}
                    </a>

                    <h2 class="mt-1 truncate text-base font-semibold text-gray-900 dark:text-white">
                        {{ $customerName ?: 'Unknown subscriber' }}
                    </h2>

                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        {{ $jobTypes[$jobOrder->job_type] ?? \Illuminate\Support\Str::headline($jobOrder->job_type) }}
                    </p>

                </div>

                <span
                    class="
                        inline-flex shrink-0 items-center gap-1
                        rounded-full px-2.5 py-1
                        text-[11px] font-medium
                        {{ $display['class'] }}
                    ">
                    {{ $display['label'] }}
                </span>

            </div>


            <div
                class="
                    mt-4 grid gap-3
                    border-t border-gray-100 pt-4
                    sm:grid-cols-2
                    dark:border-neutral-800
                ">

                <div>
                    <p class="text-xs text-gray-400">Technician</p>
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                        {{ $jobOrder->technician?->technician_code ?? 'Unassigned' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs text-gray-400">Schedule</p>
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                        {{ $jobOrder->scheduled_date?->format('M d, Y') ?? 'Not scheduled' }}
                    </p>
                </div>

            </div>

        </article>

        @endforeach

    </section>


    @if ($jobOrders->hasPages())

    <div>
        {{ $jobOrders->links() }}
    </div>

    @endif

    @endif

</div>

@endsection
