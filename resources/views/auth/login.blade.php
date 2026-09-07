<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - Rincomm</title>

    <script>
        (() => {
            const savedTheme = localStorage.getItem('rincomm-theme');

            const useDarkTheme = savedTheme === 'dark' ||
                (!savedTheme &&
                    window.matchMedia('(prefers-color-scheme: dark)').matches);

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
            px-4 py-4
            sm:px-6 sm:py-5
        ">

        <div class="w-full max-w-lg">

            <div
                class="
                    border border-neutral-200
                    bg-white
                    p-5
                    shadow-md
                    transition-colors
                    dark:border-neutral-800
                    dark:bg-neutral-900
                    sm:p-6
                ">

                {{-- Company Logo --}}
                <div
                    class="
                        mb-2 flex h-20
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
                                -translate-y-5
                                object-contain
                            ">

                    </a>

                </div>


                {{-- Header --}}
                <div class="mb-5 text-center">

                    <h1
                        class="
                            text-2xl font-semibold
                            tracking-tight
                            text-neutral-900
                            dark:text-white
                            sm:text-3xl
                        ">
                        Welcome to Rincomm
                    </h1>

                    <p
                        class="
                            mt-1.5 text-sm
                            text-neutral-500
                            dark:text-neutral-400
                            sm:text-base
                        ">
                        Sign in to access your account.
                    </p>

                </div>


                {{-- Login Form --}}
                <form
                    method="POST"
                    action="{{ route('login.store') }}"
                    data-lock-submit
                    novalidate
                    autocomplete="off"
                    class="space-y-4">

                    @csrf


                    {{-- Email --}}
                    <div>

                        <label
                            for="email"
                            class="
                                mb-1.5 block
                                text-sm font-medium
                                text-neutral-700
                                dark:text-neutral-300
                                sm:text-base
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
                                border border-neutral-300
                                bg-white
                                px-4 py-3
                                text-base
                                text-neutral-900
                                outline-none
                                transition
                                placeholder:text-neutral-400
                                focus:border-[#008080]
                                focus:ring-1
                                focus:ring-[#008080]
                                dark:border-neutral-700
                                dark:bg-neutral-950
                                dark:text-neutral-100
                                dark:placeholder:text-neutral-500
                            ">

                        <x-field-error
                            id="email-error"
                            :message="$errors->first('email')" />

                    </div>


                    {{-- Password --}}
                    <div>

                        <div
                            class="
                                mb-1.5 flex
                                items-center justify-between
                                gap-4
                            ">

                            <label
                                for="password"
                                class="
                                    text-sm font-medium
                                    text-neutral-700
                                    dark:text-neutral-300
                                    sm:text-base
                                ">
                                Password
                            </label>

                            <a
                                href="{{ url('/forgot-password') }}"
                                class="
                                    text-sm font-medium
                                    text-[#008080]
                                    underline underline-offset-2
                                    transition
                                    hover:text-[#006666]
                                    dark:text-teal-400
                                    dark:hover:text-teal-300
                                    sm:text-base
                                ">
                                Forgot Password?
                            </a>

                        </div>


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
                                    border border-neutral-300
                                    bg-white
                                    py-3 pl-4 pr-14
                                    text-base
                                    text-neutral-900
                                    outline-none
                                    transition
                                    focus:border-[#008080]
                                    focus:ring-1
                                    focus:ring-[#008080]
                                    dark:border-neutral-700
                                    dark:bg-neutral-950
                                    dark:text-neutral-100
                                ">

                            <button
                                type="button"
                                id="togglePassword"
                                aria-label="Show password"
                                class="
                                    absolute inset-y-0 right-0
                                    flex w-12
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
                            id="password-error"
                            :message="$errors->first('password')" />

                    </div>


                    {{-- Remember Me --}}
                    <div class="flex items-center">

                        <input
                            id="remember"
                            name="remember"
                            type="checkbox"
                            value="1"
                            class="
                                h-5 w-5
                                border-neutral-300
                                text-[#008080]
                                focus:ring-[#008080]
                                dark:border-neutral-700
                                dark:bg-neutral-950
                            ">

                        <label
                            for="remember"
                            class="
                                ml-2
                                text-sm
                                text-neutral-600
                                dark:text-neutral-400
                                sm:text-base
                            ">
                            Remember me
                        </label>

                    </div>


                    {{-- Sign In --}}
                    <button
                        type="submit"
                        data-loading-text="Signing in..."
                        class="
                            inline-flex w-full
                            items-center justify-center
                            gap-2
                            bg-[#008080]
                            px-5 py-3
                            text-base font-semibold
                            text-white
                            shadow-sm
                            transition
                            hover:bg-[#006666]
                            focus:outline-none
                            focus:ring-2
                            focus:ring-[#008080]
                            focus:ring-offset-2
                            dark:focus:ring-offset-neutral-900
                        ">
                        Sign In
                    </button>


                    {{-- Back to Home --}}
                    <div class="text-center">

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

                </form>

            </div>


            {{-- Footer --}}
            <p
                class="
                    mt-3 text-center
                    text-xs
                    text-neutral-500
                    dark:text-neutral-500
                    sm:text-sm
                ">
                &copy; {{ date('Y') }} Rincomm Internet Service Provider
            </p>

        </div>

    </div>


    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const passwordInput =
                document.getElementById('password');

            const toggleButton =
                document.getElementById('togglePassword');

            if (!passwordInput || !toggleButton) {
                return;
            }

            toggleButton.addEventListener('click', () => {
                const isVisible =
                    passwordInput.type === 'text';

                passwordInput.type =
                    isVisible ? 'password' : 'text';

                const label =
                    isVisible
                        ? 'Show password'
                        : 'Hide password';

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
    </script>

</body>

</html>