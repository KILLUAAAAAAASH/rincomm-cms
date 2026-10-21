@extends('layouts.app')

@section('title', 'Activity Logs')

@section('page-title', 'Activity Logs')

@section('content')

<div class="space-y-2">

    {{-- Page header --}}
    <div>
        <h1 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white">
            Activity Logs
        </h1>

        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
            Review important user account and authentication activity.
        </p>
    </div>


    {{-- Information --}}
    <div
        class="
            flex items-start gap-2
            border border-gray-200
            bg-white px-3 py-2.5
            dark:border-neutral-800
            dark:bg-neutral-900
        ">
        <i
            data-lucide="shield-check"
            class="mt-0.5 h-4 w-4 shrink-0 text-[#008080]"
            aria-hidden="true"></i>

        <p class="text-xs leading-5 text-gray-600 dark:text-gray-300">
            Activity records are retained for account monitoring and audit purposes.
        </p>
    </div>


    {{-- Search and filters --}}
    <form
        method="GET"
        action="{{ route('admin.activity-logs.index') }}"
        data-list-filters
        class="
            border border-gray-200
            bg-white p-3
            dark:border-neutral-800
            dark:bg-neutral-900
        ">

        <div class="flex flex-col gap-2 lg:flex-row lg:items-center">

            {{-- Search --}}
            <div class="relative min-w-0 flex-1">

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
                    data-list-search
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
            <div class="lg:w-40">

                <label
                    for="activity-action"
                    class="sr-only">
                    Filter by action
                </label>

                <select
                    id="activity-action"
                    name="action"
                    data-list-filter
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


            {{-- Single date filter --}}
            <div class="lg:w-40">

                <label
                    for="activity-date"
                    class="sr-only">
                    Filter by date
                </label>

                <input
                    id="activity-date"
                    type="date"
                    name="date"
                    value="{{ $date }}"
                    data-list-filter
                    aria-label="Filter by date"
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


            {{-- Sort --}}
            <div class="lg:w-44">

                <label
                    for="activity-sort"
                    class="sr-only">
                    Sort activity logs
                </label>

                <select
                    id="activity-sort"
                    name="sort"
                    data-list-filter
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

                    <option value="newest" @selected($sort === 'newest')>
                        Newest first
                    </option>

                    <option value="oldest" @selected($sort === 'oldest')>
                        Oldest first
                    </option>

                    <option value="action_asc" @selected($sort === 'action_asc')>
                        Action A-Z
                    </option>

                    <option value="action_desc" @selected($sort === 'action_desc')>
                        Action Z-A
                    </option>

                </select>

            </div>

            {{-- Clear filters --}}
            @if (
                $search !== '' ||
                $action !== '' ||
                $date !== '' ||
                $sort !== 'newest'
            )

            <a
                href="{{ route('admin.activity-logs.index') }}"
                data-list-clear
                class="
                    inline-flex min-h-10 shrink-0
                    items-center justify-center gap-2
                    border border-gray-300
                    px-4 py-2
                    text-sm font-medium
                    text-gray-700 transition
                    hover:bg-gray-50
                    focus:outline-none
                    focus:ring-2
                    focus:ring-gray-400
                    dark:border-neutral-700
                    dark:text-gray-200
                    dark:hover:bg-neutral-800
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


    {{-- Results --}}
    <div data-list-results aria-live="polite" aria-busy="false">
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
                bg-white
                xl:block
                dark:border-neutral-800
                dark:bg-neutral-900
            ">

        <div class="h-[60vh] overflow-auto">

        <table class="w-full table-fixed">

            <thead class="sticky top-0 z-10 border-b border-gray-200 bg-gray-50 dark:border-neutral-800 dark:bg-neutral-800">

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

    </div>


    {{-- Tablet and mobile cards --}}
    <div class="grid gap-2 xl:hidden">

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

</div>

@endsection
