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
                (
                    window.matchMedia('(prefers-color-scheme: dark)').matches ?
                    'dark' :
                    'light'
                );

            document.documentElement.classList.toggle(
                'dark',
                theme === 'dark'
            );
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .rincomm-secondary-nav {
            position: sticky;
            top: 3rem;
            z-index: 20;
        }

        .rincomm-feature-search-desktop {
            display: none;
        }

        @media (min-width: 640px) {
            .rincomm-secondary-nav {
                top: 3.5rem;
            }
        }

        @media print {
            #sidebar,
            #sidebar-overlay,
            #app-header,
            #page-loader,
            #feature-search-modal,
            .rincomm-soa-controls {
                display: none !important;
            }

            main {
                padding: 0 !important;
            }

            body {
                background: #ffffff !important;
                color: #000000 !important;
            }
        }

        @media (min-width: 768px) {
            .rincomm-feature-search-desktop {
                display: flex;
                width: min(24rem, 34vw);
                flex: 0 1 24rem;
            }
        }
    </style>
</head>

<body class="bg-neutral-100 text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100">

    @php
    $authenticatedUser = auth()->user();

    $isAdminOrStaff = in_array(
    $authenticatedUser->role,
    ['admin', 'staff'],
    true
    );

    $isTechnician = $authenticatedUser->role === 'technician';
    $isCustomer = $authenticatedUser->role === 'customer';

    $dashboardRouteName = match (true) {
    $isTechnician => 'technician.dashboard',
    $isCustomer => 'customer.dashboard',
    default => 'dashboard',
    };

    $featureSearchItems = [
    [
    'label' => 'Dashboard',
    'description' => 'System overview and summary.',
    'module' => 'Dashboard',
    'route' => $dashboardRouteName,
    'keywords' => 'dashboard home overview summary statistics',
    'visible' => true,
    ],
    ];

    if ($isAdminOrStaff) {
    $featureSearchItems = array_merge(
    $featureSearchItems,
    [
    [
    'label' => 'All Subscribers',
    'description' => 'Browse and select a subscriber account.',
    'module' => 'Customer Management',
    'route' => 'admin.subscribers.index',
    'keywords' => 'subscriber subscribers customer customers customer management',
    'visible' => Route::has('admin.subscribers.index'),
    ],
    [
    'label' => 'Customer Profile Management',
    'description' => 'Select a subscriber to view or manage profile information.',
    'module' => 'Customer Management',
    'route' => 'admin.subscribers.index',
    'parameters' => ['feature' => 'customer-information'],
    'keywords' => 'customer profile subscriber profile personal information contact address profile management subscriber details customer details view subscriber view customer edit subscriber edit customer edit profile update profile',
    'visible' => Route::has('admin.subscribers.index'),
    ],
    [
    'label' => 'Customer Account Status',
    'description' => 'Select a subscriber to manage service account status.',
    'module' => 'Customer Management',
    'route' => 'admin.subscribers.index',
    'parameters' => ['feature' => 'customer-account-status'],
    'keywords' => 'account status customer status subscriber status active inactive suspended disconnected status management manage status change status status history',
    'visible' => Route::has('admin.subscribers.index'),
    ],
    [
    'label' => 'Customer Documents',
    'description' => 'Select a subscriber to manage attached customer documents.',
    'module' => 'Customer Management',
    'route' => 'admin.subscribers.index',
    'parameters' => ['feature' => 'customer-documents'],
    'keywords' => 'documents document attachments attachment upload customer document files upload document view document open document download document delete document manage documents',
    'visible' => Route::has('admin.subscribers.index'),
    ],
    [
    'label' => 'Relocation & Transfer',
    'description' => 'Select a subscriber to record or process relocation requests.',
    'module' => 'Customer Management',
    'route' => 'admin.subscribers.index',
    'parameters' => ['feature' => 'relocation-transfer'],
    'keywords' => 'relocation transfer move address installation address relocation request transfer processing record relocation approve relocation reject relocation complete relocation relocation history',
    'visible' => Route::has('admin.subscribers.index'),
    ],
    [
    'label' => 'Plan Change Requests',
    'description' => 'Select a subscriber to record or process plan changes.',
    'module' => 'Customer Management',
    'route' => 'admin.subscribers.index',
    'parameters' => ['feature' => 'plan-change'],
    'keywords' => 'plan change upgrade downgrade account upgrade account downgrade plan change request record plan change approve plan change reject plan change plan change history',
    'visible' => Route::has('admin.subscribers.index'),
    ],
    [
    'label' => 'Applications',
    'description' => 'Review submitted internet service applications.',
    'module' => 'Customer Management',
    'route' => 'admin.applications.index',
    'keywords' => implode(' ', [
    'application',
    'applications',
    'applicant',
    'applicants',
    'service application',
    'new customer',
    'approval',
    'reject application',
    'application details',
    'view application',
    'review application',
    'approve application',
    ]),
    'visible' => Route::has('admin.applications.index'),
    ],
    [
    'label' => 'Internet Packages',
    'description' => 'Manage internet packages, speed, fees, duration, and availability.',
    'module' => 'Internet Packages',
    'route' => 'admin.service-plans.index',
    'keywords' => implode(' ', [
    'internet package',
    'internet packages',
    'service plan',
    'service plans',
    'plan',
    'plans',
    'package',
    'packages',
    'internet speed',
    'monthly fee',
    'duration',
    'custom plan',
    'standard plan',
    'activate package',
    'deactivate package',
    'create package',
    'edit package',
    ]),
    'visible' => Route::has('admin.service-plans.index'),
    ],
    [
    'label' => 'User Management',
    'description' => 'Manage system users and account access.',
    'module' => 'System Administration',
    'route' => 'admin.users.index',
    'keywords' => implode(' ', [
    'user',
    'users',
    'user management',
    'account',
    'accounts',
    'admin',
    'staff',
    'technician',
    'customer account',
    'access',
    'activate user',
    'deactivate user',
    'account access',
    'manage users',
    ]),
    'visible' => Route::has('admin.users.index'),
    ],
    [
    'label' => 'Activity Logs',
    'description' => 'Review recorded system and user activities.',
    'module' => 'System Administration',
    'route' => 'admin.activity-logs.index',
    'keywords' => implode(' ', [
    'activity',
    'activities',
    'logs',
    'activity logs',
    'audit',
    'audit trail',
    'history',
    'security',
    'system activity',
    'user activity',
    'view logs',
    'review logs',
    ]),
    'visible' => $authenticatedUser->role === 'admin' && Route::has('admin.activity-logs.index'),
    ],
    [
    'label' => 'Hero Slides',
    'description' => 'Manage landing-page carousel content.',
    'module' => 'System Administration',
    'route' => 'admin.hero-slides.index',
    'keywords' => implode(' ', [
    'hero',
    'hero slides',
    'slides',
    'carousel',
    'landing page',
    'homepage',
    'banner',
    'create hero slide',
    'add hero slide',
    'edit hero slide',
    'manage hero slides',
    ]),
    'visible' => Route::has('admin.hero-slides.index'),
    ],
    ]
    );
    }

    if ($isTechnician && Route::has('technician.job-orders.index')) {
    $featureSearchItems[] = [
    'label' => 'Job Orders',
    'description' => 'View and process field work assigned to you.',
    'module' => 'Field Operations',
    'route' => 'technician.job-orders.index',
    'keywords' => 'job order job orders assigned work field work installation site survey repair line maintenance physical disconnection proof of work completion report',
    'visible' => true,
    ];
    }

    if ($isCustomer && Route::has('customer.application.create')) {
    $featureSearchItems[] = [
    'label' => 'Service Application',
    'description' => 'Open your internet service application.',
    'module' => 'Services',
    'route' => 'customer.application.create',
    'keywords' => 'application service application internet apply',
    'visible' => true,
    ];
    }

    $featureSearchItems = array_values(
    array_filter(
    $featureSearchItems,
    fn ($item) => $item['visible']
    )
    );

    $currentModule = match (true) {
    $isAdminOrStaff &&
    request()->routeIs(
    'admin.subscribers.*',
    'admin.applications.*'
    ) => 'Customer Management',

    $isAdminOrStaff &&
    request()->routeIs(
    'admin.service-plans.*'
    ) => 'Internet Packages',

    $isAdminOrStaff &&
    request()->routeIs(
    'admin.invoices.*'
    ) => 'Billing & Invoicing',
    $isAdminOrStaff &&
    request()->routeIs(
    'admin.users.*',
    'admin.activity-logs.*',
    'admin.hero-slides.*'
    ) => 'System Administration',

    $isTechnician &&
    request()->routeIs(
    'technician.job-orders.*'
    ) => 'Field Operations',

    request()->routeIs(
    'dashboard',
    'technician.dashboard',
    'customer.dashboard'
    ) => 'Dashboard',

    default => null,
    };

    $contextualNavigation = [];

    if ($currentModule === 'Customer Management') {
    $contextualNavigation = [
    [
    'label' => 'All Subscribers',
    'route' => 'admin.subscribers.index',
    'active' => request()->routeIs('admin.subscribers.*'),
    ],
    [
    'label' => 'Applications',
    'route' => 'admin.applications.index',
    'active' => request()->routeIs('admin.applications.*'),
    ],
    ];
    }

    if ($currentModule === 'System Administration') {
    $contextualNavigation = [
    [
    'label' => 'User Management',
    'route' => 'admin.users.index',
    'active' => request()->routeIs('admin.users.*'),
    ],
    ];

    if ($authenticatedUser->role === 'admin') {
    $contextualNavigation[] = [
    'label' => 'Activity Logs',
    'route' => 'admin.activity-logs.index',
    'active' => request()->routeIs('admin.activity-logs.*'),
    ];
    }

    $contextualNavigation[] = [
    'label' => 'Hero Slides',
    'route' => 'admin.hero-slides.index',
    'active' => request()->routeIs('admin.hero-slides.*'),
    ];
    }
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
        class="fixed inset-0 z-40 hidden bg-slate-950/50 lg:hidden">
    </div>


    <div class="flex min-h-screen">

        {{-- Sidebar --}}
        <aside
            id="sidebar"
            class="
                fixed inset-y-0 left-0 z-50 print:hidden
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

            {{-- Sidebar header --}}
            <div
                class="
                    flex items-start justify-between
                    border-b border-neutral-200
                    px-5 py-4
                    dark:border-neutral-800
                ">

                <div class="min-w-0">

                    <h1 class="text-lg font-bold">
                        Rincomm CMS
                    </h1>

                    <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                        Internet Service Management
                    </p>

                </div>


                <button
                    id="sidebar-close"
                    type="button"
                    aria-controls="sidebar"
                    aria-label="Close navigation"
                    class="
                         p-2
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


            {{-- Main module navigation --}}
            <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto p-4">



                {{-- Dashboard --}}
                <a
                    href="{{ route($dashboardRouteName) }}"
                    class="
                        flex items-center gap-3
                         px-4 py-2.5
                        text-sm transition

                        {{ $currentModule === 'Dashboard'
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
                        Dashboard
                    </span>

                </a>


                @if ($isTechnician && Route::has('technician.job-orders.index'))

                {{-- Technician Job Orders --}}
                <a
                    href="{{ route('technician.job-orders.index') }}"
                    class="
                        flex items-center gap-3
                         px-4 py-2.5
                        text-sm transition

                        {{ $currentModule === 'Field Operations'
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
                        Job Orders
                    </span>

                </a>

                @endif


                @if ($isAdminOrStaff)

                {{-- Subscribers --}}
                <a
                    href="{{ route('admin.subscribers.index') }}"
                    class="
                            flex items-center gap-3
                             px-4 py-2.5
                            text-sm transition

                            {{ $currentModule === 'Customer Management'
                                ? 'bg-[#008080] font-medium text-white shadow-sm'
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

                    <span>
                        Customer Management
                    </span>

                </a>


                {{-- Internet Packages --}}
                @if (Route::has('admin.service-plans.index'))

                <a
                    href="{{ route('admin.service-plans.index') }}"
                    class="
                        flex items-center gap-3
                         px-4 py-2.5
                        text-sm transition

                        {{ $currentModule === 'Internet Packages'
                            ? 'bg-[#008080] font-medium text-white shadow-sm'
                            : 'text-neutral-700 hover:bg-[#008080]/10 hover:text-[#008080]
                               dark:text-neutral-300 dark:hover:bg-[#008080]/15
                               dark:hover:text-[#5EEAD4]'
                        }}
                    ">

                    <i
                        data-lucide="router"
                        class="h-5 w-5 shrink-0"
                        aria-hidden="true">
                    </i>

                    <span>
                        Internet Packages
                    </span>

                </a>

                @endif

                {{-- Billing & Invoicing --}}
                @if (Route::has('admin.invoices.index'))

                <a
                    href="{{ route('admin.invoices.index') }}"
                    class="
                        flex items-center gap-3
                         px-4 py-2.5
                        text-sm transition

                        {{ $currentModule === 'Billing & Invoicing'
                            ? 'bg-[#008080] font-medium text-white shadow-sm'
                            : 'text-neutral-700 hover:bg-[#008080]/10 hover:text-[#008080]
                               dark:text-neutral-300 dark:hover:bg-[#008080]/15
                               dark:hover:text-[#5EEAD4]'
                        }}
                    ">

                    <i
                        data-lucide="receipt-text"
                        class="h-5 w-5 shrink-0"
                        aria-hidden="true">
                    </i>

                    <span>
                        Billing & Invoicing
                    </span>

                </a>

                @endif

                {{-- System --}}
                <a
                    href="{{ route('admin.users.index') }}"
                    class="
                            flex items-center gap-3
                             px-4 py-2.5
                            text-sm transition

                            {{ $currentModule === 'System Administration'
                                ? 'bg-[#008080] font-medium text-white shadow-sm'
                                : 'text-neutral-700 hover:bg-[#008080]/10 hover:text-[#008080]
                                   dark:text-neutral-300 dark:hover:bg-[#008080]/15
                                   dark:hover:text-[#5EEAD4]'
                            }}
                        ">

                    <i
                        data-lucide="settings"
                        class="h-5 w-5 shrink-0"
                        aria-hidden="true">
                    </i>

                    <span>
                        System Administration
                    </span>

                </a>

                @endif


                @if ($isCustomer && Route::has('customer.application.create'))

                <a
                    href="{{ route('customer.application.create') }}"
                    class="
                            flex items-center gap-3
                             px-4 py-2.5
                            text-sm transition
                            text-neutral-700
                            hover:bg-[#008080]/10
                            hover:text-[#008080]
                            dark:text-neutral-300
                            dark:hover:bg-[#008080]/15
                            dark:hover:text-[#5EEAD4]
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


            {{-- Logout --}}
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
                            inline-flex min-h-10 w-full
                            items-center justify-start gap-3

                            border border-red-200
                            px-4 py-2.5
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


        {{-- Workspace --}}
        <div class="flex min-w-0 flex-1 flex-col">

            {{-- Compact top header --}}
            <header
                id="app-header"
                class="
                    sticky top-0 z-30 print:hidden
                    flex h-12 w-full shrink-0
                    items-center
                    border-b border-neutral-200
                    bg-white px-2
                    dark:border-neutral-800
                    dark:bg-neutral-900
                    sm:h-14
                    sm:px-4
                ">

                {{-- Left section --}}
                <div
                    class="
                        flex min-w-0 flex-1
                        items-center gap-2
                        sm:gap-3
                    ">

                    {{-- Mobile menu --}}
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
                            class="h-4 w-4">
                        </i>

                    </button>


                    {{-- Page title --}}
                    <h2
                        class="
                            min-w-0 shrink-0
                            truncate
                            text-sm font-semibold
                            text-neutral-800
                            dark:text-neutral-100
                            sm:max-w-52
                            sm:text-sm
                            lg:max-w-60
                        ">

                        @yield('page-title', 'Dashboard')

                    </h2>


                    {{-- Desktop feature search --}}
                    <button
                        type="button"
                        data-feature-search-open
                        class="
                            rincomm-feature-search-desktop
                            ml-1 h-9 min-w-0
                            items-center gap-2.5

                            border border-neutral-200
                            bg-neutral-50
                            px-3
                            text-left text-sm
                            text-neutral-500
                            transition
                            hover:border-[#008080]
                            hover:bg-white
                            focus:outline-none
                            focus:ring-2
                            focus:ring-[#008080]/20
                            dark:border-neutral-700
                            dark:bg-neutral-950
                            dark:text-neutral-400
                            dark:hover:border-[#14B8A6]
                            dark:hover:bg-neutral-900
                            md:flex
                        ">

                        <svg
                            class="h-4 w-4 shrink-0"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            aria-hidden="true">

                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.3-4.3"></path>

                        </svg>

                        <span class="min-w-0 flex-1 truncate">
                            Search features...
                        </span>

                        <span
                            class="
                                hidden shrink-0

                                border border-neutral-200
                                bg-white
                                px-1.5 py-0.5
                                text-[10px] font-medium
                                text-neutral-400
                                dark:border-neutral-700
                                dark:bg-neutral-900
                                dark:text-neutral-500
                                xl:inline-flex
                            ">
                            Ctrl K
                        </span>

                    </button>

                </div>


                {{-- Right section --}}
                <div class="ml-2 flex shrink-0 items-center gap-1.5 sm:gap-2">

                    {{-- Mobile search --}}
                    <button
                        type="button"
                        data-feature-search-open
                        aria-label="Search features"
                        title="Search features"
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
                            md:hidden
                        ">

                        <svg
                            class="h-3.5 w-3.5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            aria-hidden="true">

                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.3-4.3"></path>

                        </svg>

                    </button>


                    {{-- Theme toggle --}}
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
                            class="hidden h-3.5 w-3.5">
                        </i>

                        <i
                            data-theme-moon-icon
                            data-lucide="moon"
                            class="h-3.5 w-3.5">
                        </i>

                    </button>


                    {{-- Compact user identity --}}
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
                            title="{{ $authenticatedUser->name }}">

                            {{ $authenticatedUser->name }}

                        </p>

                        <p
                            class="
                                text-[10px]
                                leading-3
                                text-neutral-500
                                dark:text-neutral-400
                            ">

                            {{ \Illuminate\Support\Str::headline($authenticatedUser->role) }}

                        </p>

                    </div>

                </div>

            </header>


            {{-- Existing page-specific secondary navigation --}}
            @hasSection('secondary-navigation')

            <div
                class="
                        rincomm-secondary-nav
                        border-b border-neutral-200
                        bg-white
                        dark:border-neutral-800
                        dark:bg-neutral-950
                    ">

                <div class="px-3 sm:px-4">
                    @yield('secondary-navigation')
                </div>

            </div>

            @endif


            {{-- Automatic module secondary navigation --}}
            @if (! $__env->hasSection('secondary-navigation') && ! empty($contextualNavigation))

            <div
                class="
                        rincomm-secondary-nav
                        border-b border-neutral-200
                        bg-white
                        dark:border-neutral-800
                        dark:bg-neutral-950
                    ">

                <div class="overflow-x-auto px-2 sm:px-4">

                    <nav
                        class="
                                flex min-w-max
                                items-center gap-1
                                py-1.5
                            "
                        aria-label="{{ $currentModule }} navigation">

                        @foreach ($contextualNavigation as $navigationItem)

                        <a
                            href="{{ route($navigationItem['route']) }}"
                            class="
                                        inline-flex min-h-8
                                        items-center justify-center
                                        whitespace-nowrap

                                        px-3 py-1.5
                                        text-xs font-medium
                                        transition
                                        sm:text-sm

                                        {{ $navigationItem['active']
                                            ? 'bg-[#008080] text-white shadow-sm'
                                            : 'text-neutral-600 hover:bg-[#008080]/10 hover:text-[#008080]
                                               dark:text-neutral-400 dark:hover:bg-[#008080]/15
                                               dark:hover:text-[#5EEAD4]'
                                        }}
                                    ">

                            {{ $navigationItem['label'] }}

                        </a>

                        @endforeach

                    </nav>

                </div>

            </div>

            @endif


            {{-- Page content --}}
            <main class="flex-1 p-3 sm:p-4 {{ request()->routeIs('admin.invoices.show') ? 'lg:px-5 lg:py-2' : 'lg:p-5' }} print:p-0">
                @yield('content')
            </main>

        </div>

    </div>


    {{-- Global feature search modal --}}
    <div
        id="feature-search-modal"
        class="
            fixed inset-0 z-[90]
            hidden
            items-start justify-center
            p-3 pt-[8vh]
            sm:p-4
            sm:pt-[10vh]
        "
        aria-hidden="true"
        role="dialog"
        aria-modal="true"
        aria-labelledby="feature-search-title">

        <div
            data-feature-search-overlay
            class="absolute inset-0 bg-slate-950/60 backdrop-blur-[1px]">
        </div>


        <div
            class="
                relative z-10
                flex max-h-[80vh] w-full max-w-2xl
                flex-col overflow-hidden

                border border-neutral-200
                bg-white
                shadow-2xl
                dark:border-neutral-800
                dark:bg-neutral-900
            ">

            {{-- Search heading --}}
            <div
                class="
                    flex items-center gap-3
                    border-b border-neutral-200
                    px-4 py-3
                    dark:border-neutral-800
                ">

                <svg
                    class="h-5 w-5 shrink-0 text-[#008080] dark:text-[#5EEAD4]"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    aria-hidden="true">

                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.3-4.3"></path>

                </svg>


                <div class="min-w-0 flex-1">

                    <label
                        id="feature-search-title"
                        for="feature-search-input"
                        class="sr-only">
                        Search system features
                    </label>

                    <input
                        id="feature-search-input"
                        type="text"
                        autocomplete="off"
                        placeholder="Search features, modules, or actions..."
                        class="
                            block w-full
                            border-0 bg-transparent
                            p-0
                            text-sm
                            text-neutral-900
                            outline-none
                            ring-0
                            placeholder:text-neutral-400
                            focus:border-0
                            focus:outline-none
                            focus:ring-0
                            dark:text-white
                            dark:placeholder:text-neutral-500
                            sm:text-base
                        ">

                </div>


                <button
                    type="button"
                    data-feature-search-close
                    aria-label="Close feature search"
                    class="
                        inline-flex h-8 w-8 shrink-0
                        items-center justify-center

                        text-neutral-500
                        transition
                        hover:bg-neutral-100
                        hover:text-neutral-900
                        dark:text-neutral-400
                        dark:hover:bg-neutral-800
                        dark:hover:text-white
                    ">

                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        aria-hidden="true">

                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>

                    </svg>

                </button>

            </div>


            {{-- Search helper --}}
            <div
                class="
                    border-b border-neutral-100
                    bg-neutral-50
                    px-4 py-2
                    text-xs text-neutral-500
                    dark:border-neutral-800
                    dark:bg-neutral-950
                    dark:text-neutral-400
                ">

                Search only shows features available to your account.

            </div>


            {{-- Results --}}
            <div
                id="feature-search-results"
                class="
                    min-h-0 flex-1
                    overflow-y-auto
                    p-2
                ">

                @foreach ($featureSearchItems as $feature)

                <a
                    href="{{ route($feature['route'], $feature['parameters'] ?? []) }}"
                    data-feature-search-item
                    data-feature-search-text="{{ \Illuminate\Support\Str::lower(
                            $feature['label']
                            . ' '
                            . $feature['module']
                            . ' '
                            . $feature['description']
                            . ' '
                            . $feature['keywords']
                        ) }}"
                    class="
                            group flex items-center gap-3

                            px-3 py-3
                            transition
                            hover:bg-[#008080]/10
                            focus:outline-none
                            focus:ring-2
                            focus:ring-[#008080]/20
                            dark:hover:bg-[#008080]/15
                        ">

                    <div
                        class="
                                flex h-9 w-9 shrink-0
                                items-center justify-center

                                bg-[#008080]/10
                                text-[#008080]
                                dark:bg-[#008080]/20
                                dark:text-[#5EEAD4]
                            ">

                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            aria-hidden="true">

                            <path d="M5 12h14"></path>
                            <path d="m13 6 6 6-6 6"></path>

                        </svg>

                    </div>


                    <div class="min-w-0 flex-1">

                        <div
                            class="
                                    flex flex-col
                                    gap-0.5
                                    sm:flex-row
                                    sm:items-center
                                    sm:gap-2
                                ">

                            <p
                                class="
                                        truncate
                                        text-sm font-semibold
                                        text-neutral-900
                                        dark:text-white
                                    ">

                                {{ $feature['label'] }}

                            </p>

                            <span
                                class="
                                        w-fit
                                        text-[11px] font-medium
                                        text-[#008080]
                                        dark:text-[#5EEAD4]
                                    ">

                                {{ $feature['module'] }}

                            </span>

                        </div>


                        <p
                            class="
                                    mt-0.5
                                    line-clamp-2
                                    text-xs
                                    text-neutral-500
                                    dark:text-neutral-400
                                ">

                            {{ $feature['description'] }}

                        </p>

                    </div>

                </a>

                @endforeach


                <div
                    id="feature-search-empty"
                    class="
                        hidden
                        px-4 py-10
                        text-center
                    ">

                    <p
                        class="
                            text-sm font-semibold
                            text-neutral-800
                            dark:text-neutral-200
                        ">
                        No feature found
                    </p>

                    <p
                        class="
                            mt-1
                            text-xs
                            text-neutral-500
                            dark:text-neutral-400
                        ">
                        Try another feature name or action.
                    </p>

                </div>

            </div>


            {{-- Keyboard hints --}}
            <div
                class="
                    hidden
                    border-t border-neutral-200
                    px-4 py-2
                    text-[11px]
                    text-neutral-400
                    dark:border-neutral-800
                    dark:text-neutral-500
                    sm:flex
                    sm:items-center
                    sm:justify-between
                ">

                <span>
&uarr; &darr; Navigate &middot; Enter Open
                </span>

                <span>
                    Esc Close
                </span>

            </div>

        </div>

    </div>


    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('feature-search-modal');
            const input = document.getElementById('feature-search-input');
            const resultContainer = document.getElementById('feature-search-results');
            const emptyState = document.getElementById('feature-search-empty');

            const openButtons = [
                ...document.querySelectorAll('[data-feature-search-open]')
            ];

            const closeButton = document.querySelector(
                '[data-feature-search-close]'
            );

            const overlay = document.querySelector(
                '[data-feature-search-overlay]'
            );

            const allItems = [
                ...document.querySelectorAll('[data-feature-search-item]')
            ];

            let visibleItems = [...allItems];
            let selectedIndex = 0;


            const normalize = (value) => {
                return value
                    .toLowerCase()
                    .trim()
                    .replace(/\s+/g, ' ');
            };


            const updateSelection = () => {
                allItems.forEach((item) => {
                    item.classList.remove(
                        'bg-[#008080]/10',
                        'dark:bg-[#008080]/15'
                    );
                });

                if (visibleItems.length === 0) {
                    return;
                }

                if (selectedIndex < 0) {
                    selectedIndex = visibleItems.length - 1;
                }

                if (selectedIndex >= visibleItems.length) {
                    selectedIndex = 0;
                }

                const selectedItem = visibleItems[selectedIndex];

                selectedItem.classList.add(
                    'bg-[#008080]/10',
                    'dark:bg-[#008080]/15'
                );

                selectedItem.scrollIntoView({
                    block: 'nearest',
                });
            };


            const filterItems = () => {
                const query = normalize(input?.value ?? '');

                visibleItems = allItems.filter((item) => {
                    const searchableText = normalize(
                        item.dataset.featureSearchText ?? ''
                    );

                    const matches =
                        query === '' ||
                        searchableText.includes(query);

                    item.classList.toggle(
                        'hidden',
                        !matches
                    );

                    return matches;
                });

                selectedIndex = 0;

                emptyState?.classList.toggle(
                    'hidden',
                    visibleItems.length !== 0
                );

                updateSelection();
            };


            const openModal = () => {
                if (!modal) {
                    return;
                }

                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.setAttribute('aria-hidden', 'false');

                document.body.classList.add('overflow-hidden');

                if (input) {
                    input.value = '';
                }

                filterItems();

                window.setTimeout(() => {
                    input?.focus();
                }, 0);
            };


            const closeModal = () => {
                if (!modal) {
                    return;
                }

                modal.classList.add('hidden');
                modal.classList.remove('flex');
                modal.setAttribute('aria-hidden', 'true');

                document.body.classList.remove('overflow-hidden');

                if (input) {
                    input.value = '';
                }

                filterItems();
            };


            openButtons.forEach((button) => {
                button.addEventListener(
                    'click',
                    openModal
                );
            });


            closeButton?.addEventListener(
                'click',
                closeModal
            );


            overlay?.addEventListener(
                'click',
                closeModal
            );


            input?.addEventListener(
                'input',
                filterItems
            );


            document.addEventListener('keydown', (event) => {
                const isShortcut =
                    (event.ctrlKey || event.metaKey) &&
                    event.key.toLowerCase() === 'k';

                if (isShortcut) {
                    event.preventDefault();

                    if (
                        modal?.getAttribute('aria-hidden') === 'false'
                    ) {
                        closeModal();
                    } else {
                        openModal();
                    }

                    return;
                }

                if (
                    !modal ||
                    modal.getAttribute('aria-hidden') !== 'false'
                ) {
                    return;
                }

                if (event.key === 'Escape') {
                    event.preventDefault();
                    closeModal();
                    return;
                }

                if (
                    event.key === 'ArrowDown' &&
                    visibleItems.length > 0
                ) {
                    event.preventDefault();
                    selectedIndex += 1;
                    updateSelection();
                    return;
                }

                if (
                    event.key === 'ArrowUp' &&
                    visibleItems.length > 0
                ) {
                    event.preventDefault();
                    selectedIndex -= 1;
                    updateSelection();
                    return;
                }

                if (
                    event.key === 'Enter' &&
                    visibleItems.length > 0
                ) {
                    event.preventDefault();
                    visibleItems[selectedIndex]?.click();
                }
            });


            resultContainer?.addEventListener(
                'mousemove',
                (event) => {
                    const item = event.target.closest(
                        '[data-feature-search-item]'
                    );

                    if (!item || item.classList.contains('hidden')) {
                        return;
                    }

                    const index = visibleItems.indexOf(item);

                    if (index === -1) {
                        return;
                    }

                    selectedIndex = index;
                    updateSelection();
                }
            );


            filterItems();
        });
    </script>

</body>

</html>
