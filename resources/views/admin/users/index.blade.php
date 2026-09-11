@extends('layouts.app')

@section('title', 'User Management')

@section('page-title', 'User Management')

@section('content')
<div class="space-y-6">


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


    {{-- Information Card --}}
    <div
        class="
            border border-gray-200
            bg-white p-4 shadow-sm
            dark:border-neutral-800
            dark:bg-neutral-900
        ">
        <div class="flex items-start gap-3">

            <div
                class="
                    mt-0.5 flex h-10 w-10 shrink-0
                    items-center justify-center
                    bg-teal-50
                    dark:bg-teal-950/40
                ">
                <i
                    data-lucide="shield-check"
                    class="h-5 w-5 text-[#008080]"
                    aria-hidden="true"></i>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">
                    Account Access Control
                </p>

                <p class="mt-1 text-sm leading-6 text-gray-500 dark:text-gray-400">
                    Deactivated accounts cannot access protected Rincomm system areas.
                    Role and subscriber service status are managed separately.
                </p>
            </div>

        </div>
    </div>


    {{-- User Search and Filters --}}
    <form
        method="GET"
        action="{{ route('admin.users.index') }}"
        data-user-filters
        class="
        border border-gray-200
        bg-white p-3 shadow-sm
        dark:border-neutral-800
        dark:bg-neutral-900
    ">

        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">

            {{-- Search --}}
            <div class="relative min-w-0 flex-1">

                <label for="user-search" class="sr-only">
                    Search users
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
                    id="user-search"
                    type="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search by name or email"
                    autocomplete="off"
                    data-user-search
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


            {{-- Role Filter --}}
            <div class="lg:w-44">

                <label for="user-role" class="sr-only">
                    Filter by role
                </label>

                <select
                    id="user-role"
                    name="role"
                    data-user-filter
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
                    <option value="">All roles</option>

                    <option value="admin" @selected($role==='admin' )>
                        Administrator
                    </option>

                    <option value="staff" @selected($role==='staff' )>
                        Staff
                    </option>

                    <option value="technician" @selected($role==='technician' )>
                        Technician
                    </option>

                    <option value="customer" @selected($role==='customer' )>
                        Customer
                    </option>
                </select>
            </div>


            {{-- Status Filter --}}
            <div class="lg:w-44">

                <label for="user-status" class="sr-only">
                    Filter by account status
                </label>

                <select
                    id="user-status"
                    name="status"
                    data-user-filter
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
                    <option value="">All statuses</option>

                    <option value="active" @selected($status==='active' )>
                        Active
                    </option>

                    <option value="inactive" @selected($status==='inactive' )>
                        Inactive
                    </option>
                </select>
            </div>


            {{-- Clear Filters --}}
            @if ($search !== '' || $role !== '' || $status !== '')
            <a
                href="{{ route('admin.users.index') }}"
                class="
                inline-flex min-h-11 shrink-0
                items-center justify-center gap-2
                border border-gray-300
                px-4 py-2
                text-sm font-medium
                text-gray-700 transition
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

    {{-- User Results --}}
    <div
        data-user-results
        aria-live="polite"
        aria-busy="false">


        @if ($users->isEmpty())

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
                    aria-hidden="true"></i>
            </div>

            <h2 class="mt-4 text-base font-semibold text-gray-900 dark:text-white">
                No user accounts found
            </h2>

            <p class="mx-auto mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">
                Registered Rincomm accounts will appear here.
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

                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">

                    <thead class="
                               sticky top-0 z-10
                               bg-gray-50
                               dark:bg-neutral-800">
                        <tr>

                            <th
                                class="
                                    px-2 py-3 xl:px-5
                                    text-xs font-semibold uppercase tracking-wide
                                    text-gray-500 dark:text-gray-400
                                ">
                                User
                            </th>

                            <th
                                class="
                                    px-2 py-3 xl:px-5
                                    text-xs font-semibold uppercase tracking-wide
                                    text-gray-500 dark:text-gray-400
                                ">
                                Role
                            </th>

                            <th
                                class="
                                    px-2 py-3 xl:px-5
                                    text-xs font-semibold uppercase tracking-wide
                                    text-gray-500 dark:text-gray-400
                                ">
                                Status
                            </th>

                            <th
                                class="
                                    px-2 py-3 xl:px-5
                                    text-xs font-semibold uppercase tracking-wide
                                    text-gray-500 dark:text-gray-400
                                ">
                                Joined
                            </th>

                            <th
                                class="
                                    px-2 py-3 xl:px-5
                                    text-xs font-semibold uppercase tracking-wide
                                    text-gray-500 dark:text-gray-400
                                ">
                                Action
                            </th>

                        </tr>
                    </thead>


                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">

                        @foreach ($users as $user)

                        @php
                        $isCurrentUser = auth()->id() === $user->id;

                        $isLastActiveAdministrator =
                        $user->role === 'admin' &&
                        $user->account_status === 'active' &&
                        $activeAdministratorCount <= 1;

                            $isProtected=$isCurrentUser ||
                            $isLastActiveAdministrator;
                            @endphp

                            <tr class="transition hover:bg-gray-50/70 dark:hover:bg-gray-800/40">

                            {{-- User --}}
                            <td class="px-2 py-3 xl:px-5 xl:py-4">

                                <div class="flex min-w-[175px] items-center gap-2 xl:min-w-[240px] xl:gap-3">

                                    <div
                                        class="
                                                flex h-8 w-8 shrink-0
                                                xl:h-10 xl:w-10
                                                items-center justify-center
                                                bg-[#008080]/10
                                                text-sm font-semibold
                                                text-[#008080]
                                                dark:bg-[#008080]/20
                                                dark:text-[#5EEAD4]
                                            ">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>

                                    <div class="min-w-0">

                                        <div class="flex flex-wrap items-center gap-2">

                                            <p
                                                class="
                                                        truncate text-sm font-medium
                                                        text-gray-900 dark:text-white
                                                    ">
                                                {{ $user->name }}
                                            </p>

                                            @if ($isCurrentUser)
                                            <span
                                                class="
                                                            inline-flex items-center
                                                            bg-blue-50 px-2 py-0.5
                                                            text-xs font-medium
                                                            text-blue-700
                                                            dark:bg-blue-950/40
                                                            dark:text-blue-300
                                                        ">
                                                You
                                            </span>
                                            @endif

                                        </div>

                                        <p
                                            class="
                                                    mt-1 max-w-xs truncate
                                                    text-xs text-gray-500
                                                    dark:text-gray-400
                                                ">
                                            {{ $user->email }}
                                        </p>

                                    </div>

                                </div>
                            </td>


                            {{-- Role --}}
                            <td class="px-2 py-3 xl:px-5 xl:py-4">

                                @php
                                $roleIcon = match ($user->role) {
                                'admin' => 'shield',
                                'staff' => 'briefcase-business',
                                'technician' => 'wrench',
                                default => 'user',
                                };
                                @endphp

                                <span
                                    class="
                                            inline-flex items-center gap-1.5
                                            bg-gray-100 px-2.5 py-1
                                            text-xs font-medium
                                            text-gray-700
                                            dark:bg-neutral-800
                                            dark:text-gray-300
                                        ">
                                    <i
                                        data-lucide="{{ $roleIcon }}"
                                        class="h-3.5 w-3.5"
                                        aria-hidden="true"></i>

                                    {{ ucfirst($user->role) }}
                                </span>

                            </td>


                            {{-- Status --}}
                            <td class="px-2 py-3 text-center xl:px-5 xl:py-4">

                                @if ($user->account_status === 'active')

                                <span
                                    class="
                                                inline-flex items-center gap-1.5
                                                bg-green-50 px-2.5 py-1
                                                text-xs font-medium
                                                text-green-700
                                                dark:bg-green-950/40
                                                dark:text-green-300
                                            ">
                                    <i
                                        data-lucide="circle-check"
                                        class="h-3.5 w-3.5"
                                        aria-hidden="true"></i>

                                    Active
                                </span>

                                @else

                                <span
                                    class="
                                                inline-flex items-center gap-1.5
                                                bg-neutral-100 px-2.5 py-1
                                                text-xs font-medium
                                                text-neutral-600
                                                dark:bg-neutral-800
                                                dark:text-neutral-400
                                            ">
                                    <i
                                        data-lucide="circle-minus"
                                        class="h-3.5 w-3.5"
                                        aria-hidden="true"></i>

                                    Inactive
                                </span>

                                @endif

                            </td>


                            {{-- Joined --}}
                            <td
                                class="
        whitespace-nowrap px-2 py-3
        text-xs text-gray-600
        dark:text-gray-300
        xl:px-5 xl:py-4 xl:text-sm
    ">


                                {{-- Action --}}
                            <td class="px-2 py-3 xl:px-5 xl:py-4">

                                <div class="ml-auto w-32">

                                    @if ($user->account_status === 'inactive')

                                    <form
                                        method="POST"
                                        action="{{ route('admin.users.status', $user) }}"
                                        class="w-full"
                                        data-lock-submit>
                                        @csrf
                                        @method('PATCH')

                                        <input
                                            type="hidden"
                                            name="account_status"
                                            value="active">

                                        <button
                                            type="button"
                                            data-user-activate
                                            data-user-url="{{ route('admin.users.status', $user) }}"
                                            data-user-name="{{ $user->name }}"
                                            class="
                                                inline-flex min-h-9 w-full
                                                 items-center justify-center gap-1.5
                                                whitespace-nowrap
                                                border border-green-300
                                                px-2 py-2
                                                text-xs font-semibold
                                                text-green-700
                                                transition
                                                hover:bg-green-50

                                                dark:border-green-800
                                                dark:text-green-300
                                                dark:hover:bg-green-950/30
                                                xl:min-h-11
                                                xl:gap-2
                                                xl:px-4
                                                xl:text-sm
                                            ">
                                            <i
                                                data-lucide="user-check"
                                                class="h-4 w-4"
                                                aria-hidden="true"></i>

                                            <span class="xl:hidden">
                                                Activate
                                            </span>

                                            <span class="hidden xl:inline">
                                                Activate
                                            </span>
                                        </button>

                                    </form>

                                    @elseif ($isProtected)

                                    <span
                                        class="
                                                inline-flex min-h-9 w-full
                                                items-center justify-center gap-1.5
                                                whitespace-nowrap
                                                    border border-gray-200
                                                    bg-gray-50 px-3 py-2
                                                    text-xs font-medium
                                                    text-gray-400
                                                    dark:border-gray-700
                                                    dark:bg-neutral-800
                                                    dark:text-gray-500
                                                "
                                        title="{{ $isCurrentUser
                                                    ? 'You cannot deactivate your own account.'
                                                    : 'The last active administrator cannot be deactivated.' }}">
                                        <i
                                            data-lucide="shield"
                                            class="h-4 w-4"
                                            aria-hidden="true"></i>

                                        Protected
                                    </span>

                                    @else

                                    <button
                                        type="button"
                                        data-user-deactivate
                                        data-user-url="{{ route('admin.users.status', $user) }}"
                                        data-user-name="{{ $user->name }}"
                                        class="
    inline-flex min-h-9 w-full
    items-center justify-center gap-1.5
    whitespace-nowrap
                                                    border border-red-200
                                                    px-3 py-2
                                                    text-xs font-semibold
                                                    text-red-600
                                                    transition
                                                    hover:bg-red-50
                                                    focus:outline-none
                                                    focus:ring-2
                                                    focus:ring-red-500
                                                    focus:ring-offset-2
                                                    dark:border-red-900
                                                    dark:text-red-400
                                                    dark:hover:bg-red-950/30
                                                    dark:focus:ring-offset-neutral-900
                                                ">
                                        <i
                                            data-lucide="user-x"
                                            class="h-4 w-4"
                                            aria-hidden="true"></i>

                                        Deactivate
                                    </button>

                                    @endif

                                </div>
                            </td>

                            </tr>

                            @endforeach

                    </tbody>
                </table>

            </div>
        </div>


        {{-- Mobile Cards --}}
        <div class="grid gap-4 lg:hidden">

            @foreach ($users as $user)

            @php
            $isCurrentUser = auth()->id() === $user->id;

            $isLastActiveAdministrator =
            $user->role === 'admin' &&
            $user->account_status === 'active' &&
            $activeAdministratorCount <= 1;

                $isProtected=$isCurrentUser ||
                $isLastActiveAdministrator;

                $roleIcon=match ($user->role) {
                'admin' => 'shield',
                'staff' => 'briefcase-business',
                'technician' => 'wrench',
                default => 'user',
                };
                @endphp

                <article
                    class="
                        border border-gray-200
                        bg-white p-4 shadow-sm
                        dark:border-neutral-800
                        dark:bg-neutral-900
                    ">

                    <div class="flex items-start gap-3">

                        <div
                            class="
                                flex h-11 w-11 shrink-0
                                items-center justify-center
                                bg-[#008080]/10
                                font-semibold text-[#008080]
                                dark:bg-[#008080]/20
                                dark:text-[#5EEAD4]
                            ">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>

                        <div class="min-w-0 flex-1">

                            <div class="flex flex-wrap items-center gap-2">

                                <h2
                                    class="
                                        break-words text-base font-semibold
                                        text-gray-900 dark:text-white
                                    ">
                                    {{ $user->name }}
                                </h2>

                                @if ($isCurrentUser)
                                <span
                                    class="
                                            bg-blue-50 px-2 py-0.5
                                            text-xs font-medium text-blue-700
                                            dark:bg-blue-950/40
                                            dark:text-blue-300
                                        ">
                                    You
                                </span>
                                @endif

                            </div>

                            <p
                                class="
                                    mt-1 break-all text-sm
                                    text-gray-500 dark:text-gray-400
                                ">
                                {{ $user->email }}
                            </p>

                        </div>

                    </div>


                    <dl class="mt-5 grid grid-cols-2 gap-4 text-sm">

                        <div>
                            <dt class="text-xs text-gray-400">
                                Role
                            </dt>

                            <dd class="mt-1">

                                <span
                                    class="
                                        inline-flex items-center gap-1.5
                                        bg-gray-100 px-2.5 py-1
                                        text-xs font-medium
                                        text-gray-700
                                        dark:bg-neutral-800
                                        dark:text-gray-300
                                    ">
                                    <i
                                        data-lucide="{{ $roleIcon }}"
                                        class="h-3.5 w-3.5"
                                        aria-hidden="true"></i>

                                    {{ ucfirst($user->role) }}
                                </span>

                            </dd>
                        </div>


                        <div>
                            <dt class="text-xs text-gray-400">
                                Status
                            </dt>

                            <dd class="mt-1">

                                @if ($user->account_status === 'active')

                                <span
                                    class="
                                            inline-flex items-center gap-1.5
                                            bg-green-50 px-2.5 py-1
                                            text-xs font-medium text-green-700
                                            dark:bg-green-950/40
                                            dark:text-green-300
                                        ">
                                    <i
                                        data-lucide="circle-check"
                                        class="h-3.5 w-3.5"
                                        aria-hidden="true"></i>

                                    Active
                                </span>

                                @else

                                <span
                                    class="
                                            inline-flex items-center gap-1.5
                                            bg-neutral-100 px-2.5 py-1
                                            text-xs font-medium
                                            text-neutral-600
                                            dark:bg-neutral-800
                                            dark:text-neutral-400
                                        ">
                                    <i
                                        data-lucide="circle-minus"
                                        class="h-3.5 w-3.5"
                                        aria-hidden="true"></i>

                                    Inactive
                                </span>

                                @endif

                            </dd>
                        </div>


                        <div class="col-span-2">
                            <dt class="text-xs text-gray-400">
                                Joined
                            </dt>

                            <dd class="mt-1 text-gray-700 dark:text-gray-300">
                                {{ $user->created_at->format('M d, Y') }}
                            </dd>
                        </div>

                    </dl>


                    <div
                        class="
                            mt-5 border-t border-gray-100
                            pt-4 dark:border-neutral-800
                        ">

                        @if ($user->account_status === 'inactive')

                        <form
                            method="POST"
                            action="{{ route('admin.users.status', $user) }}"
                            data-lock-submit>
                            @csrf
                            @method('PATCH')

                            <input
                                type="hidden"
                                name="account_status"
                                value="active">

                            <button
                                type="button"
                                data-user-activate
                                data-user-url="{{ route('admin.users.status', $user) }}"
                                data-user-name="{{ $user->name }}"
                                class="
        inline-flex min-h-9
        items-center justify-center gap-2
        border border-green-300
        px-3 py-2
        text-xs font-semibold
        text-green-700
        transition
        hover:bg-green-50
        focus:outline-none
        focus:ring-2
        focus:ring-green-500
        focus:ring-offset-2
        dark:border-green-800
        dark:text-green-300
        dark:hover:bg-green-950/30
        dark:focus:ring-offset-neutral-900">
                                <i
                                    data-lucide="user-check"
                                    class="h-4 w-4"
                                    aria-hidden="true"></i>

                                Activate
                            </button>

                        </form>

                        @elseif ($isProtected)

                        <div
                            class="
                                    inline-flex min-h-11 w-full
                                    items-center justify-center gap-2
                                    border border-gray-200
                                    bg-gray-50 px-4 py-2
                                    text-sm font-medium
                                    text-gray-400
                                    dark:border-gray-700
                                    dark:bg-neutral-800
                                    dark:text-gray-500
                                ">
                            <i
                                data-lucide="shield"
                                class="h-4 w-4"
                                aria-hidden="true"></i>

                            Protected Account
                        </div>

                        @else

                        <button
                            type="button"
                            data-user-deactivate
                            data-user-url="{{ route('admin.users.status', $user) }}"
                            data-user-name="{{ $user->name }}"
                            class="
                                    inline-flex min-h-11 w-full
                                    items-center justify-center gap-2
                                    border border-red-200
                                    px-4 py-2
                                    text-sm font-semibold
                                    text-red-600
                                    transition
                                    hover:bg-red-50
                                    dark:border-red-900
                                    dark:text-red-400
                                    dark:hover:bg-red-950/30
                                ">
                            <i
                                data-lucide="user-x"
                                class="h-4 w-4"
                                aria-hidden="true"></i>

                            Deactivate Account
                        </button>

                        @endif

                    </div>

                </article>

                @endforeach

        </div>


        {{-- Pagination --}}
        @if ($users->hasPages())
        <div>
            {{ $users->links() }}
        </div>
        @endif

        @endif

    </div>

    {{-- Activation Confirmation Modal --}}
    <div
        id="user-activate-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center p-4"
        aria-hidden="true"
        role="dialog"
        aria-modal="true"
        aria-labelledby="user-activate-title">

        <div
            id="user-activate-overlay"
            class="absolute inset-0 bg-black/50"></div>

        <div
            class="
            relative z-10 w-full max-w-md
            bg-white p-6 shadow-xl
            dark:bg-neutral-900
        ">

            <div
                class="
                flex h-11 w-11
                items-center justify-center
                bg-green-50
                dark:bg-green-950/40
            ">
                <i
                    data-lucide="user-check"
                    class="h-5 w-5 text-green-600 dark:text-green-400"
                    aria-hidden="true"></i>
            </div>

            <h2
                id="user-activate-title"
                class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">
                Activate Account?
            </h2>

            <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">
                You are about to activate
                <span
                    id="user-activate-name"
                    class="font-medium text-gray-700 dark:text-gray-200"></span>.
                This user will regain access to protected Rincomm system areas according to their assigned role.
            </p>

            <form
                id="user-activate-form"
                method="POST"
                data-lock-submit
                class="
                mt-6 flex flex-col-reverse gap-2
                sm:flex-row sm:justify-end
            ">
                @csrf
                @method('PATCH')

                <input
                    type="hidden"
                    name="account_status"
                    value="active">

                <button
                    id="user-activate-cancel"
                    type="button"
                    class="
                    min-h-11 border border-gray-200
                    px-4 py-2.5
                    text-sm font-medium
                    text-gray-700
                    transition hover:bg-gray-50
                    dark:border-gray-700
                    dark:text-gray-300
                    dark:hover:bg-gray-800
                ">
                    Cancel
                </button>

                <button
                    type="submit"
                    data-loading-text="Activating..."
                    class="
                    inline-flex min-h-11
                    items-center justify-center gap-2
                    bg-green-600 px-4 py-2.5
                    text-sm font-medium text-white
                    transition hover:bg-green-700
                ">
                    <i
                        data-lucide="user-check"
                        class="h-4 w-4"
                        aria-hidden="true"></i>

                    Activate Account
                </button>

            </form>

        </div>
    </div>


    {{-- Deactivation Confirmation Modal --}}
    <div
        id="user-deactivate-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center p-4"
        aria-hidden="true"
        role="dialog"
        aria-modal="true"
        aria-labelledby="user-deactivate-title">

        <div
            id="user-deactivate-overlay"
            class="absolute inset-0 bg-black/50"></div>


        <div
            class="
                relative z-10 w-full max-w-md
                bg-white p-6 shadow-xl
                dark:bg-neutral-900
            ">

            <div
                class="
                    flex h-11 w-11
                    items-center justify-center
                    bg-red-50
                    dark:bg-red-950/40
                ">
                <i
                    data-lucide="user-x"
                    class="h-5 w-5 text-red-600 dark:text-red-400"
                    aria-hidden="true"></i>
            </div>


            <h2
                id="user-deactivate-title"
                class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">
                Deactivate Account?
            </h2>


            <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">
                You are about to deactivate
                <span
                    id="user-deactivate-name"
                    class="font-medium text-gray-700 dark:text-gray-200"></span>.
                This user will no longer be able to access protected Rincomm system areas until the account is activated again.
            </p>


            <form
                id="user-deactivate-form"
                method="POST"
                data-lock-submit
                class="
                    mt-6 flex flex-col-reverse gap-2
                    sm:flex-row sm:justify-end
                ">
                @csrf
                @method('PATCH')

                <input
                    type="hidden"
                    name="account_status"
                    value="inactive">

                <button
                    id="user-deactivate-cancel"
                    type="button"
                    class="
                        min-h-11 border border-gray-200
                        px-4 py-2.5
                        text-sm font-medium
                        text-gray-700
                        transition hover:bg-gray-50
                        dark:border-gray-700
                        dark:text-gray-300
                        dark:hover:bg-gray-800
                    ">
                    Cancel
                </button>

                <button
                    type="submit"
                    data-loading-text="Deactivating..."
                    class="
                        inline-flex min-h-11
                        items-center justify-center gap-2
                        bg-red-600 px-4 py-2.5
                        text-sm font-medium text-white
                        transition hover:bg-red-700
                    ">
                    <i
                        data-lucide="user-x"
                        class="h-4 w-4"
                        aria-hidden="true"></i>

                    Deactivate Account
                </button>

            </form>

        </div>

    </div>

</div>
@endsection