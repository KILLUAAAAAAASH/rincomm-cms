<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="h-full">

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}">

    <title>
        Choose Verification Method | {{ config('app.name', 'Rincomm') }}
    </title>

    <script>
        (() => {
            const savedTheme =
                localStorage.getItem('rincomm-theme');

            const prefersDark =
                window.matchMedia(
                    '(prefers-color-scheme: dark)'
                ).matches;

            document.documentElement.classList.toggle(
                'dark',
                savedTheme === 'dark' ||
                (!savedTheme && prefersDark)
            );
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
    class="
        min-h-full
        bg-neutral-100
        text-neutral-900
        antialiased
        dark:bg-neutral-950
        dark:text-neutral-100
    ">

    <div class="relative min-h-screen">

        <div class="absolute right-4 top-4">

            <button
                type="button"
                id="theme-toggle"
                class="
                    inline-flex h-10 w-10
                    items-center justify-center
                    border border-neutral-300
                    bg-white text-neutral-600
                    transition
                    hover:bg-neutral-50
                    focus:outline-none
                    focus:ring-2
                    focus:ring-[#008080]
                    dark:border-neutral-700
                    dark:bg-neutral-900
                    dark:text-neutral-300
                    dark:hover:bg-neutral-800
                "
                aria-label="Toggle color theme">

                <i
                    data-lucide="sun"
                    class="hidden h-4 w-4 dark:block">
                </i>

                <i
                    data-lucide="moon"
                    class="h-4 w-4 dark:hidden">
                </i>

            </button>

        </div>

        <main
            class="
                flex min-h-screen
                items-center justify-center
                px-4 py-16
            ">

            <section class="w-full max-w-lg">

                <div class="mb-5 text-center">

                    <div
                        class="
                            mx-auto flex h-11 w-11
                            items-center justify-center
                            bg-[#008080]/10
                            text-[#008080]
                            dark:bg-[#008080]/20
                            dark:text-[#5EEAD4]
                        ">

                        <i
                            data-lucide="shield-check"
                            class="h-5 w-5">
                        </i>

                    </div>

                    <h1
                        class="
                            mt-4 text-xl font-semibold
                            tracking-tight
                            dark:text-white
                        ">
                        Verify your employee account
                    </h1>

                    <p
                        class="
                            mx-auto mt-1.5
                            max-w-md
                            text-sm leading-6
                            text-neutral-500
                            dark:text-neutral-400
                        ">
                        Choose where Rincomm should send your
                        six-digit activation code.
                    </p>

                </div>

                <div
                    class="
                        border border-neutral-200
                        bg-white p-5
                        dark:border-neutral-800
                        dark:bg-neutral-900
                        sm:p-6
                    ">

                    <div
                        class="
                            mb-5 grid gap-3
                            border-b border-neutral-200
                            pb-4
                            sm:grid-cols-2
                            dark:border-neutral-800
                        ">

                        <div>
                            <p
                                class="
                                    text-xs font-medium uppercase
                                    tracking-wide text-neutral-400
                                ">
                                Employee
                            </p>

                            <p
                                class="
                                    mt-1 text-sm font-semibold
                                    dark:text-white
                                ">
                                {{ $employee->name }}
                            </p>
                        </div>

                        <div>
                            <p
                                class="
                                    text-xs font-medium uppercase
                                    tracking-wide text-neutral-400
                                ">
                                Employee Number
                            </p>

                            <p
                                class="
                                    mt-1 font-mono text-sm
                                    font-semibold text-[#008080]
                                    dark:text-[#5EEAD4]
                                ">
                                {{ $employee->employee_number }}
                            </p>
                        </div>

                    </div>

                    @if (session('error'))

                        <div
                            role="alert"
                            class="
                                mb-4 border border-red-200
                                bg-red-50 px-3 py-2.5
                                text-sm text-red-700
                                dark:border-red-900
                                dark:bg-red-950/40
                                dark:text-red-300
                            ">
                            {{ session('error') }}
                        </div>

                    @endif

                    <form
                        method="POST"
                        action="{{ route('employee.activation.channel.store') }}"
                        data-lock-submit>

                        @csrf

                        <fieldset>

                            <legend
                                class="
                                    text-sm font-medium
                                    text-neutral-800
                                    dark:text-neutral-200
                                ">
                                Verification method
                            </legend>

                            <p
                                class="
                                    mt-1 text-xs leading-5
                                    text-neutral-500
                                    dark:text-neutral-400
                                ">
                                Email is recommended. You can also
                                receive the code by SMS.
                            </p>

                            <div
                                class="
                                    mt-3 grid grid-cols-1
                                    gap-2 sm:grid-cols-2
                                ">

                                <label
                                    class="
                                        flex cursor-pointer
                                        items-start gap-3
                                        border border-neutral-300
                                        bg-white px-3 py-3
                                        transition
                                        hover:border-[#008080]
                                        has-[:checked]:border-[#008080]
                                        has-[:checked]:bg-[#008080]/5
                                        dark:border-neutral-700
                                        dark:bg-neutral-950
                                        dark:hover:border-teal-400
                                        dark:has-[:checked]:border-teal-500
                                        dark:has-[:checked]:bg-teal-950/20
                                        {{ !$emailAvailable ? 'cursor-not-allowed opacity-50' : '' }}
                                    ">

                                    <input
                                        type="radio"
                                        name="verification_channel"
                                        value="email"
                                        @checked(old('verification_channel', 'email') === 'email')
                                        @disabled(!$emailAvailable)
                                        class="
                                            mt-0.5 h-4 w-4
                                            border-neutral-300
                                            text-[#008080]
                                            focus:ring-[#008080]
                                            dark:border-neutral-600
                                            dark:bg-neutral-900
                                        ">

                                    <span class="min-w-0">

                                        <span
                                            class="
                                                flex items-center gap-2
                                                text-sm font-semibold
                                                text-neutral-800
                                                dark:text-neutral-200
                                            ">

                                            <i
                                                data-lucide="mail"
                                                class="
                                                    h-4 w-4
                                                    text-[#008080]
                                                    dark:text-teal-400
                                                ">
                                            </i>

                                            Email

                                        </span>

                                        <span
                                            class="
                                                mt-1 block break-all
                                                text-xs leading-5
                                                text-neutral-500
                                                dark:text-neutral-400
                                            ">
                                            {{ $maskedEmail }}
                                        </span>

                                    </span>

                                </label>

                                <label
                                    class="
                                        flex cursor-pointer
                                        items-start gap-3
                                        border border-neutral-300
                                        bg-white px-3 py-3
                                        transition
                                        hover:border-[#008080]
                                        has-[:checked]:border-[#008080]
                                        has-[:checked]:bg-[#008080]/5
                                        dark:border-neutral-700
                                        dark:bg-neutral-950
                                        dark:hover:border-teal-400
                                        dark:has-[:checked]:border-teal-500
                                        dark:has-[:checked]:bg-teal-950/20
                                        {{ !$smsAvailable ? 'cursor-not-allowed opacity-50' : '' }}
                                    ">

                                    <input
                                        type="radio"
                                        name="verification_channel"
                                        value="sms"
                                        @checked(old('verification_channel') === 'sms')
                                        @disabled(!$smsAvailable)
                                        class="
                                            mt-0.5 h-4 w-4
                                            border-neutral-300
                                            text-[#008080]
                                            focus:ring-[#008080]
                                            dark:border-neutral-600
                                            dark:bg-neutral-900
                                        ">

                                    <span class="min-w-0">

                                        <span
                                            class="
                                                flex items-center gap-2
                                                text-sm font-semibold
                                                text-neutral-800
                                                dark:text-neutral-200
                                            ">

                                            <i
                                                data-lucide="message-square"
                                                class="
                                                    h-4 w-4
                                                    text-[#008080]
                                                    dark:text-teal-400
                                                ">
                                            </i>

                                            SMS

                                        </span>

                                        <span
                                            class="
                                                mt-1 block
                                                text-xs leading-5
                                                text-neutral-500
                                                dark:text-neutral-400
                                            ">
                                            {{ $maskedPhone }}
                                        </span>

                                    </span>

                                </label>

                            </div>

                            @error('verification_channel')

                                <p
                                    role="alert"
                                    class="
                                        mt-2 text-xs
                                        text-red-600
                                        dark:text-red-400
                                    ">
                                    {{ $message }}
                                </p>

                            @enderror

                        </fieldset>

                        <button
                            type="submit"
                            data-loading-text="Sending code..."
                            class="
                                mt-5 inline-flex min-h-11
                                w-full items-center
                                justify-center gap-2
                                bg-[#008080]
                                px-4 py-2.5
                                text-sm font-semibold
                                text-white
                                transition
                                hover:bg-[#006f6f]
                                focus:outline-none
                                focus:ring-2
                                focus:ring-[#008080]
                                focus:ring-offset-2
                                disabled:cursor-not-allowed
                                disabled:opacity-60
                                dark:focus:ring-offset-neutral-900
                            ">

                            <i
                                data-lucide="send"
                                class="h-4 w-4">
                            </i>

                            Send verification code

                        </button>

                    </form>

                </div>

                <div
                    class="
                        mt-4 text-center
                        text-xs text-neutral-500
                        dark:text-neutral-400
                    ">

                    <a
                        href="{{ route('login') }}"
                        class="
                            inline-flex items-center gap-1.5
                            font-medium text-[#008080]
                            hover:underline
                            focus:outline-none
                            focus:ring-2
                            focus:ring-[#008080]
                            dark:text-[#5EEAD4]
                        ">

                        <i
                            data-lucide="arrow-left"
                            class="h-3.5 w-3.5">
                        </i>

                        Back to login

                    </a>

                </div>

            </section>

        </main>

    </div>

    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function () {
                const toggle =
                    document.getElementById(
                        'theme-toggle'
                    );

                if (!toggle) {
                    return;
                }

                toggle.addEventListener(
                    'click',
                    function () {
                        document.documentElement
                            .classList.toggle('dark');

                        localStorage.setItem(
                            'rincomm-theme',
                            document.documentElement
                                .classList.contains('dark')
                                ? 'dark'
                                : 'light'
                        );
                    }
                );
            }
        );
    </script>

</body>

</html>
