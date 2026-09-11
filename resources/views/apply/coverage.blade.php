<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Check Service Availability - Rincomm</title>

    <script>
        (() => {
            const savedTheme = localStorage.getItem('rincomm-theme');

            const useDarkTheme =
                savedTheme === 'dark' ||
                (!savedTheme &&
                    window.matchMedia('(prefers-color-scheme: dark)').matches);

            document.documentElement.classList.toggle('dark', useDarkTheme);
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-neutral-100 text-neutral-900 transition-colors dark:bg-neutral-950 dark:text-neutral-100">

    {{-- Theme toggle --}}
    <button
        type="button"
        data-theme-toggle
        class="
            fixed right-4 top-4 z-30
            inline-flex h-11 w-11
            items-center justify-center
            rounded-xl
            border border-neutral-300
            bg-white text-neutral-600
            shadow-sm transition
            hover:border-[#008080]
            hover:text-[#008080]
            focus:outline-none
            focus:ring-2
            focus:ring-[#008080]
            dark:border-neutral-700
            dark:bg-neutral-900
            dark:text-neutral-300
            dark:hover:border-teal-400
            dark:hover:text-teal-400
            sm:right-6 sm:top-6
        "
        aria-label="Toggle theme">

        <i
            data-lucide="sun"
            data-theme-sun-icon
            class="hidden h-5 w-5">
        </i>

        <i
            data-lucide="moon"
            data-theme-moon-icon
            class="h-5 w-5">
        </i>

    </button>


    <main class="mx-auto min-h-screen max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">

        {{-- Progress --}}
        <div class="mb-6">

            <div class="flex items-center gap-3">

                <div
                    class="
                        flex h-8 w-8 shrink-0
                        items-center justify-center
                        rounded-full
                        bg-[#008080]
                        text-sm font-semibold text-white
                    ">
                    1
                </div>


                <div class="min-w-0">

                    <div class="flex flex-wrap items-baseline">

                        <span class="text-md font-semibold text-neutral-900 dark:text-white">
                            Check Availability
                        </span>

                        <span class="ml-4 text-xs text-neutral-500 dark:text-neutral-400">
                            Confirm that Rincomm can serve your installation area.
                        </span>

                    </div>

                </div>

            </div>


            <div class="mt-3 h-1 overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-800">
                <div class="h-full w-1/4 bg-[#008080]"></div>
            </div>

        </div>


        {{-- Page intro --}}
        <div class="mb-8">

            <p
                class="
                    text-sm font-semibold uppercase
                    tracking-wide
                    text-[#008080]
                    dark:text-teal-400
                ">
                New Service Application
            </p>

        </div>


        {{-- Selected plan --}}
        @if ($selectedPlan)

        <section
            class="
                    mb-6 rounded-2xl
                    border border-[#008080]/30
                    bg-[#008080]/5 p-5
                    dark:border-teal-500/30
                    dark:bg-teal-500/10
                "
            aria-label="Selected internet plan">

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                <div>

                    <p class="text-xs font-semibold uppercase tracking-wide text-[#008080] dark:text-teal-400">
                        Selected Plan
                    </p>

                    <h2 class="mt-1 text-lg font-semibold text-neutral-950 dark:text-white">
                        {{ $selectedPlan->name }}
                    </h2>

                    @if ($selectedPlan->description)

                    <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                        {{ $selectedPlan->description }}
                    </p>

                    @endif

                </div>


                <div class="sm:text-right">

                    <p class="text-lg font-semibold text-neutral-950 dark:text-white">
                        {{ number_format((float) $selectedPlan->speed_mbps, 0) }} Mbps
                    </p>

                    <p class="text-sm text-neutral-600 dark:text-neutral-400">
                        &#8369;{{ number_format((float) $selectedPlan->monthly_fee, 2) }} / month
                    </p>

                </div>

            </div>

        </section>

        @endif


        {{-- Coverage result --}}
        @if (session('coverage_status') === 'available')

        <div
            class="
                    mb-6 rounded-xl
                    border border-emerald-300
                    bg-emerald-50/70
                    px-5 py-4
                    text-emerald-950
                    dark:border-emerald-800
                    dark:bg-emerald-950/30
                    dark:text-emerald-100
                "
            role="status">

            <div class="flex gap-3">

                <div class="mt-0.5 shrink-0">

                    <i
                        data-lucide="circle-check"
                        class="h-5 w-5">
                    </i>

                </div>


                <div class="min-w-0 flex-1">

                    <h2 class="font-semibold">
                        Service is available in your area
                    </h2>

                    <p class="mt-1 text-sm leading-6 text-emerald-800 dark:text-emerald-200">
                        Your location passed the Rincomm coverage check.
                        You can continue with the next step of your application.
                    </p>


                    <div class="mt-3 flex flex-col gap-3 sm:flex-row">

                        @if ($selectedPlan)

                        @if (
                        auth()->check() &&
                        auth()->user()->role === 'customer' &&
                        auth()->user()->account_status === 'active'
                        )

                        <a
                            href="{{ route('customer.application.create') }}"
                            class="
                                            inline-flex min-h-11
                                            items-center justify-center
                                            rounded-lg
                                            bg-[#008080]
                                            px-5 py-2.5
                                            text-sm font-semibold text-white
                                            transition
                                            hover:bg-[#006666]
                                            focus:outline-none
                                            focus:ring-2
                                            focus:ring-[#008080]
                                            focus:ring-offset-2
                                            dark:focus:ring-offset-neutral-950
                                        ">
                            Continue Application
                        </a>

                        @elseif (auth()->guest())

                        <a
                            href="{{ route('register', ['apply' => 1]) }}"
                            class="
                                            inline-flex min-h-11
                                            items-center justify-center
                                            rounded-lg
                                            bg-[#008080]
                                            px-5 py-2.5
                                            text-sm font-semibold text-white
                                            transition
                                            hover:bg-[#006666]
                                            focus:outline-none
                                            focus:ring-2
                                            focus:ring-[#008080]
                                            focus:ring-offset-2
                                            dark:focus:ring-offset-neutral-950
                                        ">
                            Continue to Account Creation
                        </a>

                        @endif

                        @else

                        <a
                            href="{{ route('home') }}#plans"
                            class="
                                        inline-flex min-h-11
                                        items-center justify-center
                                        rounded-lg
                                        bg-[#008080]
                                        px-5 py-2.5
                                        text-sm font-semibold text-white
                                        transition
                                        hover:bg-[#006666]
                                        focus:outline-none
                                        focus:ring-2
                                        focus:ring-[#008080]
                                        focus:ring-offset-2
                                        dark:focus:ring-offset-neutral-950
                                    ">
                            Choose an Internet Plan
                        </a>

                        @endif


                        <button
                            type="button"
                            onclick="document.getElementById('province').focus()"
                            class="
                                    inline-flex min-h-11
                                    items-center justify-center
                                    rounded-lg
                                    border border-neutral-300
                                    bg-white
                                    px-5 py-2.5
                                    text-sm font-semibold text-neutral-700
                                    transition
                                    hover:border-[#008080]
                                    hover:text-[#008080]
                                    dark:border-neutral-700
                                    dark:bg-neutral-900
                                    dark:text-neutral-200
                                ">
                            Check Another Location
                        </button>

                    </div>

                </div>

            </div>

        </div>


        @elseif (session('coverage_status') === 'unavailable')

        <div
            class="
                    mb-6 rounded-xl
                    border border-amber-300
                    bg-amber-50/70
                    px-5 py-4
                    text-amber-950
                    dark:border-amber-800
                    dark:bg-amber-950/30
                    dark:text-amber-100
                "
            role="status">

            <div class="flex gap-3">

                <div class="mt-0.5 shrink-0">

                    <i
                        data-lucide="triangle-alert"
                        class="h-5 w-5">
                    </i>

                </div>


                <div>

                    <h2 class="font-semibold">
                        Service is not currently available in this area
                    </h2>

                    <p class="mt-1 text-sm leading-6 text-amber-800 dark:text-amber-200">
                        We could not find an active Rincomm service area matching
                        the location you entered. You can check another installation
                        address.
                    </p>

                </div>

            </div>

        </div>

        @endif


        <div class="grid gap-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] lg:items-start">

            {{-- Coverage form --}}
            <section
                class="
                    rounded-xl
                    border border-neutral-200
                    bg-white/70
                    p-5
                    dark:border-neutral-800
                    dark:bg-neutral-900/60
                    sm:p-6
                ">

                <div class="pb-6">

                    <h2 class="text-lg font-semibold text-neutral-950 dark:text-white">
                        Installation Location
                    </h2>

                    <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                        Enter the exact province, city or municipality, and barangay for the installation site.
                    </p>

                </div>


                <form
                    method="POST"
                    action="{{ route('apply.coverage.check') }}"
                    data-lock-submit
                    novalidate
                    class="space-y-5">

                    @csrf


                    @if ($selectedPlan)

                    <input
                        type="hidden"
                        name="plan_id"
                        value="{{ $selectedPlan->id }}">

                    @endif


                    <div class="grid grid-cols-1 gap-5">

                        {{-- Province --}}
                        <div>

                            <label
                                for="province"
                                class="mb-2 block text-sm font-medium text-neutral-800 dark:text-neutral-200">

                                Province

                                <span
                                    class="text-red-600"
                                    aria-hidden="true">
                                    *
                                </span>

                            </label>

                            <input
                                id="province"
                                name="province"
                                type="text"
                                value="{{ old('province') }}"
                                autocomplete="address-level1"
                                placeholder="Example: Tarlac"
                                aria-invalid="{{ $errors->has('province') ? 'true' : 'false' }}"
                                aria-describedby="{{ $errors->has('province') ? 'province-error' : '' }}"
                                class="
                                    block min-h-12 w-full
                                    rounded-xl
                                    bg-white px-4 py-3
                                    text-base text-neutral-950
                                    outline-none transition
                                    placeholder:text-neutral-400

                                    {{ $errors->has('province')
                                        ? 'border border-red-500 ring-1 ring-red-500/20 focus:border-red-500 focus:ring-red-500'
                                        : 'border border-neutral-300 focus:border-[#008080] focus:ring-1 focus:ring-[#008080] dark:border-neutral-700'
                                    }}

                                    dark:bg-neutral-950
                                    dark:text-white
                                    dark:placeholder:text-neutral-500
                                ">

                            <x-field-error
                                id="province-error"
                                :message="$errors->first('province')" />

                        </div>


                        {{-- City --}}
                        <div>

                            <label
                                for="city_municipality"
                                class="mb-2 block text-sm font-medium text-neutral-800 dark:text-neutral-200">

                                City / Municipality

                                <span
                                    class="text-red-600"
                                    aria-hidden="true">
                                    *
                                </span>

                            </label>

                            <input
                                id="city_municipality"
                                name="city_municipality"
                                type="text"
                                value="{{ old('city_municipality') }}"
                                autocomplete="address-level2"
                                placeholder="Example: Paniqui"
                                aria-invalid="{{ $errors->has('city_municipality') ? 'true' : 'false' }}"
                                aria-describedby="{{ $errors->has('city_municipality') ? 'city-municipality-error' : '' }}"
                                class="
                                    block min-h-12 w-full
                                    rounded-xl
                                    bg-white px-4 py-3
                                    text-base text-neutral-950
                                    outline-none transition
                                    placeholder:text-neutral-400

                                    {{ $errors->has('city_municipality')
                                        ? 'border border-red-500 ring-1 ring-red-500/20 focus:border-red-500 focus:ring-red-500'
                                        : 'border border-neutral-300 focus:border-[#008080] focus:ring-1 focus:ring-[#008080] dark:border-neutral-700'
                                    }}

                                    dark:bg-neutral-950
                                    dark:text-white
                                    dark:placeholder:text-neutral-500
                                ">

                            <x-field-error
                                id="city-municipality-error"
                                :message="$errors->first('city_municipality')" />

                        </div>

                    </div>


                    {{-- Barangay --}}
                    <div>

                        <label
                            for="barangay"
                            class="mb-2 block text-sm font-medium text-neutral-800 dark:text-neutral-200">

                            Barangay

                            <span
                                class="text-red-600"
                                aria-hidden="true">
                                *
                            </span>

                        </label>

                        <input
                            id="barangay"
                            name="barangay"
                            type="text"
                            value="{{ old('barangay') }}"
                            placeholder="Enter barangay"
                            aria-invalid="{{ $errors->has('barangay') ? 'true' : 'false' }}"
                            aria-describedby="{{ $errors->has('barangay') ? 'barangay-error' : '' }}"
                            class="
                                block min-h-12 w-full
                                rounded-xl
                                bg-white px-4 py-3
                                text-base text-neutral-950
                                outline-none transition
                                placeholder:text-neutral-400

                                {{ $errors->has('barangay')
                                    ? 'border border-red-500 ring-1 ring-red-500/20 focus:border-red-500 focus:ring-red-500'
                                    : 'border border-neutral-300 focus:border-[#008080] focus:ring-1 focus:ring-[#008080] dark:border-neutral-700'
                                }}

                                dark:bg-neutral-950
                                dark:text-white
                                dark:placeholder:text-neutral-500
                            ">

                        <x-field-error
                            id="barangay-error"
                            :message="$errors->first('barangay')" />

                    </div>


                    {{-- Coverage note --}}
                    <div class="rounded-xl bg-neutral-50 p-4 text-sm text-neutral-700 dark:bg-neutral-950 dark:text-neutral-300">

                        <div class="flex gap-3">

                            <i
                                data-lucide="map-pin"
                                class="mt-0.5 h-5 w-5 shrink-0 text-[#008080] dark:text-teal-400">
                            </i>

                            <p class="text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                                We only need your province, city or municipality, and barangay for this
                                coverage check. Your complete installation address will be collected
                                during the service application.
                            </p>

                        </div>

                    </div>


                    {{-- Actions --}}
                    <div class="border-t border-neutral-200 pt-6 dark:border-neutral-800">

                        <button
                            type="submit"
                            class="
                                inline-flex min-h-12 w-full
                                items-center justify-center
                                rounded-lg
                                bg-[#008080]
                                px-6 py-3
                                text-base font-semibold text-white
                                transition
                                hover:bg-[#006666]
                                focus:outline-none
                                focus:ring-2
                                focus:ring-[#008080]
                                focus:ring-offset-2
                                disabled:cursor-not-allowed
                                disabled:opacity-70
                                dark:focus:ring-offset-neutral-900
                            ">
                            Check Availability
                        </button>


                        <a
                            href="{{ route('home') }}"
                            class="
                                mt-3 inline-flex min-h-11 w-full
                                items-center justify-center gap-2
                                rounded-lg
                                text-sm font-medium text-neutral-600
                                transition
                                hover:bg-neutral-100
                                hover:text-neutral-950
                                focus:outline-none
                                focus:ring-2
                                focus:ring-neutral-400
                                dark:text-neutral-400
                                dark:hover:bg-neutral-800
                                dark:hover:text-white
                            ">

                            <i
                                data-lucide="arrow-left"
                                class="h-4 w-4">
                            </i>

                            Back to Home

                        </a>

                    </div>

                </form>

            </section>


            {{-- Coverage map --}}
            <section
                class="bg-transparent"
                aria-labelledby="coverage-map-heading">

                <div class="pb-4">

                    <div class="flex flex-col gap-4">

                        <div>

                            <p class="text-xs font-semibold uppercase tracking-wide text-[#008080] dark:text-teal-400">
                                Coverage Visualization
                            </p>

                            <h2
                                id="coverage-map-heading"
                                class="mt-1 text-lg font-semibold text-neutral-950 dark:text-white">
                                Rincomm Service Area Map
                            </h2>

                            <p class="mt-1 max-w-2xl text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                                View registered Rincomm service areas on the map.
                                Final availability is verified using the installation location you provide.
                            </p>

                        </div>


                        {{-- Map legend --}}
                        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs font-medium text-neutral-700 dark:text-neutral-300">

                            <div class="inline-flex items-center gap-2">

                                <span
                                    class="h-3 w-3 rounded-full bg-emerald-500"
                                    aria-hidden="true">
                                </span>

                                Serviceable

                            </div>


                            <div class="inline-flex items-center gap-2">

                                <span
                                    class="h-3 w-3 rounded-full bg-neutral-400"
                                    aria-hidden="true">
                                </span>

                                Not Serviceable

                            </div>

                        </div>

                    </div>

                </div>


                <div
                    class="
                        relative overflow-hidden
                        rounded-xl
                        border border-neutral-200
                        dark:border-neutral-800
                    ">

                    <div
                        id="coverage-map"
                        class="h-[360px] w-full bg-neutral-100 sm:h-[420px] lg:h-[430px] dark:bg-neutral-950"
                        role="region"
                        aria-label="Rincomm service coverage map">
                    </div>


                    @if ($coverageAreas->isEmpty())

                    <div
                        id="coverage-map-empty"
                        class="
                                pointer-events-none
                                absolute inset-x-4 bottom-4
                                z-[500]
                                rounded-xl
                                border border-neutral-200
                                bg-white/95
                                px-4 py-3
                                shadow-sm
                                backdrop-blur-sm
                                dark:border-neutral-700
                                dark:bg-neutral-900/95
                            ">

                        <div class="flex items-start gap-3">

                            <i
                                data-lucide="map-pinned"
                                class="mt-0.5 h-5 w-5 shrink-0 text-neutral-500 dark:text-neutral-400">
                            </i>


                            <div>

                                <p class="text-sm font-semibold text-neutral-900 dark:text-white">
                                    No service areas have been plotted yet
                                </p>

                                <p class="mt-1 text-xs leading-5 text-neutral-600 dark:text-neutral-400">
                                    Coverage markers will appear here after verified Rincomm service areas are added.
                                </p>

                            </div>

                        </div>

                    </div>

                    @endif

                </div>

            </section>

        </div>


        {{-- Map data --}}
        <script
            id="coverage-map-data"
            type="application/json">
            @json($coverageAreas)
        </script>


        {{-- Existing account --}}
        <p class="mt-6 text-center text-sm text-neutral-600 dark:text-neutral-400">

            Already have a Rincomm account?

            <a
                href="{{ route('login') }}"
                class="
                    font-semibold text-[#008080]
                    underline underline-offset-4
                    transition
                    hover:text-[#006666]
                    dark:text-teal-400
                    dark:hover:text-teal-300
                ">
                Sign In
            </a>

        </p>

    </main>

</body>

</html>