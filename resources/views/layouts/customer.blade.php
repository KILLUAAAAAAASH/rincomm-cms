<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Rincomm Customer Portal')</title>

    <script>
        (() => {
            const savedTheme = localStorage.getItem('rincomm-theme');

            const theme = savedTheme ??
                (
                    window.matchMedia('(prefers-color-scheme: dark)').matches
                        ? 'dark'
                        : 'light'
                );

            document.documentElement.classList.toggle(
                'dark',
                theme === 'dark'
            );
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-neutral-100 text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100">

    @php
        $portalUser = auth()->user();
        $portalCustomer = $portalUser->customer;

        $nameParts = collect(
            preg_split('/\s+/', trim($portalUser->name))
        )->filter();

        $portalInitials = $nameParts
            ->take(2)
            ->map(
                fn ($part) => \Illuminate\Support\Str::upper(
                    \Illuminate\Support\Str::substr($part, 0, 1)
                )
            )
            ->implode('');

        $portalInitials = $portalInitials !== ''
            ? $portalInitials
            : 'CU';
    @endphp


    {{-- Page loader --}}
    <div
        id="page-loader"
        class="pointer-events-none fixed inset-x-0 top-0 z-[100] hidden h-1"
        aria-hidden="true">

        <div
            id="page-loader-bar"
            class="h-full bg-[#008080] transition-[width] duration-300 ease-out"
            style="width: 0%">
        </div>

    </div>


    {{-- Mobile sidebar overlay --}}
    <div
        id="sidebar-overlay"
        class="fixed inset-0 z-40 hidden bg-slate-950/50 lg:hidden"
        aria-hidden="true">
    </div>


    <div class="flex min-h-screen">

        {{-- Customer sidebar --}}
        <aside
            id="sidebar"
            class="
                fixed inset-y-0 left-0 z-50
                flex h-screen w-72 max-w-[85vw] flex-col
                -translate-x-full
                border-r border-neutral-200
                bg-white text-neutral-900
                transition-transform duration-200 ease-in-out

                dark:border-neutral-800
                dark:bg-neutral-950
                dark:text-white

                lg:sticky
                lg:top-0
                lg:z-auto
                lg:w-64
                lg:max-w-none
                lg:shrink-0
                lg:translate-x-0
            "
            aria-label="Customer portal navigation">


            {{-- Sidebar header --}}
            <div
                class="
                    flex items-start justify-between
                    border-b border-neutral-200
                    px-5 py-4
                    dark:border-neutral-800
                ">

                <a
                    href="{{ route('customer.dashboard') }}"
                    class="min-w-0">

                    <h1 class="text-lg font-bold">
                        Rincomm
                    </h1>

                    <p
                        class="
                            mt-0.5 text-xs
                            text-neutral-500
                            dark:text-neutral-400
                        ">
                        Subscriber Portal
                    </p>

                </a>


                <button
                    id="sidebar-close"
                    type="button"
                    aria-controls="sidebar"
                    aria-label="Close navigation"
                    class="
                        p-2
                        text-neutral-700
                        transition
                        hover:bg-[#008080]/10
                        hover:text-[#008080]
                        dark:text-neutral-300
                        dark:hover:bg-[#008080]/15
                        dark:hover:text-[#5EEAD4]
                        lg:hidden
                    ">

                    <i
                        data-lucide="x"
                        class="h-5 w-5"
                        aria-hidden="true">
                    </i>

                </button>

            </div>


            {{-- Navigation --}}
            <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto p-4">

                <a
                    href="{{ route('customer.dashboard') }}"
                    class="
                        flex items-center gap-3
                        px-4 py-2.5
                        text-sm transition

                        {{ request()->routeIs('customer.dashboard')
                            ? 'bg-[#008080] font-medium text-white shadow-sm'
                            : 'text-neutral-700 hover:bg-[#008080]/10 hover:text-[#008080]
                               dark:text-neutral-300 dark:hover:bg-[#008080]/15
                               dark:hover:text-[#5EEAD4]'
                        }}
                    ">

                    <i
                        data-lucide="layout-dashboard"
                        class="h-5 w-5 shrink-0"
                        aria-hidden="true">
                    </i>

                    <span>
                        Overview
                    </span>

                </a>


                {{-- Customer Support Tickets --}}
                @if (Route::has('customer.tickets.index'))
                    <a
                        href="{{ route('customer.tickets.index') }}"
                        class="
                            flex items-center gap-3
                            px-4 py-2.5 text-sm transition
                            {{ request()->routeIs('customer.tickets.*')
                                ? 'bg-[#008080] font-medium text-white shadow-sm'
                                : 'text-neutral-700 hover:bg-[#008080]/10 hover:text-[#008080]
                                   dark:text-neutral-300 dark:hover:bg-[#008080]/15
                                   dark:hover:text-[#5EEAD4]' }}
                        ">
                        <i data-lucide="ticket"
                           class="h-5 w-5 shrink-0"
                           aria-hidden="true"></i>
                        <span>My Support Tickets</span>
                    </a>
                @endif
                @if ($portalCustomer !== null)

                    <a
                        href="{{ route('customer.plan-changes.index') }}"
                        class="
                            flex items-center gap-3
                            px-4 py-2.5 text-sm transition
                            {{ request()->routeIs('customer.plan-changes.*')
                                ? 'bg-[#008080] font-medium text-white shadow-sm'
                                : 'text-neutral-700 hover:bg-[#008080]/10 hover:text-[#008080]
                                   dark:text-neutral-300 dark:hover:bg-[#008080]/15
                                   dark:hover:text-[#5EEAD4]'
                            }}
                        ">
                        <i data-lucide="refresh-cw" class="h-5 w-5 shrink-0" aria-hidden="true"></i>
                        <span>My Internet Plan</span>
                    </a>

                    <a
                        href="{{ route('customer.billing.index') }}"
                        class="
                            flex items-center gap-3
                            px-4 py-2.5
                            text-sm transition

                            {{ request()->routeIs('customer.billing.*')
                                ? 'bg-[#008080] font-medium text-white shadow-sm'
                                : 'text-neutral-700 hover:bg-[#008080]/10 hover:text-[#008080]
                                   dark:text-neutral-300 dark:hover:bg-[#008080]/15
                                   dark:hover:text-[#5EEAD4]'
                            }}
                        ">

                        <i
                            data-lucide="credit-card"
                            class="h-5 w-5 shrink-0"
                            aria-hidden="true">
                        </i>

                        <span>
                            Billing &amp; Payments
                        </span>

                    </a>

                @else

                    <a
                        href="{{ route('customer.application.create') }}"
                        class="
                            flex items-center gap-3
                            px-4 py-2.5
                            text-sm transition

                            {{ request()->routeIs('customer.application.*')
                                ? 'bg-[#008080] font-medium text-white shadow-sm'
                                : 'text-neutral-700 hover:bg-[#008080]/10 hover:text-[#008080]
                                   dark:text-neutral-300 dark:hover:bg-[#008080]/15
                                   dark:hover:text-[#5EEAD4]'
                            }}
                        ">

                        <i
                            data-lucide="clipboard-list"
                            class="h-5 w-5 shrink-0"
                            aria-hidden="true">
                        </i>

                        <span>
                            Service Application
                        </span>

                    </a>

                @endif

            </nav>


            {{-- Account profile --}}
            <div
                class="
                    shrink-0
                    border-t border-neutral-200
                    bg-white p-4
                    dark:border-neutral-800
                    dark:bg-neutral-950
                ">

                <details class="group relative">

                    <summary
                        class="
                            flex cursor-pointer
                            list-none items-center gap-3
                            px-2 py-2
                            transition
                            hover:bg-[#008080]/10
                            dark:hover:bg-[#008080]/15
                        ">

                        <div
                            class="
                                flex h-9 w-9 shrink-0
                                items-center justify-center
                                rounded-full
                                bg-[#008080]
                                text-xs font-bold
                                text-white
                            "
                            aria-hidden="true">

                            {{ $portalInitials }}

                        </div>


                        <div class="min-w-0 flex-1">

                            <p
                                class="
                                    truncate
                                    text-sm font-semibold
                                    text-neutral-800
                                    dark:text-neutral-100
                                ">
                                {{ $portalUser->name }}
                            </p>

                            <p
                                class="
                                    mt-0.5 truncate
                                    text-[11px]
                                    text-neutral-500
                                    dark:text-neutral-400
                                ">

                                @if ($portalCustomer !== null)
                                    {{ $portalCustomer->customer_code }}
                                @else
                                    Customer Account
                                @endif

                            </p>

                        </div>


                        <i
                            data-lucide="chevron-down"
                            class="
                                h-4 w-4 shrink-0
                                text-neutral-400
                                transition-transform
                                group-open:rotate-180
                            "
                            aria-hidden="true">
                        </i>

                    </summary>


                    <div
                        class="
                            absolute bottom-full left-0 right-0 z-30
                            mb-2
                            overflow-hidden
                            rounded-md
                            border border-neutral-200
                            bg-white
                            py-1
                            shadow-lg
                            dark:border-neutral-700
                            dark:bg-neutral-900
                        ">

                        <div
                            class="
                                border-b border-neutral-200
                                px-3 py-2.5
                                dark:border-neutral-800
                            ">

                            <p
                                class="
                                    truncate text-xs font-semibold
                                    text-neutral-900
                                    dark:text-white
                                ">
                                Account Profile
                            </p>

                            <p
                                class="
                                    mt-0.5 truncate text-[11px]
                                    text-neutral-500
                                    dark:text-neutral-400
                                ">
                                {{ $portalUser->email }}
                            </p>

                        </div>


                        <a
                            href="{{ route('profile.edit') }}"
                            class="
                                flex items-center gap-2.5
                                px-3 py-2.5
                                text-sm
                                text-neutral-700
                                transition
                                hover:bg-[#008080]/10
                                hover:text-[#008080]
                                dark:text-neutral-300
                                dark:hover:bg-[#008080]/15
                                dark:hover:text-[#5EEAD4]
                            ">

                            <i
                                data-lucide="user"
                                class="h-4 w-4"
                                aria-hidden="true">
                            </i>

                            <span>
                                My Profile
                            </span>

                        </a>


                        <form
                            method="POST"
                            action="{{ route('logout') }}"
                            data-lock-submit>

                            @csrf

                            <button
                                type="submit"
                                class="
                                    flex w-full
                                    items-center gap-2.5
                                    px-3 py-2.5
                                    text-left text-sm
                                    text-red-600
                                    transition
                                    hover:bg-red-50
                                    dark:text-red-400
                                    dark:hover:bg-red-950/30
                                ">

                                <i
                                    data-lucide="log-out"
                                    class="h-4 w-4"
                                    aria-hidden="true">
                                </i>

                                <span>
                                    Logout
                                </span>

                            </button>

                        </form>

                    </div>

                </details>

            </div>

        </aside>


        {{-- Workspace --}}
        <div class="flex min-w-0 flex-1 flex-col">

            {{-- Admin-aligned compact header --}}
            <header
                id="app-header"
                class="
                    sticky top-0 z-30
                    flex h-12 w-full shrink-0
                    items-center
                    border-b border-neutral-200
                    bg-white px-2
                    dark:border-neutral-800
                    dark:bg-neutral-900
                    sm:h-14
                    sm:px-4
                ">

                {{-- Left --}}
                <div
                    class="
                        flex min-w-0 flex-1
                        items-center gap-2
                        sm:gap-3
                    ">

                    <button
                        id="sidebar-open"
                        type="button"
                        aria-controls="sidebar"
                        aria-expanded="false"
                        aria-label="Open navigation"
                        class="
                            inline-flex h-8 w-8 shrink-0
                            items-center justify-center
                            border border-neutral-200
                            text-neutral-600
                            transition
                            hover:bg-neutral-100
                            focus:outline-none
                            focus:ring-2
                            focus:ring-[#008080]/20
                            dark:border-neutral-700
                            dark:text-neutral-300
                            dark:hover:bg-neutral-800
                            lg:hidden
                        ">

                        <i
                            data-lucide="menu"
                            class="h-4 w-4"
                            aria-hidden="true">
                        </i>

                    </button>


                    <h2
                        class="
                            min-w-0 shrink-0
                            truncate
                            text-sm font-semibold
                            text-neutral-800
                            dark:text-neutral-100
                            sm:max-w-52
                            lg:max-w-60
                        ">

                        @yield('page-title', 'Overview')

                    </h2>

                </div>


                {{-- Right --}}
                <div class="ml-2 flex shrink-0 items-center gap-1.5 sm:gap-2">

                    @hasSection('header-actions')
                        @yield('header-actions')
                    @endif


                    <button
                        data-theme-toggle
                        type="button"
                        aria-label="Toggle color theme"
                        title="Toggle color theme"
                        class="
                            inline-flex h-8 w-8
                            items-center justify-center
                            border border-neutral-200
                            text-neutral-600
                            transition
                            hover:border-[#008080]
                            hover:bg-[#008080]/10
                            hover:text-[#008080]
                            dark:border-neutral-700
                            dark:text-neutral-300
                            dark:hover:border-[#14B8A6]
                            dark:hover:bg-[#008080]/15
                            dark:hover:text-[#5EEAD4]
                        ">

                        <i
                            data-theme-sun-icon
                            data-lucide="sun"
                            class="hidden h-3.5 w-3.5"
                            aria-hidden="true">
                        </i>

                        <i
                            data-theme-moon-icon
                            data-lucide="moon"
                            class="h-3.5 w-3.5"
                            aria-hidden="true">
                        </i>

                    </button>


                    <div
                        class="
                            hidden min-w-0
                            border-l border-neutral-200
                            pl-2 text-right
                            dark:border-neutral-700
                            lg:block
                        ">

                        <p
                            class="
                                max-w-36 truncate
                                text-xs font-semibold
                                leading-4
                                text-neutral-700
                                dark:text-neutral-200
                            "
                            title="{{ $portalUser->name }}">

                            {{ $portalUser->name }}

                        </p>

                        <p
                            class="
                                text-[10px]
                                leading-3
                                text-neutral-500
                                dark:text-neutral-400
                            ">

                            @if ($portalCustomer !== null)
                                {{ $portalCustomer->customer_code }}
                            @else
                                Customer
                            @endif

                        </p>

                    </div>

                </div>

            </header>


            {{-- Page content --}}
            <main class="flex-1 p-3 sm:p-4 lg:p-5">

                @yield('content')

            </main>

        </div>

    </div>


    @stack('scripts')

</body>

</html>
