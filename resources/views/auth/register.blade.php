<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register - Rincomm</title>

    <script>
        (() => {
            const savedTheme = localStorage.getItem('rincomm-theme');

            const useDarkTheme =
                savedTheme === 'dark' ||
                (
                    !savedTheme &&
                    window.matchMedia('(prefers-color-scheme: dark)').matches
                );

            document.documentElement.classList.toggle(
                'dark',
                useDarkTheme
            );
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
    class="
        min-h-dvh
        bg-neutral-100
        text-neutral-900
        transition-colors
        dark:bg-neutral-950
        dark:text-neutral-100
    ">

    {{-- Theme Toggle --}}
    <button
        type="button"
        data-theme-toggle
        class="
            fixed right-4 top-4 z-30
            inline-flex h-10 w-10
            items-center justify-center
            border border-neutral-300
            bg-white
            text-neutral-600
            shadow-sm
            transition
            hover:border-[#008080]
            hover:text-[#008080]
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


    <div
        class="
            flex min-h-dvh
            items-center justify-center
            px-4 py-3
            sm:px-6 sm:py-5
        ">

        <div class="w-full max-w-xl">

            <div
                class="
                    border border-neutral-200
                    bg-white
                    p-4
                    shadow-md
                    transition-colors
                    dark:border-neutral-800
                    dark:bg-neutral-900
                    sm:p-6
                ">

                {{-- Company Logo --}}
                <div
                    class="
                        mb-1 flex h-20
                        items-center justify-center
                    ">

                    <a
                        href="{{ route('home') }}"
                        class="
                            inline-flex h-20 w-56
                            items-center justify-center
                        "
                        aria-label="Rincomm Home">

                        <img
                            src="{{ asset('images/rincomm-logo.svg') }}"
                            alt="Rincomm Logo"
                            class="
                                pointer-events-none
                                h-52 w-52
                                max-w-none
                                -translate-y-4
                                object-contain
                            ">

                    </a>

                </div>


                {{-- Header --}}
                <div class="mb-4 text-center">

                    <h1
                        class="
                            text-2xl font-semibold
                            tracking-tight
                            text-neutral-900
                            dark:text-white
                            sm:text-3xl
                        ">
                        Create your account
                    </h1>

                    <p
                        class="
                            mt-1 text-sm
                            text-neutral-500
                            dark:text-neutral-400
                        ">
                        Register to access the Rincomm customer portal.
                    </p>

                </div>


                {{-- Registration Form --}}
                <form
                    method="POST"
                    action="{{ route('register.store') }}"
                    data-lock-submit
                    novalidate
                    class="space-y-3">

                    @csrf


                    {{-- Preserve coverage-first application flow --}}
                    @if (
                    (request()->boolean('apply') || old('application_flow') === '1') &&
                    session()->has('service_application.coverage') &&
                    session()->has('service_application.plan_id')
                    )
                    <input
                        type="hidden"
                        name="application_flow"
                        value="1">
                    @endif


                    {{-- First Name + Last Name --}}
                    <div
                        class="
                            grid grid-cols-1
                            gap-3
                            sm:grid-cols-2
                        ">

                        {{-- First Name --}}
                        <div>

                            <label
                                for="first_name"
                                class="
                                    mb-1 block
                                    text-sm font-medium
                                    text-neutral-700
                                    dark:text-neutral-300
                                ">
                                First Name
                            </label>

                            <input
                                id="first_name"
                                name="first_name"
                                type="text"
                                value="{{ old('first_name') }}"
                                autocomplete="given-name"
                                aria-invalid="{{ $errors->has('first_name') ? 'true' : 'false' }}"
                                aria-describedby="{{ $errors->has('first_name') ? 'first-name-error' : '' }}"
                                class="
                                    block w-full
                                    bg-white
                                    px-4 py-2.5
                                    text-base
                                    text-neutral-900
                                    outline-none
                                    transition
                                    placeholder:text-neutral-400

                                    {{ $errors->has('first_name')
                                        ? 'border border-red-500 focus:border-red-500 focus:ring-1 focus:ring-red-500 dark:border-red-500'
                                        : 'border border-neutral-300 focus:border-[#008080] focus:ring-1 focus:ring-[#008080] dark:border-neutral-700'
                                    }}

                                    dark:bg-neutral-950
                                    dark:text-neutral-100
                                    dark:placeholder:text-neutral-500
                                ">

                            <x-field-error
                                id="first-name-error"
                                :message="$errors->first('first_name')" />

                        </div>


                        {{-- Last Name --}}
                        <div>

                            <label
                                for="last_name"
                                class="
                                    mb-1 block
                                    text-sm font-medium
                                    text-neutral-700
                                    dark:text-neutral-300
                                ">
                                Last Name
                            </label>

                            <input
                                id="last_name"
                                name="last_name"
                                type="text"
                                value="{{ old('last_name') }}"
                                autocomplete="family-name"
                                aria-invalid="{{ $errors->has('last_name') ? 'true' : 'false' }}"
                                aria-describedby="{{ $errors->has('last_name') ? 'last-name-error' : '' }}"
                                class="
                                    block w-full
                                    bg-white
                                    px-4 py-2.5
                                    text-base
                                    text-neutral-900
                                    outline-none
                                    transition
                                    placeholder:text-neutral-400

                                    {{ $errors->has('last_name')
                                        ? 'border border-red-500 focus:border-red-500 focus:ring-1 focus:ring-red-500 dark:border-red-500'
                                        : 'border border-neutral-300 focus:border-[#008080] focus:ring-1 focus:ring-[#008080] dark:border-neutral-700'
                                    }}

                                    dark:bg-neutral-950
                                    dark:text-neutral-100
                                    dark:placeholder:text-neutral-500
                                ">

                            <x-field-error
                                id="last-name-error"
                                :message="$errors->first('last_name')" />

                        </div>

                    </div>


                    {{-- Email --}}
                    <div>

                        <label
                            for="email"
                            class="
                                mb-1 block
                                text-sm font-medium
                                text-neutral-700
                                dark:text-neutral-300
                            ">
                            Email Address
                        </label>

                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            autocomplete="email"
                            placeholder="you@example.com"
                            aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                            aria-describedby="{{ $errors->has('email') ? 'email-error' : '' }}"
                            class="
                                block w-full
                                bg-white
                                px-4 py-2.5
                                text-base
                                text-neutral-900
                                outline-none
                                transition
                                placeholder:text-neutral-400

                                {{ $errors->has('email')
                                    ? 'border border-red-500 focus:border-red-500 focus:ring-1 focus:ring-red-500 dark:border-red-500'
                                    : 'border border-neutral-300 focus:border-[#008080] focus:ring-1 focus:ring-[#008080] dark:border-neutral-700'
                                }}

                                dark:bg-neutral-950
                                dark:text-neutral-100
                                dark:placeholder:text-neutral-500
                            ">

                        <x-field-error
                            id="email-error"
                            :message="$errors->first('email')" />

                    </div>


                    {{-- Password + Confirm Password --}}
                    <div
                        class="
                            grid grid-cols-1
                            gap-3
                            sm:grid-cols-2
                        ">

                        {{-- Password --}}
                        <div>

                            <label
                                for="password"
                                class="
                                    mb-1 block
                                    text-sm font-medium
                                    text-neutral-700
                                    dark:text-neutral-300
                                ">
                                Password
                            </label>

                            <div class="relative">

                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    autocomplete="new-password"
                                    aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                                    aria-describedby="{{ $errors->has('password') ? 'password-error' : '' }}"
                                    class="
                                        block w-full
                                        bg-white
                                        py-2.5 pl-4 pr-12
                                        text-base
                                        text-neutral-900
                                        outline-none
                                        transition

                                        {{ $errors->has('password')
                                            ? 'border border-red-500 focus:border-red-500 focus:ring-1 focus:ring-red-500 dark:border-red-500'
                                            : 'border border-neutral-300 focus:border-[#008080] focus:ring-1 focus:ring-[#008080] dark:border-neutral-700'
                                        }}

                                        dark:bg-neutral-950
                                        dark:text-neutral-100
                                    ">

                                <button
                                    type="button"
                                    data-password-toggle
                                    data-password-target="password"
                                    aria-label="Show password"
                                    class="
                                        absolute inset-y-0 right-0
                                        flex w-11
                                        items-center justify-center
                                        text-neutral-600
                                        transition
                                        hover:text-[#008080]
                                        dark:text-neutral-300
                                        dark:hover:text-teal-400
                                    ">

                                    <i
                                        data-lucide="eye"
                                        class="h-5 w-5">
                                    </i>

                                </button>

                            </div>

                            <p
                                class="
                                    mt-1 text-xs
                                    text-neutral-500
                                    dark:text-neutral-400
                                ">
                                At least 8 characters.
                            </p>

                            <x-field-error
                                id="password-error"
                                :message="$errors->first('password')" />

                        </div>


                        {{-- Confirm Password --}}
                        <div>

                            <label
                                for="password_confirmation"
                                class="
                                    mb-1 block
                                    text-sm font-medium
                                    text-neutral-700
                                    dark:text-neutral-300
                                ">
                                Confirm Password
                            </label>

                            <div class="relative">

                                <input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    autocomplete="new-password"
                                    aria-invalid="{{ $errors->has('password_confirmation') ? 'true' : 'false' }}"
                                    aria-describedby="{{ $errors->has('password_confirmation') ? 'password-confirmation-error' : '' }}"
                                    class="
                                        block w-full
                                        bg-white
                                        py-2.5 pl-4 pr-12
                                        text-base
                                        text-neutral-900
                                        outline-none
                                        transition

                                        {{ $errors->has('password_confirmation')
                                            ? 'border border-red-500 focus:border-red-500 focus:ring-1 focus:ring-red-500 dark:border-red-500'
                                            : 'border border-neutral-300 focus:border-[#008080] focus:ring-1 focus:ring-[#008080] dark:border-neutral-700'
                                        }}

                                        dark:bg-neutral-950
                                        dark:text-neutral-100
                                    ">

                                <button
                                    type="button"
                                    data-password-toggle
                                    data-password-target="password_confirmation"
                                    aria-label="Show password"
                                    class="
                                        absolute inset-y-0 right-0
                                        flex w-11
                                        items-center justify-center
                                        text-neutral-600
                                        transition
                                        hover:text-[#008080]
                                        dark:text-neutral-300
                                        dark:hover:text-teal-400
                                    ">

                                    <i
                                        data-lucide="eye"
                                        class="h-5 w-5">
                                    </i>

                                </button>

                            </div>

                            <x-field-error
                                id="password-confirmation-error"
                                :message="$errors->first('password_confirmation')" />

                        </div>

                    </div>


                    {{-- Submit --}}
                    <button
                        type="submit"
                        data-loading-text="Creating account..."
                        class="
                            inline-flex w-full
                            items-center justify-center
                            bg-[#008080]
                            px-5 py-2.5
                            text-base font-semibold
                            text-white
                            shadow-sm
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
                        Create Account
                    </button>


                    {{-- Account Navigation --}}
<div class="text-center">

    <p
        class="
            text-sm
            text-neutral-600
            dark:text-neutral-400
        ">

        Already have an account?

        <a
            href="{{ route('login') }}"
            class="
                ml-1 font-semibold
                text-[#008080]
                underline underline-offset-4
                transition
                hover:text-[#006666]
                dark:text-teal-400
                dark:hover:text-teal-300
            ">
            Sign In
        </a>

    </p>


    <div class="mt-2">

        <a
            href="{{ route('home') }}"
            class="
                text-sm font-medium
                text-[#008080]
                underline underline-offset-4
                transition
                hover:text-[#006666]
                dark:text-teal-400
                dark:hover:text-teal-300
            ">
            Back to Home
        </a>

    </div>

</div>

                </form>

            </div>


            {{-- Footer --}}
            <p
                class="
                    mt-2 text-center
                    text-xs
                    text-neutral-500
                    dark:text-neutral-500
                ">
                &copy; {{ date('Y') }} Rincomm Internet Service Provider
            </p>

        </div>

    </div>


    {{-- Password Visibility --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggleButtons =
                document.querySelectorAll('[data-password-toggle]');

            toggleButtons.forEach((toggleButton) => {
                toggleButton.addEventListener('click', () => {
                    const targetId =
                        toggleButton.dataset.passwordTarget;

                    const passwordInput =
                        document.getElementById(targetId);

                    if (!passwordInput) {
                        return;
                    }

                    const isVisible =
                        passwordInput.type === 'text';

                    passwordInput.type =
                        isVisible ? 'password' : 'text';

                    const label =
                        isVisible ?
                        'Show password' :
                        'Hide password';

                    toggleButton.setAttribute(
                        'aria-label',
                        label
                    );

                    toggleButton.setAttribute(
                        'title',
                        label
                    );
                });
            });
        });
    </script>

</body>

</html>