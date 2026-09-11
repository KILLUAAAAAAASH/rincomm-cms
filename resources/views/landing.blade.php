@extends('layouts.public')

@section('title', 'Rincomm Internet Services')

@section('content')

<!-- Public Navbar -->
<header
    class="
        sticky top-0 z-50
        border-b border-slate-200
        bg-white/95 backdrop-blur
        dark:border-zinc-800
        dark:bg-zinc-950/95
    ">
    <nav class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <div class="flex h-16 items-center justify-between">

            <!-- Brand -->
            <a
                href="#home"
                class="flex items-center gap-3">
                <div
                    class="
        flex h-10 w-10 items-center justify-center
        rounded-md
        bg-[#008080]
        text-white
    ">
                    <i data-lucide="wifi" class="h-5 w-5"></i>
                </div>

                <div>
                    <p
                        class="
                            text-base
                            font-semibold leading-none
                            text-slate-950
                            dark:text-white
                            lg:text-xl
                        ">
                        Rincomm
                    </p>

                    <p
                        class="
                            mt-1 hidden text-xs
                            text-slate-500
                            dark:text-zinc-400
                            sm:block
                        ">
                        Internet Services
                    </p>
                </div>
            </a>

            <!-- Desktop Navigation -->
            <div class="hidden items-center gap-1 lg:flex">

                <!-- Home -->
                <a
                    href="#home"
                    class="
                        rounded-xl px-4 py-2.5
                        text-sm font-medium
                        text-slate-700
                        transition-colors
                        hover:bg-[#008080]/10
                        hover:text-[#008080]
                        dark:text-zinc-300
                        dark:hover:bg-[#008080]/15
                        dark:hover:text-[#2DD4BF]
                    ">
                    Home
                </a>

                <!-- Plans -->
                <a
                    href="#plans"
                    class="
        rounded-xl px-4 py-2.5
        text-sm font-medium
        text-slate-700
        transition-colors
        hover:bg-[#008080]/10
        hover:text-[#008080]
        dark:text-zinc-300
        dark:hover:bg-[#008080]/15
        dark:hover:text-[#2DD4BF]
    ">
                    Plans
                </a>



                <!-- Support Dropdown -->
                <div class="group relative">

                    <button
                        type="button"
                        data-desktop-dropdown="desktop-support"
                        aria-expanded="false"
                        class="
        flex items-center gap-1.5
        rounded-xl px-4 py-2.5
        text-sm font-medium
        text-slate-700
        hover:bg-[#008080]/10
        hover:text-[#008080]
        dark:text-zinc-300
        dark:hover:bg-[#008080]/15
        dark:hover:text-[#2DD4BF]
    ">
                        Support

                        <i
                            data-lucide="chevron-down"
                            class="h-4 w-4 transition-transform duration-150 group-hover:rotate-180"></i>
                    </button>

                    <div
                        id="desktop-support"
                        class="
                            invisible absolute left-0 top-full z-50
                            w-64 translate-y-1
                            pt-3 opacity-0
                            transition-all duration-150
                            group-hover:visible
                            group-hover:translate-y-0
                            group-hover:opacity-100
                        ">
                        <div
                            class="
                                rounded-2xl
                                border border-slate-200
                                bg-white p-2
                                shadow-xl
                                dark:border-zinc-700
                                dark:bg-zinc-900
                            ">
                            <a href="{{ route('login') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 hover:bg-slate-100 dark:hover:bg-zinc-800">
                                <i data-lucide="ticket-plus" class="h-4 w-4 text-[#008080]"></i>
                                <span class="text-sm font-medium dark:text-zinc-200">Create Ticket</span>
                            </a>

                            <a href="{{ route('login') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 hover:bg-slate-100 dark:hover:bg-zinc-800">
                                <i data-lucide="activity" class="h-4 w-4 text-[#008080]"></i>
                                <span class="text-sm font-medium dark:text-zinc-200">Service Status</span>
                            </a>

                            <a href="#faqs" class="flex items-center gap-3 rounded-xl px-3 py-3 hover:bg-slate-100 dark:hover:bg-zinc-800">
                                <i data-lucide="circle-help" class="h-4 w-4 text-[#008080]"></i>
                                <span class="text-sm font-medium dark:text-zinc-200">FAQs</span>
                            </a>

                            <a href="#contact" class="flex items-center gap-3 rounded-xl px-3 py-3 hover:bg-slate-100 dark:hover:bg-zinc-800">
                                <i data-lucide="headphones" class="h-4 w-4 text-[#008080]"></i>
                                <span class="text-sm font-medium dark:text-zinc-200">Contact Support</span>
                            </a>
                        </div>
                    </div>

                </div>



            </div>

            <!-- Desktop Apply Button -->
            <div class="hidden lg:block">
                <a
                    href="{{ route('apply.coverage') }}"
                    class="
            inline-flex min-h-11
            items-center justify-center
            rounded-md
            bg-[#008080]
            px-4 py-2.5
            text-sm font-semibold text-white
            transition
            hover:bg-[#006666]
            focus:outline-none
            focus:ring-2 focus:ring-[#008080]
            focus:ring-offset-2
            dark:focus:ring-offset-zinc-950
        ">
                    Get connected now
                </a>
            </div>

            <!-- Mobile Hamburger -->
            <button
                id="public-drawer-open"
                type="button"
                aria-label="Open navigation"
                aria-controls="public-mobile-drawer"
                aria-expanded="false"
                class="
                    rounded-xl
                    border border-slate-200
                    p-2.5
                    text-slate-700
                    hover:border-[#008080]/40
                    hover:text-[#008080]
                    dark:border-zinc-700
                    dark:text-zinc-200
                    lg:hidden
                ">
                <i data-lucide="menu" class="h-5 w-5"></i>
            </button>

        </div>

    </nav>
</header>

<!-- Mobile Drawer Overlay -->
<div
    id="public-drawer-overlay"
    class="fixed inset-0 z-[60] hidden bg-black/50 backdrop-blur-[1px] lg:hidden"></div>

<!-- Mobile Navigation Drawer -->
<aside
    id="public-mobile-drawer"
    aria-hidden="true"
    class="fixed inset-y-0 right-0 z-[70]
           w-80 max-w-[88vw]
           translate-x-full
           pointer-events-none
           overflow-y-auto
           bg-white shadow-2xl
           transition-transform duration-200 ease-out
           dark:bg-zinc-900
           lg:hidden">
    <!-- Drawer Header -->
    <div
        class="
            flex h-16 items-center justify-between
            border-b border-slate-200
            px-5
            dark:border-zinc-800
        ">
        <div class="flex items-center gap-3">

            <div
                class="
        flex h-9 w-9 items-center justify-center
        rounded-md
        bg-[#008080]
        text-white
    ">
                <i data-lucide="wifi" class="h-4 w-4"></i>
            </div>

            <span class="font-semibold dark:text-white">
                Rincomm
            </span>

        </div>

        <button
            id="public-drawer-close"
            type="button"
            aria-label="Close navigation"
            class="
                rounded-xl p-2
                text-slate-600
                hover:bg-slate-100
                dark:text-zinc-300
                dark:hover:bg-zinc-800
            ">
            <i data-lucide="x" class="h-5 w-5"></i>
        </button>
    </div>

    <!-- Drawer Navigation -->
    <nav class="space-y-2 p-4">

        <a
            href="#home"
            class="
                public-drawer-link
                flex min-h-12 items-center
                rounded-xl px-4
                text-sm font-medium
                text-slate-700
                hover:bg-[#008080]/10
                hover:text-[#008080]
                dark:text-zinc-200
            ">
            Home
        </a>

        <!-- Plans Mobile-Nav -->
        <a
            href="#plans"
            class="
        public-drawer-link
        flex min-h-12 items-center
        rounded-xl px-4
        text-sm font-medium
        text-slate-700
        hover:bg-[#008080]/10
        hover:text-[#008080]
        dark:text-zinc-200
    ">
            Plans
        </a>

        <!-- Support Accordion -->
        <div>
            <button
                type="button"
                data-mobile-dropdown="mobile-support"
                class="
                    flex min-h-12 w-full
                    items-center justify-between
                    rounded-xl px-4
                    text-sm font-medium
                    text-slate-700
                    hover:bg-[#008080]/10
                    dark:text-zinc-200
                ">
                Support
                <i data-lucide="chevron-down" class="h-4 w-4"></i>
            </button>

            <div id="mobile-support" class="hidden space-y-1 px-3 pb-2">
                <a href="{{ route('login') }}" class="public-drawer-link block rounded-xl px-4 py-3 text-sm text-slate-600 dark:text-zinc-400">Create Ticket</a>
                <a href="{{ route('login') }}" class="public-drawer-link block rounded-xl px-4 py-3 text-sm text-slate-600 dark:text-zinc-400">Service Status</a>
                <a href="#faqs" class="public-drawer-link block rounded-xl px-4 py-3 text-sm text-slate-600 dark:text-zinc-400">FAQs</a>
                <a href="#contact" class="public-drawer-link block rounded-xl px-4 py-3 text-sm text-slate-600 dark:text-zinc-400">Contact Support</a>
            </div>
        </div>


        <!-- Apply -->
        <div class="pt-4">
            <a
                href="{{ route('apply.coverage') }}"
                class="
            public-drawer-link
            flex min-h-12 w-full
            items-center justify-center
            rounded-md
            bg-[#008080]
            px-5 py-3
            text-sm font-semibold text-white
            transition
            hover:bg-[#006666]
            focus:outline-none
            focus:ring-2 focus:ring-[#008080]
            focus:ring-offset-2
            dark:focus:ring-offset-zinc-900
        ">
                Get connected now
            </a>
        </div>
    </nav>

</aside>

<!-- Hero -->
<section
    id="home"
    class="bg-slate-50 dark:bg-neutral-950">
    <div
        class="
            mx-auto grid max-w-7xl
            items-center gap-10
            px-4 pb-12 pt-6
            sm:px-6 sm:pb-14 sm:pt-8
            lg:grid-cols-2
            lg:gap-12
            lg:px-8 lg:pb-16 lg:pt-8
        ">

        <!-- Hero Content -->
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-neutral-400">
                Rincomm Internet Services
            </p>

            <h1
                class="
                    mt-4
                    text-4xl font-bold tracking-tight
                    text-slate-950
                    sm:text-5xl
                    lg:text-6xl
                    dark:text-white
                ">
                Reliable internet for your home.
            </h1>

            <p
                class="
                    mt-6 max-w-xl
                    text-base leading-7 text-slate-600
                    sm:text-lg
                    dark:text-neutral-300
                ">
                Find an internet plan that fits your needs and manage
                your subscription, bills, payments, and support requests
                online.
            </p>

            <!-- Hero Actions -->
            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">

                <a
                    href="{{ route('apply.coverage') }}"
                    class="
                        inline-flex min-h-12
                        items-center justify-center
                        rounded-md
                        bg-[#008080]
                        px-5 py-3
                        text-sm font-semibold text-white
                        transition
                        hover:bg-[#006666]
                        focus:outline-none
                        focus:ring-2 focus:ring-[#008080]
                        focus:ring-offset-2
                        dark:focus:ring-offset-neutral-950
                    ">
                    Get connected now
                </a>

                <a
                    href="{{ route('login') }}"
                    class="
                        inline-flex min-h-12
                        items-center justify-center
                        rounded-md
                        border border-neutral-300
                        bg-white
                        px-5 py-3
                        text-sm font-semibold text-neutral-800
                        transition
                        hover:border-[#008080]
                        hover:text-[#008080]
                        focus:outline-none
                        focus:ring-2 focus:ring-[#008080]
                        focus:ring-offset-2
                        dark:border-neutral-700
                        dark:bg-neutral-900
                        dark:text-neutral-100
                        dark:hover:border-teal-400
                        dark:hover:text-teal-400
                        dark:focus:ring-offset-neutral-950
                    ">
                    Login
                </a>

            </div>
        </div>

        <!-- Hero Carousel -->
        <div
            class="
                relative
                min-h-[420px]
                overflow-hidden
                rounded-2xl
                bg-neutral-900
                shadow-lg
                lg:min-h-[500px]
            "
            data-hero-carousel>

            @if ($heroSlides->isNotEmpty())

            <div class="relative h-[420px] lg:h-[500px]">

                @foreach ($heroSlides as $index => $slide)

                <article
                    data-hero-slide
                    class="
                                absolute inset-0
                                transition-opacity duration-500
                                {{ $index === 0
                                    ? 'z-10 opacity-100'
                                    : 'pointer-events-none z-0 opacity-0' }}
                            "
                    aria-hidden="{{ $index === 0 ? 'false' : 'true' }}">

                    <!-- Slide Image -->
                    <img
                        src="{{ asset('storage/' . $slide->image_path) }}"
                        alt="{{ $slide->alt_text ?: $slide->title ?: 'Rincomm homepage slide' }}"
                        class="h-full w-full object-cover">

                    @if ($slide->content_type !== 'image_only')

                    <!-- Readability Overlay -->
                    <div
                        class="
                                        absolute inset-0
                                        bg-gradient-to-t
                                        from-black/85 via-black/45 to-black/10
                                        lg:bg-gradient-to-r
                                        lg:from-black/80
                                        lg:via-black/40
                                        lg:to-transparent
                                    "></div>

                    <!-- Slide Content -->
                    <div
                        class="
                                        absolute inset-0 z-10
                                        flex items-end
                                        p-4 pb-12
                                        sm:p-8 sm:pb-16
                                        lg:p-10 lg:pb-16
                                    ">
                        <div class="max-w-xl">

                            <span
                                class="
        inline-flex rounded-full
        border border-white/20
        bg-black/30
        px-2 py-0.5
        text-[9px] font-semibold uppercase
        tracking-wide text-white
        backdrop-blur-sm
        sm:px-3 sm:py-1 sm:text-xs
    ">
                                {{ \Illuminate\Support\Str::headline($slide->category) }}
                            </span>

                            <!-- Title -->
                            @if ($slide->title)
                            <h2
                                class="
        mt-2
        text-lg font-bold leading-[1.15] text-white
        sm:mt-3 sm:text-3xl
        lg:text-4xl
    ">
                                {{ $slide->title }}
                            </h2>
                            @endif

                            <!-- Description -->
                            @if ($slide->description)
                            <p
                                class="
        mt-2 max-w-lg
        text-[10px] leading-[1.4] text-neutral-200
        sm:mt-3 sm:text-sm sm:leading-5
        lg:text-base lg:leading-6
    ">
                                {{ $slide->description }}
                            </p>
                            @endif

                            <!-- CTA -->
                            @if (
                            $slide->content_type === 'image_text_cta' &&
                            $slide->cta_text &&
                            $slide->cta_url
                            )
                            <a
                                href="{{ $slide->cta_url }}"
                                class="
        mt-3 inline-flex
        items-center justify-center gap-1.5
        rounded-lg
        bg-[#008080]
        px-3 py-2
        text-xs font-semibold text-white
        shadow-sm transition
        hover:bg-[#006666]
        sm:mt-4 sm:px-4 sm:py-2.5 sm:text-sm
    ">
                                {{ $slide->cta_text }}

                                <i
                                    data-lucide="arrow-right"
                                    class="h-3.5 w-3.5"></i>
                            </a>
                            @endif

                        </div>
                    </div>

                    @endif

                </article>

                @endforeach

            </div>

            @if ($heroSlides->count() > 1)

            <!-- Previous -->
            <button
                type="button"
                data-hero-previous
                class="
                            absolute left-4 top-1/2 z-20
                            inline-flex h-9 w-9
                            -translate-y-1/2
                            items-center justify-center
                            rounded-full
                            border border-white/20
                            bg-black/30 text-white
                            backdrop-blur-sm
                            transition hover:bg-black/50
                        "
                aria-label="Previous slide">
                <i
                    data-lucide="chevron-left"
                    class="h-5 w-5"></i>
            </button>

            <!-- Next -->
            <button
                type="button"
                data-hero-next
                class="
                            absolute right-4 top-1/2 z-20
                            inline-flex h-9 w-9
                            -translate-y-1/2
                            items-center justify-center
                            rounded-full
                            border border-white/20
                            bg-black/30 text-white
                            backdrop-blur-sm
                            transition hover:bg-black/50
                        "
                aria-label="Next slide">
                <i
                    data-lucide="chevron-right"
                    class="h-5 w-5"></i>
            </button>

            <!-- Carousel Dots -->
            <div
                class="
                            absolute bottom-4 left-1/2 z-20
                            flex -translate-x-1/2
                            items-center gap-2
                            rounded-full
                            bg-black/25
                            px-3 py-2
                            backdrop-blur-sm
                        "
                data-hero-dots>
                @foreach ($heroSlides as $index => $slide)
                <button
                    type="button"
                    data-hero-dot="{{ $index }}"
                    class="
                                    h-2.5 rounded-full transition-all
                                    {{ $index === 0
                                        ? 'w-7 bg-white'
                                        : 'w-2.5 bg-white/50 hover:bg-white/80' }}
                                "
                    aria-label="Go to slide {{ $index + 1 }}"
                    aria-current="{{ $index === 0 ? 'true' : 'false' }}"></button>
                @endforeach
            </div>

            @endif

            @else

            <!-- Empty Carousel -->
            <div
                class="
                        flex h-[420px]
                        items-center justify-center
                        bg-gradient-to-br
                        from-[#006666] to-[#008080]
                        px-8 text-center
                        lg:h-[500px]
                    ">
                <div class="max-w-md text-white">

                    <div
                        class="
                                mx-auto flex h-14 w-14
                                items-center justify-center
                                rounded-2xl bg-white/10
                            ">
                        <i
                            data-lucide="wifi"
                            class="h-7 w-7"></i>
                    </div>

                    <h2 class="mt-5 text-2xl font-bold">
                        Reliable Internet for Your Connection
                    </h2>

                    <p class="mt-3 text-sm leading-6 text-white/80">
                        Explore Rincomm internet plans and services
                        available for homes and businesses.
                    </p>

                </div>
            </div>

            @endif

        </div>

    </div>
</section>

<!-- Internet Plans -->
<section
    id="plans"
    class="bg-white py-16 sm:py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <!-- Section Header -->
        <div class="mx-auto max-w-2xl text-center">

            <p class="text-md font-semibold uppercase tracking-wide text-slate-600">
                Internet Plans
            </p>

            <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                Choose a plan that fits your needs
            </h2>

            <p class="mt-4 text-base leading-7 text-slate-600">
                Browse available Rincomm internet plans for your home or business.
            </p>

        </div>

        <!-- Plans Grid -->
        @if ($servicePlans->isNotEmpty())

        <div class="mx-auto mt-10 grid max-w-5xl gap-6 md:grid-cols-2 lg:grid-cols-3">

            @foreach ($servicePlans as $plan)

            <article
                class="
        flex w-full flex-col
        rounded-lg
        border border-slate-200
        bg-white
        p-6
    ">
                <div class="flex items-start justify-between gap-4">

                    <div>
                        <h3 class="text-xl font-bold text-slate-900">
                            {{ $plan->name }}
                        </h3>

                        @if ($plan->description)
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            {{ $plan->description }}
                        </p>
                        @endif
                    </div>

                    <div
                        class="
                flex h-10 w-10 shrink-0
                items-center justify-center
                rounded-md
                bg-slate-100
            ">
                        <i
                            data-lucide="wifi"
                            class="h-5 w-5 text-slate-700"></i>
                    </div>

                </div>

                <!-- Speed -->
                <div class="mt-6">

                    <p class="text-sm text-slate-500">
                        Internet Speed
                    </p>

                    <p class="mt-1 text-3xl font-bold text-slate-950">
                        {{ number_format((float) $plan->speed_mbps, 0) }}

                        <span class="text-base font-medium text-slate-500">
                            Mbps
                        </span>
                    </p>

                </div>

                <!-- Monthly Fee -->
                <div class="mt-5 border-t border-slate-200 pt-5">

                    <p class="text-sm text-slate-500">
                        Monthly Fee
                    </p>

                    <p class="mt-1 text-2xl font-bold text-slate-950">
                        ₱{{ number_format((float) $plan->monthly_fee, 2) }}

                        <span class="text-sm font-normal text-slate-500">
                            / month
                        </span>
                    </p>

                </div>

                <!-- Action -->
                <div class="mt-auto pt-6">

                    <a
                        href="{{ route('apply.coverage', ['plan' => $plan->id]) }}"
                        class="
                inline-flex min-h-12 w-full
                items-center justify-center
                rounded-md
                bg-[#008080]
                px-5 py-3
                text-sm font-semibold text-white
                transition
                hover:bg-[#006666]
                focus:outline-none
                focus:ring-2
                focus:ring-[#008080]
                focus:ring-offset-2
            ">
                        Check availability
                    </a>

                </div>

            </article>

            @endforeach

        </div>

        @else

        <div
            class="
        mx-auto mt-10 max-w-xl
        border-y border-slate-200
        py-8 text-center
    ">
            <i
                data-lucide="wifi-off"
                class="mx-auto h-7 w-7 text-slate-400"></i>

            <h3 class="mt-3 font-semibold text-slate-900">
                No plans available
            </h3>

            <p class="mt-2 text-sm text-slate-600">
                Internet plans will appear here when they become available.
            </p>
        </div>

        @endif

    </div>
</section>

<!-- About Rincomm -->
<section
    id="about"
    class="
        border-t border-slate-200
        bg-slate-50 py-16
        sm:py-20
    ">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <div
            class="
                grid gap-10
                lg:grid-cols-2
                lg:items-start
                lg:gap-16
            ">
            <!-- Section Heading -->
            <div>
                <p class="text-sm font-bold uppercase tracking-wide text-slate-600">
                    About Rincomm
                </p>
            </div>
    </div>
</section>

<!-- FAQs -->
<section
    id="faqs"
    class="
        border-t border-slate-200
        bg-slate-50 py-16
        sm:py-20
    ">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">

        <!-- Section Header -->
        <div class="max-w-2xl">

            <p class="text-sm font-semibold uppercase tracking-wide text-slate-600">
                FAQs
            </p>

            <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                Common questions about Rincomm services
            </h2>

            <p class="mt-4 text-base leading-7 text-slate-600">
                Find quick answers about applications, service availability,
                billing, and customer support.
            </p>

        </div>

        <!-- FAQ List -->
        <div
            class="
                mt-10
                divide-y divide-slate-200
                border-y border-slate-200
            ">

            <details class="py-5">
                <summary
                    class="
                        cursor-pointer
                        text-base font-semibold
                        text-slate-900
                    ">
                    How do I apply for a Rincomm internet connection?
                </summary>

                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                    Select Get connected now, check whether your location
                    is serviceable, choose an available internet plan,
                    and continue with your account and service application.
                </p>
            </details>

            <details class="py-5">
                <summary
                    class="
                        cursor-pointer
                        text-base font-semibold
                        text-slate-900
                    ">
                    How do I know if Rincomm is available in my area?
                </summary>

                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                    Use the service availability check before selecting a
                    plan. Your location will be checked against Rincomm's
                    currently serviceable areas.
                </p>
            </details>

            <details class="py-5">
                <summary
                    class="
                        cursor-pointer
                        text-base font-semibold
                        text-slate-900
                    ">
                    Where can I view my bills and payments?
                </summary>

                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                    Subscribers can access billing information, statements,
                    payment records, and receipts through their customer
                    account.
                </p>
            </details>

            <details class="py-5">
                <summary
                    class="
                        cursor-pointer
                        text-base font-semibold
                        text-slate-900
                    ">
                    How do I report an internet problem?
                </summary>

                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                    Sign in to your customer account and submit a support
                    ticket so the concern can be recorded and tracked.
                </p>
            </details>

        </div>

    </div>
</section>

<!-- Contact Support -->
<section
    id="contact"
    class="
        border-t border-slate-200
        bg-white py-16
        sm:py-20
    ">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <div
            class="
                grid gap-8
                lg:grid-cols-[1fr_auto]
                lg:items-center
            ">
            <div class="max-w-2xl">

                <p class="text-sm font-semibold uppercase tracking-wide text-slate-600">
                    Contact Support
                </p>

                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                    Need help with your connection?
                </h2>

                <p class="mt-4 text-base leading-7 text-slate-600">
                    Existing customers can sign in to submit and track
                    support concerns through their Rincomm account.
                </p>

            </div>

            <div class="flex flex-col gap-3 sm:flex-row lg:flex-col">

                <a
                    href="{{ route('login') }}"
                    class="
                        inline-flex min-h-12
                        items-center justify-center
                        rounded-md
                        bg-[#008080]
                        px-5 py-3
                        text-sm font-semibold text-white
                        transition
                        hover:bg-[#006666]
                        focus:outline-none
                        focus:ring-2
                        focus:ring-[#008080]
                        focus:ring-offset-2
                    ">
                    Login for Support
                </a>

                <a
                    href="{{ route('apply.coverage') }}"
                    class="
                        inline-flex min-h-12
                        items-center justify-center
                        rounded-md
                        border border-neutral-300
                        bg-white
                        px-5 py-3
                        text-sm font-semibold text-neutral-800
                        transition
                        hover:border-[#008080]
                        hover:text-[#008080]
                    ">
                    Get connected now
                </a>

            </div>

        </div>

    </div>
</section>

<!-- Public Footer -->
<footer class="border-t border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        <div
            class="
                flex flex-col gap-6
                sm:flex-row
                sm:items-center
                sm:justify-between
            ">
            <!-- Brand -->
            <div>
                <p class="font-semibold text-slate-950">
                    Rincomm
                </p>

                <p class="mt-1 text-sm text-slate-500">
                    Internet Services
                </p>
            </div>

            <!-- Footer Navigation -->
            <nav
                class="flex flex-wrap gap-x-6 gap-y-3 text-sm"
                aria-label="Footer navigation">
                <a
                    href="#home"
                    class="text-slate-600 hover:text-[#008080]">
                    Home
                </a>

                <a
                    href="#plans"
                    class="text-slate-600 hover:text-[#008080]">
                    Plans
                </a>

                <a
                    href="#faqs"
                    class="text-slate-600 hover:text-[#008080]">
                    FAQs
                </a>

                <a
                    href="#contact"
                    class="text-slate-600 hover:text-[#008080]">
                    Contact Support
                </a>

                <a
                    href="{{ route('login') }}"
                    class="text-slate-600 hover:text-[#008080]">
                    Login
                </a>
            </nav>

        </div>

        <div class="mt-6 border-t border-slate-200 pt-6">
            <p class="text-sm text-slate-500">
                &copy; {{ now()->year }} Rincomm Internet Services.
                All rights reserved.
            </p>
        </div>

    </div>
</footer>

@endsection