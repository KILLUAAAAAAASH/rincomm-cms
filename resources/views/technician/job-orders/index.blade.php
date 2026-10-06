@extends('layouts.app')

@section('title', 'Job Orders | Rincomm CMS')
@section('page-title', 'Job Orders')

@section('secondary-navigation')
<nav class="flex min-w-max items-center gap-1 overflow-x-auto py-1.5" aria-label="Technician navigation">
    <a
        href="{{ route('technician.dashboard') }}"
        class="inline-flex min-h-8 items-center justify-center whitespace-nowrap px-3 py-1.5 text-xs font-medium text-neutral-600 transition hover:bg-[#008080]/10 hover:text-[#008080] dark:text-neutral-400 dark:hover:bg-[#008080]/15 dark:hover:text-[#5EEAD4] sm:text-sm">
        Dashboard
    </a>

    <a
        href="{{ route('technician.job-orders.index') }}"
        class="inline-flex min-h-8 items-center justify-center whitespace-nowrap bg-[#008080] px-3 py-1.5 text-xs font-medium text-white shadow-sm sm:text-sm">
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

$statusClassFor = fn (string $jobStatus) => match ($jobStatus) {
    'assigned' => 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-950/40 dark:text-blue-300 dark:ring-blue-400/30',
    'in_progress' => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-400/30',
    'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-400/30',
    'cancelled' => 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-950/40 dark:text-red-300 dark:ring-red-400/30',
    default => 'bg-neutral-100 text-neutral-700 ring-neutral-600/20 dark:bg-neutral-800 dark:text-neutral-300 dark:ring-neutral-500/30',
};

$customerNameFor = fn ($jobOrder) => trim(
    collect([
        $jobOrder->customer?->first_name,
        $jobOrder->customer?->middle_name,
        $jobOrder->customer?->last_name,
    ])->filter()->implode(' ')
);

$jobTypeLabelFor = fn ($jobOrder) =>
    $jobOrder->job_type
        ? \Illuminate\Support\Str::headline($jobOrder->job_type)
        : 'Type Not Set';

$modalContext = (string) old('modal_context', '');
$oldProofJobOrderId = (string) old('proof_job_order_id', '');
$oldCompletionJobOrderId = (string) old('completion_job_order_id', '');
$sessionProofJobOrderId = (string) session('proof_job_order_id', '');

$requestedJobOrderId = (string) request()->query(
    'job',
    request()->query('proof', '')
);

$requestedJobIsAvailable =
    $requestedJobOrderId !== ''
    && $jobOrders->contains(
        fn ($jobOrder) =>
            (string) $jobOrder->id === $requestedJobOrderId
    );

$proofValidationFailed =
    $errors->has('proof')
    || $errors->has('notes');

$completionValidationFailed =
    $errors->has('completion_report');

$reopenCompletionJobOrderId =
    $completionValidationFailed
    && $modalContext === 'completion'
        ? $oldCompletionJobOrderId
        : (
            $proofValidationFailed
            && $modalContext === 'completion_proof'
                ? $oldProofJobOrderId
                : (
                    $sessionProofJobOrderId !== ''
                        ? $sessionProofJobOrderId
                        : ''
                )
        );

$reopenJobOrderId =
    $reopenCompletionJobOrderId === ''
    && $requestedJobIsAvailable
        ? $requestedJobOrderId
        : '';
@endphp


<div
    id="technician-job-modal-state"
    class="hidden"
    data-reopen-job-order-id="{{ $reopenJobOrderId }}"
    data-reopen-completion-job-order-id="{{ $reopenCompletionJobOrderId }}">
</div>


