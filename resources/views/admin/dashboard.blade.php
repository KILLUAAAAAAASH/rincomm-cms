@extends('layouts.app')

@section('title', 'Dashboard - Rincomm CMS')

@section('page-title', 'Dashboard')

@section('content')

<div class="space-y-6">

    {{-- Page Header --}}
    <div>

        <h1
            class="
                text-2xl font-semibold
                text-neutral-900
                dark:text-neutral-100
            ">
            Admin Dashboard
        </h1>

        <p
            class="
                mt-1 text-sm
                text-neutral-600
                dark:text-neutral-400
            ">
            Monitor current Rincomm operations and pending administrative work.
        </p>

    </div>


    {{-- Operational Summary --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

        {{-- Total Subscribers --}}
        <div
            class="
                border border-neutral-200
                bg-white p-5 shadow-sm
                dark:border-neutral-800
                dark:bg-neutral-900
            ">

            <div class="flex items-start justify-between gap-3">

                <div>

                    <p
                        class="
                            text-sm
                            text-neutral-500
                            dark:text-neutral-400
                        ">
                        Total Subscribers
                    </p>

                    <p
                        class="
                            mt-2 text-3xl font-bold
                            text-neutral-900
                            dark:text-neutral-100
                        ">
                        {{ $totalCustomers }}
                    </p>

                </div>


                <div
                    class="
                        flex h-10 w-10 shrink-0
                        items-center justify-center
                        bg-[#008080]/10
                        text-[#008080]
                        dark:bg-[#008080]/15
                        dark:text-[#5EEAD4]
                    ">

                    <i
                        data-lucide="users"
                        class="h-5 w-5"
                        aria-hidden="true">
                    </i>

                </div>

            </div>

        </div>


        {{-- Active Subscriptions --}}
        <div
            class="
                border border-neutral-200
                bg-white p-5 shadow-sm
                dark:border-neutral-800
                dark:bg-neutral-900
            ">

            <div class="flex items-start justify-between gap-3">

                <div>

                    <p
                        class="
                            text-sm
                            text-neutral-500
                            dark:text-neutral-400
                        ">
                        Active Subscriptions
                    </p>

                    <p
                        class="
                            mt-2 text-3xl font-bold
                            text-neutral-900
                            dark:text-neutral-100
                        ">
                        {{ $activeSubscriptions }}
                    </p>

                </div>


                <div
                    class="
                        flex h-10 w-10 shrink-0
                        items-center justify-center
                        bg-[#008080]/10
                        text-[#008080]
                        dark:bg-[#008080]/15
                        dark:text-[#5EEAD4]
                    ">

                    <i
                        data-lucide="radio-tower"
                        class="h-5 w-5"
                        aria-hidden="true">
                    </i>

                </div>

            </div>

        </div>


        {{-- Open Tickets --}}
        <div
            class="
                border border-neutral-200
                bg-white p-5 shadow-sm
                dark:border-neutral-800
                dark:bg-neutral-900
            ">

            <div class="flex items-start justify-between gap-3">

                <div>

                    <p
                        class="
                            text-sm
                            text-neutral-500
                            dark:text-neutral-400
                        ">
                        Open Tickets
                    </p>

                    <p
                        class="
                            mt-2 text-3xl font-bold
                            text-neutral-900
                            dark:text-neutral-100
                        ">
                        {{ $openTickets }}
                    </p>

                </div>


                <div
                    class="
                        flex h-10 w-10 shrink-0
                        items-center justify-center
                        bg-[#008080]/10
                        text-[#008080]
                        dark:bg-[#008080]/15
                        dark:text-[#5EEAD4]
                    ">

                    <i
                        data-lucide="ticket"
                        class="h-5 w-5"
                        aria-hidden="true">
                    </i>

                </div>

            </div>

        </div>


        {{-- Pending Job Orders --}}
        <div
            class="
                border border-neutral-200
                bg-white p-5 shadow-sm
                dark:border-neutral-800
                dark:bg-neutral-900
            ">

            <div class="flex items-start justify-between gap-3">

                <div>

                    <p
                        class="
                            text-sm
                            text-neutral-500
                            dark:text-neutral-400
                        ">
                        Pending Job Orders
                    </p>

                    <p
                        class="
                            mt-2 text-3xl font-bold
                            text-neutral-900
                            dark:text-neutral-100
                        ">
                        {{ $pendingJobOrders }}
                    </p>

                </div>


                <div
                    class="
                        flex h-10 w-10 shrink-0
                        items-center justify-center
                        bg-[#008080]/10
                        text-[#008080]
                        dark:bg-[#008080]/15
                        dark:text-[#5EEAD4]
                    ">

                    <i
                        data-lucide="clipboard-list"
                        class="h-5 w-5"
                        aria-hidden="true">
                    </i>

                </div>

            </div>

        </div>

    </div>


    {{-- Pending Applications --}}
    <section
        class="
            border border-neutral-200
            bg-white shadow-sm
            dark:border-neutral-800
            dark:bg-neutral-900
        ">

        {{-- Section Header --}}
        <div
            class="
                flex flex-col gap-3
                border-b border-neutral-200
                px-4 py-4
                sm:flex-row
                sm:items-center
                sm:justify-between
                dark:border-neutral-800
            ">

            <div>

                <div class="flex flex-wrap items-center gap-2">

                    <h2
                        class="
                            text-base font-semibold
                            text-neutral-900
                            dark:text-neutral-100
                        ">
                        Pending Applications
                    </h2>


                    <span
                        class="
                            inline-flex min-w-7
                            items-center justify-center
                            bg-amber-50 px-2 py-1
                            text-xs font-semibold
                            text-amber-700
                            dark:bg-amber-950/40
                            dark:text-amber-300
                        ">

                        {{ $pendingApplicationsCount }}

                    </span>

                </div>


                <p
                    class="
                        mt-1 text-sm
                        text-neutral-500
                        dark:text-neutral-400
                    ">
                    Review recently submitted internet service applications awaiting action.
                </p>

            </div>


            <a
                href="{{ route('admin.applications.index', ['status' => 'pending']) }}"
                class="
                    inline-flex min-h-10
                    shrink-0 items-center
                    justify-center gap-2
                    text-sm font-semibold
                    text-[#008080]
                    transition
                    hover:text-[#006666]
                    focus:outline-none
                    focus:ring-2
                    focus:ring-[#008080]/30
                ">

                View All Applications

                <i
                    data-lucide="arrow-right"
                    class="h-4 w-4"
                    aria-hidden="true">
                </i>

            </a>

        </div>


        @if ($pendingApplications->isEmpty())

        {{-- Empty State --}}
        <div class="px-6 py-10 text-center">

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
                    data-lucide="clipboard-list"
                    class="h-5 w-5"
                    aria-hidden="true">
                </i>

            </div>


            <h3
                class="
                        mt-3 text-sm font-semibold
                        text-neutral-900
                        dark:text-neutral-100
                    ">
                No pending applications
            </h3>


            <p
                class="
                        mt-1 text-sm
                        text-neutral-500
                        dark:text-neutral-400
                    ">
                New service applications awaiting review will appear here.
            </p>

        </div>


        @else

        {{-- Desktop / Tablet Table --}}
        <div class="hidden overflow-x-auto lg:block">

            <table
                class="
                        min-w-full
                        divide-y divide-neutral-200
                        dark:divide-neutral-800
                    ">

                <thead class="bg-neutral-50 dark:bg-neutral-800">

                    <tr>

                        <th
                            scope="col"
                            class="
                                    px-4 py-3 text-left
                                    text-xs font-semibold uppercase tracking-wide
                                    text-neutral-500
                                    dark:text-neutral-400
                                ">
                            Application
                        </th>


                        <th
                            scope="col"
                            class="
                                    px-4 py-3 text-left
                                    text-xs font-semibold uppercase tracking-wide
                                    text-neutral-500
                                    dark:text-neutral-400
                                ">
                            Applicant
                        </th>


                        <th
                            scope="col"
                            class="
                                    px-4 py-3 text-left
                                    text-xs font-semibold uppercase tracking-wide
                                    text-neutral-500
                                    dark:text-neutral-400
                                ">
                            Plan
                        </th>


                        <th
                            scope="col"
                            class="
                                    px-4 py-3 text-left
                                    text-xs font-semibold uppercase tracking-wide
                                    text-neutral-500
                                    dark:text-neutral-400
                                ">
                            Submitted
                        </th>


                        <th
                            scope="col"
                            class="
                                    px-4 py-3 text-right
                                    text-xs font-semibold uppercase tracking-wide
                                    text-neutral-500
                                    dark:text-neutral-400
                                ">
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody
                    class="
                            divide-y divide-neutral-100
                            dark:divide-neutral-800
                        ">

                    @foreach ($pendingApplications as $application)

                    @php
                    $applicantName = trim(
                    $application->first_name . ' ' .
                    ($application->middle_name
                    ? $application->middle_name . ' '
                    : '') .
                    $application->last_name
                    );
                    @endphp


                    <tr
                        class="
                                    transition
                                    hover:bg-neutral-50/70
                                    dark:hover:bg-neutral-800/40
                                ">

                        <td class="whitespace-nowrap px-4 py-4">

                            <p
                                class="
                                            text-sm font-medium
                                            text-neutral-900
                                            dark:text-neutral-100
                                        ">
                                {{ $application->application_number }}
                            </p>

                            <span
                                class="
                                            mt-1 inline-flex items-center gap-1.5
                                            bg-amber-50 px-2 py-0.5
                                            text-xs font-medium
                                            text-amber-700
                                            dark:bg-amber-950/40
                                            dark:text-amber-300
                                        ">

                                <i
                                    data-lucide="clock-3"
                                    class="h-3.5 w-3.5"
                                    aria-hidden="true">
                                </i>

                                Pending

                            </span>

                        </td>


                        <td class="px-4 py-4">

                            <p
                                class="
                                            text-sm font-medium
                                            text-neutral-900
                                            dark:text-neutral-100
                                        ">
                                {{ $applicantName }}
                            </p>

                        </td>


                        <td class="px-4 py-4">

                            <p
                                class="
                                            text-sm
                                            text-neutral-700
                                            dark:text-neutral-300
                                        ">

                                {{ $application->servicePlan?->name ?? 'Plan unavailable' }}

                            </p>

                        </td>


                        <td
                            class="
                                        whitespace-nowrap
                                        px-4 py-4
                                        text-sm
                                        text-neutral-600
                                        dark:text-neutral-300
                                    ">

                            {{ $application->submitted_at?->format('M d, Y') ?? 'Not available' }}

                        </td>


                        <td class="whitespace-nowrap px-4 py-4 text-right">

                            <a
                                href="{{ route('admin.applications.show', $application) }}"
                                class="
                                            inline-flex min-h-10
                                            items-center justify-center gap-1.5
                                            px-3 py-2
                                            text-sm font-semibold
                                            text-[#008080]
                                            transition
                                            hover:bg-[#008080]/5
                                            hover:text-[#006666]
                                            focus:outline-none
                                            focus:ring-2
                                            focus:ring-[#008080]/30
                                        ">

                                Review

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


        {{-- Mobile Cards --}}
        <div
            class="
                    divide-y divide-neutral-200
                    lg:hidden
                    dark:divide-neutral-800
                ">

            @foreach ($pendingApplications as $application)

            @php
            $applicantName = trim(
            $application->first_name . ' ' .
            ($application->middle_name
            ? $application->middle_name . ' '
            : '') .
            $application->last_name
            );
            @endphp


            <article class="p-4">

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
                            {{ $application->application_number }}
                        </p>


                        <h3
                            class="
                                        mt-1 break-words
                                        text-base font-semibold
                                        text-neutral-900
                                        dark:text-neutral-100
                                    ">
                            {{ $applicantName }}
                        </h3>

                    </div>


                    <span
                        class="
                                    inline-flex shrink-0
                                    items-center gap-1.5
                                    bg-amber-50 px-2.5 py-1
                                    text-xs font-medium
                                    text-amber-700
                                    dark:bg-amber-950/40
                                    dark:text-amber-300
                                ">

                        <i
                            data-lucide="clock-3"
                            class="h-3.5 w-3.5"
                            aria-hidden="true">
                        </i>

                        Pending

                    </span>

                </div>


                <dl class="mt-4 grid gap-4 sm:grid-cols-2">

                    <div>

                        <dt
                            class="
                                        text-xs
                                        text-neutral-500
                                        dark:text-neutral-400
                                    ">
                            Service Plan
                        </dt>


                        <dd
                            class="
                                        mt-1 text-sm
                                        text-neutral-700
                                        dark:text-neutral-300
                                    ">

                            {{ $application->servicePlan?->name ?? 'Plan unavailable' }}

                        </dd>

                    </div>


                    <div>

                        <dt
                            class="
                                        text-xs
                                        text-neutral-500
                                        dark:text-neutral-400
                                    ">
                            Submitted
                        </dt>


                        <dd
                            class="
                                        mt-1 text-sm
                                        text-neutral-700
                                        dark:text-neutral-300
                                    ">

                            {{ $application->submitted_at?->format('M d, Y') ?? 'Not available' }}

                        </dd>

                    </div>

                </dl>


                <div
                    class="
                                mt-4 border-t
                                border-neutral-200 pt-3
                                dark:border-neutral-800
                            ">

                    <a
                        href="{{ route('admin.applications.show', $application) }}"
                        class="
                                    inline-flex min-h-11 w-full
                                    items-center justify-center gap-2
                                    text-sm font-semibold
                                    text-[#008080]
                                    transition
                                    hover:bg-[#008080]/5
                                    hover:text-[#006666]
                                    focus:outline-none
                                    focus:ring-2
                                    focus:ring-[#008080]/30
                                ">

                        Review Application

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

    </section>

</div>

@endsection