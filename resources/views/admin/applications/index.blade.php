@extends('layouts.app')

@section('title', 'Service Applications')

@section('page-title', 'Service Applications')

@section('content')
<div class="space-y-4">

    {{-- Page Header --}}
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
            Service Applications
        </h1>

        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Review submitted internet service applications from new Rincomm applicants.
        </p>
    </div>


    {{-- Success Message --}}
    @if (session('success'))
    <div
        role="status"
        aria-live="polite"
        class="
                flex items-start gap-3
                border border-green-200
                bg-green-50 px-4 py-3
                text-sm text-green-700
                dark:border-green-900
                dark:bg-green-950/40
                dark:text-green-300
            ">
        <i
            data-lucide="circle-check"
            class="mt-0.5 h-5 w-5 shrink-0"
            aria-hidden="true"></i>

        <span>{{ session('success') }}</span>
    </div>
    @endif


    {{-- Error Message --}}
    @if (session('error'))
    <div
        role="alert"
        aria-live="assertive"
        class="
                flex items-start gap-3
                border border-red-200
                bg-red-50 px-4 py-3
                text-sm text-red-700
                dark:border-red-900
                dark:bg-red-950/40
                dark:text-red-300
            ">
        <i
            data-lucide="triangle-alert"
            class="mt-0.5 h-5 w-5 shrink-0"
            aria-hidden="true"></i>

        <span>{{ session('error') }}</span>
    </div>
    @endif


    {{-- Review Information --}}
    <div
        class="
            flex items-start gap-2.5
            border border-gray-200
            bg-white px-4 py-3 shadow-sm
            dark:border-neutral-800
            dark:bg-neutral-900
        ">

        <i
            data-lucide="clipboard-list"
            class="mt-0.5 h-4 w-4 shrink-0 text-[#008080]"
            aria-hidden="true"></i>

        <p class="text-sm leading-5 text-gray-600 dark:text-gray-300">
            Review submitted applications before creating subscriber and subscription records.
        </p>

    </div>


    {{-- Search and Filters --}}
    <form
        method="GET"
        action="{{ route('admin.applications.index') }}"
        class="
            border border-gray-200
            bg-white p-3 shadow-sm
            dark:border-neutral-800
            dark:bg-neutral-900
        ">

        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">

            {{-- Search --}}
            <div class="relative min-w-0 flex-1">

                <label for="application-search" class="sr-only">
                    Search service applications
                </label>

                <i
                    data-lucide="search"
                    class="
                        pointer-events-none absolute
                        left-3 top-1/2 h-4 w-4
                        -translate-y-1/2
                        text-gray-500 dark:text-gray-400
                    "
                    aria-hidden="true"></i>

                <input
                    id="application-search"
                    type="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search application, applicant, email, or phone"
                    autocomplete="off"
                    class="
                        min-h-11 w-full
                        border border-gray-300
                        bg-white py-2 pl-10 pr-3
                        text-sm text-gray-900
                        outline-none transition
                        placeholder:text-gray-500
                        focus:border-[#008080]
                        focus:ring-2 focus:ring-[#008080]/20
                        dark:border-neutral-700
                        dark:bg-neutral-950
                        dark:text-gray-100
                        dark:placeholder:text-gray-500
                    ">
            </div>


            {{-- Status Filter --}}
            <div class="lg:w-48">

                <label for="application-status" class="sr-only">
                    Filter by application status
                </label>

                <select
                    id="application-status"
                    name="status"
                    class="
                        min-h-11 w-full
                        border border-gray-300
                        bg-white px-3 py-2
                        text-sm text-gray-800
                        outline-none transition
                        focus:border-[#008080]
                        focus:ring-2 focus:ring-[#008080]/20
                        dark:border-neutral-700
                        dark:bg-neutral-950
                        dark:text-gray-100
                    ">
                    <option value="pending" @selected($status==='pending' )>
                        Pending
                    </option>

                    <option value="approved" @selected($status==='approved' )>
                        Approved
                    </option>

                    <option value="rejected" @selected($status==='rejected' )>
                        Rejected
                    </option>

                    <option value="cancelled" @selected($status==='cancelled' )>
                        Cancelled
                    </option>
                </select>
            </div>


            {{-- Apply Filters --}}
            <button
                type="submit"
                class="
                    inline-flex min-h-11 shrink-0
                    items-center justify-center gap-2
                    bg-[#008080] px-4 py-2
                    text-sm font-semibold text-white
                    transition
                    hover:bg-[#006666]
                    focus:outline-none
                    focus:ring-2 focus:ring-[#008080]
                    focus:ring-offset-2
                    dark:focus:ring-offset-neutral-900
                ">
                <i
                    data-lucide="list-filter"
                    class="h-4 w-4"
                    aria-hidden="true"></i>

                Apply
            </button>


            {{-- Clear --}}
            @if ($search !== '' || $status !== 'pending')
            <a
                href="{{ route('admin.applications.index') }}"
                class="
                        inline-flex min-h-11 shrink-0
                        items-center justify-center gap-2
                        border border-gray-300
                        px-4 py-2
                        text-sm font-medium text-gray-700
                        transition
                        hover:bg-gray-50
                        focus:outline-none
                        focus:ring-2 focus:ring-gray-400
                        focus:ring-offset-2
                        dark:border-neutral-700
                        dark:text-gray-200
                        dark:hover:bg-neutral-800
                        dark:focus:ring-offset-neutral-900
                    ">
                <i
                    data-lucide="x"
                    class="h-4 w-4"
                    aria-hidden="true"></i>

                Clear
            </a>
            @endif

        </div>

    </form>


    {{-- Application Results --}}
    <div aria-live="polite">

        @if ($applications->isEmpty())

        {{-- Empty State --}}
        <div
            class="
                    border border-dashed border-gray-300
                    bg-white px-6 py-14
                    text-center shadow-sm
                    dark:border-gray-700
                    dark:bg-neutral-900
                ">

            <div
                class="
                        mx-auto flex h-12 w-12
                        items-center justify-center
                        bg-gray-100
                        dark:bg-neutral-800
                    ">
                <i
                    data-lucide="clipboard-list"
                    class="h-6 w-6 text-gray-500 dark:text-gray-400"
                    aria-hidden="true"></i>
            </div>

            <h2 class="mt-4 text-base font-semibold text-gray-900 dark:text-white">
                No {{ $status }} applications
            </h2>

            <p class="mx-auto mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">
                @if ($status === 'pending')
                New service applications waiting for review will appear here.
                @else
                No service applications match the selected status and search criteria.
                @endif
            </p>

        </div>

        @else

        {{-- Desktop Table --}}
        <div
            class="
                    hidden overflow-hidden
                    border border-gray-200
                    bg-white shadow-sm
                    lg:block
                    dark:border-neutral-800
                    dark:bg-neutral-900
                ">

            <div class="max-h-[60vh] overflow-auto">

                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">

                    <thead
                        class="
                                sticky top-0 z-10
                                bg-gray-50
                                dark:bg-neutral-800
                            ">
                        <tr>

                            <th
                                scope="col"
                                class="
                                        px-4 py-3 text-left
                                        text-xs font-semibold uppercase tracking-wide
                                        text-gray-500 dark:text-gray-400
                                    ">
                                Application
                            </th>

                            <th
                                scope="col"
                                class="
                                        px-4 py-3 text-left
                                        text-xs font-semibold uppercase tracking-wide
                                        text-gray-500 dark:text-gray-400
                                    ">
                                Applicant
                            </th>

                            <th
                                scope="col"
                                class="
                                        px-4 py-3 text-left
                                        text-xs font-semibold uppercase tracking-wide
                                        text-gray-500 dark:text-gray-400
                                    ">
                                Contact
                            </th>

                            <th
                                scope="col"
                                class="
                                        px-4 py-3 text-center
                                        text-xs font-semibold uppercase tracking-wide
                                        text-gray-500 dark:text-gray-400
                                    ">
                                Status
                            </th>

                            <th
                                scope="col"
                                class="
                                        px-4 py-3 text-left
                                        text-xs font-semibold uppercase tracking-wide
                                        text-gray-500 dark:text-gray-400
                                    ">
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

                            <td class="px-4 py-4">
                                <p class="break-all text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $application->application_number }}
                                </p>
                            </td>


                            <td class="px-4 py-4">
                                <div class="min-w-0">

                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ trim(
                                                    $application->first_name . ' ' .
                                                    ($application->middle_name ? $application->middle_name . ' ' : '') .
                                                    $application->last_name
                                                ) }}
                                    </p>

                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Applicant
                                    </p>

                                </div>
                            </td>


                            <td class="px-4 py-4">
                                <div class="min-w-0">

                                    <p class="break-all text-sm text-gray-700 dark:text-gray-300">
                                        {{ $application->email }}
                                    </p>

                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $application->phone }}
                                    </p>

                                </div>
                            </td>


                            <td class="px-4 py-4 text-center">

                                <span
                                    class="
                                                inline-flex items-center gap-1.5
                                                px-2.5 py-1
                                                text-xs font-medium
                                                {{ $statusDisplay['class'] }}
                                            ">
                                    <i
                                        data-lucide="{{ $statusDisplay['icon'] }}"
                                        class="h-3.5 w-3.5"
                                        aria-hidden="true"></i>

                                    {{ $statusDisplay['label'] }}
                                </span>

                            </td>


                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $application->submitted_at?->format('M d, Y') ?? 'Not submitted' }}
                            </td>

                        </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>


        {{-- Mobile Cards --}}
        <div class="grid gap-4 lg:hidden">

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
                            bg-white p-4 shadow-sm
                            dark:border-neutral-800
                            dark:bg-neutral-900
                        ">

                <div class="flex items-start justify-between gap-3">

                    <div class="min-w-0">

                        <p class="text-xs font-medium text-[#008080] dark:text-[#5EEAD4]">
                            {{ $application->application_number }}
                        </p>

                        <h2 class="mt-1 break-words text-base font-semibold text-gray-900 dark:text-white">
                            {{ trim(
                                        $application->first_name . ' ' .
                                        ($application->middle_name ? $application->middle_name . ' ' : '') .
                                        $application->last_name
                                    ) }}
                        </h2>

                    </div>

                    <span
                        class="
                                    inline-flex shrink-0 items-center gap-1.5
                                    px-2.5 py-1
                                    text-xs font-medium
                                    {{ $statusDisplay['class'] }}
                                ">
                        <i
                            data-lucide="{{ $statusDisplay['icon'] }}"
                            class="h-3.5 w-3.5"
                            aria-hidden="true"></i>

                        {{ $statusDisplay['label'] }}
                    </span>

                </div>


                <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">

                    <div>
                        <dt class="text-xs text-gray-400">
                            Email
                        </dt>

                        <dd class="mt-1 break-all text-gray-700 dark:text-gray-300">
                            {{ $application->email }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-xs text-gray-400">
                            Phone
                        </dt>

                        <dd class="mt-1 text-gray-700 dark:text-gray-300">
                            {{ $application->phone }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-xs text-gray-400">
                            Submitted
                        </dt>

                        <dd class="mt-1 text-gray-700 dark:text-gray-300">
                            {{ $application->submitted_at?->format('M d, Y') ?? 'Not submitted' }}
                        </dd>
                    </div>

                </dl>

            </article>

            @endforeach

        </div>


        {{-- Pagination --}}
        @if ($applications->hasPages())
        <div class="mt-6">
            {{ $applications->links() }}
        </div>
        @endif

        @endif

    </div>

</div>
@endsection