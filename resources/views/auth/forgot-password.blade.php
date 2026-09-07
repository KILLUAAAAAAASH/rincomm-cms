<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Forgot Password - Rincomm</title>

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
                <div class="mb-5 text-center">

                    <h1
                        class="
                            text-2xl font-semibold
                            tracking-tight
                            text-neutral-900
                            dark:text-white
                        ">
                        Forgot Password?
                    </h1>

                    <p
                        class="
                            mx-auto mt-1.5
                            max-w-sm
                            text-sm leading-5
                            text-neutral-500
                            dark:text-neutral-400
                        ">
                        Enter your email address and we'll send you a password reset link.
                    </p>

                </div>


                {{-- Success Message --}}
                @if (session('status'))

                <div
                    class="
                            mb-4
                            border border-green-200
                            bg-green-50
                            px-4 py-3
                            text-sm
                            text-green-700
                            dark:border-green-900/60
                            dark:bg-green-950/40
                            dark:text-green-300
                        ">
                    {{ session('status') }}
                </div>

                @endif


                {{-- Validation Errors --}}
                @if ($errors->any())

                <div
                    class="
                            mb-4
                            border border-red-200
                            bg-red-50
                            px-4 py-3
                            text-sm
                            text-red-700
                            dark:border-red-900/60
                            dark:bg-red-950/40
                            dark:text-red-300
                        ">

                    @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                    @endforeach

                </div>

                @endif


                {{-- Password Reset Form --}}
                <form
                    method="POST"
                    action="{{ route('password.email') }}"
                    data-lock-submit
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
                            ">
                            Email Address
                        </label>

                        <div class="relative">

                            <div
                                class="
                                    pointer-events-none
                                    absolute inset-y-0 left-0
                                    flex w-11
                                    items-center justify-center
                                    border-r border-neutral-300
                                    text-neutral-500
                                    dark:border-neutral-700
                                    dark:text-neutral-400
                                ">

                                <i
                                    data-lucide="mail"
                                    class="h-4 w-4"
                                    aria-hidden="true">
                                </i>

                            </div>

                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="email"
                                placeholder="you@example.com"
                                aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                                class="
                                    block w-full
                                    border border-neutral-300
                                    bg-white
                                    py-3 pr-4
                                    text-sm
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
                                "
                                style="padding-left: 3.5rem;">

                        </div>

                    </div>


                    {{-- Send Reset Link --}}
                    <button
                        type="submit"
                        data-loading-text="Sending reset link..."
                        class="
                            inline-flex w-full
                            items-center justify-center
                            gap-2
                            bg-[#008080]
                            px-4 py-3
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

                        <i
                            data-lucide="send"
                            class="h-4 w-4"
                            aria-hidden="true">
                        </i>

                        Send Reset Link

                    </button>


                    {{-- Back to Login --}}
                    <div class="text-center">

                        <a
                            href="{{ route('login') }}"
                            class="
                                text-sm font-medium
                                text-[#008080]
                                underline underline-offset-4
                                transition
                                hover:text-[#006666]
                                dark:text-teal-400
                                dark:hover:text-teal-300
                            ">
                            Back to Login
                        </a>

                    </div>


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
                ">
                &copy; {{ date('Y') }} Rincomm Internet Service Provider
            </p>

        </div>

    </div>

</body>

</html>