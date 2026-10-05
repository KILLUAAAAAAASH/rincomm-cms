@extends('layouts.app')

@section('title', $jobOrder->job_order_number)
@section('page-title', 'Job Order Details')

@section('content')

@php
$statusDisplay = match ($jobOrder->status) {
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
    default => [
        'label' => \Illuminate\Support\Str::headline($jobOrder->status),
        'icon' => 'circle-help',
        'class' => 'bg-gray-100 text-gray-600 dark:bg-neutral-800 dark:text-gray-300',
    ],
};

$customerName = trim(implode(' ', array_filter([
    $jobOrder->customer?->first_name,
    $jobOrder->customer?->middle_name,
    $jobOrder->customer?->last_name,
])));
@endphp

<div class="mx-auto max-w-6xl space-y-4">

    <section class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

        <div class="flex items-start gap-3">

            <a
                href="{{ route('admin.job-orders.index') }}"
                class="
                    inline-flex h-9 w-9 shrink-0 items-center justify-center
                    border border-gray-300
                    text-gray-600
                    hover:border-[#008080]
                    hover:text-[#008080]
                    dark:border-neutral-700
                    dark:text-gray-300
                "
                aria-label="Back to Job Orders">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
            </a>

            <div class="min-w-0">

                <p class="text-xs font-semibold text-[#008080] dark:text-[#5EEAD4]">
                    {{ $jobOrder->job_order_number }}
                </p>

                <h1 class="mt-0.5 text-xl font-semibold text-gray-900 dark:text-white sm:text-2xl">
                    {{ $jobTypes[$jobOrder->job_type] ?? \Illuminate\Support\Str::headline($jobOrder->job_type) }}
                </h1>

                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                    Field Job Order record
                </p>

            </div>

        </div>


        <span
            class="
                inline-flex w-fit items-center gap-1.5
                rounded-full px-3 py-1.5
                text-xs font-semibold
                {{ $statusDisplay['class'] }}
            ">
            <i data-lucide="{{ $statusDisplay['icon'] }}" class="h-3.5 w-3.5"></i>
            {{ $statusDisplay['label'] }}
        </span>

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


    @if ($jobOrder->status === 'pending')

    <section
        class="
            border border-amber-200 bg-amber-50
            px-4 py-3
            dark:border-amber-900
            dark:bg-amber-950/30
        ">

        <div class="flex items-start gap-2">

            <i
                data-lucide="calendar-clock"
                class="mt-0.5 h-4 w-4 shrink-0 text-amber-700 dark:text-amber-300">
            </i>

            <div>
                <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                    Pending technician assignment
                </p>

                <p class="mt-0.5 text-xs leading-5 text-amber-700 dark:text-amber-300">
                    This Job Order has been created successfully.
                    Scheduling and technician assignment will be handled in the next Field Operations feature.
                </p>
            </div>

        </div>

    </section>

    @endif


    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_22rem]">

        <div class="space-y-4">

            <section
                class="
                    border border-gray-200 bg-white
                    dark:border-neutral-800 dark:bg-neutral-900
                ">

                <header class="border-b border-gray-100 px-4 py-3 dark:border-neutral-800">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
                        Work Details
                    </h2>
                </header>

                <div class="space-y-5 p-4 sm:p-5">

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            Job Type
                        </p>

                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $jobTypes[$jobOrder->job_type] ?? \Illuminate\Support\Str::headline($jobOrder->job_type) }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            Description / Instructions
                        </p>

                        <p class="mt-1 whitespace-pre-line text-sm leading-6 text-gray-700 dark:text-gray-300">
                            {{ $jobOrder->description }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            Related Service Request
                        </p>

                        @if ($jobOrder->serviceRequest)

                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $jobOrder->serviceRequest->ticket_number }}
                            </p>

                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                {{ \Illuminate\Support\Str::headline($jobOrder->serviceRequest->request_type) }}
                                ·
                                {{ \Illuminate\Support\Str::headline($jobOrder->serviceRequest->status) }}
                            </p>

                        @else

                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                No Service Request linked.
                            </p>

                        @endif

                    </div>

                </div>

            </section>


            <section
                class="
                    border border-gray-200 bg-white
                    dark:border-neutral-800 dark:bg-neutral-900
                ">

                <header class="border-b border-gray-100 px-4 py-3 dark:border-neutral-800">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
                        Scheduling & Assignment
                    </h2>
                </header>

                <div class="grid gap-4 p-4 sm:grid-cols-2 sm:p-5">

                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-400">
                            Technician
                        </p>

                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $jobOrder->technician?->technician_code ?? 'Unassigned' }}
                        </p>

                        @if ($jobOrder->technician?->specialization)
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                {{ $jobOrder->technician->specialization }}
                            </p>
                        @endif
                    </div>

                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-400">
                            Schedule
                        </p>

                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                            @if ($jobOrder->scheduled_date)
                                {{ $jobOrder->scheduled_date->format('F d, Y') }}

                                @if ($jobOrder->scheduled_time)
                                    at {{ \Illuminate\Support\Carbon::parse($jobOrder->scheduled_time)->format('g:i A') }}
                                @endif
                            @else
                                Not scheduled
                            @endif
                        </p>
                    </div>

                </div>

            </section>

        </div>


        <aside class="space-y-4">

            <section
                class="
                    border border-gray-200 bg-white
                    dark:border-neutral-800 dark:bg-neutral-900
                ">

                <header class="border-b border-gray-100 px-4 py-3 dark:border-neutral-800">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
                        Subscriber
                    </h2>
                </header>

                <div class="space-y-3 p-4">

                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $customerName ?: 'Unknown subscriber' }}
                        </p>

                        <p class="mt-0.5 text-xs font-medium text-[#008080] dark:text-[#5EEAD4]">
                            {{ $jobOrder->customer?->customer_code }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400">Email</p>
                        <p class="mt-0.5 break-all text-sm text-gray-700 dark:text-gray-300">
                            {{ $jobOrder->customer?->email ?: 'Not recorded' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400">Phone</p>
                        <p class="mt-0.5 text-sm text-gray-700 dark:text-gray-300">
                            {{ $jobOrder->customer?->phone ?: 'Not recorded' }}
                        </p>
                    </div>

                    @if ($jobOrder->customer)

                    <a
                        href="{{ route('admin.subscribers.show', $jobOrder->customer) }}"
                        class="
                            inline-flex min-h-9 w-full
                            items-center justify-center gap-2
                            border border-gray-300
                            px-3 py-2 text-xs font-semibold
                            text-gray-700
                            hover:border-[#008080]
                            hover:text-[#008080]
                            dark:border-neutral-700
                            dark:text-gray-300
                        ">
                        <i data-lucide="user-round" class="h-3.5 w-3.5"></i>
                        Open Subscriber
                    </a>

                    @endif

                </div>

            </section>


            <section
                class="
                    border border-gray-200 bg-white
                    dark:border-neutral-800 dark:bg-neutral-900
                ">

                <header class="border-b border-gray-100 px-4 py-3 dark:border-neutral-800">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
                        Record Information
                    </h2>
                </header>

                <div class="space-y-3 p-4 text-sm">

                    <div class="flex items-center justify-between gap-4">
                        <span class="text-gray-500 dark:text-gray-400">Status</span>
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ $statusDisplay['label'] }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span class="text-gray-500 dark:text-gray-400">Created</span>
                        <span class="text-right font-medium text-gray-900 dark:text-white">
                            {{ $jobOrder->created_at?->format('M d, Y g:i A') }}
                        </span>
                    </div>

                    @if ($jobOrder->started_at)

                    <div class="flex items-center justify-between gap-4">
                        <span class="text-gray-500 dark:text-gray-400">Started</span>
                        <span class="text-right font-medium text-gray-900 dark:text-white">
                            {{ $jobOrder->started_at->format('M d, Y g:i A') }}
                        </span>
                    </div>

                    @endif

                    @if ($jobOrder->completed_at)

                    <div class="flex items-center justify-between gap-4">
                        <span class="text-gray-500 dark:text-gray-400">Completed</span>
                        <span class="text-right font-medium text-gray-900 dark:text-white">
                            {{ $jobOrder->completed_at->format('M d, Y g:i A') }}
                        </span>
                    </div>

                    @endif

                </div>

            </section>

        </aside>

    </div>

</div>

@endsection
