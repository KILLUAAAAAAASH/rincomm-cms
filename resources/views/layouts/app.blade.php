<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Rincomm CMS')</title>

    <script>
        (() => {
            const savedTheme = localStorage.getItem('rincomm-theme');

            const theme = savedTheme ??
                (window.matchMedia('(prefers-color-scheme: dark)').matches ?
                    'dark' :
                    'light');

            document.documentElement.classList.toggle(
                'dark',
                theme === 'dark'
            );
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /*
         * Secondary authenticated navigation.
         *
         * Mobile header:  3.5rem / 56px
         * sm+ header:     4rem / 64px
         *
         * This element lives inside the main workspace, so it never
         * needs a manual desktop sidebar offset.
         */
        .rincomm-secondary-nav {
            position: sticky;
            top: 3.5rem;
            z-index: 20;
        }

        @media (min-width: 640px) {
            .rincomm-secondary-nav {
                top: 4rem;
            }
        }
    </style>
</head>


<body class="bg-neutral-100 text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100">

    <!-- Global Page Loader -->
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


    <!-- Mobile Sidebar Overlay -->
    <div
        id="sidebar-overlay"
        class="fixed inset-0 z-40 hidden bg-slate-950/50 lg:hidden">
    </div>


    <div class="flex min-h-screen">

        <!-- Sidebar -->
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
            ">

            <!-- Sidebar Header -->
            <div
                class="
                    flex items-start justify-between
                    border-b border-neutral-200
                    px-5 py-5
                    dark:border-neutral-800
                ">

                <div>

                    <h1 class="text-xl font-bold">
                        Rincomm CMS
                    </h1>

                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        Internet Service Management
                    </p>

                </div>


                <!-- Mobile Close Button -->
                <button
                    id="sidebar-close"
                    type="button"
                    aria-controls="sidebar"
                    aria-label="Close navigation"
                    class="
                        rounded-xl p-2
                        text-neutral-700
                        hover:bg-[#008080]/10
                        hover:text-[#008080]
                        dark:text-neutral-300
                        dark:hover:bg-[#008080]/15
                        dark:hover:text-[#5EEAD4]
                        lg:hidden
                    ">

                    <i
                        data-lucide="x"
                        class="h-5 w-5">
                    </i>

                </button>

            </div>


            <!-- Navigation -->
            <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto p-4">

                {{-- Dashboard --}}
                <a
                    href="{{ route('dashboard') }}"
                    class="
                        flex items-center gap-3
                        rounded-xl px-4 py-3
                        text-sm transition

                        {{ request()->routeIs('dashboard')
                            ? 'bg-[#008080] font-medium text-white shadow-sm'
                            : 'text-neutral-700 hover:bg-[#008080]/10 hover:text-[#008080]
                               dark:text-neutral-300 dark:hover:bg-[#008080]/15 dark:hover:text-[#5EEAD4]'
                        }}
                    ">

                    <i
                        data-lucide="layout-dashboard"
                        class="h-5 w-5">
                    </i>

                    <span>
                        Dashboard
                    </span>

                </a>


                {{-- User Management --}}
                <a
                    href="{{ route('admin.users.index') }}"
                    class="
                        flex items-center gap-3
                        rounded-xl px-4 py-3
                        text-sm transition

                        {{ request()->routeIs('admin.users.*')
                            ? 'bg-[#008080] font-medium text-white shadow-sm'
                            : 'text-neutral-700 hover:bg-[#008080]/10 hover:text-[#008080]
                               dark:text-neutral-300 dark:hover:bg-[#008080]/15 dark:hover:text-[#5EEAD4]'
                        }}
                    ">

                    <i
                        data-lucide="users-round"
                        class="h-5 w-5">
                    </i>

                    <span>
                        User Management
                    </span>

                </a>


                {{-- Hero Slides --}}
                <a
                    href="{{ route('admin.hero-slides.index') }}"
                    class="
                        flex items-center gap-3
                        rounded-xl px-4 py-3
                        text-sm transition

                        {{ request()->routeIs('admin.hero-slides.*')
                            ? 'bg-[#008080] font-medium text-white shadow-sm'
                            : 'text-neutral-700 hover:bg-[#008080]/10 hover:text-[#008080]
                               dark:text-neutral-300 dark:hover:bg-[#008080]/15 dark:hover:text-[#5EEAD4]'
                        }}
                    ">

                    <i
                        data-lucide="images"
                        class="h-5 w-5">
                    </i>

                    <span>
                        Hero Slides
                    </span>

                </a>


                {{-- Subscribers --}}
                <details
                    class="group"
                    {{ request()->routeIs('admin.subscribers.*', 'admin.applications.*') ? 'open' : '' }}>

                    <summary
                        class="
                            flex cursor-pointer list-none items-center gap-3
                            rounded-xl px-4 py-3
                            text-sm transition

                            {{ request()->routeIs('admin.subscribers.*', 'admin.applications.*')
                                ? 'font-medium text-[#008080] dark:text-[#5EEAD4]'
                                : 'text-neutral-700 hover:bg-[#008080]/10 hover:text-[#008080]
                                   dark:text-neutral-300 dark:hover:bg-[#008080]/15
                                   dark:hover:text-[#5EEAD4]'
                            }}
                        ">

                        <i
                            data-lucide="users"
                            class="h-5 w-5 shrink-0"
                            aria-hidden="true">
                        </i>

                        <span class="flex-1">
                            Subscribers
                        </span>

                        <span
                            class="text-xl transition-transform group-open:rotate-90"
                            aria-hidden="true">
                            &rsaquo;
                        </span>

                    </summary>


                    <div class="mt-1 space-y-1 pl-4">

                        {{-- All Subscribers --}}
                        <a
                            href="{{ route('admin.subscribers.index') }}"
                            class="
                                flex items-center gap-2.5
                                rounded-xl px-4 py-2.5
                                text-sm transition

                                {{ request()->routeIs('admin.subscribers.*')
                                    ? 'bg-[#008080] font-medium text-white shadow-sm'
                                    : 'text-neutral-600 hover:bg-[#008080]/10 hover:text-[#008080]
                                       dark:text-neutral-400 dark:hover:bg-[#008080]/15
                                       dark:hover:text-[#5EEAD4]'
                                }}
                            ">

                            <span
                                class="shrink-0 text-base leading-none"
                                aria-hidden="true">
                                &rarr;
                            </span>

                            <i
                                data-lucide="contact"
                                class="h-4 w-4 shrink-0"
                                aria-hidden="true">
                            </i>

                            <span>
                                All Subscribers
                            </span>

                        </a>


                        {{-- Applications --}}
                        <a
                            href="{{ route('admin.applications.index') }}"
                            class="
                                flex items-center gap-2.5
                                rounded-xl px-4 py-2.5
                                text-sm transition

                                {{ request()->routeIs('admin.applications.*')
                                    ? 'bg-[#008080] font-medium text-white shadow-sm'
                                    : 'text-neutral-600 hover:bg-[#008080]/10 hover:text-[#008080]
                                       dark:text-neutral-400 dark:hover:bg-[#008080]/15
                                       dark:hover:text-[#5EEAD4]'
                                }}
                            ">

                            <span
                                class="shrink-0 text-base leading-none"
                                aria-hidden="true">
                                &rarr;
                            </span>

                            <i
                                data-lucide="clipboard-list"
                                class="h-4 w-4 shrink-0"
                                aria-hidden="true">
                            </i>

                            <span>
                                Applications
                            </span>

                        </a>

                    </div>

                </details>


                {{-- Service Plans --}}
                <a
                    href="#"
                    class="
                        flex items-center gap-3
                        rounded-xl px-4 py-3
                        text-sm text-neutral-700
                        transition
                        hover:bg-[#008080]/10
                        hover:text-[#008080]
                        dark:text-neutral-300
                        dark:hover:bg-[#008080]/15
                        dark:hover:text-[#5EEAD4]
                    ">

                    <i
                        data-lucide="wifi"
                        class="h-5 w-5">
                    </i>

                    <span>
                        Service Plans
                    </span>

                </a>


                {{-- Subscriptions --}}
                <a
                    href="#"
                    class="
                        flex items-center gap-3
                        rounded-xl px-4 py-3
                        text-sm text-neutral-700
                        transition
                        hover:bg-[#008080]/10
                        hover:text-[#008080]
                        dark:text-neutral-300
                        dark:hover:bg-[#008080]/15
                        dark:hover:text-[#5EEAD4]
                    ">

                    <i
                        data-lucide="radio-tower"
                        class="h-5 w-5">
                    </i>

                    <span>
                        Subscriptions
                    </span>

                </a>


                {{-- Tickets --}}
                <a
                    href="#"
                    class="
                        flex items-center gap-3
                        rounded-xl px-4 py-3
                        text-sm text-neutral-700
                        transition
                        hover:bg-[#008080]/10
                        hover:text-[#008080]
                        dark:text-neutral-300
                        dark:hover:bg-[#008080]/15
                        dark:hover:text-[#5EEAD4]
                    ">

                    <i
                        data-lucide="ticket"
                        class="h-5 w-5">
                    </i>

                    <span>
                        Tickets
                    </span>

                </a>


                {{-- Job Orders --}}
                <a
                    href="#"
                    class="
                        flex items-center gap-3
                        rounded-xl px-4 py-3
                        text-sm text-neutral-700
                        transition
                        hover:bg-[#008080]/10
                        hover:text-[#008080]
                        dark:text-neutral-300
                        dark:hover:bg-[#008080]/15
                        dark:hover:text-[#5EEAD4]
                    ">

                    <i
                        data-lucide="clipboard-list"
                        class="h-5 w-5">
                    </i>

                    <span>
                        Job Orders
                    </span>

                </a>


                {{-- Billing --}}
                <a
                    href="#"
                    class="
                        flex items-center gap-3
                        rounded-xl px-4 py-3
                        text-sm text-neutral-700
                        transition
                        hover:bg-[#008080]/10
                        hover:text-[#008080]
                        dark:text-neutral-300
                        dark:hover:bg-[#008080]/15
                        dark:hover:text-[#5EEAD4]
                    ">

                    <i
                        data-lucide="file-text"
                        class="h-5 w-5">
                    </i>

                    <span>
                        Billing
                    </span>

                </a>


                {{-- Payments --}}
                <a
                    href="#"
                    class="
                        flex items-center gap-3
                        rounded-xl px-4 py-3
                        text-sm text-neutral-700
                        transition
                        hover:bg-[#008080]/10
                        hover:text-[#008080]
                        dark:text-neutral-300
                        dark:hover:bg-[#008080]/15
                        dark:hover:text-[#5EEAD4]
                    ">

                    <i
                        data-lucide="credit-card"
                        class="h-5 w-5">
                    </i>

                    <span>
                        Payments
                    </span>

                </a>

            </nav>


            <!-- Sidebar Logout -->
            <div
                class="
                    shrink-0
                    border-t border-neutral-200
                    bg-white p-4
                    dark:border-neutral-800
                    dark:bg-neutral-950
                ">

                <form
                    method="POST"
                    action="{{ route('logout') }}"
                    data-lock-submit>

                    @csrf

                    <button
                        type="submit"
                        class="
                            inline-flex min-h-11 w-full
                            items-center justify-start gap-3
                            rounded-xl
                            border border-red-200
                            px-4 py-3
                            text-sm font-semibold
                            text-red-600
                            transition
                            hover:border-red-300
                            hover:bg-red-50
                            focus:outline-none
                            focus:ring-2
                            focus:ring-red-500/30
                            dark:border-red-900
                            dark:text-red-400
                            dark:hover:border-red-800
                            dark:hover:bg-red-950/40
                        ">

                        <i
                            data-lucide="log-out"
                            class="h-5 w-5 shrink-0"
                            aria-hidden="true">
                        </i>

                        <span>
                            Logout
                        </span>

                    </button>

                </form>

            </div>

        </aside>


        <!-- Main Workspace -->
        <div class="flex min-w-0 flex-1 flex-col">

            <!-- Main Header -->
            <header
                class="
                    sticky top-0 z-30
                    flex h-14 w-full shrink-0
                    items-center justify-between
                    border-b border-neutral-200
                    bg-white px-2

                    dark:border-neutral-800
                    dark:bg-neutral-900

                    sm:h-16
                    sm:px-4
                ">

                <div class="flex min-w-0 flex-1 items-center gap-1.5 sm:gap-3">

                    <!-- Hamburger -->
                    <button
                        id="sidebar-open"
                        type="button"
                        aria-controls="sidebar"
                        aria-expanded="false"
                        aria-label="Open navigation"
                        class="
                            inline-flex h-8 w-8 shrink-0
                            items-center justify-center
                            border border-slate-200
                            text-slate-700
                            hover:bg-slate-100
                            focus:outline-none
                            focus:ring-2
                            focus:ring-slate-400
                            lg:hidden
                        ">

                        <i
                            data-lucide="menu"
                            class="h-4 w-4">
                        </i>

                    </button>


                    <h2
                        class="
                            min-w-0 truncate
                            text-xs font-semibold
                            text-neutral-800
                            dark:text-neutral-100
                            sm:text-base
                        ">

                        @yield('page-title', 'Dashboard')

                    </h2>

                </div>


                <div class="flex shrink-0 items-center gap-1 sm:gap-3">

                    {{-- Theme Toggle --}}
                    <button
                        data-theme-toggle
                        type="button"
                        aria-label="Toggle color theme"
                        title="Toggle color theme"
                        class="
                            inline-flex h-8 w-8
                            items-center justify-center
                            rounded-xl
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
                            class="hidden h-3.5 w-3.5">
                        </i>

                        <i
                            data-theme-moon-icon
                            data-lucide="moon"
                            class="h-3.5 w-3.5">
                        </i>

                    </button>


                    {{-- Authenticated User Identity --}}
                    <div class="hidden min-w-0 text-right md:block">

                        <p
                            class="
                                max-w-40 truncate
                                text-sm font-medium
                                text-neutral-700
                                dark:text-neutral-200
                            "
                            title="{{ auth()->user()->name }}">

                            {{ auth()->user()->name }}

                        </p>

                        <p class="text-xs text-neutral-500 dark:text-neutral-400">
                            {{ \Illuminate\Support\Str::headline(auth()->user()->role) }}
                        </p>

                    </div>

                </div>

            </header>


            {{-- Optional Secondary Navigation --}}
            @hasSection('secondary-navigation')

            <div
                class="
            rincomm-secondary-nav
            border-b border-neutral-200
            bg-neutral-100
            shadow-sm
            dark:border-neutral-800
            dark:bg-neutral-950
        ">

                <div class="px-4 sm:px-6">
                    @yield('secondary-navigation')
                </div>

            </div>

            @endif


            <!-- Page Content -->
            <main class="flex-1 p-4 sm:p-6">
                @yield('content')
            </main>

        </div>

    </div>

</body>

</html>