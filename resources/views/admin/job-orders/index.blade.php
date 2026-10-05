@extends('layouts.app')

@section('title', 'Job Orders')
@section('page-title', 'Job Orders')

@section('content')

@php
$statusMap = [
'pending' => [
'label' => 'Pending',
'class' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300',
],
'assigned' => [
'label' => 'Assigned',
'class' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300',
],
'in_progress' => [
'label' => 'In Progress',
'class' => 'bg-cyan-50 text-cyan-700 dark:bg-cyan-950/40 dark:text-cyan-300',
],
'completed' => [
'label' => 'Completed',
'class' => 'bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-300',
],
'cancelled' => [
'label' => 'Cancelled',
'class' => 'bg-gray-100 text-gray-600 dark:bg-neutral-800 dark:text-gray-300',
],
];

$modalContext = (string) old('modal_context', '');

$reopenCreateModal =
$errors->any()
&& $modalContext === 'create';

$reopenAssignmentModal =
$errors->any()
&& $modalContext === 'assignment';

$oldAssignmentJobOrderId =
(string) old('assignment_job_order_id', '');

$createdJobOrderId =
(string) session('created_job_order_id', '');
@endphp


<div
    id="job-order-modal-state"
    class="hidden"
    data-reopen-create="{{ $reopenCreateModal ? '1' : '0' }}"
    data-reopen-assignment="{{ $reopenAssignmentModal ? '1' : '0' }}"
    data-assignment-job-order-id="{{ $oldAssignmentJobOrderId }}"
    data-created-job-order-id="{{ $createdJobOrderId }}">
</div>


