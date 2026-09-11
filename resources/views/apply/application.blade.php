<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Service Application - Rincomm</title>

    <script>
        (() => {
            const savedTheme = localStorage.getItem('rincomm-theme');

            if (
                savedTheme === 'dark' ||
                (!savedTheme &&
                    window.matchMedia('(prefers-color-scheme: dark)').matches)
            ) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-neutral-100 text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100">

    <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mb-8 flex items-start justify-between gap-4">

            <div>

                <p class="text-sm font-semibold text-[#008080] dark:text-teal-400">
                    Rincomm Service Application
                </p>

                <h1 class="mt-1 text-2xl font-semibold text-neutral-950 dark:text-white sm:text-3xl">
                    Complete Your Application
                </h1>

                <p class="mt-2 max-w-2xl text-sm leading-6 text-neutral-600 dark:text-neutral-400 sm:text-base">
                    Provide the remaining information needed for your Rincomm internet service application.
                </p>

            </div>


            {{-- Theme toggle --}}
            <button
                type="button"
                data-theme-toggle
                class="
                    inline-flex h-11 w-11 shrink-0
                    items-center justify-center
                    border border-neutral-300
                    bg-white text-neutral-700
                    transition
                    hover:bg-neutral-50
                    focus:outline-none
                    focus:ring-2
                    focus:ring-[#008080]
                    dark:border-neutral-700
                    dark:bg-neutral-900
                    dark:text-neutral-200
                    dark:hover:bg-neutral-800
                "
                aria-label="Switch theme">

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

        </div>


        {{-- Success message --}}
        @if (session('success'))

        <div
            role="status"
            class="
                    mb-6 flex items-start gap-3
                    border border-emerald-300
                    bg-emerald-50 px-4 py-3
                    text-sm text-emerald-900
                    dark:border-emerald-800
                    dark:bg-emerald-950/40
                    dark:text-emerald-200
                ">

            <i
                data-lucide="circle-check"
                class="mt-0.5 h-5 w-5 shrink-0">
            </i>

            <span>
                {{ session('success') }}
            </span>

        </div>

        @endif


        {{-- Application summary --}}
        <section
            class="
                mb-6 border border-neutral-200
                bg-white p-5 shadow-sm
                dark:border-neutral-800
                dark:bg-neutral-900
                sm:p-6
            ">

            <div class="mb-5">

                <h2 class="text-lg font-semibold text-neutral-950 dark:text-white">
                    Application Summary
                </h2>

                <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                    Your selected plan and verified coverage location.
                </p>

            </div>


            <div class="grid gap-5 sm:grid-cols-2">

                {{-- Selected plan --}}
                <div>

                    <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">
                        Selected Internet Plan
                    </p>

                    <p class="mt-1 text-base font-semibold text-neutral-950 dark:text-white">
                        {{ $servicePlan->name }}
                    </p>

                    <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                        {{ number_format($servicePlan->speed_mbps, 0) }} Mbps
                        &middot;
                        &#8369;{{ number_format($servicePlan->monthly_fee, 2) }}/month
                    </p>

                </div>


                {{-- Service area --}}
                <div>

                    <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">
                        Verified Service Area
                    </p>

                    <p class="mt-1 text-base font-semibold text-neutral-950 dark:text-white">
                        {{ $serviceArea->barangay }},
                        {{ $serviceArea->city_municipality }},
                        {{ $serviceArea->province }}
                    </p>

                    @if ($serviceArea->postal_code)

                    <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                        Postal Code: {{ $serviceArea->postal_code }}
                    </p>

                    @endif

                </div>

            </div>

        </section>


        {{-- Applicant data --}}
        @php
        $applicant = session('service_application.applicant', []);
        @endphp


        {{-- Application form --}}
        <form
            method="POST"
            action="{{ route('customer.application.store') }}"
            data-lock-submit
            novalidate
            class="space-y-6">

            @csrf


            {{-- Application error --}}
            @if ($errors->has('application'))

            <div
                role="alert"
                class="
                        flex items-start gap-3
                        border border-red-300
                        bg-red-50 px-4 py-3
                        text-sm text-red-800
                        dark:border-red-900
                        dark:bg-red-950/40
                        dark:text-red-200
                    ">

                <i
                    data-lucide="triangle-alert"
                    class="mt-0.5 h-5 w-5 shrink-0">
                </i>

                <p>
                    {{ $errors->first('application') }}
                </p>

            </div>

            @endif


            {{-- Applicant information --}}
            <section
                class="
                    border border-neutral-200
                    bg-white p-5 shadow-sm
                    dark:border-neutral-800
                    dark:bg-neutral-900
                    sm:p-6
                ">

                <div class="mb-6">

                    <h2 class="text-lg font-semibold text-neutral-950 dark:text-white">
                        Applicant Information
                    </h2>

                    <p class="mt-1 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                        Review your personal and contact information before submitting your application.
                    </p>

                </div>


                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                    {{-- First name --}}
                    <div>

                        <label
                            for="first_name"
                            class="mb-2 block text-sm font-medium text-neutral-800 dark:text-neutral-300">

                            First Name

                            <span
                                class="text-red-600"
                                aria-hidden="true">
                                *
                            </span>

                        </label>

                        <input
                            id="first_name"
                            name="first_name"
                            type="text"
                            value="{{ old('first_name', $applicant['first_name'] ?? '') }}"
                            autocomplete="given-name"
                            aria-invalid="{{ $errors->has('first_name') ? 'true' : 'false' }}"
                            aria-describedby="{{ $errors->has('first_name') ? 'first-name-error' : '' }}"
                            class="
                                block min-h-11 w-full
                                bg-white px-4 py-3
                                text-base text-neutral-950
                                outline-none transition

                                {{ $errors->has('first_name')
                                    ? 'border border-red-500 ring-1 ring-red-500/20 focus:border-red-500 focus:ring-red-500'
                                    : 'border border-neutral-300 focus:border-[#008080] focus:ring-1 focus:ring-[#008080] dark:border-neutral-700'
                                }}

                                dark:bg-neutral-950
                                dark:text-white
                            ">

                        <x-field-error
                            id="first-name-error"
                            :message="$errors->first('first_name')" />

                    </div>


                    {{-- Middle name --}}
                    <div>

                        <label
                            for="middle_name"
                            class="mb-2 block text-sm font-medium text-neutral-800 dark:text-neutral-300">

                            Middle Name

                            <span class="font-normal text-neutral-500 dark:text-neutral-500">
                                (Optional)
                            </span>

                        </label>

                        <input
                            id="middle_name"
                            name="middle_name"
                            type="text"
                            value="{{ old('middle_name') }}"
                            autocomplete="additional-name"
                            aria-invalid="{{ $errors->has('middle_name') ? 'true' : 'false' }}"
                            aria-describedby="{{ $errors->has('middle_name') ? 'middle-name-error' : '' }}"
                            class="
                                block min-h-11 w-full
                                bg-white px-4 py-3
                                text-base text-neutral-950
                                outline-none transition

                                {{ $errors->has('middle_name')
                                    ? 'border border-red-500 ring-1 ring-red-500/20 focus:border-red-500 focus:ring-red-500'
                                    : 'border border-neutral-300 focus:border-[#008080] focus:ring-1 focus:ring-[#008080] dark:border-neutral-700'
                                }}

                                dark:bg-neutral-950
                                dark:text-white
                            ">

                        <x-field-error
                            id="middle-name-error"
                            :message="$errors->first('middle_name')" />

                    </div>


                    {{-- Last name --}}
                    <div>

                        <label
                            for="last_name"
                            class="mb-2 block text-sm font-medium text-neutral-800 dark:text-neutral-300">

                            Last Name

                            <span
                                class="text-red-600"
                                aria-hidden="true">
                                *
                            </span>

                        </label>

                        <input
                            id="last_name"
                            name="last_name"
                            type="text"
                            value="{{ old('last_name', $applicant['last_name'] ?? '') }}"
                            autocomplete="family-name"
                            aria-invalid="{{ $errors->has('last_name') ? 'true' : 'false' }}"
                            aria-describedby="{{ $errors->has('last_name') ? 'last-name-error' : '' }}"
                            class="
                                block min-h-11 w-full
                                bg-white px-4 py-3
                                text-base text-neutral-950
                                outline-none transition

                                {{ $errors->has('last_name')
                                    ? 'border border-red-500 ring-1 ring-red-500/20 focus:border-red-500 focus:ring-red-500'
                                    : 'border border-neutral-300 focus:border-[#008080] focus:ring-1 focus:ring-[#008080] dark:border-neutral-700'
                                }}

                                dark:bg-neutral-950
                                dark:text-white
                            ">

                        <x-field-error
                            id="last-name-error"
                            :message="$errors->first('last_name')" />

                    </div>


                    {{-- Phone --}}
                    <div>

                        <label
                            for="phone"
                            class="mb-2 block text-sm font-medium text-neutral-800 dark:text-neutral-300">

                            Phone Number

                            <span
                                class="text-red-600"
                                aria-hidden="true">
                                *
                            </span>

                        </label>

                        <input
                            id="phone"
                            name="phone"
                            type="tel"
                            value="{{ old('phone') }}"
                            autocomplete="tel"
                            placeholder="Example: 09171234567"
                            aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}"
                            aria-describedby="{{ $errors->has('phone') ? 'phone-error' : '' }}"
                            class="
                                block min-h-11 w-full
                                bg-white px-4 py-3
                                text-base text-neutral-950
                                outline-none transition
                                placeholder:text-neutral-400

                                {{ $errors->has('phone')
                                    ? 'border border-red-500 ring-1 ring-red-500/20 focus:border-red-500 focus:ring-red-500'
                                    : 'border border-neutral-300 focus:border-[#008080] focus:ring-1 focus:ring-[#008080] dark:border-neutral-700'
                                }}

                                dark:bg-neutral-950
                                dark:text-white
                                dark:placeholder:text-neutral-500
                            ">

                        <x-field-error
                            id="phone-error"
                            :message="$errors->first('phone')" />

                    </div>


                    {{-- Email --}}
                    <div class="sm:col-span-2">

                        <label
                            for="email"
                            class="mb-2 block text-sm font-medium text-neutral-800 dark:text-neutral-300">

                            Email Address

                            <span
                                class="text-red-600"
                                aria-hidden="true">
                                *
                            </span>

                        </label>

                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email', $applicant['email'] ?? auth()->user()->email) }}"
                            autocomplete="email"
                            aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                            aria-describedby="{{ $errors->has('email') ? 'email-error' : '' }}"
                            class="
                                block min-h-11 w-full
                                bg-white px-4 py-3
                                text-base text-neutral-950
                                outline-none transition

                                {{ $errors->has('email')
                                    ? 'border border-red-500 ring-1 ring-red-500/20 focus:border-red-500 focus:ring-red-500'
                                    : 'border border-neutral-300 focus:border-[#008080] focus:ring-1 focus:ring-[#008080] dark:border-neutral-700'
                                }}

                                dark:bg-neutral-950
                                dark:text-white
                            ">

                        <x-field-error
                            id="email-error"
                            :message="$errors->first('email')" />

                    </div>

                </div>

            </section>


            {{-- Installation address --}}
            <section
                class="
                    border border-neutral-200
                    bg-white p-5 shadow-sm
                    dark:border-neutral-800
                    dark:bg-neutral-900
                    sm:p-6
                ">

                <div class="mb-6">

                    <h2 class="text-lg font-semibold text-neutral-950 dark:text-white">
                        Installation Address
                    </h2>

                    <p class="mt-1 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                        Provide the detailed location where Rincomm service will be installed.
                    </p>

                </div>


                {{-- Verified area --}}
                <div
                    class="
                        mb-5 bg-neutral-50
                        px-4 py-3
                        text-sm text-neutral-700
                        dark:bg-neutral-950
                        dark:text-neutral-300
                    ">

                    <span class="font-medium">
                        Verified area:
                    </span>

                    {{ $serviceArea->barangay }},
                    {{ $serviceArea->city_municipality }},
                    {{ $serviceArea->province }}

                    @if ($serviceArea->postal_code)
                    {{ $serviceArea->postal_code }}
                    @endif

                </div>


                <div>

                    <label
                        for="installation_address"
                        class="mb-2 block text-sm font-medium text-neutral-800 dark:text-neutral-300">

                        Detailed Installation Address

                        <span
                            class="text-red-600"
                            aria-hidden="true">
                            *
                        </span>

                    </label>

                    <textarea
                        id="installation_address"
                        name="installation_address"
                        rows="4"
                        autocomplete="street-address"
                        placeholder="House number, street, subdivision, purok or sitio, and nearby landmark"
                        aria-invalid="{{ $errors->has('installation_address') ? 'true' : 'false' }}"
                        aria-describedby="{{ $errors->has('installation_address') ? 'installation-address-error' : 'installation-address-help' }}"
                        class="
                            block w-full resize-y
                            bg-white px-4 py-3
                            text-base text-neutral-950
                            outline-none transition
                            placeholder:text-neutral-400

                            {{ $errors->has('installation_address')
                                ? 'border border-red-500 ring-1 ring-red-500/20 focus:border-red-500 focus:ring-red-500'
                                : 'border border-neutral-300 focus:border-[#008080] focus:ring-1 focus:ring-[#008080] dark:border-neutral-700'
                            }}

                            dark:bg-neutral-950
                            dark:text-white
                            dark:placeholder:text-neutral-500
                        ">{{ old('installation_address') }}</textarea>

                    <p
                        id="installation-address-help"
                        class="mt-2 text-sm leading-5 text-neutral-500 dark:text-neutral-400">
                        You do not need to re-enter your barangay, city, or province because the coverage area has already been verified.
                    </p>

                    <x-field-error
                        id="installation-address-error"
                        :message="$errors->first('installation_address')" />

                </div>

            </section>


            {{-- Billing address --}}
            <section
                class="
                    border border-neutral-200
                    bg-white p-5 shadow-sm
                    dark:border-neutral-800
                    dark:bg-neutral-900
                    sm:p-6
                ">

                <div class="mb-6">

                    <h2 class="text-lg font-semibold text-neutral-950 dark:text-white">
                        Billing Address
                    </h2>

                    <p class="mt-1 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                        Enter a billing address only when it is different from the installation address.
                    </p>

                </div>


                <div>

                    <label
                        for="billing_address"
                        class="mb-2 block text-sm font-medium text-neutral-800 dark:text-neutral-300">

                        Billing Address

                        <span class="font-normal text-neutral-500 dark:text-neutral-500">
                            (Optional)
                        </span>

                    </label>

                    <textarea
                        id="billing_address"
                        name="billing_address"
                        rows="4"
                        autocomplete="street-address"
                        placeholder="Leave blank if your billing address is the same as your installation address"
                        aria-invalid="{{ $errors->has('billing_address') ? 'true' : 'false' }}"
                        aria-describedby="{{ $errors->has('billing_address') ? 'billing-address-error' : '' }}"
                        class="
                            block w-full resize-y
                            bg-white px-4 py-3
                            text-base text-neutral-950
                            outline-none transition
                            placeholder:text-neutral-400

                            {{ $errors->has('billing_address')
                                ? 'border border-red-500 ring-1 ring-red-500/20 focus:border-red-500 focus:ring-red-500'
                                : 'border border-neutral-300 focus:border-[#008080] focus:ring-1 focus:ring-[#008080] dark:border-neutral-700'
                            }}

                            dark:bg-neutral-950
                            dark:text-white
                            dark:placeholder:text-neutral-500
                        ">{{ old('billing_address') }}</textarea>

                    <x-field-error
                        id="billing-address-error"
                        :message="$errors->first('billing_address')" />

                </div>

            </section>


            {{-- Submission note --}}
            <div
                class="
                    border border-neutral-200
                    bg-neutral-50 px-4 py-3
                    text-sm leading-6 text-neutral-600
                    dark:border-neutral-800
                    dark:bg-neutral-900
                    dark:text-neutral-400
                ">

                Submitting this form sends your application to Rincomm for review.
                Your internet service is not activated until the application and installation process are completed.

            </div>


            {{-- Actions --}}
            <div
                class="
                    flex flex-col-reverse gap-3
                    border-t border-neutral-200 pt-6
                    sm:flex-row
                    sm:items-center
                    sm:justify-between
                    dark:border-neutral-800
                ">

                <a
                    href="{{ route('customer.dashboard') }}"
                    class="
                        inline-flex min-h-11
                        items-center justify-center gap-2
                        border border-neutral-300
                        bg-white px-5 py-3
                        text-sm font-semibold text-neutral-800
                        transition
                        hover:bg-neutral-50
                        focus:outline-none
                        focus:ring-2
                        focus:ring-[#008080]
                        dark:border-neutral-700
                        dark:bg-neutral-900
                        dark:text-neutral-200
                        dark:hover:bg-neutral-800
                    ">

                    <i
                        data-lucide="arrow-left"
                        class="h-4 w-4">
                    </i>

                    Customer Portal

                </a>


                <button
                    type="submit"
                    class="
                        inline-flex min-h-11
                        items-center justify-center
                        bg-[#008080] px-6 py-3
                        text-sm font-semibold text-white
                        transition
                        hover:bg-[#006666]
                        focus:outline-none
                        focus:ring-2
                        focus:ring-[#008080]
                        focus:ring-offset-2
                        disabled:cursor-not-allowed
                        disabled:opacity-60
                        dark:focus:ring-offset-neutral-950
                    ">
                    Submit Application
                </button>

            </div>

        </form>

    </main>

</body>

</html>