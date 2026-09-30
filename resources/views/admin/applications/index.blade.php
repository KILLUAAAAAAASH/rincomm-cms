@extends('layouts.app')

@section('title', 'Service Applications')

@section('page-title', 'Service Applications')

@section('content')

@php
    $statusLabel = match ($status) {
        '' => 'All',
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
        default => 'All',
    };
@endphp

<div class="space-y-2">

    {{-- Page Header --}}
    <div>
        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
            Service Applications
        </h1>

        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
            Review submitted applications before creating subscriber records.
        </p>
    </div>


    {{-- Success Message --}}
    @if (session('success'))
    <div
        role="status"
        aria-live="polite"
        class="
            flex items-start gap-2
            border border-green-200
            bg-green-50 px-3 py-2
            text-sm text-green-700
            dark:border-green-900
            dark:bg-green-950/40
            dark:text-green-300
        "
    >
        <i
            data-lucide="circle-check"
            class="mt-0.5 h-4 w-4 shrink-0"
            aria-hidden="true"
        ></i>

        <span>{{ session('success') }}</span>
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
            text-sm text-red-700
            dark:border-red-900
            dark:bg-red-950/40
            dark:text-red-300
        "
    >
        <i
            data-lucide="triangle-alert"
            class="mt-0.5 h-4 w-4 shrink-0"
            aria-hidden="true"
        ></i>

        <span>{{ session('error') }}</span>
    </div>
    @endif


    {{-- Search and Filters --}}
    <form
        method="GET"
        action="{{ route('admin.applications.index') }}"
        class="
            border border-gray-200
            bg-white p-2
            dark:border-neutral-800
            dark:bg-neutral-900
        "
    >
        <div class="flex flex-col gap-2 lg:flex-row lg:items-center">

            {{-- Search --}}
            <div class="relative min-w-0 flex-1">

                <label
                    for="application-search"
                    class="sr-only"
                >
                    Search service applications
                </label>

                <i
                    data-lucide="search"
                    class="
                        pointer-events-none absolute
                        left-3 top-1/2 z-10
                        h-4 w-4
                        -translate-y-1/2
                        text-gray-500
                        dark:text-gray-400
                    "
                    aria-hidden="true"
                ></i>

                <input
                    id="application-search"
                    type="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search application, applicant, email, or phone"
                    autocomplete="off"
                    style="padding-left: 2.75rem;"
                    class="
                        min-h-10 w-full
                        border border-gray-300
                        bg-white py-2 pr-3
                        text-sm text-gray-900
                        outline-none transition
                        placeholder:text-gray-500
                        focus:border-[#008080]
                        focus:ring-2
                        focus:ring-[#008080]/20
                        dark:border-neutral-700
                        dark:bg-neutral-950
                        dark:text-gray-100
                        dark:placeholder:text-gray-500
                    "
                >

            </div>


            {{-- Status --}}
            <div class="lg:w-44">

                <label
                    for="application-status"
                    class="sr-only"
                >
                    Filter by application status
                </label>

                <select
                    id="application-status"
                    name="status"
                    class="
                        min-h-10 w-full
                        border border-gray-300
                        bg-white px-3 py-2
                        text-sm text-gray-800
                        outline-none transition
                        focus:border-[#008080]
                        focus:ring-2
                        focus:ring-[#008080]/20
                        dark:border-neutral-700
                        dark:bg-neutral-950
                        dark:text-gray-100
                    "
                >
                    <option value="" @selected($status === '')>
                        All Status
                    </option>

                    <option value="pending" @selected($status === 'pending')>
                        Pending
                    </option>

                    <option value="approved" @selected($status === 'approved')>
                        Approved
                    </option>

                    <option value="rejected" @selected($status === 'rejected')>
                        Rejected
                    </option>

                    <option value="cancelled" @selected($status === 'cancelled')>
                        Cancelled
                    </option>
                </select>

            </div>


            {{-- Apply --}}
            <button
                type="submit"
                class="
                    inline-flex min-h-10 shrink-0
                    items-center justify-center gap-2
                    bg-[#008080] px-4 py-2
                    text-sm font-semibold text-white
                    transition hover:bg-[#006666]
                    focus:outline-none
                    focus:ring-2 focus:ring-[#008080]
                    focus:ring-offset-2
                    dark:focus:ring-offset-neutral-900
                "
            >
                <i
                    data-lucide="list-filter"
                    class="h-4 w-4"
                    aria-hidden="true"
                ></i>

                Apply
            </button>


            {{-- Clear --}}
            @if ($search !== '' || $status !== '')
            <a
                href="{{ route('admin.applications.index') }}"
                class="
                    inline-flex min-h-10 shrink-0
                    items-center justify-center gap-2
                    border border-gray-300
                    px-4 py-2
                    text-sm font-medium text-gray-700
                    transition hover:bg-gray-50
                    focus:outline-none
                    focus:ring-2 focus:ring-gray-400
                    focus:ring-offset-2
                    dark:border-neutral-700
                    dark:text-gray-200
                    dark:hover:bg-neutral-800
                    dark:focus:ring-offset-neutral-900
                "
            >
                <i
                    data-lucide="x"
                    class="h-4 w-4"
                    aria-hidden="true"
                ></i>

                Clear
            </a>
            @endif

        </div>
    </form>


    {{-- Application Results --}}
    <div aria-live="polite">

        @if ($applications->isEmpty())

        <div
            class="
                border border-dashed border-gray-300
                bg-white px-4 py-8
                text-center
                dark:border-gray-700
                dark:bg-neutral-900
            "
        >

            <div
                class="
                    mx-auto flex h-10 w-10
                    items-center justify-center
                    border border-gray-200
                    bg-gray-50
                    dark:border-neutral-700
                    dark:bg-neutral-800
                "
            >
                <i
                    data-lucide="clipboard-list"
                    class="h-5 w-5 text-gray-500 dark:text-gray-400"
                    aria-hidden="true"
                ></i>
            </div>

            <h2 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">
                No
                @if ($status === '')
                    applications
                @else
                    {{ strtolower($statusLabel) }} applications
                @endif
            </h2>

            <p class="mx-auto mt-1 max-w-md text-xs text-gray-500 dark:text-gray-400">
                @if ($status === '')
                    No service applications match the current search criteria.
                @elseif ($status === 'pending')
                    New service applications waiting for review will appear here.
                @else
                    No {{ strtolower($statusLabel) }} service applications match the selected status and search criteria.
                @endif
            </p>

        </div>

        @else

        {{-- Desktop Table --}}
        <div
            class="
                hidden overflow-hidden
                border border-gray-200
                bg-white
                lg:block
                dark:border-neutral-800
                dark:bg-neutral-900
            "
        >
            <div class="max-h-[60vh] overflow-auto">

                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">

                    <thead class="sticky top-0 z-10 bg-gray-50 dark:bg-neutral-800">
                        <tr>

                            <th
                                scope="col"
                                class="
                                    px-3 py-2.5 text-left
                                    text-[11px] font-semibold uppercase tracking-wide
                                    text-gray-500 dark:text-gray-400
                                "
                            >
                                Application
                            </th>

                            <th
                                scope="col"
                                class="
                                    px-3 py-2.5 text-left
                                    text-[11px] font-semibold uppercase tracking-wide
                                    text-gray-500 dark:text-gray-400
                                "
                            >
                                Applicant
                            </th>

                            <th
                                scope="col"
                                class="
                                    px-3 py-2.5 text-left
                                    text-[11px] font-semibold uppercase tracking-wide
                                    text-gray-500 dark:text-gray-400
                                "
                            >
                                Contact
                            </th>

                            <th
                                scope="col"
                                class="
                                    px-3 py-2.5 text-center
                                    text-[11px] font-semibold uppercase tracking-wide
                                    text-gray-500 dark:text-gray-400
                                "
                            >
                                Status
                            </th>

                            <th
                                scope="col"
                                class="
                                    px-3 py-2.5 text-left
                                    text-[11px] font-semibold uppercase tracking-wide
                                    text-gray-500 dark:text-gray-400
                                "
                            >
                                Submitted
                            </th>

                        </tr>
                    </thead>


                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">

                        @foreach ($applications as $application)

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
                                    'icon' => 'circle-x',
                                    'class' => 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300',
                                ],
                                default => [
                                    'label' => 'Cancelled',
                                    'icon' => 'ban',
                                    'class' => 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400',
                                ],
                            };
                        @endphp

                        <tr class="transition hover:bg-gray-50/70 dark:hover:bg-gray-800/40">

                            <td class="px-3 py-3">

                                <a
                                    href="{{ route('admin.applications.show', $application) }}"
                                    class="
                                        break-all text-sm font-medium
                                        text-[#008080]
                                        hover:underline
                                        focus:outline-none
                                        focus:ring-2
                                        focus:ring-[#008080]/30
                                        dark:text-teal-300
                                    "
                                >
                                    {{ $application->application_number }}
                                </a>

                            </td>


                            <td class="px-3 py-3">

                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ trim(
                                        $application->first_name . ' ' .
                                        ($application->middle_name ? $application->middle_name . ' ' : '') .
                                        $application->last_name
                                    ) }}
                                </p>

                            </td>


                            <td class="px-3 py-3">

                                <p class="break-all text-sm text-gray-700 dark:text-gray-300">
                                    {{ $application->email }}
                                </p>

                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $application->phone }}
                                </p>

                            </td>


                            <td class="px-3 py-3 text-center">

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

                            </td>


                            <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-600 dark:text-gray-300">
                                {{ $application->submitted_at?->format('M d, Y') ?? 'Not submitted' }}
                            </td>

                        </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>
        </div>


        {{-- Mobile Cards --}}
        <div class="grid gap-2 lg:hidden">

            @foreach ($applications as $application)

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
                        'icon' => 'circle-x',
                        'class' => 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300',
                    ],
                    default => [
                        'label' => 'Cancelled',
                        'icon' => 'ban',
                        'class' => 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400',
                    ],
                };
            @endphp

            <article
                class="
                    border border-gray-200
                    bg-white p-3
                    dark:border-neutral-800
                    dark:bg-neutral-900
                "
            >

                <div class="flex items-start justify-between gap-3">

                    <div class="min-w-0">

                        <a
                            href="{{ route('admin.applications.show', $application) }}"
                            class="
                                break-all text-xs font-medium
                                text-[#008080]
                                hover:underline
                                dark:text-teal-300
                            "
                        >
                            {{ $application->application_number }}
                        </a>

                        <h2 class="mt-0.5 break-words text-sm font-semibold text-gray-900 dark:text-white">
                            {{ trim(
                                $application->first_name . ' ' .
                                ($application->middle_name ? $application->middle_name . ' ' : '') .
                                $application->last_name
                            ) }}
                        </h2>

                    </div>


                    <span
                        class="
                            inline-flex shrink-0 items-center gap-1
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


                <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-2">

                    <div>
                        <dt class="text-[11px] text-gray-400">
                            Email
                        </dt>

                        <dd class="mt-0.5 break-all text-gray-700 dark:text-gray-300">
                            {{ $application->email }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-[11px] text-gray-400">
                            Phone
                        </dt>

                        <dd class="mt-0.5 text-gray-700 dark:text-gray-300">
                            {{ $application->phone }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-[11px] text-gray-400">
                            Submitted
                        </dt>

                        <dd class="mt-0.5 text-gray-700 dark:text-gray-300">
                            {{ $application->submitted_at?->format('M d, Y') ?? 'Not submitted' }}
                        </dd>
                    </div>

                </dl>

            </article>

            @endforeach

        </div>


        {{-- Pagination --}}
        @if ($applications->hasPages())
        <div class="mt-3">
            {{ $applications->links() }}
        </div>
        @endif

        @endif

    </div>

</div>

@endsection