<div class="mx-auto max-w-[1600px] space-y-4">

    <section
        class="
            flex flex-col gap-3
            sm:flex-row sm:items-end sm:justify-between
        ">

        <div class="flex items-center gap-3">

            <div
                class="
                    flex h-9 w-9 shrink-0
                    items-center justify-center
                    border border-[#008080]/15
                    bg-[#008080]/10
                    text-[#008080]
                    dark:border-[#14B8A6]/20
                    dark:bg-[#008080]/20
                    dark:text-[#5EEAD4]
                ">

                <i
                    data-lucide="clipboard-list"
                    class="h-4 w-4">
                </i>

            </div>


            <div>

                <h1
                    class="
                        text-xl font-semibold
                        text-gray-900 dark:text-white
                        sm:text-2xl
                    ">
                    Job Orders
                </h1>

                <p
                    class="
                        mt-0.5 text-sm
                        text-gray-500 dark:text-gray-400
                    ">
                    Create, assign, schedule, and monitor field work orders.
                </p>

            </div>

        </div>


        <button
            type="button"
            data-create-job-order-open
            class="
                inline-flex min-h-10
                items-center justify-center gap-2
                bg-[#008080] px-4 py-2
                text-sm font-semibold text-white
                transition
                hover:bg-[#006666]
                focus:outline-none
                focus:ring-2
                focus:ring-[#008080]/30
            ">

            <i
                data-lucide="plus"
                class="h-4 w-4">
            </i>

            Create Job Order

        </button>

    </section>


    @if (session('success'))

    <div
        class="
                border border-green-200
                bg-green-50 px-4 py-3
                text-sm text-green-800
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
            border border-gray-200
            bg-white p-3
            dark:border-neutral-800
            dark:bg-neutral-900
        ">

        <div
            class="
                flex flex-col gap-2
                lg:flex-row lg:items-center
            ">

            <div class="relative min-w-0 flex-1">

                <label
                    for="job-order-search"
                    class="sr-only">
                    Search Job Orders
                </label>

                <i
                    data-lucide="search"
                    class="
                        pointer-events-none
                        absolute left-3 top-1/2
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
                        border border-gray-300
                        bg-white py-2 pl-10 pr-3
                        text-sm text-gray-900
                        outline-none
                        focus:border-[#008080]
                        focus:ring-2
                        focus:ring-[#008080]/20
                        dark:border-neutral-700
                        dark:bg-neutral-950
                        dark:text-white
                    ">

            </div>


            <select
                name="job_type"
                aria-label="Filter by Job Order type"
                class="
                    min-h-10
                    border border-gray-300
                    bg-white px-3 py-2
                    text-sm text-gray-800
                    outline-none
                    focus:border-[#008080]
                    focus:ring-2
                    focus:ring-[#008080]/20
                    dark:border-neutral-700
                    dark:bg-neutral-950
                    dark:text-gray-100
                    lg:w-52
                ">

                <option value="">
                    All Job Types
                </option>

                @foreach ($jobTypes as $typeValue => $typeLabel)

                <option
                    value="{{ $typeValue }}"
                    @selected($jobType===$typeValue)>
                    {{ $typeLabel }}
                </option>

                @endforeach

            </select>


            <select
                name="status"
                aria-label="Filter by Job Order status"
                class="
                    min-h-10
                    border border-gray-300
                    bg-white px-3 py-2
                    text-sm text-gray-800
                    outline-none
                    focus:border-[#008080]
                    focus:ring-2
                    focus:ring-[#008080]/20
                    dark:border-neutral-700
                    dark:bg-neutral-950
                    dark:text-gray-100
                    lg:w-44
                ">

                <option value="">
                    All Status
                </option>

                @foreach ($statuses as $statusValue)

                <option
                    value="{{ $statusValue }}"
                    @selected($status===$statusValue)>
                    {{ \Illuminate\Support\Str::headline($statusValue) }}
                </option>

                @endforeach

            </select>


            <button
                type="submit"
                class="
                    inline-flex min-h-10
                    items-center justify-center gap-2
                    bg-[#008080] px-4 py-2
                    text-sm font-semibold text-white
                    transition
                    hover:bg-[#006666]
                ">

                <i
                    data-lucide="list-filter"
                    class="h-4 w-4">
                </i>

                Filter

            </button>


            @if ($search !== '' || $status !== '' || $jobType !== '')

            <a
                href="{{ route('admin.job-orders.index') }}"
                title="Clear filters"
                aria-label="Clear filters"
                class="
                        inline-flex h-10 w-10
                        items-center justify-center
                        border border-gray-300
                        text-gray-500
                        transition
                        hover:border-[#008080]
                        hover:text-[#008080]
                        dark:border-neutral-700
                        dark:text-gray-400
                    ">

                <i
                    data-lucide="x"
                    class="h-4 w-4">
                </i>

            </a>

            @endif

        </div>

    </form>


    @if ($jobOrders->isEmpty())

    <section
        class="
                border border-dashed
                border-gray-300
                bg-white px-6 py-14
                text-center
                dark:border-neutral-700
                dark:bg-neutral-900
            ">

        <div
            class="
                    mx-auto flex h-11 w-11
                    items-center justify-center
                    bg-gray-100 text-gray-400
                    dark:bg-neutral-800
                ">

            <i
                data-lucide="clipboard-list"
                class="h-5 w-5">
            </i>

        </div>

        <h2
            class="
                    mt-4 text-sm font-semibold
                    text-gray-900 dark:text-white
                ">
            No Job Orders found
        </h2>

        <p
            class="
                    mx-auto mt-1 max-w-sm
                    text-sm text-gray-500
                    dark:text-gray-400
                ">

            @if ($search !== '' || $status !== '' || $jobType !== '')
            Try changing the current search or filters.
            @else
            Create the first field Job Order to begin.
            @endif

        </p>

    </section>

    @else

    <section
        class="
                hidden
                border border-gray-200
                bg-white
                dark:border-neutral-800
                dark:bg-neutral-900
                lg:block
            ">

        <header
            class="
                    flex items-center justify-between
                    border-b border-gray-100
                    px-4 py-3
                    dark:border-neutral-800
                ">

            <div>

                <h2
                    class="
                            text-sm font-semibold
                            text-gray-900 dark:text-white
                        ">
                    Field Job Orders
                </h2>

                <p
                    class="
                            mt-0.5 text-xs
                            text-gray-500 dark:text-gray-400
                        ">
                    Select View to review a Job Order and its available action.
                </p>

            </div>


            <span
                class="
                        text-xs text-gray-500
                        dark:text-gray-400
                    ">
                {{ number_format($jobOrders->total()) }}
                {{ $jobOrders->total() === 1 ? 'record' : 'records' }}
            </span>

        </header>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[1020px]">

                <thead
                    class="
                            bg-gray-50
                            dark:bg-neutral-800/60
                        ">

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


                <tbody
                    class="
                            divide-y divide-gray-100
                            dark:divide-neutral-800
                        ">

                    @foreach ($jobOrders as $jobOrder)

                    @php
                    $display = $statusMap[$jobOrder->status] ?? [
                    'label' => \Illuminate\Support\Str::headline($jobOrder->status),
                    'class' => 'bg-gray-100 text-gray-600 dark:bg-neutral-800 dark:text-gray-300',
                    ];

                    $customerName = trim(
                    implode(
                    ' ',
                    array_filter([
                    $jobOrder->customer?->first_name,
                    $jobOrder->customer?->middle_name,
                    $jobOrder->customer?->last_name,
                    ])
                    )
                    );

                    $jobTypeLabel =
                    $jobTypes[$jobOrder->job_type]
                    ?? \Illuminate\Support\Str::headline(
                    $jobOrder->job_type
                    );

                    $address =
                    $jobOrder->customer?->installation_address
                    ?: $jobOrder->customer?->address
                    ?: 'Not recorded';

                    $serviceRequestNumber =
                    $jobOrder->serviceRequest?->ticket_number
                    ?? '';

                    $serviceRequestMeta =
                    $jobOrder->serviceRequest
                    ? \Illuminate\Support\Str::headline(
                    $jobOrder->serviceRequest->request_type
                    )
                    . ' · '
                    . \Illuminate\Support\Str::headline(
                    $jobOrder->serviceRequest->status
                    )
                    : '';

                    $scheduledDateInput =
                    $jobOrder->scheduled_date?->format('Y-m-d')
                    ?? '';

                    $scheduledTimeInput =
                    $jobOrder->scheduled_time
                    ? substr(
                    (string) $jobOrder->scheduled_time,
                    0,
                    5
                    )
                    : '';

                    $scheduleDisplay =
                    'Not scheduled';

                    if ($jobOrder->scheduled_date) {
                    $scheduleDisplay =
                    $jobOrder->scheduled_date->format(
                    'M d'
                    );

                    if ($jobOrder->scheduled_time) {
                    $scheduleDisplay .=
                    ' · '
                    . \Illuminate\Support\Carbon::parse(
                    $jobOrder->scheduled_time
                    )->format('H:i');
                    }
                    }

                    $canEditAssignment = in_array(
                    $jobOrder->status,
                    [
                    'pending',
                    'assigned',
                    ],
                    true
                    );
                    @endphp


                    <tr
                        class="
                                    transition
                                    hover:bg-gray-50
                                    dark:hover:bg-neutral-800/40
                                ">

                        <td class="px-4 py-3.5">

                            <span
                                class="
                                            text-sm font-semibold
                                            text-[#008080]
                                            dark:text-[#5EEAD4]
                                        ">
                                {{ $jobOrder->job_order_number }}
                            </span>

                        </td>


                        <td class="px-4 py-3.5">

                            <p
                                class="
                                            text-sm font-semibold
                                            text-gray-900 dark:text-white
                                        ">
                                {{ $customerName ?: 'Unknown subscriber' }}
                            </p>

                            <p class="mt-0.5 text-xs text-gray-400">
                                {{ $jobOrder->customer?->customer_code }}
                            </p>

                        </td>


                        <td
                            class="
                                        px-4 py-3.5
                                        text-sm text-gray-700
                                        dark:text-gray-300
                                    ">
                            {{ $jobTypeLabel }}
                        </td>


                        <td class="px-4 py-3.5">

                            <span
                                class="
                                            inline-flex
                                            rounded-full
                                            px-2.5 py-1
                                            text-xs font-medium
                                            {{ $display['class'] }}
                                        ">
                                {{ $display['label'] }}
                            </span>

                        </td>


                        <td class="px-4 py-3.5">

                            @if ($jobOrder->technician)

                            <p
                                class="
                                                text-sm font-medium
                                                text-gray-800
                                                dark:text-gray-200
                                            ">
                                {{ $jobOrder->technician->technician_code }}
                            </p>

                            @if ($jobOrder->technician->user?->name)

                            <p class="mt-0.5 text-xs text-gray-400">
                                {{ $jobOrder->technician->user->name }}
                            </p>

                            @endif

                            @else

                            <span class="text-sm text-gray-400">
                                —
                            </span>

                            @endif

                        </td>


                        <td
                            class="
                                        px-4 py-3.5
                                        text-sm text-gray-600
                                        dark:text-gray-300
                                    ">
                            {{ $scheduleDisplay }}
                        </td>


                        <td class="px-4 py-3.5 text-right">

                            <button
                                type="button"
                                data-job-order-view
                                data-job-order-id="{{ $jobOrder->id }}"
                                data-job-order-number="{{ $jobOrder->job_order_number }}"
                                data-job-type="{{ $jobTypeLabel }}"
                                data-status="{{ $jobOrder->status }}"
                                data-status-label="{{ $display['label'] }}"
                                data-description="{{ $jobOrder->description }}"
                                data-customer-name="{{ $customerName ?: 'Unknown subscriber' }}"
                                data-customer-code="{{ $jobOrder->customer?->customer_code ?? '' }}"
                                data-customer-address="{{ $address }}"
                                data-customer-url="{{
                                            $jobOrder->customer
                                                ? route(
                                                    'admin.subscribers.show',
                                                    $jobOrder->customer
                                                )
                                                : ''
                                        }}"
                                data-service-request-number="{{ $serviceRequestNumber }}"
                                data-service-request-meta="{{ $serviceRequestMeta }}"
                                data-technician-id="{{ $jobOrder->technician_id ?? '' }}"
                                data-technician-code="{{ $jobOrder->technician?->technician_code ?? 'Unassigned' }}"
                                data-technician-name="{{ $jobOrder->technician?->user?->name ?? '' }}"
                                data-scheduled-date="{{ $scheduledDateInput }}"
                                data-scheduled-time="{{ $scheduledTimeInput }}"
                                data-schedule-display="{{ $scheduleDisplay }}"
                                data-can-assign="{{ $canEditAssignment ? '1' : '0' }}"
                                data-assignment-mode="{{ $jobOrder->status === 'assigned' ? 'edit' : 'assign' }}"
                                data-assignment-url="{{
                                            route(
                                                'admin.job-orders.assignment.update',
                                                $jobOrder
                                            )
                                        }}"
                                class="
                                            inline-flex min-h-8
                                            items-center justify-center
                                            border border-gray-300
                                            bg-white px-3
                                            text-xs font-semibold
                                            text-gray-700
                                            transition
                                            hover:border-[#008080]
                                            hover:text-[#008080]
                                            dark:border-neutral-700
                                            dark:bg-neutral-900
                                            dark:text-gray-300
                                        ">
                                View
                            </button>

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
        'class' => 'bg-gray-100 text-gray-600 dark:bg-neutral-800 dark:text-gray-300',
        ];

        $customerName = trim(
        implode(
        ' ',
        array_filter([
        $jobOrder->customer?->first_name,
        $jobOrder->customer?->middle_name,
        $jobOrder->customer?->last_name,
        ])
        )
        );

        $jobTypeLabel =
        $jobTypes[$jobOrder->job_type]
        ?? \Illuminate\Support\Str::headline(
        $jobOrder->job_type
        );

        $address =
        $jobOrder->customer?->installation_address
        ?: $jobOrder->customer?->address
        ?: 'Not recorded';

        $serviceRequestNumber =
        $jobOrder->serviceRequest?->ticket_number
        ?? '';

        $serviceRequestMeta =
        $jobOrder->serviceRequest
        ? \Illuminate\Support\Str::headline(
        $jobOrder->serviceRequest->request_type
        )
        . ' · '
        . \Illuminate\Support\Str::headline(
        $jobOrder->serviceRequest->status
        )
        : '';

        $scheduledDateInput =
        $jobOrder->scheduled_date?->format('Y-m-d')
        ?? '';

        $scheduledTimeInput =
        $jobOrder->scheduled_time
        ? substr(
        (string) $jobOrder->scheduled_time,
        0,
        5
        )
        : '';

        $scheduleDisplay =
        'Not scheduled';

        if ($jobOrder->scheduled_date) {
        $scheduleDisplay =
        $jobOrder->scheduled_date->format(
        'M d'
        );

        if ($jobOrder->scheduled_time) {
        $scheduleDisplay .=
        ' · '
        . \Illuminate\Support\Carbon::parse(
        $jobOrder->scheduled_time
        )->format('H:i');
        }
        }

        $canEditAssignment = in_array(
        $jobOrder->status,
        [
        'pending',
        'assigned',
        ],
        true
        );
        @endphp


        <article
            class="
                        border border-gray-200
                        bg-white p-4
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
                                    text-sm font-semibold
                                    text-[#008080]
                                    dark:text-[#5EEAD4]
                                ">
                        {{ $jobOrder->job_order_number }}
                    </p>

                    <h2
                        class="
                                    mt-1 truncate
                                    text-base font-semibold
                                    text-gray-900
                                    dark:text-white
                                ">
                        {{ $customerName ?: 'Unknown subscriber' }}
                    </h2>

                    <p
                        class="
                                    mt-0.5 text-xs
                                    text-gray-500
                                    dark:text-gray-400
                                ">
                        {{ $jobTypeLabel }}
                    </p>

                </div>


                <span
                    class="
                                inline-flex shrink-0
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
                            border-t border-gray-100
                            pt-4 sm:grid-cols-2
                            dark:border-neutral-800
                        ">

                <div>

                    <p class="text-xs text-gray-400">
                        Technician
                    </p>

                    <p
                        class="
                                    mt-1 text-sm
                                    text-gray-700
                                    dark:text-gray-300
                                ">
                        {{ $jobOrder->technician?->technician_code ?? 'Unassigned' }}
                    </p>

                </div>


                <div>

                    <p class="text-xs text-gray-400">
                        Schedule
                    </p>

                    <p
                        class="
                                    mt-1 text-sm
                                    text-gray-700
                                    dark:text-gray-300
                                ">
                        {{ $scheduleDisplay }}
                    </p>

                </div>

            </div>


            <button
                type="button"
                data-job-order-view
                data-job-order-id="{{ $jobOrder->id }}"
                data-job-order-number="{{ $jobOrder->job_order_number }}"
                data-job-type="{{ $jobTypeLabel }}"
                data-status="{{ $jobOrder->status }}"
                data-status-label="{{ $display['label'] }}"
                data-description="{{ $jobOrder->description }}"
                data-customer-name="{{ $customerName ?: 'Unknown subscriber' }}"
                data-customer-code="{{ $jobOrder->customer?->customer_code ?? '' }}"
                data-customer-address="{{ $address }}"
                data-customer-url="{{
                            $jobOrder->customer
                                ? route(
                                    'admin.subscribers.show',
                                    $jobOrder->customer
                                )
                                : ''
                        }}"
                data-service-request-number="{{ $serviceRequestNumber }}"
                data-service-request-meta="{{ $serviceRequestMeta }}"
                data-technician-id="{{ $jobOrder->technician_id ?? '' }}"
                data-technician-code="{{ $jobOrder->technician?->technician_code ?? 'Unassigned' }}"
                data-technician-name="{{ $jobOrder->technician?->user?->name ?? '' }}"
                data-scheduled-date="{{ $scheduledDateInput }}"
                data-scheduled-time="{{ $scheduledTimeInput }}"
                data-schedule-display="{{ $scheduleDisplay }}"
                data-can-assign="{{ $canEditAssignment ? '1' : '0' }}"
                data-assignment-mode="{{ $jobOrder->status === 'assigned' ? 'edit' : 'assign' }}"
                data-assignment-url="{{
                            route(
                                'admin.job-orders.assignment.update',
                                $jobOrder
                            )
                        }}"
                class="
                            mt-4 inline-flex min-h-10 w-full
                            items-center justify-center
                            border border-gray-300
                            px-3 py-2
                            text-sm font-semibold
                            text-gray-700
                            transition
                            hover:border-[#008080]
                            hover:text-[#008080]
                            dark:border-neutral-700
                            dark:text-gray-300
                        ">
                View Job Order
            </button>

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


{{-- ========================================================= --}}
{{-- CREATE JOB ORDER MODAL                                    --}}
{{-- ========================================================= --}}

<div
    id="create-job-order-modal"
    class="
        fixed inset-0 z-[9999]
        hidden items-center justify-center
        overflow-y-auto
        bg-slate-950/50
        px-4 py-6
        backdrop-blur-[1px]
    "
    role="dialog"
    aria-modal="true"
    aria-hidden="true"
    aria-labelledby="create-job-order-title">

    <div
        data-create-job-order-overlay
        class="absolute inset-0">
    </div>


    <section
        class="
            relative z-10
            w-full max-w-[620px]
            overflow-hidden
            rounded-lg
            border border-gray-200
            bg-white shadow-2xl
            dark:border-neutral-700
            dark:bg-neutral-900
        ">

        <header
            class="
                flex items-center justify-between
                border-b border-gray-200
                px-6 py-5
                dark:border-neutral-800
            ">

            <div>

                <h2
                    id="create-job-order-title"
                    class="
                        text-base font-semibold
                        text-gray-900
                        dark:text-white
                    ">
                    Create Job Order
                </h2>

                <p
                    class="
                        mt-1 text-xs
                        text-gray-500
                        dark:text-gray-400
                    ">
                    Create the field work record first. Assignment is handled after creation.
                </p>

            </div>


            <button
                type="button"
                data-create-job-order-close
                aria-label="Close Create Job Order"
                class="
                    inline-flex h-8 w-8
                    items-center justify-center
                    text-gray-500
                    transition
                    hover:text-gray-900
                    dark:text-gray-400
                    dark:hover:text-white
                ">

                <i
                    data-lucide="x"
                    class="h-4 w-4">
                </i>

            </button>

        </header>


        <form
            method="POST"
            action="{{ route('admin.job-orders.store') }}"
            data-lock-submit>

            @csrf

            <input
                type="hidden"
                name="modal_context"
                value="create">


            <div
                class="
                    max-h-[68vh]
                    space-y-4
                    overflow-y-auto
                    px-6 py-5
                ">

                @if ($reopenCreateModal)

                <div
                    class="
                            border border-red-200
                            bg-red-50 px-3 py-3
                            text-sm text-red-700
                            dark:border-red-900
                            dark:bg-red-950/30
                            dark:text-red-300
                        ">

                    <p class="font-semibold">
                        Please correct the highlighted fields.
                    </p>

                    <ul class="mt-1 list-disc space-y-0.5 pl-5 text-xs">

                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach

                    </ul>

                </div>

                @endif


                <div>

                    <label
                        for="create-customer-id"
                        class="
                            mb-1.5 block
                            text-sm font-medium
                            text-gray-700
                            dark:text-gray-200
                        ">
                        Subscriber
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="create-customer-id"
                        name="customer_id"
                        required
                        class="
                            min-h-10 w-full
                            border border-gray-300
                            bg-white px-3 py-2
                            text-sm text-gray-900
                            outline-none
                            focus:border-[#008080]
                            focus:ring-2
                            focus:ring-[#008080]/20
                            dark:border-neutral-700
                            dark:bg-neutral-950
                            dark:text-white
                        ">

                        <option value="">
                            Select subscriber
                        </option>

                        @foreach ($customers as $customer)

                        @php
                        $fullName = trim(
                        implode(
                        ' ',
                        array_filter([
                        $customer->first_name,
                        $customer->middle_name,
                        $customer->last_name,
                        ])
                        )
                        );
                        @endphp

                        <option
                            value="{{ $customer->id }}"
                            @selected(
                            $reopenCreateModal
                            && (string) old('customer_id')===(string) $customer->id
                            )>
                            {{ $customer->customer_code }}
                            — {{ $fullName }}
                        </option>

                        @endforeach

                    </select>

                    @if ($reopenCreateModal)

                    @error('customer_id')

                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                        {{ $message }}
                    </p>

                    @enderror

                    @endif

                </div>


                <div>

                    <label
                        for="create-service-request-id"
                        class="
                            mb-1.5 block
                            text-sm font-medium
                            text-gray-700
                            dark:text-gray-200
                        ">
                        Related Service Request
                        <span class="font-normal text-gray-400">
                            (optional)
                        </span>
                    </label>

                    <select
                        id="create-service-request-id"
                        name="service_request_id"
                        class="
                            min-h-10 w-full
                            border border-gray-300
                            bg-white px-3 py-2
                            text-sm text-gray-900
                            outline-none
                            focus:border-[#008080]
                            focus:ring-2
                            focus:ring-[#008080]/20
                            dark:border-neutral-700
                            dark:bg-neutral-950
                            dark:text-white
                        ">

                        <option value="">
                            No related Service Request
                        </option>

                        @foreach ($serviceRequests as $serviceRequest)

                        <option
                            value="{{ $serviceRequest->id }}"
                            data-customer-id="{{ $serviceRequest->customer_id }}"
                            @selected(
                            $reopenCreateModal
                            && (string) old('service_request_id')===(string) $serviceRequest->id
                            )>

                            {{ $serviceRequest->ticket_number }}
                            —
                            {{ \Illuminate\Support\Str::headline(
                                    $serviceRequest->request_type
                                ) }}
                            —
                            {{ \Illuminate\Support\Str::headline(
                                    $serviceRequest->status
                                ) }}

                        </option>

                        @endforeach

                    </select>

                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Service Requests are filtered by the selected subscriber.
                    </p>

                    @if ($reopenCreateModal)

                    @error('service_request_id')

                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                        {{ $message }}
                    </p>

                    @enderror

                    @endif

                </div>


                <div>

                    <label
                        for="create-job-type"
                        class="
                            mb-1.5 block
                            text-sm font-medium
                            text-gray-700
                            dark:text-gray-200
                        ">
                        Job Type
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="create-job-type"
                        name="job_type"
                        required
                        class="
                            min-h-10 w-full
                            border border-gray-300
                            bg-white px-3 py-2
                            text-sm text-gray-900
                            outline-none
                            focus:border-[#008080]
                            focus:ring-2
                            focus:ring-[#008080]/20
                            dark:border-neutral-700
                            dark:bg-neutral-950
                            dark:text-white
                        ">

                        <option value="">
                            Select Job Order type
                        </option>

                        @foreach ($jobTypes as $value => $label)

                        <option
                            value="{{ $value }}"
                            @selected(
                            $reopenCreateModal
                            && old('job_type')===$value
                            )>
                            {{ $label }}
                        </option>

                        @endforeach

                    </select>

                    @if ($reopenCreateModal)

                    @error('job_type')

                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                        {{ $message }}
                    </p>

                    @enderror

                    @endif

                </div>


                <div>

                    <label
                        for="create-description"
                        class="
                            mb-1.5 block
                            text-sm font-medium
                            text-gray-700
                            dark:text-gray-200
                        ">
                        Work Description / Instructions
                        <span class="text-red-500">*</span>
                    </label>

                    <textarea
                        id="create-description"
                        name="description"
                        rows="4"
                        maxlength="5000"
                        required
                        placeholder="Describe the work to be performed and any technician instructions."
                        class="
                            w-full resize-y
                            border border-gray-300
                            bg-white px-3 py-2.5
                            text-sm text-gray-900
                            outline-none
                            focus:border-[#008080]
                            focus:ring-2
                            focus:ring-[#008080]/20
                            dark:border-neutral-700
                            dark:bg-neutral-950
                            dark:text-white
                        ">{{ $reopenCreateModal ? old('description') : '' }}</textarea>

                    @if ($reopenCreateModal)

                    @error('description')

                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                        {{ $message }}
                    </p>

                    @enderror

                    @endif

                </div>

            </div>


            <footer
                class="
                    flex justify-end gap-2
                    border-t border-gray-200
                    px-6 py-4
                    dark:border-neutral-800
                ">

                <button
                    type="button"
                    data-create-job-order-close
                    class="
                        inline-flex min-h-10
                        items-center justify-center
                        rounded-md
                        border border-gray-300
                        bg-white px-4
                        text-sm font-semibold
                        text-gray-700
                        transition
                        hover:bg-gray-50
                        dark:border-neutral-700
                        dark:bg-neutral-900
                        dark:text-gray-300
                    ">
                    Cancel
                </button>


                <button
                    type="submit"
                    class="
                        inline-flex min-h-10
                        items-center justify-center
                        rounded-md
                        bg-[#008080] px-4
                        text-sm font-semibold
                        text-white
                        transition
                        hover:bg-[#006666]
                        disabled:cursor-not-allowed
                        disabled:opacity-60
                    ">
                    Create Job Order
                </button>

            </footer>

        </form>

    </section>

</div>


{{-- ========================================================= --}}
{{-- JOB ORDER DETAILS MODAL                                   --}}
{{-- ========================================================= --}}

<div
    id="job-order-details-modal"
    class="
        fixed inset-0 z-[9999]
        hidden items-center justify-center
        overflow-y-auto
        bg-slate-950/50
        px-4 py-6
        backdrop-blur-[1px]
    "
    role="dialog"
    aria-modal="true"
    aria-hidden="true"
    aria-labelledby="details-modal-title">

    <div
        data-job-order-details-overlay
        class="absolute inset-0">
    </div>


    <section
        class="
            relative z-10
            w-full max-w-[560px]
            overflow-hidden
            rounded-lg
            border border-gray-200
            bg-white
            shadow-2xl
            dark:border-neutral-700
            dark:bg-neutral-900
        ">

        <header
            class="
                flex min-h-[70px]
                items-center justify-between
                gap-4
                border-b border-gray-200
                px-6 py-4
                dark:border-neutral-800
            ">

            <h2
                id="details-modal-title"
                class="
                    min-w-0 truncate
                    text-base font-semibold
                    text-gray-900
                    dark:text-white
                ">
            </h2>


            <button
                type="button"
                data-job-order-details-close
                aria-label="Close Job Order details"
                class="
                    inline-flex h-8 w-8
                    shrink-0 items-center justify-center
                    text-gray-500 transition
                    hover:text-gray-900
                    dark:text-gray-400
                    dark:hover:text-white
                ">

                <i
                    data-lucide="x"
                    class="h-4 w-4">
                </i>

            </button>

        </header>


        <div class="px-6 py-5">

            <div
                class="
                    divide-y divide-gray-200
                    dark:divide-neutral-800
                ">

                <div
                    class="
                        grid grid-cols-[125px_minmax(0,1fr)]
                        items-start gap-4 py-3
                    ">

                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        Customer
                    </span>

                    <div class="text-right">

                        <p
                            id="details-customer-name"
                            class="
                                text-sm font-semibold
                                text-gray-800
                                dark:text-gray-100
                            ">
                        </p>

                        <p
                            id="details-customer-code"
                            class="
                                mt-0.5 text-xs
                                text-gray-400
                            ">
                        </p>

                    </div>

                </div>


                <div
                    class="
                        grid grid-cols-[125px_minmax(0,1fr)]
                        items-start gap-4 py-3
                    ">

                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        Address
                    </span>

                    <p
                        id="details-customer-address"
                        class="
                            text-right text-sm
                            font-semibold
                            text-gray-800
                            dark:text-gray-100
                        ">
                    </p>

                </div>


                <div
                    class="
                        grid grid-cols-[125px_minmax(0,1fr)]
                        items-start gap-4 py-3
                    ">

                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        Schedule
                    </span>

                    <p
                        id="details-schedule"
                        class="
                            text-right text-sm
                            font-semibold
                            text-gray-800
                            dark:text-gray-100
                        ">
                    </p>

                </div>


                <div
                    class="
                        grid grid-cols-[125px_minmax(0,1fr)]
                        items-start gap-4 py-3
                    ">

                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        Status
                    </span>

                    <p
                        id="details-status"
                        class="
                            text-right text-sm
                            font-semibold
                            text-gray-800
                            dark:text-gray-100
                        ">
                    </p>

                </div>


                <div
                    class="
                        grid grid-cols-[125px_minmax(0,1fr)]
                        items-start gap-4 py-3
                    ">

                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        Assigned technician
                    </span>

                    <div class="text-right">

                        <p
                            id="details-technician-name"
                            class="
                                text-sm font-semibold
                                text-gray-800
                                dark:text-gray-100
                            ">
                        </p>

                        <p
                            id="details-technician-code"
                            class="
                                mt-0.5 text-xs
                                text-gray-400
                            ">
                        </p>

                    </div>

                </div>


                <div
                    id="details-service-request-row"
                    class="
                        grid grid-cols-[125px_minmax(0,1fr)]
                        items-start gap-4 py-3
                    ">

                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        Service request
                    </span>

                    <div class="text-right">

                        <p
                            id="details-service-request-number"
                            class="
                                text-sm font-semibold
                                text-gray-800
                                dark:text-gray-100
                            ">
                        </p>

                        <p
                            id="details-service-request-meta"
                            class="
                                mt-0.5 text-xs
                                text-gray-400
                            ">
                        </p>

                    </div>

                </div>

            </div>


            <p
                id="details-description"
                class="
                    mt-4 whitespace-pre-line
                    text-sm leading-6
                    text-gray-500
                    dark:text-gray-400
                ">
            </p>

        </div>


        <footer
            class="
                flex flex-wrap
                items-center justify-end gap-2
                px-6 pb-6 pt-2
            ">

            <a
                id="details-customer-link"
                href="#"
                class="
                    hidden min-h-10
                    items-center justify-center
                    rounded-md
                    border border-gray-300
                    bg-white px-4
                    text-sm font-semibold
                    text-gray-700
                    transition
                    hover:bg-gray-50
                    dark:border-neutral-700
                    dark:bg-neutral-900
                    dark:text-gray-300
                    dark:hover:bg-neutral-800
                ">
                Open subscriber
            </a>


            <button
                id="details-assignment-button"
                type="button"
                class="
                    hidden min-h-10
                    items-center justify-center
                    rounded-md
                    bg-[#008080] px-4
                    text-sm font-semibold
                    text-white
                    transition
                    hover:bg-[#006666]
                ">

                <span id="details-assignment-button-label">
                    Assign technician
                </span>

            </button>

        </footer>

    </section>

</div>


{{-- ========================================================= --}}
{{-- TECHNICIAN ASSIGNMENT MODAL                               --}}
{{-- ========================================================= --}}

<div
    id="assignment-modal"
    class="
        fixed inset-0 z-[10000]
        hidden items-center justify-center
        overflow-y-auto
        bg-slate-950/50
        px-4 py-6
        backdrop-blur-[1px]
    "
    role="dialog"
    aria-modal="true"
    aria-hidden="true"
    aria-labelledby="assignment-modal-title">

    <div
        data-assignment-overlay
        class="absolute inset-0">
    </div>


    <section
        class="
            relative z-10
            w-full max-w-[520px]
            overflow-hidden
            rounded-lg
            border border-gray-200
            bg-white
            shadow-2xl
            dark:border-neutral-700
            dark:bg-neutral-900
        ">

        <header
            class="
                flex items-center justify-between
                border-b border-gray-200
                px-6 py-5
                dark:border-neutral-800
            ">

            <div>

                <h2
                    id="assignment-modal-title"
                    class="
                        text-base font-semibold
                        text-gray-900
                        dark:text-white
                    ">
                    Assign technician
                </h2>

                <p
                    id="assignment-modal-job"
                    class="
                        mt-1 text-xs
                        text-gray-500
                        dark:text-gray-400
                    ">
                </p>

            </div>


            <button
                type="button"
                data-assignment-close
                aria-label="Close technician assignment"
                class="
                    inline-flex h-8 w-8
                    items-center justify-center
                    text-gray-500
                    transition
                    hover:text-gray-900
                    dark:text-gray-400
                    dark:hover:text-white
                ">

                <i
                    data-lucide="x"
                    class="h-4 w-4">
                </i>

            </button>

        </header>


        <form
            id="assignment-form"
            method="POST"
            action=""
            data-lock-submit>

            @csrf
            @method('PATCH')

            <input
                type="hidden"
                name="modal_context"
                value="assignment">

            <input
                id="assignment-job-order-id"
                type="hidden"
                name="assignment_job_order_id"
                value="{{ $reopenAssignmentModal ? old('assignment_job_order_id') : '' }}">


            <div class="space-y-4 px-6 py-5">

                @if (
                $reopenAssignmentModal
                && $errors->has('assignment')
                )

                <div
                    class="
                            border border-red-200
                            bg-red-50 px-3 py-2
                            text-sm text-red-700
                            dark:border-red-900
                            dark:bg-red-950/30
                            dark:text-red-300
                        ">
                    {{ $errors->first('assignment') }}
                </div>

                @endif


                <div>

                    <label
                        for="assignment-technician"
                        class="
                            mb-1.5 block
                            text-sm font-medium
                            text-gray-700
                            dark:text-gray-200
                        ">
                        Technician
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="assignment-technician"
                        name="technician_id"
                        required
                        class="
                            min-h-10 w-full
                            border border-gray-300
                            bg-white px-3 py-2
                            text-sm text-gray-900
                            outline-none
                            focus:border-[#008080]
                            focus:ring-2
                            focus:ring-[#008080]/20
                            dark:border-neutral-700
                            dark:bg-neutral-950
                            dark:text-white
                        ">

                        <option value="">
                            Select an available technician
                        </option>

                        @foreach ($eligibleTechnicians as $technician)

                        <option
                            value="{{ $technician->id }}"
                            @selected(
                            $reopenAssignmentModal
                            && (string) old('technician_id')===(string) $technician->id
                            )>

                            {{ $technician->technician_code }}

                            @if ($technician->user?->name)
                            — {{ $technician->user->name }}
                            @endif

                            @if ($technician->specialization)
                            — {{ $technician->specialization }}
                            @endif

                        </option>

                        @endforeach

                    </select>

                    @if ($reopenAssignmentModal)

                    @error('technician_id')

                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                        {{ $message }}
                    </p>

                    @enderror

                    @endif

                </div>


                <div class="grid gap-4 sm:grid-cols-2">

                    <div>

                        <label
                            for="assignment-date"
                            class="
                                mb-1.5 block
                                text-sm font-medium
                                text-gray-700
                                dark:text-gray-200
                            ">
                            Scheduled Date
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            id="assignment-date"
                            type="date"
                            name="scheduled_date"
                            required
                            min="{{ now()->toDateString() }}"
                            value="{{ $reopenAssignmentModal ? old('scheduled_date') : '' }}"
                            class="
                                min-h-10 w-full
                                border border-gray-300
                                bg-white px-3 py-2
                                text-sm text-gray-900
                                outline-none
                                focus:border-[#008080]
                                focus:ring-2
                                focus:ring-[#008080]/20
                                dark:border-neutral-700
                                dark:bg-neutral-950
                                dark:text-white
                            ">

                        @if ($reopenAssignmentModal)

                        @error('scheduled_date')

                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>

                        @enderror

                        @endif

                    </div>


                    <div>

                        <label
                            for="assignment-time"
                            class="
                                mb-1.5 block
                                text-sm font-medium
                                text-gray-700
                                dark:text-gray-200
                            ">
                            Scheduled Time
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            id="assignment-time"
                            type="time"
                            name="scheduled_time"
                            required
                            value="{{ $reopenAssignmentModal ? old('scheduled_time') : '' }}"
                            class="
                                min-h-10 w-full
                                border border-gray-300
                                bg-white px-3 py-2
                                text-sm text-gray-900
                                outline-none
                                focus:border-[#008080]
                                focus:ring-2
                                focus:ring-[#008080]/20
                                dark:border-neutral-700
                                dark:bg-neutral-950
                                dark:text-white
                            ">

                        @if ($reopenAssignmentModal)

                        @error('scheduled_time')

                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>

                        @enderror

                        @endif

                    </div>

                </div>

            </div>


            <footer
                class="
                    flex justify-end gap-2
                    border-t border-gray-200
                    px-6 py-4
                    dark:border-neutral-800
                ">

                <button
                    type="button"
                    data-assignment-close
                    class="
                        inline-flex min-h-10
                        items-center justify-center
                        rounded-md
                        border border-gray-300
                        bg-white px-4
                        text-sm font-semibold
                        text-gray-700
                        transition
                        hover:bg-gray-50
                        dark:border-neutral-700
                        dark:bg-neutral-900
                        dark:text-gray-300
                    ">
                    Cancel
                </button>


                <button
                    id="assignment-submit"
                    type="submit"
                    @disabled($eligibleTechnicians->isEmpty())
                    class="
                    inline-flex min-h-10
                    items-center justify-center
                    rounded-md
                    bg-[#008080] px-4
                    text-sm font-semibold
                    text-white
                    transition
                    hover:bg-[#006666]
                    disabled:cursor-not-allowed
                    disabled:opacity-50
                    ">

                    <span id="assignment-submit-label">
                        Save assignment
                    </span>

                </button>

            </footer>

        </form>

    </section>

</div>


<script>
    document.addEventListener('DOMContentLoaded', () => {
        const body =
            document.body;

        const state =
            document.getElementById(
                'job-order-modal-state'
            );

        let createModal =
            document.getElementById(
                'create-job-order-modal'
            );

        let detailsModal =
            document.getElementById(
                'job-order-details-modal'
            );

        let assignmentModal =
            document.getElementById(
                'assignment-modal'
            );


        /*
        |--------------------------------------------------------------------------
        | Move dialogs to the document body
        |--------------------------------------------------------------------------
        |
        | This prevents the application layout, header, or sidebar from creating
        | a stacking context above the modal backdrop.
        |
        */

        [
            createModal,
            detailsModal,
            assignmentModal,
        ].forEach((modal) => {
            if (
                modal &&
                modal.parentElement !== body
            ) {
                body.appendChild(modal);
            }
        });


        /*
        |--------------------------------------------------------------------------
        | Shared modal helpers
        |--------------------------------------------------------------------------
        */

        const modalIsOpen = (modal) => {
            return (
                modal &&
                modal.getAttribute('aria-hidden') === 'false'
            );
        };


        const showModal = (modal) => {
            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');

            modal.setAttribute(
                'aria-hidden',
                'false'
            );

            body.classList.add(
                'overflow-hidden'
            );
        };


        const hideModal = (modal) => {
            if (!modal) {
                return;
            }

            modal.classList.add('hidden');
            modal.classList.remove('flex');

            modal.setAttribute(
                'aria-hidden',
                'true'
            );

            const anotherModalIsOpen = [
                createModal,
                detailsModal,
                assignmentModal,
            ].some(
                (candidate) =>
                modalIsOpen(candidate)
            );

            if (!anotherModalIsOpen) {
                body.classList.remove(
                    'overflow-hidden'
                );
            }
        };


        /*
        |--------------------------------------------------------------------------
        | Create Job Order
        |--------------------------------------------------------------------------
        */

        const createOpenButtons = [
            ...document.querySelectorAll(
                '[data-create-job-order-open]'
            ),
        ];

        const createCloseButtons = [
            ...document.querySelectorAll(
                '[data-create-job-order-close]'
            ),
        ];

        const createOverlay =
            createModal?.querySelector(
                '[data-create-job-order-overlay]'
            );

        const createCustomerSelect =
            createModal?.querySelector(
                '#create-customer-id'
            );

        const createServiceRequestSelect =
            createModal?.querySelector(
                '#create-service-request-id'
            );


        const filterCreateServiceRequests = () => {
            if (
                !createCustomerSelect ||
                !createServiceRequestSelect
            ) {
                return;
            }

            const customerId =
                createCustomerSelect.value;

            const selectedValue =
                createServiceRequestSelect.value;

            const requestOptions = [
                ...createServiceRequestSelect
                .querySelectorAll(
                    'option[data-customer-id]'
                ),
            ];

            requestOptions.forEach((option) => {
                const belongsToCustomer =
                    customerId !== '' &&
                    option.dataset.customerId ===
                    customerId;

                option.hidden = !belongsToCustomer;

                option.disabled = !belongsToCustomer;
            });

            const selectedOption =
                createServiceRequestSelect
                .querySelector(
                    `option[value="${selectedValue}"]`
                );

            if (
                selectedValue !== '' &&
                (
                    !selectedOption ||
                    selectedOption.disabled
                )
            ) {
                createServiceRequestSelect.value = '';
            }
        };


        const openCreateModal = () => {
            filterCreateServiceRequests();

            showModal(createModal);

            window.setTimeout(
                () => {
                    createCustomerSelect?.focus();
                },
                0
            );
        };


        const closeCreateModal = () => {
            hideModal(createModal);
        };


        createOpenButtons.forEach((button) => {
            button.addEventListener(
                'click',
                openCreateModal
            );
        });


        createCloseButtons.forEach((button) => {
            button.addEventListener(
                'click',
                closeCreateModal
            );
        });


        createOverlay?.addEventListener(
            'click',
            closeCreateModal
        );


        createCustomerSelect?.addEventListener(
            'change',
            filterCreateServiceRequests
        );


        filterCreateServiceRequests();


        /*
        |--------------------------------------------------------------------------
        | Job Order Details
        |--------------------------------------------------------------------------
        */

        const viewButtons = [
            ...document.querySelectorAll(
                '[data-job-order-view]'
            ),
        ];

        const detailsOverlay =
            detailsModal?.querySelector(
                '[data-job-order-details-overlay]'
            );

        const detailsCloseButtons = [
            ...detailsModal?.querySelectorAll(
                '[data-job-order-details-close]'
            ) ?? [],
        ];

        const detailsTitle =
            detailsModal?.querySelector(
                '#details-modal-title'
            );

        const detailsCustomerName =
            detailsModal?.querySelector(
                '#details-customer-name'
            );

        const detailsCustomerCode =
            detailsModal?.querySelector(
                '#details-customer-code'
            );

        const detailsCustomerAddress =
            detailsModal?.querySelector(
                '#details-customer-address'
            );

        const detailsSchedule =
            detailsModal?.querySelector(
                '#details-schedule'
            );

        const detailsStatus =
            detailsModal?.querySelector(
                '#details-status'
            );

        const detailsTechnicianName =
            detailsModal?.querySelector(
                '#details-technician-name'
            );

        const detailsTechnicianCode =
            detailsModal?.querySelector(
                '#details-technician-code'
            );

        const detailsServiceRequestRow =
            detailsModal?.querySelector(
                '#details-service-request-row'
            );

        const detailsServiceRequestNumber =
            detailsModal?.querySelector(
                '#details-service-request-number'
            );

        const detailsServiceRequestMeta =
            detailsModal?.querySelector(
                '#details-service-request-meta'
            );

        const detailsDescription =
            detailsModal?.querySelector(
                '#details-description'
            );

        const detailsCustomerLink =
            detailsModal?.querySelector(
                '#details-customer-link'
            );

        const detailsAssignmentButton =
            detailsModal?.querySelector(
                '#details-assignment-button'
            );

        const detailsAssignmentButtonLabel =
            detailsModal?.querySelector(
                '#details-assignment-button-label'
            );

        let currentDetailsSource = null;


        const populateDetailsModal = (source) => {
            if (!source) {
                return;
            }

            currentDetailsSource = source;


            if (detailsTitle) {
                detailsTitle.textContent = [
                        source.dataset.jobOrderNumber ?? '',
                        source.dataset.jobType ?? '',
                    ]
                    .filter(Boolean)
                    .join(' · ');
            }


            if (detailsCustomerName) {
                detailsCustomerName.textContent =
                    source.dataset.customerName ??
                    'Unknown subscriber';
            }


            if (detailsCustomerCode) {
                detailsCustomerCode.textContent =
                    source.dataset.customerCode ??
                    '';
            }


            if (detailsCustomerAddress) {
                detailsCustomerAddress.textContent =
                    source.dataset.customerAddress ??
                    'Not recorded';
            }


            if (detailsSchedule) {
                detailsSchedule.textContent =
                    source.dataset.scheduleDisplay ??
                    'Not scheduled';
            }


            if (detailsStatus) {
                detailsStatus.textContent =
                    source.dataset.statusLabel ??
                    '';
            }


            if (detailsTechnicianName) {
                detailsTechnicianName.textContent =
                    source.dataset.technicianName ||
                    (
                        source.dataset.technicianCode &&
                        source.dataset.technicianCode !==
                        'Unassigned' ?
                        source.dataset.technicianCode :
                        'Unassigned'
                    );
            }


            if (detailsTechnicianCode) {
                const technicianCode =
                    source.dataset.technicianCode ??
                    '';

                detailsTechnicianCode.textContent =
                    (
                        source.dataset.technicianName &&
                        technicianCode !== 'Unassigned'
                    ) ?
                    technicianCode :
                    '';
            }


            const serviceRequestNumber =
                source.dataset.serviceRequestNumber ??
                '';

            if (
                detailsServiceRequestRow &&
                detailsServiceRequestNumber &&
                detailsServiceRequestMeta
            ) {
                if (serviceRequestNumber !== '') {
                    detailsServiceRequestNumber.textContent =
                        serviceRequestNumber;

                    detailsServiceRequestMeta.textContent =
                        source.dataset.serviceRequestMeta ??
                        '';

                    detailsServiceRequestRow.classList.remove(
                        'hidden'
                    );

                    detailsServiceRequestRow.classList.add(
                        'grid'
                    );
                } else {
                    detailsServiceRequestNumber.textContent =
                        'No Service Request linked.';

                    detailsServiceRequestMeta.textContent =
                        '';

                    detailsServiceRequestRow.classList.remove(
                        'hidden'
                    );

                    detailsServiceRequestRow.classList.add(
                        'grid'
                    );
                }
            }


            if (detailsDescription) {
                detailsDescription.textContent =
                    source.dataset.description ??
                    '';
            }


            if (detailsCustomerLink) {
                const customerUrl =
                    source.dataset.customerUrl ??
                    '';

                if (customerUrl !== '') {
                    detailsCustomerLink.href =
                        customerUrl;

                    detailsCustomerLink.classList.remove(
                        'hidden'
                    );

                    detailsCustomerLink.classList.add(
                        'inline-flex'
                    );
                } else {
                    detailsCustomerLink.href = '#';

                    detailsCustomerLink.classList.add(
                        'hidden'
                    );

                    detailsCustomerLink.classList.remove(
                        'inline-flex'
                    );
                }
            }


            if (
                detailsAssignmentButton &&
                detailsAssignmentButtonLabel
            ) {
                const canAssign =
                    source.dataset.canAssign ===
                    '1';

                if (canAssign) {
                    const mode =
                        source.dataset.assignmentMode ===
                        'edit' ?
                        'edit' :
                        'assign';

                    detailsAssignmentButtonLabel.textContent =
                        mode === 'edit' ?
                        'Edit assignment' :
                        'Assign technician';

                    detailsAssignmentButton.classList.remove(
                        'hidden'
                    );

                    detailsAssignmentButton.classList.add(
                        'inline-flex'
                    );
                } else {
                    detailsAssignmentButton.classList.add(
                        'hidden'
                    );

                    detailsAssignmentButton.classList.remove(
                        'inline-flex'
                    );
                }
            }
        };


        const openDetailsModal = (source) => {
            populateDetailsModal(source);

            showModal(detailsModal);
        };


        const closeDetailsModal = () => {
            hideModal(detailsModal);
        };


        viewButtons.forEach((button) => {
            button.addEventListener(
                'click',
                () => {
                    openDetailsModal(button);
                }
            );
        });


        detailsCloseButtons.forEach((button) => {
            button.addEventListener(
                'click',
                closeDetailsModal
            );
        });


        detailsOverlay?.addEventListener(
            'click',
            closeDetailsModal
        );


        /*
        |--------------------------------------------------------------------------
        | Technician Assignment
        |--------------------------------------------------------------------------
        */

        const assignmentOverlay =
            assignmentModal?.querySelector(
                '[data-assignment-overlay]'
            );

        const assignmentCloseButtons = [
            ...assignmentModal?.querySelectorAll(
                '[data-assignment-close]'
            ) ?? [],
        ];

        const assignmentForm =
            assignmentModal?.querySelector(
                '#assignment-form'
            );

        const assignmentJobOrderId =
            assignmentModal?.querySelector(
                '#assignment-job-order-id'
            );

        const assignmentTechnician =
            assignmentModal?.querySelector(
                '#assignment-technician'
            );

        const assignmentDate =
            assignmentModal?.querySelector(
                '#assignment-date'
            );

        const assignmentTime =
            assignmentModal?.querySelector(
                '#assignment-time'
            );

        const assignmentTitle =
            assignmentModal?.querySelector(
                '#assignment-modal-title'
            );

        const assignmentJob =
            assignmentModal?.querySelector(
                '#assignment-modal-job'
            );

        const assignmentSubmitLabel =
            assignmentModal?.querySelector(
                '#assignment-submit-label'
            );


        const openAssignmentModal = (
            source,
            preserveOldInput = false
        ) => {
            if (
                !source ||
                !assignmentForm ||
                !assignmentJobOrderId ||
                !assignmentTechnician ||
                !assignmentDate ||
                !assignmentTime ||
                !assignmentTitle ||
                !assignmentJob ||
                !assignmentSubmitLabel
            ) {
                return;
            }


            hideModal(detailsModal);


            const mode =
                source.dataset.assignmentMode ===
                'edit' ?
                'edit' :
                'assign';


            assignmentForm.action =
                source.dataset.assignmentUrl ??
                '';


            assignmentJobOrderId.value =
                source.dataset.jobOrderId ??
                '';


            assignmentTitle.textContent =
                mode === 'edit' ?
                'Edit assignment' :
                'Assign technician';


            assignmentJob.textContent = [
                    source.dataset.jobOrderNumber ??
                    '',
                    source.dataset.jobType ??
                    '',
                ]
                .filter(Boolean)
                .join(' · ');


            assignmentSubmitLabel.textContent =
                mode === 'edit' ?
                'Save assignment' :
                'Assign technician';


            if (!preserveOldInput) {
                assignmentTechnician.value =
                    source.dataset.technicianId ??
                    '';

                assignmentDate.value =
                    source.dataset.scheduledDate ??
                    '';

                assignmentTime.value =
                    source.dataset.scheduledTime ??
                    '';
            }


            showModal(assignmentModal);


            window.setTimeout(
                () => {
                    assignmentTechnician.focus();
                },
                0
            );
        };


        const closeAssignmentModal = () => {
            hideModal(assignmentModal);
        };


        detailsAssignmentButton?.addEventListener(
            'click',
            () => {
                openAssignmentModal(
                    currentDetailsSource
                );
            }
        );


        assignmentCloseButtons.forEach((button) => {
            button.addEventListener(
                'click',
                closeAssignmentModal
            );
        });


        assignmentOverlay?.addEventListener(
            'click',
            closeAssignmentModal
        );


        /*
        |--------------------------------------------------------------------------
        | Keyboard close
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            'keydown',
            (event) => {
                if (event.key !== 'Escape') {
                    return;
                }


                if (modalIsOpen(assignmentModal)) {
                    event.preventDefault();

                    closeAssignmentModal();

                    return;
                }


                if (modalIsOpen(detailsModal)) {
                    event.preventDefault();

                    closeDetailsModal();

                    return;
                }


                if (modalIsOpen(createModal)) {
                    event.preventDefault();

                    closeCreateModal();
                }
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Restore modal state after redirect
        |--------------------------------------------------------------------------
        */

        if (
            state?.dataset.reopenCreate ===
            '1'
        ) {
            openCreateModal();

            return;
        }


        if (
            state?.dataset.reopenAssignment ===
            '1'
        ) {
            const jobOrderId =
                state.dataset.assignmentJobOrderId ??
                '';

            const source =
                viewButtons.find(
                    (button) =>
                    button.dataset.jobOrderId ===
                    jobOrderId
                );

            if (source) {
                openAssignmentModal(
                    source,
                    true
                );
            }

            return;
        }


        const createdJobOrderId =
            state?.dataset.createdJobOrderId ??
            '';

        if (createdJobOrderId !== '') {
            const source =
                viewButtons.find(
                    (button) =>
                    button.dataset.jobOrderId ===
                    createdJobOrderId
                );

            if (source) {
                openDetailsModal(source);
            }
        }
    });
</script>

@endsection