<div class="mx-auto w-full max-w-7xl space-y-4">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

        <div>
            <h1 class="text-lg font-bold text-neutral-900 dark:text-white sm:text-xl">
                Assigned Job Orders
            </h1>

            <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                View and process field work assigned to you.
            </p>
        </div>


        <div class="inline-flex w-fit items-center gap-2 border border-neutral-200 bg-white px-3 py-2 dark:border-neutral-800 dark:bg-neutral-900">
            <i
                data-lucide="badge-check"
                class="h-4 w-4 shrink-0 text-[#008080] dark:text-[#5EEAD4]"
                aria-hidden="true">
            </i>

            <div class="min-w-0">
                <p class="text-[10px] font-medium uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                    Technician
                </p>

                <p class="truncate text-sm font-semibold text-neutral-800 dark:text-neutral-100">
                    {{ $technician->technician_code }}
                </p>
            </div>
        </div>

    </div>


    @if (session('success'))

    <div
        class="flex items-start gap-3 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300"
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


    <div class="overflow-x-auto border-y border-neutral-200 py-2 dark:border-neutral-800 sm:border sm:bg-white sm:px-3 dark:sm:bg-neutral-900">

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
                    inline-flex min-h-9 items-center justify-center
                    whitespace-nowrap px-3 py-2 text-xs font-semibold
                    transition sm:text-sm

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


    @if ($jobOrders->isEmpty())

    <div class="border border-dashed border-neutral-300 bg-white px-5 py-12 text-center dark:border-neutral-700 dark:bg-neutral-900">

        <div class="mx-auto flex h-11 w-11 items-center justify-center bg-neutral-100 text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400">
            <i
                data-lucide="clipboard-check"
                class="h-5 w-5"
                aria-hidden="true">
            </i>
        </div>

        <h2 class="mt-3 text-sm font-semibold text-neutral-900 dark:text-white">
            No Job Orders Found
        </h2>

        <p class="mx-auto mt-1 max-w-sm text-xs leading-5 text-neutral-500 dark:text-neutral-400">
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


    <div class="space-y-3 md:hidden">

        @foreach ($jobOrders as $jobOrder)

        @php
        $customerName = $customerNameFor($jobOrder);
        $jobTypeLabel = $jobTypeLabelFor($jobOrder);
        $statusClasses = $statusClassFor($jobOrder->status);
        @endphp

        <article class="overflow-hidden border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">

            <div class="p-4">

                <div class="flex items-start justify-between gap-3">

                    <div class="min-w-0">
                        <p class="text-xs font-medium text-[#008080] dark:text-[#5EEAD4]">
                            {{ $jobOrder->job_order_number }}
                        </p>

                        <h2 class="mt-1 truncate text-base font-semibold text-neutral-900 dark:text-white">
                            {{ $jobTypeLabel }}
                        </h2>
                    </div>


                    <span class="inline-flex shrink-0 items-center px-2.5 py-1 text-[11px] font-semibold ring-1 ring-inset {{ $statusClasses }}">
                        {{ \Illuminate\Support\Str::headline($jobOrder->status) }}
                    </span>

                </div>


                <div class="mt-3 grid grid-cols-2 gap-3">

                    <div class="min-w-0">
                        <p class="text-[10px] font-medium uppercase tracking-wide text-neutral-400">
                            Customer
                        </p>

                        <p class="mt-0.5 truncate text-sm font-medium text-neutral-800 dark:text-neutral-200">
                            {{ $customerName !== ''
                                ? $customerName
                                : 'Unavailable'
                            }}
                        </p>
                    </div>


                    <div>
                        <p class="text-[10px] font-medium uppercase tracking-wide text-neutral-400">
                            Schedule
                        </p>

                        <p class="mt-0.5 text-sm text-neutral-700 dark:text-neutral-300">
                            @if ($jobOrder->scheduled_date)
                                {{ $jobOrder->scheduled_date->format('M j, Y') }}

                                @if ($jobOrder->scheduled_time)
                                    &middot;
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

            </div>


            <div class="border-t border-neutral-200 bg-neutral-50 px-4 py-3 dark:border-neutral-800 dark:bg-neutral-950/50">

                <button
                    type="button"
                    data-job-modal-open="{{ $jobOrder->id }}"
                    class="inline-flex min-h-10 w-full items-center justify-center gap-2 bg-[#008080] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#006f6f] focus:outline-none focus:ring-2 focus:ring-[#008080]/30">

                    <i
                        data-lucide="eye"
                        class="h-4 w-4"
                        aria-hidden="true">
                    </i>

                    View
                </button>

            </div>

        </article>

        @endforeach

    </div>


    <div class="hidden overflow-hidden border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900 md:block">

        <div class="overflow-x-auto">

            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-800">

                <thead class="bg-neutral-50 dark:bg-neutral-950/60">

                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                            Job Order
                        </th>

                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                            Customer
                        </th>

                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                            Schedule
                        </th>

                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                            Status
                        </th>

                        <th scope="col" class="px-4 py-3 text-right">
                            <span class="sr-only">
                                Actions
                            </span>
                        </th>
                    </tr>

                </thead>


                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-800">

                    @foreach ($jobOrders as $jobOrder)

                    @php
                    $customerName = $customerNameFor($jobOrder);
                    $statusClasses = $statusClassFor($jobOrder->status);
                    @endphp

                    <tr class="transition hover:bg-neutral-50 dark:hover:bg-neutral-800/40">

                        <td class="px-4 py-3">
                            <p class="text-sm font-semibold text-neutral-900 dark:text-white">
                                {{ $jobOrder->job_order_number }}
                            </p>

                            <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                                {{ $jobTypeLabelFor($jobOrder) }}
                            </p>
                        </td>


                        <td class="px-4 py-3">
                            <p class="max-w-52 truncate text-sm font-medium text-neutral-800 dark:text-neutral-200">
                                {{ $customerName !== ''
                                    ? $customerName
                                    : 'Unavailable'
                                }}
                            </p>

                            @if ($jobOrder->customer?->phone)

                            <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                                {{ $jobOrder->customer->phone }}
                            </p>

                            @endif
                        </td>


                        <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-700 dark:text-neutral-300">

                            @if ($jobOrder->scheduled_date)

                            <p>
                                {{ $jobOrder->scheduled_date->format('M j, Y') }}
                            </p>

                            <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
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
                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusClasses }}">
                                {{ \Illuminate\Support\Str::headline($jobOrder->status) }}
                            </span>
                        </td>


                        <td class="px-4 py-3 text-right">

                            <button
                                type="button"
                                data-job-modal-open="{{ $jobOrder->id }}"
                                class="inline-flex min-h-9 items-center justify-center gap-1.5 border border-neutral-200 px-3 py-2 text-xs font-semibold text-neutral-700 transition hover:border-[#008080] hover:bg-[#008080]/10 hover:text-[#008080] focus:outline-none focus:ring-2 focus:ring-[#008080]/20 dark:border-neutral-700 dark:text-neutral-300 dark:hover:border-[#14B8A6] dark:hover:bg-[#008080]/15 dark:hover:text-[#5EEAD4]">

                                <i
                                    data-lucide="eye"
                                    class="h-3.5 w-3.5"
                                    aria-hidden="true">
                                </i>

                                View
                            </button>

                        </td>

                    </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>


    @if ($jobOrders->hasPages())
    <div>
        {{ $jobOrders->links() }}
    </div>
    @endif

    @endif

</div>


@foreach ($jobOrders as $jobOrder)

@php
$customerName = $customerNameFor($jobOrder);
$jobTypeLabel = $jobTypeLabelFor($jobOrder);
$statusClasses = $statusClassFor($jobOrder->status);
$proofCount = $jobOrder->proofs->count();

$isCompletionModalReopened =
    $reopenCompletionJobOrderId ===
    (string) $jobOrder->id;
@endphp


