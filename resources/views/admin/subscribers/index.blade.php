@extends('layouts.app')

@section('title', 'All Subscribers')

@section('page-title', 'All Subscribers')

@section('content')
<div class="space-y-4">

    {{-- Page Header --}}
    <div>

        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
            All Subscribers
        </h1>

        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            View and find approved Rincomm subscriber records.
        </p>

    </div>


    {{-- Subscriber Information --}}
    <div
        class="
            flex items-start gap-2.5
            border border-gray-200
            bg-white px-4 py-3 shadow-sm
            dark:border-neutral-800
            dark:bg-neutral-900
        ">

        <i
            data-lucide="users"
            class="mt-0.5 h-4 w-4 shrink-0 text-[#008080]"
            aria-hidden="true">
        </i>

        <p class="text-sm leading-5 text-gray-600 dark:text-gray-300">
            Subscriber records are created only after an internet service application is approved.
        </p>

    </div>


    {{-- Search and Filters --}}
    <form
        method="GET"
        action="{{ route('admin.subscribers.index') }}"
        class="
            border border-gray-200
            bg-white p-3 shadow-sm
            dark:border-neutral-800
            dark:bg-neutral-900
        ">

        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">

            {{-- Search --}}
            <div class="relative min-w-0 flex-1">

                <label
                    for="subscriber-search"
                    class="sr-only">
                    Search subscribers
                </label>

                <i
                    data-lucide="search"
                    class="
                        pointer-events-none absolute
                        left-3 top-1/2 h-4 w-4
                        -translate-y-1/2
                        text-gray-500
                        dark:text-gray-400
                    "
                    aria-hidden="true">
                </i>

                <input
                    id="subscriber-search"
                    type="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search customer code, name, email, or phone"
                    autocomplete="off"
                    class="
                        min-h-11 w-full
                        border border-gray-300
                        bg-white py-2 pl-10 pr-3
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
                    ">

            </div>


            {{-- Status Filter --}}
            <div class="lg:w-48">

                <label
                    for="subscriber-status"
                    class="sr-only">
                    Filter by subscriber status
                </label>

                <select
                    id="subscriber-status"
                    name="status"
                    class="
                        min-h-11 w-full
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
                    ">

                    <option value="">
                        All statuses
                    </option>

                    @foreach ($statuses as $subscriberStatus)

                    <option
                        value="{{ $subscriberStatus }}"
                        @selected($status===$subscriberStatus)>
                        {{ ucfirst($subscriberStatus) }}
                    </option>

                    @endforeach

                </select>

            </div>


            {{-- Apply --}}
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
                    focus:ring-2
                    focus:ring-[#008080]
                    focus:ring-offset-2
                    dark:focus:ring-offset-neutral-900
                ">

                <i
                    data-lucide="list-filter"
                    class="h-4 w-4"
                    aria-hidden="true">
                </i>

                Apply

            </button>


            {{-- Clear --}}
            @if ($search !== '' || $status !== '')

            <a
                href="{{ route('admin.subscribers.index') }}"
                class="
                        inline-flex min-h-11 shrink-0
                        items-center justify-center gap-2
                        border border-gray-300
                        px-4 py-2
                        text-sm font-medium text-gray-700
                        transition
                        hover:bg-gray-50
                        focus:outline-none
                        focus:ring-2
                        focus:ring-gray-400
                        focus:ring-offset-2
                        dark:border-neutral-700
                        dark:text-gray-200
                        dark:hover:bg-neutral-800
                        dark:focus:ring-offset-neutral-900
                    ">

                <i
                    data-lucide="x"
                    class="h-4 w-4"
                    aria-hidden="true">
                </i>

                Clear

            </a>

            @endif

        </div>

    </form>


    {{-- Subscriber Results --}}
    <div
        aria-live="polite"
        aria-busy="false">

        @if ($subscribers->isEmpty())

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
                    data-lucide="users"
                    class="h-6 w-6 text-gray-500 dark:text-gray-400"
                    aria-hidden="true">
                </i>

            </div>


            <h2 class="mt-4 text-base font-semibold text-gray-900 dark:text-white">
                No subscribers found
            </h2>


            <p class="mx-auto mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">

                @if ($search !== '' || $status !== '')
                No subscriber records match the current search and filters.
                @else
                Approved subscriber records will appear here.
                @endif

            </p>

        </div>

        @else

        {{-- Desktop / Tablet Table --}}
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

                <table
                    class="
                            min-w-full
                            divide-y divide-gray-200
                            dark:divide-gray-800
                        ">

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
                                        px-2 py-3 text-left
                                        text-xs font-semibold uppercase tracking-wide
                                        text-gray-500
                                        dark:text-gray-400
                                        xl:px-5
                                    ">
                                Subscriber
                            </th>

                            <th
                                scope="col"
                                class="
                                        px-2 py-3 text-left
                                        text-xs font-semibold uppercase tracking-wide
                                        text-gray-500
                                        dark:text-gray-400
                                        xl:px-5
                                    ">
                                Contact
                            </th>

                            <th
                                scope="col"
                                class="
                                        px-2 py-3 text-left
                                        text-xs font-semibold uppercase tracking-wide
                                        text-gray-500
                                        dark:text-gray-400
                                        xl:px-5
                                    ">
                                Location
                            </th>

                            <th
                                scope="col"
                                class="
                                        px-2 py-3 text-center
                                        text-xs font-semibold uppercase tracking-wide
                                        text-gray-500
                                        dark:text-gray-400
                                        xl:px-5
                                    ">
                                Status
                            </th>

                            <th
                                scope="col"
                                class="
                                        px-2 py-3 text-left
                                        text-xs font-semibold uppercase tracking-wide
                                        text-gray-500
                                        dark:text-gray-400
                                        xl:px-5
                                    ">
                                Created
                            </th>

                            <th
                                scope="col"
                                class="
                                        px-2 py-3 text-right
                                        text-xs font-semibold uppercase tracking-wide
                                        text-gray-500
                                        dark:text-gray-400
                                        xl:px-5
                                    ">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">

                        @foreach ($subscribers as $subscriber)

                        @php
                        $statusDisplay = match ($subscriber->status) {
                        'pending' => [
                        'label' => 'Pending',
                        'icon' => 'clock-3',
                        'class' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300',
                        ],
                        'active' => [
                        'label' => 'Active',
                        'icon' => 'circle-check',
                        'class' => 'bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-300',
                        ],
                        'inactive' => [
                        'label' => 'Inactive',
                        'icon' => 'circle-minus',
                        'class' => 'bg-gray-100 text-gray-700 dark:bg-neutral-800 dark:text-gray-300',
                        ],
                        'suspended' => [
                        'label' => 'Suspended',
                        'icon' => 'pause-circle',
                        'class' => 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300',
                        ],
                        'disconnected' => [
                        'label' => 'Disconnected',
                        'icon' => 'wifi-off',
                        'class' => 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300',
                        ],
                        default => [
                        'label' => ucfirst($subscriber->status),
                        'icon' => 'circle-help',
                        'class' => 'bg-gray-100 text-gray-700 dark:bg-neutral-800 dark:text-gray-300',
                        ],
                        };

                        $fullName = trim(
                        $subscriber->first_name . ' ' .
                        ($subscriber->middle_name ? $subscriber->middle_name . ' ' : '') .
                        $subscriber->last_name
                        );
                        @endphp


                        <tr
                            class="
                                        transition
                                        hover:bg-gray-50/70
                                        dark:hover:bg-gray-800/40
                                    ">

                            {{-- Subscriber --}}
                            <td class="px-2 py-3 xl:px-5 xl:py-4">

                                <div class="min-w-[150px]">

                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $fullName }}
                                    </p>

                                    <p class="mt-1 text-xs font-medium text-[#008080] dark:text-[#5EEAD4]">
                                        {{ $subscriber->customer_code }}
                                    </p>

                                </div>

                            </td>


                            {{-- Contact --}}
                            <td class="px-2 py-3 xl:px-5 xl:py-4">

                                <div class="min-w-[180px]">

                                    <p class="break-all text-sm text-gray-700 dark:text-gray-300">
                                        {{ $subscriber->email ?: 'No email recorded' }}
                                    </p>

                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $subscriber->phone ?: 'No phone recorded' }}
                                    </p>

                                </div>

                            </td>


                            {{-- Location --}}
                            <td class="px-2 py-3 xl:px-5 xl:py-4">

                                <div class="min-w-[120px]">

                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        {{ $subscriber->city ?: 'Not specified' }}
                                    </p>

                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $subscriber->province ?: 'Province not specified' }}
                                    </p>

                                </div>

                            </td>


                            {{-- Status --}}
                            <td class="px-2 py-3 text-center xl:px-5 xl:py-4">

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
                                        aria-hidden="true">
                                    </i>

                                    {{ $statusDisplay['label'] }}

                                </span>

                            </td>


                            {{-- Created --}}
                            <td
                                class="
                                            whitespace-nowrap
                                            px-2 py-3
                                            text-xs text-gray-600
                                            dark:text-gray-300
                                            xl:px-5 xl:py-4 xl:text-sm
                                        ">

                                {{ $subscriber->created_at?->format('M d, Y') ?? 'Not available' }}

                            </td>


                            {{-- Action --}}
                            <td class="px-2 py-3 text-right xl:px-5 xl:py-4">

                                <a
                                    href="{{ route('admin.subscribers.show', $subscriber) }}"
                                    class="
                                                inline-flex min-h-9
                                                items-center justify-center gap-1.5
                                                whitespace-nowrap
                                                px-2 py-2
                                                text-xs font-semibold
                                                text-[#008080]
                                                transition
                                                hover:bg-[#008080]/5
                                                hover:text-[#006666]
                                                focus:outline-none
                                                focus:ring-2
                                                focus:ring-[#008080]/30
                                                xl:min-h-11
                                                xl:gap-2
                                                xl:px-4
                                                xl:text-sm
                                            ">

                                    View

                                    <i
                                        data-lucide="chevron-right"
                                        class="h-4 w-4"
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


        {{-- Mobile Cards --}}
        <div class="grid gap-4 lg:hidden">

            @foreach ($subscribers as $subscriber)

            @php
            $statusDisplay = match ($subscriber->status) {
            'pending' => [
            'label' => 'Pending',
            'icon' => 'clock-3',
            'class' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300',
            ],
            'active' => [
            'label' => 'Active',
            'icon' => 'circle-check',
            'class' => 'bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-300',
            ],
            'inactive' => [
            'label' => 'Inactive',
            'icon' => 'circle-minus',
            'class' => 'bg-gray-100 text-gray-700 dark:bg-neutral-800 dark:text-gray-300',
            ],
            'suspended' => [
            'label' => 'Suspended',
            'icon' => 'pause-circle',
            'class' => 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300',
            ],
            'disconnected' => [
            'label' => 'Disconnected',
            'icon' => 'wifi-off',
            'class' => 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300',
            ],
            default => [
            'label' => ucfirst($subscriber->status),
            'icon' => 'circle-help',
            'class' => 'bg-gray-100 text-gray-700 dark:bg-neutral-800 dark:text-gray-300',
            ],
            };

            $fullName = trim(
            $subscriber->first_name . ' ' .
            ($subscriber->middle_name ? $subscriber->middle_name . ' ' : '') .
            $subscriber->last_name
            );
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
                            {{ $subscriber->customer_code }}
                        </p>

                        <h2 class="mt-1 break-words text-base font-semibold text-gray-900 dark:text-white">
                            {{ $fullName }}
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
                            aria-hidden="true">
                        </i>

                        {{ $statusDisplay['label'] }}

                    </span>

                </div>


                <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">

                    <div>

                        <dt class="text-xs text-gray-400">
                            Email
                        </dt>

                        <dd class="mt-1 break-all text-gray-700 dark:text-gray-300">
                            {{ $subscriber->email ?: 'Not recorded' }}
                        </dd>

                    </div>


                    <div>

                        <dt class="text-xs text-gray-400">
                            Phone
                        </dt>

                        <dd class="mt-1 text-gray-700 dark:text-gray-300">
                            {{ $subscriber->phone ?: 'Not recorded' }}
                        </dd>

                    </div>


                    <div>

                        <dt class="text-xs text-gray-400">
                            Location
                        </dt>

                        <dd class="mt-1 text-gray-700 dark:text-gray-300">

                            {{ $subscriber->city ?: 'Not specified' }}

                            @if ($subscriber->province)
                            , {{ $subscriber->province }}
                            @endif

                        </dd>

                    </div>


                    <div>

                        <dt class="text-xs text-gray-400">
                            Subscriber Since
                        </dt>

                        <dd class="mt-1 text-gray-700 dark:text-gray-300">
                            {{ $subscriber->created_at?->format('M d, Y') ?? 'Not available' }}
                        </dd>

                    </div>

                </dl>


                <div class="mt-4 border-t border-gray-200 pt-3 dark:border-neutral-800">

                    <a
                        href="{{ route('admin.subscribers.show', $subscriber) }}"
                        class="
                                    inline-flex min-h-11 w-full
                                    items-center justify-center gap-2
                                    text-sm font-semibold text-[#008080]
                                    transition
                                    hover:bg-[#008080]/5
                                    hover:text-[#006666]
                                    focus:outline-none
                                    focus:ring-2
                                    focus:ring-[#008080]/30
                                ">

                        View Subscriber

                        <i
                            data-lucide="chevron-right"
                            class="h-4 w-4"
                            aria-hidden="true">
                        </i>

                    </a>

                </div>

            </article>

            @endforeach

        </div>

        @endif

    </div>

</div>
@endsection