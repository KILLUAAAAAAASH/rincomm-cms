<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Login - Rincomm</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
    class="
        h-dvh overflow-hidden
        bg-neutral-100 text-neutral-900
        dark:bg-neutral-950 dark:text-neutral-100
    ">

    <div class="relative flex h-full flex-col">

        {{-- Theme toggle --}}
        <div class="absolute right-4 top-4 z-10 sm:right-6 sm:top-6">

            <button
                type="button"
                data-theme-toggle
                class="
                    inline-flex h-10 w-10
                    items-center justify-center
                    rounded-xl
                    border border-neutral-300
                    bg-white
                    text-neutral-700
                    shadow-sm
                    transition
                    hover:border-[#008080]
                    hover:text-[#008080]
                    dark:border-neutral-700
                    dark:bg-neutral-900
                    dark:text-neutral-200
                "
                aria-label="Toggle theme">
                <i
                    data-lucide="moon"
                    class="h-5 w-5"
                    aria-hidden="true"></i>
            </button>

        </div>


        <main
            class="
                flex min-h-0 flex-1
                items-center justify-center
                px-4 py-3
                sm:px-6
            ">

            <div class="w-full max-w-lg">

                {{-- Login card --}}
                <div
                    class="
                        rounded-2xl
                        border border-neutral-200
                        bg-white
                        px-6 py-5
                        shadow-sm
                        dark:border-neutral-800
                        dark:bg-neutral-900
                        sm:px-8
                    ">

                    {{-- Heading --}}
                    <div class="text-center">

                        <h1 class="font-semibold text-neutral-900 dark:text-white">

                            <span class="block text-4xl leading-none sm:text-5xl">
                                Welcome
                            </span>

                            <span class="mt-2 block text-lg leading-tight sm:text-xl">
                                to Rincomm Internet Service Provider
                            </span>

                        </h1>

                        <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
                            Sign in to access your account.
                        </p>

                    </div>


                    {{-- Session status --}}
                    @if (session('status'))

                    <div
                        class="
                                mt-4 rounded-xl
                                border border-green-200
                                bg-green-50
                                px-4 py-2.5
                                text-sm text-green-700
                                dark:border-green-900
                                dark:bg-green-950/40
                                dark:text-green-300
                            "
                        role="status"
                        aria-live="polite">
                        {{ session('status') }}
                    </div>

                    @endif


                    {{-- Login form --}}
                    <form
                        method="POST"
                        action="{{ route('login') }}"
                        class="mt-5 space-y-4"
                        autocomplete="on"
                        data-lock-submit
                        novalidate>
                        @csrf


                        {{-- Username --}}
                        <div>

                            <label
                                for="email"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-neutral-800
                                    dark:text-neutral-200
                                ">
                                Username
                            </label>

                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="username"
                                autocapitalize="none"
                                spellcheck="false"
                                placeholder="you@example.com"
                                class="
                                    block min-h-11 w-full
                                    border border-neutral-300
                                    bg-white
                                    px-4 py-2.5
                                    text-sm text-neutral-900
                                    placeholder:text-neutral-400
                                    outline-none
                                    transition
                                    focus:border-[#008080]
                                    focus:ring-2
                                    focus:ring-[#008080]/20
                                    dark:border-neutral-700
                                    dark:bg-neutral-950
                                    dark:text-white
                                    dark:placeholder:text-neutral-500
                                ">

                            <x-field-error :message="$errors->first('email')" />

                        </div>


                        {{-- Password --}}
                        <div>

                            <label
                                for="password"
                                class="
                                    mb-1.5 block
                                    text-sm font-medium
                                    text-neutral-800
                                    dark:text-neutral-200
                                ">
                                Password
                            </label>

                            <div class="relative">

                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    required
                                    autocomplete="current-password"
                                    class="
                                        block min-h-11 w-full
                                        border border-neutral-300
                                        bg-white
                                        px-4 py-2.5 pr-12
                                        text-sm text-neutral-900
                                        outline-none
                                        transition
                                        focus:border-[#008080]
                                        focus:ring-2
                                        focus:ring-[#008080]/20
                                        dark:border-neutral-700
                                        dark:bg-neutral-950
                                        dark:text-white
                                    ">

                                <button
                                    type="button"
                                    data-password-toggle
                                    data-target="password"
                                    class="
                                        absolute inset-y-0 right-0
                                        inline-flex w-12
                                        items-center justify-center
                                        text-neutral-500
                                        transition
                                        hover:text-[#008080]
                                        dark:text-neutral-400
                                    "
                                    aria-label="Toggle password visibility">
                                    <i
                                        data-lucide="eye"
                                        class="h-5 w-5"
                                        aria-hidden="true"></i>
                                </button>

                            </div>

                            <x-field-error :message="$errors->first('password')" />

                        </div>


                        {{-- Remember me --}}
                        <div class="flex items-center">

                            <label
                                class="
                                    inline-flex items-center gap-2.5
                                    text-sm text-neutral-700
                                    dark:text-neutral-300
                                ">

                                <input
                                    id="remember"
                                    name="remember"
                                    type="checkbox"
                                    class="
                                        h-4 w-4 rounded
                                        border-neutral-300
                                        text-[#008080]
                                        focus:ring-[#008080]/30
                                        dark:border-neutral-700
                                        dark:bg-neutral-900
                                    "
                                    {{ old('remember') ? 'checked' : '' }}>

                                <span>
                                    Remember me
                                </span>

                            </label>

                        </div>


                        {{-- Sign in and password recovery --}}
                        <div class="space-y-2">

                            <button
                                type="submit"
                                class="
                                    inline-flex min-h-11 w-full
                                    items-center justify-center
                                    rounded-xl
                                    bg-[#008080]
                                    px-4 py-2.5
                                    text-sm font-semibold
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


                            @if (Route::has('password.request'))

                            <div class="flex justify-end">

                                <a
                                    href="{{ route('password.request') }}"
                                    class="
                                            text-sm font-medium
                                            text-[#008080]
                                            underline-offset-4
                                            transition
                                            hover:underline
                                        ">
                                    Forgot Password?
                                </a>

                            </div>

                            @endif

                        </div>


                        {{-- Registration and home --}}
                        <div
                            class="
                                flex flex-wrap
                                items-center justify-center
                                gap-2
                                pt-5
                                text-sm
                            ">

                            @if (Route::has('register'))

                            <span class="text-neutral-600 dark:text-neutral-400">
                                Don't have an account?
                            </span>

                            <a
                                href="{{ route('register') }}"
                                class="
                                        font-medium
                                        text-[#008080]
                                        underline-offset-4
                                        transition
                                        hover:underline
                                    ">
                                Sign Up
                            </a>

                            <span
                                class="mx-1 text-neutral-400 dark:text-neutral-600"
                                aria-hidden="true">
                                |
                            </span>

                            @endif


                            <a
                                href="{{ url('/') }}"
                                class="
                                    font-medium
                                    text-[#008080]
                                    underline-offset-4
                                    transition
                                    hover:underline
                                ">
                                Back to Home
                            </a>

                        </div>

                    </form>

                </div>


                {{-- Footer --}}
                <p class="mt-3 text-center text-sm text-neutral-500 dark:text-neutral-400">
                    © 2026 Rincomm Internet Service Provider
                </p>

            </div>

        </main>

    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButtons = document.querySelectorAll('[data-password-toggle]');

            toggleButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const targetId = button.getAttribute('data-target');
                    const input = document.getElementById(targetId);

                    if (!input) {
                        return;
                    }

                    input.type = input.type === 'password' ?
                        'text' :
                        'password';
                });
            });
        });
    </script>

</body>

</html>