<div
    id="job-modal-{{ $jobOrder->id }}"
    data-job-modal="{{ $jobOrder->id }}"
    data-workflow-modal
    class="fixed inset-0 z-[9999] hidden items-center justify-center overflow-hidden bg-slate-950/50 p-2 backdrop-blur-[1px] sm:p-4"
    role="dialog"
    aria-modal="true"
    aria-hidden="true"
    aria-labelledby="job-modal-title-{{ $jobOrder->id }}">

    <div
        data-workflow-overlay
        class="absolute inset-0">
    </div>


    <section class="relative z-10 w-[calc(100vw-1rem)] max-w-[560px] rounded-lg border border-neutral-200 bg-white shadow-2xl dark:border-neutral-700 dark:bg-neutral-900">

        <header class="flex items-start justify-between gap-3 border-b border-neutral-200 px-4 py-3 dark:border-neutral-800">

            <div class="min-w-0">

                <div class="flex flex-wrap items-center gap-2">

                    <h2
                        id="job-modal-title-{{ $jobOrder->id }}"
                        class="text-sm font-semibold text-neutral-900 dark:text-white sm:text-base">
                        {{ $jobOrder->job_order_number }}
                    </h2>

                    <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-semibold ring-1 ring-inset {{ $statusClasses }}">
                        {{ \Illuminate\Support\Str::headline($jobOrder->status) }}
                    </span>

                </div>

                <p class="mt-0.5 text-[11px] text-neutral-500 dark:text-neutral-400">
                    {{ $jobTypeLabel }}
                </p>

            </div>


            <button
                type="button"
                data-workflow-close
                class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-700 focus:outline-none focus:ring-2 focus:ring-[#008080]/20 dark:hover:bg-neutral-800 dark:hover:text-neutral-200"
                aria-label="Close job details">

                <i
                    data-lucide="x"
                    class="h-4 w-4"
                    aria-hidden="true">
                </i>
            </button>

        </header>


        <div class="overflow-hidden">

            @if ($jobOrder->job_type === null)

            <div class="px-4 pt-3">
                <div class="flex items-start gap-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-[11px] text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300">

                    <i
                        data-lucide="triangle-alert"
                        class="mt-0.5 h-3.5 w-3.5 shrink-0"
                        aria-hidden="true">
                    </i>

                    <span>
                        Job type must be assigned before this Job Order can be started.
                    </span>
                </div>
            </div>

            @endif


            <dl class="grid grid-cols-2 gap-x-4 gap-y-3 px-4 py-3">

                <div class="min-w-0">
                    <dt class="text-[9px] font-semibold uppercase tracking-wide text-neutral-400">
                        Customer
                    </dt>

                    <dd class="mt-0.5 break-words text-xs font-medium text-neutral-800 dark:text-neutral-200 sm:text-sm">
                        {{ $customerName !== ''
                            ? $customerName
                            : 'Unavailable'
                        }}
                    </dd>
                </div>


                <div class="min-w-0">
                    <dt class="text-[9px] font-semibold uppercase tracking-wide text-neutral-400">
                        Schedule
                    </dt>

                    <dd class="mt-0.5 break-words text-xs text-neutral-800 dark:text-neutral-200 sm:text-sm">

                        @if ($jobOrder->scheduled_date)

                        {{ $jobOrder->scheduled_date->format('M j, Y') }}

                        @if ($jobOrder->scheduled_time)
                            &middot;
                            {{ \Carbon\Carbon::parse(
                                $jobOrder->scheduled_time
                            )->format('g:i A') }}
                        @endif

                        @else

                        Not scheduled

                        @endif

                    </dd>
                </div>


                <div class="min-w-0">
                    <dt class="text-[9px] font-semibold uppercase tracking-wide text-neutral-400">
                        Service Request
                    </dt>

                    <dd class="mt-0.5 break-words text-xs text-neutral-800 dark:text-neutral-200 sm:text-sm">
                        {{ $jobOrder->serviceRequest?->ticket_number
                            ?: 'Not available'
                        }}
                    </dd>
                </div>


                <div class="min-w-0">
                    <dt class="text-[9px] font-semibold uppercase tracking-wide text-neutral-400">
                        Request Type
                    </dt>

                    <dd class="mt-0.5 break-words text-xs text-neutral-800 dark:text-neutral-200 sm:text-sm">
                        {{ $jobOrder->serviceRequest?->request_type
                            ? \Illuminate\Support\Str::headline(
                                $jobOrder->serviceRequest->request_type
                            )
                            : 'Not available'
                        }}
                    </dd>
                </div>


                @if ($jobOrder->started_at)

                <div class="min-w-0">
                    <dt class="text-[9px] font-semibold uppercase tracking-wide text-neutral-400">
                        Started
                    </dt>

                    <dd class="mt-0.5 break-words text-xs text-neutral-800 dark:text-neutral-200 sm:text-sm">
                        {{ $jobOrder->started_at->format('M j, Y g:i A') }}
                    </dd>
                </div>

                @endif


                @if ($jobOrder->completed_at)

                <div class="min-w-0">
                    <dt class="text-[9px] font-semibold uppercase tracking-wide text-neutral-400">
                        Completed
                    </dt>

                    <dd class="mt-0.5 break-words text-xs text-neutral-800 dark:text-neutral-200 sm:text-sm">
                        {{ $jobOrder->completed_at->format('M j, Y g:i A') }}
                    </dd>
                </div>

                @endif

            </dl>


            @if (
                $jobOrder->customer?->installation_address
                || $jobOrder->customer?->address
            )

            <div class="border-t border-neutral-200 px-4 py-3 dark:border-neutral-800">

                <div class="flex items-start gap-2">

                    <i
                        data-lucide="map-pin"
                        class="mt-0.5 h-3.5 w-3.5 shrink-0 text-neutral-400"
                        aria-hidden="true">
                    </i>

                    <div class="min-w-0">
                        <p class="text-[10px] font-medium text-neutral-500 dark:text-neutral-400">
                            Location
                        </p>

                        <p class="mt-0.5 break-words text-xs leading-5 text-neutral-800 dark:text-neutral-200">
                            {{ $jobOrder->customer?->installation_address
                                ?: $jobOrder->customer?->address
                            }}
                        </p>
                    </div>

                </div>

            </div>

            @endif


            @if ($jobOrder->description)

            <div class="border-t border-neutral-200 px-4 py-3 dark:border-neutral-800">
                <p class="text-[10px] font-medium text-neutral-500 dark:text-neutral-400">
                    Work Description
                </p>

                <p class="mt-0.5 whitespace-pre-line break-words text-xs leading-5 text-neutral-800 dark:text-neutral-200">
                    {{ $jobOrder->description }}
                </p>
            </div>

            @endif


            @if (
                $jobOrder->status === 'completed'
                && $jobOrder->completion_report
            )

            <div class="border-t border-neutral-200 px-4 py-3 dark:border-neutral-800">
                <p class="text-[10px] font-medium text-neutral-500 dark:text-neutral-400">
                    Completion Report
                </p>

                <p class="mt-0.5 whitespace-pre-line break-words text-xs leading-5 text-neutral-800 dark:text-neutral-200">
                    {{ $jobOrder->completion_report }}
                </p>
            </div>

            @endif


            @if ($jobOrder->status === 'completed')

            <div class="flex items-center justify-between gap-3 border-t border-neutral-200 bg-neutral-50/70 px-4 py-2.5 dark:border-neutral-800 dark:bg-neutral-950/30">

                <div class="flex min-w-0 items-center gap-2">

                    <i
                        data-lucide="paperclip"
                        class="h-3.5 w-3.5 shrink-0 text-neutral-400"
                        aria-hidden="true">
                    </i>

                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold text-neutral-700 dark:text-neutral-300">
                            Proof of Work
                        </p>

                        <p class="text-[10px] text-neutral-500 dark:text-neutral-400">
                            {{ $proofCount }}
                            {{ \Illuminate\Support\Str::plural(
                                'file',
                                $proofCount
                            ) }}
                            attached
                        </p>
                    </div>

                </div>


                @if ($proofCount > 0)

                <details class="relative shrink-0">

                    <summary
                        class="flex h-7 w-7 cursor-pointer list-none items-center justify-center rounded-md text-neutral-500 transition hover:bg-neutral-200/70 hover:text-[#008080] focus:outline-none focus:ring-2 focus:ring-[#008080]/20 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-[#5EEAD4]"
                        aria-label="More proof options">

                        <i
                            data-lucide="ellipsis-vertical"
                            class="h-4 w-4"
                            aria-hidden="true">
                        </i>
                    </summary>


                    <div class="absolute bottom-8 right-0 z-30 w-[min(18rem,calc(100vw-2rem))] rounded-lg border border-neutral-200 bg-white shadow-xl dark:border-neutral-700 dark:bg-neutral-900">

                        @foreach ($jobOrder->proofs as $proof)

                        <div class="border-b border-neutral-200 p-2.5 last:border-b-0 dark:border-neutral-800">

                            <p
                                class="truncate text-[11px] font-semibold text-neutral-900 dark:text-white"
                                title="{{ $proof->original_name }}">
                                {{ $proof->original_name }}
                            </p>

                            <p class="mt-0.5 text-[9px] text-neutral-500 dark:text-neutral-400">
                                {{ number_format(
                                    $proof->file_size / 1024,
                                    1
                                ) }}
                                KB
                            </p>


                            <div class="mt-2 flex gap-1">

                                <button
                                    type="button"
                                    data-proof-preview-open
                                    data-proof-preview-url="{{ route(
                                        'technician.job-orders.proofs.show',
                                        [$jobOrder, $proof]
                                    ) }}"
                                    data-proof-preview-name="{{ $proof->original_name }}"
                                    data-proof-preview-meta="{{ number_format(
                                        $proof->file_size / 1024,
                                        1
                                    ) }} KB"
                                    data-proof-return-modal="job-modal-{{ $jobOrder->id }}"
                                    class="inline-flex min-h-8 flex-1 items-center justify-center gap-1 rounded-md px-2 text-[11px] font-semibold text-neutral-700 transition hover:bg-[#008080]/10 hover:text-[#008080] dark:text-neutral-300 dark:hover:bg-[#008080]/15 dark:hover:text-[#5EEAD4]">

                                    <i
                                        data-lucide="eye"
                                        class="h-3.5 w-3.5"
                                        aria-hidden="true">
                                    </i>

                                    View
                                </button>


                                <a
                                    href="{{ route(
                                        'technician.job-orders.proofs.download',
                                        [$jobOrder, $proof]
                                    ) }}"
                                    class="inline-flex min-h-8 flex-1 items-center justify-center gap-1 rounded-md px-2 text-[11px] font-semibold text-neutral-700 transition hover:bg-[#008080]/10 hover:text-[#008080] dark:text-neutral-300 dark:hover:bg-[#008080]/15 dark:hover:text-[#5EEAD4]">

                                    <i
                                        data-lucide="download"
                                        class="h-3.5 w-3.5"
                                        aria-hidden="true">
                                    </i>

                                    Download
                                </a>

                            </div>

                        </div>

                        @endforeach

                    </div>

                </details>

                @endif

            </div>

            @endif

        </div>


        <footer class="flex items-center justify-end gap-2 border-t border-neutral-200 px-4 py-3 dark:border-neutral-800">

            <button
                type="button"
                data-workflow-close
                class="inline-flex min-h-9 items-center justify-center rounded-md border border-neutral-300 bg-white px-3 py-2 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-neutral-300/50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300 dark:hover:bg-neutral-800">
                Close
            </button>


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
                    class="inline-flex min-h-9 items-center justify-center rounded-md bg-[#008080] px-4 py-2 text-xs font-semibold text-white transition hover:bg-[#006f6f] focus:outline-none focus:ring-2 focus:ring-[#008080]/30 disabled:cursor-not-allowed disabled:bg-neutral-300 disabled:text-neutral-500 dark:disabled:bg-neutral-700 dark:disabled:text-neutral-400">
                    Start Job
                </button>

            </form>

            @elseif ($jobOrder->status === 'in_progress')

            <button
                type="button"
                data-completion-modal-open="{{ $jobOrder->id }}"
                class="inline-flex min-h-9 items-center justify-center gap-1.5 rounded-md bg-[#008080] px-4 py-2 text-xs font-semibold text-white transition hover:bg-[#006f6f] focus:outline-none focus:ring-2 focus:ring-[#008080]/30">

                <i
                    data-lucide="circle-check"
                    class="h-3.5 w-3.5"
                    aria-hidden="true">
                </i>

                Submit Completion Report
            </button>

            @endif

        </footer>

    </section>

