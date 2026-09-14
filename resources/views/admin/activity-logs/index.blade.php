@extends('layouts.app')

@section('title', 'Activity Logs')

@section('page-title', 'Activity Logs')

@section('content')

<div class="space-y-4">

    {{-- Page header --}}
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
            Activity Logs
        </h1>

        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Review important user account and authentication activity.
        </p>
    </div>


    {{-- Information --}}
    <div
        class="
            flex items-start gap-2.5
            border border-gray-200
            bg-white px-4 py-3
            shadow-sm
            dark:border-neutral-800
            dark:bg-neutral-900
        ">
        <i
            data-lucide="shield-check"
            class="mt-0.5 h-4 w-4 shrink-0 text-[#008080]"
            aria-hidden="true"></i>

        <p class="text-sm leading-5 text-gray-600 dark:text-gray-300">
            Activity records are retained for account monitoring and audit purposes.
        </p>
    </div>


    {{-- Search and filters --}}
    <form
        method="GET"
        action="{{ route('admin.activity-logs.index') }}"
        class="
            border border-gray-200
            bg-white p-3
            shadow-sm
            dark:border-neutral-800
            dark:bg-neutral-900
        ">

        <div class="grid gap-2 lg:grid-cols-12">

            {{-- Search --}}
            <div class="relative lg:col-span-4">

                <label
                    for="activity-search"
                    class="sr-only">
                    Search activity logs
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
                    aria-hidden="true"></i>

                <input
                    id="activity-search"
                    type="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search user, action, description, or IP"
                    autocomplete="off"
                    class="
                        min-h-10 w-full
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


            {{-- Action --}}
            <div class="lg:col-span-2">

                <label
                    for="activity-action"
                    class="sr-only">
                    Filter by action
                </label>

                <select
                    id="activity-action"
                    name="action"
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
                    ">
                    <option value="">
                        All actions
                    </option>

                    @foreach ($actions as $value => $label)
                    <option
                        value="{{ $value }}"
                        @selected($action===$value)>
                        {{ $label }}
                    </option>
                    @endforeach
                </select>

            </div>


            {{-- Date from --}}
            <div class="lg:col-span-2">

                <label
                    for="date-from"
                    class="sr-only">
                    Date from
                </label>

                <input
                    id="date-from"
                    type="date"
                    name="date_from"
                    value="{{ $dateFrom }}"
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
                    ">

            </div>


            {{-- Date to --}}
            <div class="lg:col-span-2">

                <label
                    for="date-to"
                    class="sr-only">
                    Date to
                </label>

                <input
                    id="date-to"
                    type="date"
                    name="date_to"
                    value="{{ $dateTo }}"
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
                    ">

            </div>


            {{-- Apply --}}
            <div class="flex gap-2 lg:col-span-2">

                <button
                    type="submit"
                    class="
                        inline-flex min-h-10 flex-1
                        items-center justify-center gap-1.5
                        bg-[#008080]
                        px-3 py-2
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
                        aria-hidden="true"></i>

                    Apply
                </button>

                @if (
                $search !== '' ||
                $action !== '' ||
                $dateFrom !== '' ||
                $dateTo !== ''
                )

                <a
                    href="{{ route('admin.activity-logs.index') }}"
                    class="
                            inline-flex min-h-10
                            items-center justify-center
                            border border-gray-300
                            px-3 py-2
                            text-sm font-medium
                            text-gray-700
                            transition
                            hover:bg-gray-50
                            focus:outline-none
                            focus:ring-2
                            focus:ring-gray-400
                            dark:border-neutral-700
                            dark:text-gray-200
                            dark:hover:bg-neutral-800
                        "
                    aria-label="Clear filters"
                    title="Clear filters">
                    <i
                        data-lucide="x"
                        class="h-4 w-4"
                        aria-hidden="true"></i>
                </a>

                @endif

            </div>

        </div>

    </form>


    {{-- Results --}}
    @if ($logs->isEmpty())

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
                data-lucide="history"
                class="h-6 w-6 text-gray-500 dark:text-gray-400"
                aria-hidden="true"></i>
        </div>

        <h2 class="mt-4 text-base font-semibold text-gray-900 dark:text-white">
            No activity logs found
        </h2>

        <p class="mx-auto mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">
            User account activity will appear here as actions are recorded.
        </p>

    </div>

    @else

    {{-- Desktop table --}}
    <div
        class="
                hidden overflow-hidden
                border border-gray-200
                bg-white shadow-sm
                xl:block
                dark:border-neutral-800
                dark:bg-neutral-900
            ">

        <table class="w-full table-fixed">

            <thead class="border-b border-gray-200 bg-gray-50 dark:border-neutral-800 dark:bg-neutral-800">

                <tr>

                    <th
                        scope="col"
                        class="
                                w-[15%] px-4 py-3
                                text-left text-xs
                                font-semibold uppercase tracking-wide
                                text-gray-500
                                dark:text-gray-400
                            ">
                        Date / Time
                    </th>

                    <th
                        scope="col"
                        class="
                                w-[18%] px-4 py-3
                                text-left text-xs
                                font-semibold uppercase tracking-wide
                                text-gray-500
                                dark:text-gray-400
                            ">
                        Actor
                    </th>

                    <th
                        scope="col"
                        class="
                                w-[27%] px-4 py-3
                                text-left text-xs
                                font-semibold uppercase tracking-wide
                                text-gray-500
                                dark:text-gray-400
                            ">
                        Activity
                    </th>

                    <th
                        scope="col"
                        class="
                                w-[18%] px-4 py-3
                                text-left text-xs
                                font-semibold uppercase tracking-wide
                                text-gray-500
                                dark:text-gray-400
                            ">
                        Affected User
                    </th>

                    <th
                        scope="col"
                        class="
                                w-[12%] px-4 py-3
                                text-left text-xs
                                font-semibold uppercase tracking-wide
                                text-gray-500
                                dark:text-gray-400
                            ">
                        IP Address
                    </th>

                    <th
                        scope="col"
                        class="
                                w-[10%] px-4 py-3
                                text-left text-xs
                                font-semibold uppercase tracking-wide
                                text-gray-500
                                dark:text-gray-400
                            ">
                        Role
                    </th>

                </tr>

            </thead>


            <tbody class="divide-y divide-gray-100 dark:divide-neutral-800">

                @foreach ($logs as $log)

                @php
                $actionLabel = $actions[$log->action]
                ?? \Illuminate\Support\Str::headline(
                str_replace('.', ' ', $log->action)
                );
                @endphp

                <tr class="transition hover:bg-gray-50/70 dark:hover:bg-gray-800/40">

                    <td class="px-4 py-3 align-top">

                        <p class="text-sm text-gray-800 dark:text-gray-200">
                            {{ $log->created_at->format('M d, Y') }}
                        </p>

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $log->created_at->format('h:i A') }}
                        </p>

                    </td>


                    <td class="px-4 py-3 align-top">

                        <p class="truncate text-sm font-medium text-gray-900 dark:text-white">
                            {{ $log->actor?->name ?? 'System / Deleted User' }}
                        </p>

                        <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400">
                            {{ $log->actor?->email ?? 'Not available' }}
                        </p>

                    </td>


                    <td class="px-4 py-3 align-top">

                        <p class="text-sm font-medium text-[#008080] dark:text-[#5EEAD4]">
                            {{ $actionLabel }}
                        </p>

                        <p class="mt-1 line-clamp-2 text-xs leading-5 text-gray-600 dark:text-gray-300">
                            {{ $log->description ?: 'No description recorded.' }}
                        </p>

                    </td>


                    <td class="px-4 py-3 align-top">

                        <p class="truncate text-sm text-gray-800 dark:text-gray-200">
                            {{ $log->targetUser?->name ?? 'System / Deleted User' }}
                        </p>

                        <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400">
                            {{ $log->targetUser?->email ?? 'Not available' }}
                        </p>

                    </td>


                    <td class="px-4 py-3 align-top">

                        <span class="text-xs text-gray-700 dark:text-gray-300">
                            {{ $log->ip_address ?: 'Not available' }}
                        </span>

                    </td>


                    <td class="px-4 py-3 align-top">

                        <span
                            class="
                                        inline-flex px-2 py-1
                                        text-xs font-medium
                                        bg-gray-100 text-gray-700
                                        dark:bg-neutral-800
                                        dark:text-gray-300
                                    ">
                            {{ $log->actor?->role
                                        ? \Illuminate\Support\Str::headline($log->actor->role)
                                        : 'System'
                                    }}
                        </span>

                    </td>

                </tr>

                @endforeach

            </tbody>

        </table>

    </div>


    {{-- Tablet and mobile cards --}}
    <div class="grid gap-3 xl:hidden">

        @foreach ($logs as $log)

        @php
        $actionLabel = $actions[$log->action]
        ?? \Illuminate\Support\Str::headline(
        str_replace('.', ' ', $log->action)
        );
        @endphp

        <article
            class="
                        border border-gray-200
                        bg-white p-4
                        shadow-sm
                        dark:border-neutral-800
                        dark:bg-neutral-900
                    ">

            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

                <div class="min-w-0">

                    <p class="text-sm font-semibold text-[#008080] dark:text-[#5EEAD4]">
                        {{ $actionLabel }}
                    </p>

                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                        {{ $log->description ?: 'No description recorded.' }}
                    </p>

                </div>


                <div class="shrink-0 text-left sm:text-right">

                    <p class="text-xs text-gray-600 dark:text-gray-300">
                        {{ $log->created_at->format('M d, Y') }}
                    </p>

                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ $log->created_at->format('h:i A') }}
                    </p>

                </div>

            </div>


            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">

                <div>
                    <dt class="text-xs text-gray-400">
                        Actor
                    </dt>

                    <dd class="mt-1 break-words text-gray-700 dark:text-gray-300">
                        {{ $log->actor?->name ?? 'System / Deleted User' }}
                    </dd>
                </div>


                <div>
                    <dt class="text-xs text-gray-400">
                        Affected User
                    </dt>

                    <dd class="mt-1 break-words text-gray-700 dark:text-gray-300">
                        {{ $log->targetUser?->name ?? 'System / Deleted User' }}
                    </dd>
                </div>


                <div>
                    <dt class="text-xs text-gray-400">
                        IP Address
                    </dt>

                    <dd class="mt-1 text-gray-700 dark:text-gray-300">
                        {{ $log->ip_address ?: 'Not available' }}
                    </dd>
                </div>


                <div>
                    <dt class="text-xs text-gray-400">
                        Role
                    </dt>

                    <dd class="mt-1 text-gray-700 dark:text-gray-300">
                        {{ $log->actor?->role
                                    ? \Illuminate\Support\Str::headline($log->actor->role)
                                    : 'System'
                                }}
                    </dd>
                </div>

            </dl>

        </article>

        @endforeach

    </div>


    {{-- Pagination --}}
    @if ($logs->hasPages())

    <div class="mt-4">
        {{ $logs->links() }}
    </div>

    @endif

    @endif

</div>

@endsection