</div>


@if ($jobOrder->status === 'in_progress')

<div
    id="completion-modal-{{ $jobOrder->id }}"
    data-completion-modal="{{ $jobOrder->id }}"
    data-workflow-modal
    class="fixed inset-0 z-[9999] hidden items-center justify-center overflow-hidden bg-slate-950/50 p-2 backdrop-blur-[1px] sm:p-4"
    role="dialog"
    aria-modal="true"
    aria-hidden="true"
    aria-labelledby="completion-modal-title-{{ $jobOrder->id }}">

    <div
        data-workflow-overlay
        class="absolute inset-0">
    </div>


    <section class="relative z-10 w-[calc(100vw-1rem)] max-w-[560px] rounded-lg border border-neutral-200 bg-white shadow-2xl dark:border-neutral-700 dark:bg-neutral-900">

        <header class="flex items-start justify-between gap-3 border-b border-neutral-200 px-4 py-3 dark:border-neutral-800">

            <div class="min-w-0">

                <h2
                    id="completion-modal-title-{{ $jobOrder->id }}"
                    class="text-sm font-semibold text-neutral-900 dark:text-white sm:text-base">
                    Submit Completion Report
                </h2>

                <p class="mt-0.5 truncate text-[10px] text-neutral-500 dark:text-neutral-400">
                    {{ $jobOrder->job_order_number }}
                    &middot;
                    {{ $jobTypeLabel }}
                </p>

            </div>


            <button
                type="button"
                data-workflow-close
                class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-700 focus:outline-none focus:ring-2 focus:ring-[#008080]/20 dark:hover:bg-neutral-800 dark:hover:text-neutral-200"
                aria-label="Close completion report">

                <i
                    data-lucide="x"
                    class="h-4 w-4"
                    aria-hidden="true">
                </i>
            </button>

        </header>


        <div class="overflow-hidden">

            @if (
                session('success')
                && $sessionProofJobOrderId
                    === (string) $jobOrder->id
            )

            <div class="px-4 pt-3">

                <div class="flex items-start gap-2 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300">

                    <i
                        data-lucide="circle-check"
                        class="mt-0.5 h-3.5 w-3.5 shrink-0"
                        aria-hidden="true">
                    </i>

                    <span>
                        {{ session('success') }}
                    </span>

                </div>

            </div>

            @endif


            <form
                id="completion-form-{{ $jobOrder->id }}"
                method="POST"
                action="{{ route(
                    'technician.job-orders.complete',
                    $jobOrder
                ) }}"
                class="px-4 pt-3"
                data-lock-submit>

                @csrf
                @method('PATCH')

                <input
                    type="hidden"
                    name="modal_context"
                    value="completion">

                <input
                    type="hidden"
                    name="completion_job_order_id"
                    value="{{ $jobOrder->id }}">


                <label
                    for="completion-report-{{ $jobOrder->id }}"
                    class="block text-xs font-semibold text-neutral-800 dark:text-neutral-200">
                    Work Performed
                </label>

                <p class="mt-0.5 text-[10px] leading-4 text-neutral-500 dark:text-neutral-400">
                    Describe the work completed, findings, testing, and final result.
                </p>

                <textarea
                    id="completion-report-{{ $jobOrder->id }}"
                    name="completion_report"
                    rows="4"
                    maxlength="5000"
                    required
                    placeholder="Describe the work performed and final result..."
                    class="
                        mt-1.5 block w-full resize-none rounded-md border
                        bg-white px-3 py-2 text-xs text-neutral-900
                        outline-none transition placeholder:text-neutral-400
                        focus:border-[#008080] focus:ring-2 focus:ring-[#008080]/20
                        dark:bg-neutral-950 dark:text-white
                        dark:placeholder:text-neutral-500

                        {{ $isCompletionModalReopened
                            && $errors->has('completion_report')
                                ? 'border-red-500 ring-1 ring-red-500/20'
                                : 'border-neutral-300 dark:border-neutral-700'
                        }}
                    ">{{ $isCompletionModalReopened
                        ? old('completion_report')
                        : ''
                    }}</textarea>


                @if (
                    $isCompletionModalReopened
                    && $errors->has('completion_report')
                )

                <p class="mt-1 flex items-start gap-1 text-[10px] text-red-600 dark:text-red-400">
                    <i
                        data-lucide="triangle-alert"
                        class="mt-0.5 h-3 w-3 shrink-0"
                        aria-hidden="true">
                    </i>

                    {{ $errors->first('completion_report') }}
                </p>

                @endif

            </form>


            <div class="mt-3 border-y border-neutral-200 bg-neutral-50/70 px-4 py-3 dark:border-neutral-800 dark:bg-neutral-950/30">

                <div class="flex items-center justify-between gap-3">

                    <div class="flex min-w-0 items-center gap-2">

                        <i
                            data-lucide="camera"
                            class="h-3.5 w-3.5 shrink-0 text-[#008080] dark:text-[#5EEAD4]"
                            aria-hidden="true">
                        </i>

                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-neutral-900 dark:text-white">
                                Proof of Work
                            </p>

                            <p class="text-[10px] text-neutral-500 dark:text-neutral-400">
                                {{ $proofCount }}
                                {{ \Illuminate\Support\Str::plural(
                                    'file',
                                    $proofCount
                                ) }}
                                attached
                            </p>
                        </div>

                    </div>


                    @if ($proofCount > 0)

                    <details class="relative shrink-0">

                        <summary
                            class="flex h-7 w-7 cursor-pointer list-none items-center justify-center rounded-md text-neutral-500 transition hover:bg-neutral-200/70 hover:text-[#008080] focus:outline-none focus:ring-2 focus:ring-[#008080]/20 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-[#5EEAD4]"
                            aria-label="More proof options">

                            <i
                                data-lucide="ellipsis-vertical"
                                class="h-4 w-4"
                                aria-hidden="true">
                            </i>
                        </summary>


                        <div class="absolute right-0 top-8 z-30 w-[min(18rem,calc(100vw-2rem))] rounded-lg border border-neutral-200 bg-white shadow-xl dark:border-neutral-700 dark:bg-neutral-900">

                            @foreach ($jobOrder->proofs as $proof)

                            <div class="border-b border-neutral-200 p-2.5 last:border-b-0 dark:border-neutral-800">

                                <p
                                    class="truncate text-[11px] font-semibold text-neutral-900 dark:text-white"
                                    title="{{ $proof->original_name }}">
                                    {{ $proof->original_name }}
                                </p>

                                <p class="mt-0.5 text-[9px] text-neutral-500 dark:text-neutral-400">
                                    {{ number_format(
                                        $proof->file_size / 1024,
                                        1
                                    ) }}
                                    KB
                                    &middot;
                                    {{ $proof->created_at?->format(
                                        'M j, Y g:i A'
                                    ) }}
                                </p>


                                @if ($proof->notes)

                                <p class="mt-1 line-clamp-2 text-[10px] leading-4 text-neutral-600 dark:text-neutral-300">
                                    {{ $proof->notes }}
                                </p>

                                @endif


                                <div class="mt-2 flex items-center gap-1">

                                    <button
                                        type="button"
                                        data-proof-preview-open
                                        data-proof-preview-url="{{ route(
                                            'technician.job-orders.proofs.show',
                                            [$jobOrder, $proof]
                                        ) }}"
                                        data-proof-preview-name="{{ $proof->original_name }}"
                                        data-proof-preview-meta="{{ number_format(
                                            $proof->file_size / 1024,
                                            1
                                        ) }} KB &middot; {{ $proof->created_at?->format(
                                            'M j, Y g:i A'
                                        ) }}"
                                        data-proof-return-modal="completion-modal-{{ $jobOrder->id }}"
                                        class="inline-flex min-h-8 flex-1 items-center justify-center gap-1 rounded-md px-2 text-[11px] font-semibold text-neutral-700 transition hover:bg-[#008080]/10 hover:text-[#008080] dark:text-neutral-300 dark:hover:bg-[#008080]/15 dark:hover:text-[#5EEAD4]">

                                        <i
                                            data-lucide="eye"
                                            class="h-3.5 w-3.5"
                                            aria-hidden="true">
                                        </i>

                                        View
                                    </button>


                                    <a
                                        href="{{ route(
                                            'technician.job-orders.proofs.download',
                                            [$jobOrder, $proof]
                                        ) }}"
                                        class="inline-flex min-h-8 flex-1 items-center justify-center gap-1 rounded-md px-2 text-[11px] font-semibold text-neutral-700 transition hover:bg-[#008080]/10 hover:text-[#008080] dark:text-neutral-300 dark:hover:bg-[#008080]/15 dark:hover:text-[#5EEAD4]">

                                        <i
                                            data-lucide="download"
                                            class="h-3.5 w-3.5"
                                            aria-hidden="true">
                                        </i>

                                        Download
                                    </a>


                                    <button
                                        type="button"
                                        data-proof-delete-open
                                        data-proof-delete-action="{{ route(
                                            'technician.job-orders.proofs.destroy',
                                            [$jobOrder, $proof]
                                        ) }}"
                                        data-proof-delete-name="{{ $proof->original_name }}"
                                        data-proof-delete-job-order-id="{{ $jobOrder->id }}"
                                        data-proof-return-modal="completion-modal-{{ $jobOrder->id }}"
                                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500/20 dark:text-red-400 dark:hover:bg-red-950/40"
                                        aria-label="Delete {{ $proof->original_name }}">

                                        <i
                                            data-lucide="trash-2"
                                            class="h-3.5 w-3.5"
                                            aria-hidden="true">
                                        </i>
                                    </button>

                                </div>

                            </div>

                            @endforeach

                        </div>

                    </details>

                    @endif

                </div>


                <form
                    method="POST"
                    action="{{ route(
                        'technician.job-orders.proofs.store',
                        $jobOrder
                    ) }}"
                    enctype="multipart/form-data"
                    class="mt-2.5"
                    data-lock-submit>

                    @csrf

                    <input
                        type="hidden"
                        name="modal_context"
                        value="completion_proof">

                    <input
                        type="hidden"
                        name="proof_job_order_id"
                        value="{{ $jobOrder->id }}">


                    @if (
                        $isCompletionModalReopened
                        && $errors->has('proof')
                    )

                    <div class="mb-2 flex items-start gap-2 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-[10px] text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300" role="alert">

                        <i
                            data-lucide="triangle-alert"
                            class="mt-0.5 h-3 w-3 shrink-0"
                            aria-hidden="true">
                        </i>

                        <span>
                            {{ $errors->first('proof') }}
                        </span>

                    </div>

                    @endif


                    <label
                        for="completion-proof-{{ $jobOrder->id }}"
                        class="flex cursor-pointer items-center gap-2 rounded-md border border-dashed border-neutral-300 bg-white px-3 py-2 transition hover:border-[#008080] hover:bg-[#008080]/5 dark:border-neutral-700 dark:bg-neutral-900 dark:hover:border-[#14B8A6] dark:hover:bg-[#008080]/10">

                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-[#008080]/10 text-[#008080] dark:bg-[#008080]/20 dark:text-[#5EEAD4]">
                            <i
                                data-lucide="upload-cloud"
                                class="h-3.5 w-3.5"
                                aria-hidden="true">
                            </i>
                        </div>


                        <div class="min-w-0 flex-1">

                            <p class="text-[11px] font-semibold text-neutral-800 dark:text-neutral-200">
                                Choose proof file
                            </p>

                            <p class="text-[9px] text-neutral-500 dark:text-neutral-400">
                                PDF, JPG, JPEG, PNG &middot; Max 5 MB
                            </p>

                            <p
                                data-proof-file-name
                                class="mt-0.5 hidden truncate text-[10px] font-medium text-[#008080] dark:text-[#5EEAD4]">
                            </p>

                        </div>

                    </label>


                    <input
                        id="completion-proof-{{ $jobOrder->id }}"
                        name="proof"
                        type="file"
                        accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                        required
                        data-proof-file-input
                        class="sr-only">


                    <div class="mt-2 grid grid-cols-[minmax(0,1fr)_auto] items-end gap-2">

                        <div class="min-w-0">

                            <label
                                for="proof-notes-{{ $jobOrder->id }}"
                                class="block text-[10px] font-semibold text-neutral-700 dark:text-neutral-300">
                                Proof note
                                <span class="font-normal text-neutral-400">
                                    (optional)
                                </span>
                            </label>

                            <input
                                id="proof-notes-{{ $jobOrder->id }}"
                                name="notes"
                                type="text"
                                maxlength="1000"
                                value="{{ $isCompletionModalReopened
                                    && $modalContext === 'completion_proof'
                                        ? old('notes')
                                        : ''
                                }}"
                                placeholder="What does this proof show?"
                                class="
                                    mt-1 block w-full rounded-md border bg-white
                                    px-2.5 py-2 text-[11px] text-neutral-900
                                    outline-none transition placeholder:text-neutral-400
                                    focus:border-[#008080] focus:ring-2
                                    focus:ring-[#008080]/20 dark:bg-neutral-950
                                    dark:text-white dark:placeholder:text-neutral-500

                                    {{ $isCompletionModalReopened
                                        && $errors->has('notes')
                                            ? 'border-red-500 ring-1 ring-red-500/20'
                                            : 'border-neutral-300 dark:border-neutral-700'
                                    }}
                                ">
                        </div>


                        <button
                            type="submit"
                            class="inline-flex min-h-8 shrink-0 items-center justify-center gap-1 rounded-md border border-[#008080] bg-white px-3 py-2 text-[10px] font-semibold text-[#008080] transition hover:bg-[#008080]/10 focus:outline-none focus:ring-2 focus:ring-[#008080]/20 dark:bg-neutral-900 dark:text-[#5EEAD4]">

                            <i
                                data-lucide="upload"
                                class="h-3 w-3"
                                aria-hidden="true">
                            </i>

                            Add Proof
                        </button>

                    </div>


                    @if (
                        $isCompletionModalReopened
                        && $errors->has('notes')
                    )

                    <p class="mt-1 text-[10px] text-red-600 dark:text-red-400">
                        {{ $errors->first('notes') }}
                    </p>

                    @endif

                </form>

            </div>


            @if ($proofCount === 0)

            <div class="px-4 py-2.5">

                <div class="flex items-start gap-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-[10px] text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300">

                    <i
                        data-lucide="triangle-alert"
                        class="mt-0.5 h-3 w-3 shrink-0"
                        aria-hidden="true">
                    </i>

                    <span>
                        Add at least one proof-of-work file before submitting.
                    </span>

                </div>

            </div>

            @endif

        </div>


        <footer class="flex items-center justify-end gap-2 border-t border-neutral-200 px-4 py-3 dark:border-neutral-800">

            <button
                type="button"
                data-completion-back="{{ $jobOrder->id }}"
                class="inline-flex min-h-9 items-center justify-center rounded-md border border-neutral-300 bg-white px-3 py-2 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-neutral-300/50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300 dark:hover:bg-neutral-800">
                Cancel
            </button>


            <button
                type="submit"
                form="completion-form-{{ $jobOrder->id }}"
                @disabled($proofCount === 0)
                class="inline-flex min-h-9 items-center justify-center gap-1.5 rounded-md bg-[#008080] px-4 py-2 text-xs font-semibold text-white transition hover:bg-[#006f6f] focus:outline-none focus:ring-2 focus:ring-[#008080]/30 disabled:cursor-not-allowed disabled:bg-neutral-300 disabled:text-neutral-500 dark:disabled:bg-neutral-700 dark:disabled:text-neutral-400">

                <i
                    data-lucide="circle-check"
                    class="h-3.5 w-3.5"
                    aria-hidden="true">
                </i>

                Submit Report
            </button>

        </footer>

    </section>

</div>

@endif

@endforeach


<div
    id="proof-preview-modal"
    data-proof-preview-modal
    data-workflow-modal
    class="fixed inset-0 z-[10000] hidden items-center justify-center overflow-hidden bg-slate-950/60 p-2 backdrop-blur-[1px] sm:p-4"
    role="dialog"
    aria-modal="true"
    aria-hidden="true"
    aria-labelledby="proof-preview-title">

    <div
        data-preview-overlay
        class="absolute inset-0">
    </div>


    <section class="relative z-10 flex h-[min(82dvh,760px)] w-[calc(100vw-1rem)] max-w-5xl flex-col overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-2xl dark:border-neutral-700 dark:bg-neutral-900">

        <header class="flex items-start justify-between gap-3 border-b border-neutral-200 px-4 py-3 dark:border-neutral-800">

            <div class="min-w-0">

                <h2
                    id="proof-preview-title"
                    data-proof-preview-title
                    class="truncate text-sm font-semibold text-neutral-900 dark:text-white">
                    Proof Preview
                </h2>

                <p
                    data-proof-preview-meta
                    class="mt-0.5 truncate text-[10px] text-neutral-500 dark:text-neutral-400">
                </p>

            </div>


            <button
                type="button"
                data-preview-back
                class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-700 focus:outline-none focus:ring-2 focus:ring-[#008080]/20 dark:hover:bg-neutral-800 dark:hover:text-neutral-200"
                aria-label="Close preview">

                <i
                    data-lucide="x"
                    class="h-4 w-4"
                    aria-hidden="true">
                </i>
            </button>

        </header>


        <div class="min-h-0 flex-1 overflow-hidden bg-neutral-100 dark:bg-neutral-950">

            <iframe
                data-proof-preview-frame
                src="about:blank"
                title="Proof of work preview"
                class="h-full w-full border-0">
            </iframe>

        </div>

    </section>

</div>


<div
    id="proof-delete-modal"
    data-proof-delete-modal
    data-workflow-modal
    class="fixed inset-0 z-[10000] hidden items-center justify-center overflow-hidden bg-slate-950/60 p-3 backdrop-blur-[1px]"
    role="dialog"
    aria-modal="true"
    aria-hidden="true"
    aria-labelledby="proof-delete-title">

    <div
        data-delete-overlay
        class="absolute inset-0">
    </div>


    <section class="relative z-10 w-[calc(100vw-1.5rem)] max-w-md rounded-lg border border-neutral-200 bg-white shadow-2xl dark:border-neutral-700 dark:bg-neutral-900">

        <div class="px-4 py-4">

            <div class="flex items-start gap-3">

                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600 dark:bg-red-950/40 dark:text-red-400">
                    <i
                        data-lucide="trash-2"
                        class="h-4 w-4"
                        aria-hidden="true">
                    </i>
                </div>


                <div class="min-w-0">

                    <h2
                        id="proof-delete-title"
                        class="text-sm font-semibold text-neutral-900 dark:text-white">
                        Delete proof file?
                    </h2>

                    <p class="mt-1 break-words text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                        <span
                            data-proof-delete-name
                            class="font-medium text-neutral-700 dark:text-neutral-200">
                        </span>
                        will be permanently removed.
                    </p>

                </div>

            </div>

        </div>


        <div class="flex items-center justify-end gap-2 border-t border-neutral-200 px-4 py-3 dark:border-neutral-800">

            <button
                type="button"
                data-delete-cancel
                class="inline-flex min-h-9 items-center justify-center rounded-md border border-neutral-300 bg-white px-3 py-2 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300">
                Cancel
            </button>


            <form
                method="POST"
                action=""
                data-proof-delete-form
                data-lock-submit>

                @csrf
                @method('DELETE')

                <input
                    type="hidden"
                    name="modal_context"
                    value="completion_proof">

                <input
                    type="hidden"
                    name="proof_job_order_id"
                    value=""
                    data-proof-delete-job-order-id>

                <button
                    type="submit"
                    class="inline-flex min-h-9 items-center justify-center gap-1.5 rounded-md bg-red-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/30">

                    <i
                        data-lucide="trash-2"
                        class="h-3.5 w-3.5"
                        aria-hidden="true">
                    </i>

                    Delete
                </button>

            </form>

        </div>

    </section>

</div>


<script>
document.addEventListener('DOMContentLoaded', () => {
    const body =
        document.body;

    const state =
        document.getElementById(
            'technician-job-modal-state'
        );

    const workflowModals = [
        ...document.querySelectorAll(
            '[data-workflow-modal]'
        ),
    ];

    const jobModals = [
        ...document.querySelectorAll(
            '[data-job-modal]'
        ),
    ];

    const completionModals = [
        ...document.querySelectorAll(
            '[data-completion-modal]'
        ),
    ];

    const previewModal =
        document.querySelector(
            '[data-proof-preview-modal]'
        );

    const previewFrame =
        previewModal?.querySelector(
            '[data-proof-preview-frame]'
        ) ?? null;

    const previewTitle =
        previewModal?.querySelector(
            '[data-proof-preview-title]'
        ) ?? null;

    const previewMeta =
        previewModal?.querySelector(
            '[data-proof-preview-meta]'
        ) ?? null;

    const deleteModal =
        document.querySelector(
            '[data-proof-delete-modal]'
        );

    const deleteForm =
        document.querySelector(
            '[data-proof-delete-form]'
        );

    const deleteName =
        document.querySelector(
            '[data-proof-delete-name]'
        );

    const deleteJobOrderId =
        document.querySelector(
            '[data-proof-delete-job-order-id]'
        );

    const modalTriggers =
        new Map();

    let previewReturnModalId =
        null;

    let deleteReturnModalId =
        null;


    workflowModals.forEach((modal) => {
        if (modal.parentElement !== body) {
            body.appendChild(modal);
        }
    });


    const modalIsOpen = (modal) =>
        modal
        && modal.getAttribute(
            'aria-hidden'
        ) === 'false';


    const syncBodyLock = () => {
        const anyOpen =
            workflowModals.some(
                (modal) =>
                    modalIsOpen(modal)
            );

        body.classList.toggle(
            'overflow-hidden',
            anyOpen
        );
    };


    const focusFirstControl = (modal) => {
        window.setTimeout(() => {
            const control =
                modal?.querySelector(
                    'button:not([disabled]), a[href], input:not([type="hidden"]):not([disabled]), textarea:not([disabled])'
                );

            control?.focus();
        }, 0);
    };


    const showModal = (
        modal,
        trigger = null
    ) => {
        if (!modal) {
            return;
        }

        if (
            trigger
            instanceof HTMLElement
        ) {
            modalTriggers.set(
                modal.id,
                trigger
            );
        }

        modal.classList.remove(
            'hidden'
        );

        modal.classList.add(
            'flex'
        );

        modal.setAttribute(
            'aria-hidden',
            'false'
        );

        syncBodyLock();
        focusFirstControl(modal);
    };


    const hideModal = (
        modal,
        restoreFocus = false
    ) => {
        if (!modal) {
            return;
        }

        const activeElement =
            document.activeElement;

        const trigger =
            modalTriggers.get(
                modal.id
            );

        if (
            restoreFocus
            && trigger
                instanceof HTMLElement
            && trigger.offsetParent
                !== null
        ) {
            trigger.focus();
        } else if (
            activeElement
                instanceof HTMLElement
            && modal.contains(
                activeElement
            )
        ) {
            activeElement.blur();
        }

        modal.classList.add(
            'hidden'
        );

        modal.classList.remove(
            'flex'
        );

        modal.setAttribute(
            'aria-hidden',
            'true'
        );

        syncBodyLock();
    };


    const getJobModal = (
        jobOrderId
    ) =>
        document.getElementById(
            `job-modal-${jobOrderId}`
        );


    const getCompletionModal = (
        jobOrderId
    ) =>
        document.getElementById(
            `completion-modal-${jobOrderId}`
        );


    const closeAllWorkflow = () => {
        workflowModals.forEach(
            (modal) => {
                if (
                    modalIsOpen(modal)
                ) {
                    hideModal(
                        modal,
                        true
                    );
                }
            }
        );

        if (previewFrame) {
            previewFrame.src =
                'about:blank';
        }

        previewReturnModalId =
            null;

        deleteReturnModalId =
            null;
    };


    document
        .querySelectorAll(
            '[data-job-modal-open]'
        )
        .forEach((button) => {
            button.addEventListener(
                'click',
                () => {
                    showModal(
                        getJobModal(
                            button.dataset
                                .jobModalOpen
                        ),
                        button
                    );
                }
            );
        });


    document
        .querySelectorAll(
            '[data-completion-modal-open]'
        )
        .forEach((button) => {
            button.addEventListener(
                'click',
                () => {
                    const jobOrderId =
                        button.dataset
                            .completionModalOpen;

                    hideModal(
                        getJobModal(
                            jobOrderId
                        ),
                        false
                    );

                    showModal(
                        getCompletionModal(
                            jobOrderId
                        ),
                        button
                    );
                }
            );
        });


    document
        .querySelectorAll(
            '[data-completion-back]'
        )
        .forEach((button) => {
            button.addEventListener(
                'click',
                () => {
                    const jobOrderId =
                        button.dataset
                            .completionBack;

                    hideModal(
                        getCompletionModal(
                            jobOrderId
                        ),
                        false
                    );

                    showModal(
                        getJobModal(
                            jobOrderId
                        )
                    );
                }
            );
        });


    document
        .querySelectorAll(
            '[data-workflow-close]'
        )
        .forEach((button) => {
            button.addEventListener(
                'click',
                closeAllWorkflow
            );
        });


    workflowModals.forEach(
        (modal) => {
            modal
                .querySelector(
                    '[data-workflow-overlay]'
                )
                ?.addEventListener(
                    'click',
                    closeAllWorkflow
                );
        }
    );


    completionModals.forEach(
        (modal) => {
            const fileInput =
                modal.querySelector(
                    '[data-proof-file-input]'
                );

            const fileName =
                modal.querySelector(
                    '[data-proof-file-name]'
                );

            fileInput
                ?.addEventListener(
                    'change',
                    () => {
                        const selectedFile =
                            fileInput
                                .files?.[0];

                        if (
                            !selectedFile
                            || !fileName
                        ) {
                            return;
                        }

                        fileName
                            .textContent =
                                selectedFile
                                    .name;

                        fileName
                            .classList
                            .remove(
                                'hidden'
                            );
                    }
                );
        }
    );


    document
        .querySelectorAll(
            '[data-proof-preview-open]'
        )
        .forEach((button) => {
            button.addEventListener(
                'click',
                () => {
                    const returnModalId =
                        button.dataset
                            .proofReturnModal;

                    const returnModal =
                        returnModalId
                            ? document
                                .getElementById(
                                    returnModalId
                                )
                            : null;

                    previewReturnModalId =
                        returnModalId
                        || null;

                    if (returnModal) {
                        hideModal(
                            returnModal,
                            false
                        );
                    }

                    if (previewTitle) {
                        previewTitle
                            .textContent =
                                button.dataset
                                    .proofPreviewName
                                || 'Proof Preview';
                    }

                    if (previewMeta) {
                        previewMeta
                            .textContent =
                                button.dataset
                                    .proofPreviewMeta
                                || '';
                    }

                    if (previewFrame) {
                        previewFrame.src =
                            button.dataset
                                .proofPreviewUrl;
                    }

                    showModal(
                        previewModal,
                        button
                    );
                }
            );
        });


    const returnFromPreview = () => {
        hideModal(
            previewModal,
            false
        );

        if (previewFrame) {
            previewFrame.src =
                'about:blank';
        }

        const returnModal =
            previewReturnModalId
                ? document
                    .getElementById(
                        previewReturnModalId
                    )
                : null;

        previewReturnModalId =
            null;

        if (returnModal) {
            showModal(
                returnModal
            );
        }
    };


    document
        .querySelector(
            '[data-preview-back]'
        )
        ?.addEventListener(
            'click',
            returnFromPreview
        );


    document
        .querySelector(
            '[data-preview-overlay]'
        )
        ?.addEventListener(
            'click',
            returnFromPreview
        );


    document
        .querySelectorAll(
            '[data-proof-delete-open]'
        )
        .forEach((button) => {
            button.addEventListener(
                'click',
                () => {
                    const returnModalId =
                        button.dataset
                            .proofReturnModal;

                    const returnModal =
                        returnModalId
                            ? document
                                .getElementById(
                                    returnModalId
                                )
                            : null;

                    deleteReturnModalId =
                        returnModalId
                        || null;

                    if (returnModal) {
                        hideModal(
                            returnModal,
                            false
                        );
                    }

                    if (deleteForm) {
                        deleteForm.action =
                            button.dataset
                                .proofDeleteAction;
                    }

                    if (deleteName) {
                        deleteName
                            .textContent =
                                button.dataset
                                    .proofDeleteName
                                || 'This proof file';
                    }

                    if (
                        deleteJobOrderId
                    ) {
                        deleteJobOrderId
                            .value =
                                button.dataset
                                    .proofDeleteJobOrderId
                                || '';
                    }

                    showModal(
                        deleteModal,
                        button
                    );
                }
            );
        });


    const returnFromDelete = () => {
        hideModal(
            deleteModal,
            false
        );

        const returnModal =
            deleteReturnModalId
                ? document
                    .getElementById(
                        deleteReturnModalId
                    )
                : null;

        deleteReturnModalId =
            null;

        if (returnModal) {
            showModal(
                returnModal
            );
        }
    };


    document
        .querySelector(
            '[data-delete-cancel]'
        )
        ?.addEventListener(
            'click',
            returnFromDelete
        );


    document
        .querySelector(
            '[data-delete-overlay]'
        )
        ?.addEventListener(
            'click',
            returnFromDelete
        );


    document.addEventListener(
        'keydown',
        (event) => {
            if (
                event.key !== 'Escape'
            ) {
                return;
            }

            if (
                modalIsOpen(
                    previewModal
                )
            ) {
                event.preventDefault();
                returnFromPreview();
                return;
            }

            if (
                modalIsOpen(
                    deleteModal
                )
            ) {
                event.preventDefault();
                returnFromDelete();
                return;
            }

            const completionModal =
                completionModals.find(
                    (modal) =>
                        modalIsOpen(
                            modal
                        )
                );

            if (completionModal) {
                event.preventDefault();

                const jobOrderId =
                    completionModal
                        .dataset
                        .completionModal;

                hideModal(
                    completionModal,
                    false
                );

                showModal(
                    getJobModal(
                        jobOrderId
                    )
                );

                return;
            }

            const jobModal =
                jobModals.find(
                    (modal) =>
                        modalIsOpen(
                            modal
                        )
                );

            if (jobModal) {
                event.preventDefault();

                hideModal(
                    jobModal,
                    true
                );
            }
        }
    );


    const reopenCompletionJobOrderId =
        state?.dataset
            .reopenCompletionJobOrderId;

    const reopenJobOrderId =
        state?.dataset
            .reopenJobOrderId;


    if (
        reopenCompletionJobOrderId
    ) {
        showModal(
            getCompletionModal(
                reopenCompletionJobOrderId
            )
        );
    } else if (
        reopenJobOrderId
    ) {
        showModal(
            getJobModal(
                reopenJobOrderId
            )
        );
    }
});
</script>

@endsection