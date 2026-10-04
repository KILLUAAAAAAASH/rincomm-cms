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
        Activate Employee Account | {{ config('app.name', 'Rincomm') }}
    </title>

    <script>
        (() => {
            const savedTheme =
                localStorage.getItem('rincomm-theme');

            const prefersDark =
                window.matchMedia(
                    '(prefers-color-scheme: dark)'
                ).matches;

            const useDarkTheme =
                savedTheme === 'dark' ||
                (!savedTheme && prefersDark);

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
        min-h-full
        bg-neutral-100
        text-neutral-900
        antialiased
        dark:bg-neutral-950
        dark:text-neutral-100
    ">

    <div class="relative min-h-screen">

        <div class="absolute right-4 top-4 z-10">

            <button
                type="button"
                id="theme-toggle"
                class="
                    inline-flex h-10 w-10
                    items-center justify-center
                    border border-neutral-300
                    bg-white
                    text-neutral-600
                    transition
                    hover:bg-neutral-50
                    focus:outline-none
                    focus:ring-2
                    focus:ring-[#008080]
                    focus:ring-offset-2
                    dark:border-neutral-700
                    dark:bg-neutral-900
                    dark:text-neutral-300
                    dark:hover:bg-neutral-800
                    dark:focus:ring-offset-neutral-950
                "
                aria-label="Toggle color theme">

                <i
                    data-lucide="sun"
                    class="hidden h-4 w-4 dark:block"
                    aria-hidden="true">
                </i>

                <i
                    data-lucide="moon"
                    class="h-4 w-4 dark:hidden"
                    aria-hidden="true">
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
                            data-lucide="user-check"
                            class="h-5 w-5"
                            aria-hidden="true">
                        </i>

                    </div>

                    <h1
                        class="
                            mt-4 text-xl font-semibold
                            tracking-tight
                            text-neutral-900
                            dark:text-white
                        ">
                        Activate your employee account
                    </h1>

                    <p
                        class="
                            mx-auto mt-1.5 max-w-md
                            text-sm leading-6
                            text-neutral-500
                            dark:text-neutral-400
                        ">
                        Verify your identity and replace the
                        temporary password before accessing Rincomm.
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
                                    tracking-wide
                                    text-neutral-400
                                ">
                                Employee
                            </p>

                            <p
                                class="
                                    mt-1 text-sm font-semibold
                                    text-neutral-900
                                    dark:text-white
                                ">
                                {{ $employee->name }}
                            </p>
                        </div>

                        <div>
                            <p
                                class="
                                    text-xs font-medium uppercase
                                    tracking-wide
                                    text-neutral-400
                                ">
                                Employee Number
                            </p>

                            <p
                                class="
                                    mt-1 font-mono text-sm font-semibold
                                    text-[#008080]
                                    dark:text-[#5EEAD4]
                                ">
                                {{ $employee->employee_number }}
                            </p>
                        </div>

                    </div>


                    @if (session('success'))

                        <div
                            role="status"
                            aria-live="polite"
                            class="
                                mb-4 flex items-start gap-2.5
                                border border-green-200
                                bg-green-50
                                px-3 py-2.5
                                text-sm text-green-700
                                dark:border-green-900
                                dark:bg-green-950/40
                                dark:text-green-300
                            ">

                            <i
                                data-lucide="circle-check"
                                class="mt-0.5 h-4 w-4 shrink-0"
                                aria-hidden="true">
                            </i>

                            <span>
                                {{ session('success') }}
                            </span>

                        </div>

                    @endif


                    @if (session('error'))

                        <div
                            role="alert"
                            class="
                                mb-4 flex items-start gap-2.5
                                border border-red-200
                                bg-red-50
                                px-3 py-2.5
                                text-sm text-red-700
                                dark:border-red-900
                                dark:bg-red-950/40
                                dark:text-red-300
                            ">

                            <i
                                data-lucide="triangle-alert"
                                class="mt-0.5 h-4 w-4 shrink-0"
                                aria-hidden="true">
                            </i>

                            <span>
                                {{ session('error') }}
                            </span>

                        </div>

                    @endif


                    @if ($isExpired)

                        <div
                            role="alert"
                            class="
                                mb-4 flex items-start gap-2.5
                                border border-amber-200
                                bg-amber-50
                                px-3 py-2.5
                                text-sm text-amber-800
                                dark:border-amber-900
                                dark:bg-amber-950/30
                                dark:text-amber-300
                            ">

                            <i
                                data-lucide="clock-alert"
                                class="mt-0.5 h-4 w-4 shrink-0"
                                aria-hidden="true">
                            </i>

                            <div>
                                <p class="font-medium">
                                    Verification code expired
                                </p>

                                <p class="mt-0.5 leading-5">
                                    Request a new verification code below to continue activation.
                                </p>
                            </div>

                        </div>

                    @endif


                    <div
                        class="
                            mb-5 flex items-start gap-2.5
                            border border-[#008080]/20
                            bg-[#008080]/5
                            px-3 py-2.5
                            text-sm
                            text-neutral-700
                            dark:border-[#008080]/30
                            dark:bg-[#008080]/10
                            dark:text-neutral-300
                        ">

                        <i
                            data-lucide="shield-check"
                            class="
                                mt-0.5 h-4 w-4 shrink-0
                                text-[#008080]
                                dark:text-[#5EEAD4]
                            "
                            aria-hidden="true">
                        </i>

                        <p>
                            A six-digit verification code was sent by
                            <span class="font-semibold">
                                {{ strtolower($channelLabel) }}
                            </span>
                            to
                            <span class="font-semibold">
                                {{ $maskedDestination }}
                            </span>.
                        </p>

                    </div>


                    <form
                        method="POST"
                        action="{{ route('employee.activation.store', $challenge) }}"
                        data-lock-submit
                        novalidate>

                        @csrf

                        <div>

                            <label
                                for="verification-code"
                                class="
                                    block text-sm font-medium
                                    text-neutral-800
                                    dark:text-neutral-200
                                ">
                                Verification code
                            </label>

                            <input
                                id="verification-code"
                                type="text"
                                name="code"
                                value="{{ old('code') }}"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                maxlength="6"
                                pattern="[0-9]{6}"
                                placeholder="000000"
                                @disabled($isExpired)
                                @unless($isExpired) autofocus @endunless
                                class="
                                    mt-2 block min-h-12 w-full
                                    border border-neutral-300
                                    bg-white
                                    px-4 py-2.5
                                    text-center
                                    font-mono text-xl font-semibold
                                    tracking-[0.35em]
                                    text-neutral-900
                                    outline-none
                                    transition
                                    placeholder:text-neutral-300
                                    focus:border-[#008080]
                                    focus:ring-2
                                    focus:ring-[#008080]/20
                                    disabled:cursor-not-allowed
                                    disabled:bg-neutral-100
                                    disabled:text-neutral-400
                                    dark:border-neutral-700
                                    dark:bg-neutral-950
                                    dark:text-white
                                    dark:placeholder:text-neutral-700
                                    dark:disabled:bg-neutral-800
                                    dark:disabled:text-neutral-500
                                    {{ $errors->has('code') ? 'border-red-500 ring-2 ring-red-500/20 focus:border-red-500 focus:ring-red-500/20 dark:border-red-500' : '' }}
                                "
                                aria-invalid="{{ $errors->has('code') ? 'true' : 'false' }}">

                            @error('code')

                                <p
                                    role="alert"
                                    class="
                                        mt-2 flex items-start gap-1.5
                                        text-xs leading-5
                                        text-red-600
                                        dark:text-red-400
                                    ">

                                    <i
                                        data-lucide="circle-alert"
                                        class="mt-0.5 h-3.5 w-3.5 shrink-0"
                                        aria-hidden="true">
                                    </i>

                                    <span>
                                        {{ $message }}
                                    </span>

                                </p>

                            @enderror

                            <div
                                class="
                                    mt-2 flex flex-wrap
                                    items-center justify-between
                                    gap-2
                                    text-xs
                                    text-neutral-500
                                    dark:text-neutral-400
                                ">

                                <span>
                                    Code expires after
                                    {{ \App\Services\VerificationChallengeService::EXPIRY_MINUTES }}
                                    minutes.
                                </span>

                                <span>
                                    {{ $remainingAttempts }}
                                    {{ $remainingAttempts === 1 ? 'attempt' : 'attempts' }}
                                    remaining
                                </span>

                            </div>

                        </div>


                        <div class="mt-5">

                            <label
                                for="password"
                                class="
                                    block text-sm font-medium
                                    text-neutral-800
                                    dark:text-neutral-200
                                ">
                                New password
                            </label>

                            <div class="relative mt-2">

                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    autocomplete="new-password"
                                    @disabled($isExpired)
                                    class="
                                        block min-h-11 w-full
                                        border border-neutral-300
                                        bg-white
                                        px-3 py-2.5 pr-11
                                        text-sm text-neutral-900
                                        outline-none
                                        transition
                                        focus:border-[#008080]
                                        focus:ring-2
                                        focus:ring-[#008080]/20
                                        disabled:cursor-not-allowed
                                        disabled:bg-neutral-100
                                        dark:border-neutral-700
                                        dark:bg-neutral-950
                                        dark:text-white
                                        dark:disabled:bg-neutral-800
                                        {{ $errors->has('password') ? 'border-red-500 ring-2 ring-red-500/20 focus:border-red-500 focus:ring-red-500/20 dark:border-red-500' : '' }}
                                    "
                                    aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}">

                                <button
                                    type="button"
                                    data-password-toggle="password"
                                    class="
                                        absolute inset-y-0 right-0
                                        inline-flex w-11
                                        items-center justify-center
                                        text-neutral-400
                                        hover:text-neutral-700
                                        focus:outline-none
                                        dark:hover:text-neutral-200
                                    "
                                    aria-label="Show or hide new password">

                                    <i
                                        data-lucide="eye"
                                        class="h-4 w-4"
                                        aria-hidden="true">
                                    </i>

                                </button>

                            </div>

                            @error('password')

                                <p
                                    role="alert"
                                    class="
                                        mt-2 flex items-start gap-1.5
                                        text-xs leading-5
                                        text-red-600
                                        dark:text-red-400
                                    ">

                                    <i
                                        data-lucide="circle-alert"
                                        class="mt-0.5 h-3.5 w-3.5 shrink-0"
                                        aria-hidden="true">
                                    </i>

                                    <span>
                                        {{ $message }}
                                    </span>

                                </p>

                            @enderror

                            <p
                                class="
                                    mt-2 text-xs leading-5
                                    text-neutral-500
                                    dark:text-neutral-400
                                ">
                                Use at least 8 characters. Your new password
                                cannot be the default password
                                <span class="font-mono font-medium">12345678</span>.
                            </p>

                        </div>


                        <div class="mt-4">

                            <label
                                for="password_confirmation"
                                class="
                                    block text-sm font-medium
                                    text-neutral-800
                                    dark:text-neutral-200
                                ">
                                Confirm new password
                            </label>

                            <div class="relative mt-2">

                                <input
                                    id="password_confirmation"
                                    type="password"
                                    name="password_confirmation"
                                    autocomplete="new-password"
                                    @disabled($isExpired)
                                    class="
                                        block min-h-11 w-full
                                        border border-neutral-300
                                        bg-white
                                        px-3 py-2.5 pr-11
                                        text-sm text-neutral-900
                                        outline-none
                                        transition
                                        focus:border-[#008080]
                                        focus:ring-2
                                        focus:ring-[#008080]/20
                                        disabled:cursor-not-allowed
                                        disabled:bg-neutral-100
                                        dark:border-neutral-700
                                        dark:bg-neutral-950
                                        dark:text-white
                                        dark:disabled:bg-neutral-800
                                    ">

                                <button
                                    type="button"
                                    data-password-toggle="password_confirmation"
                                    class="
                                        absolute inset-y-0 right-0
                                        inline-flex w-11
                                        items-center justify-center
                                        text-neutral-400
                                        hover:text-neutral-700
                                        focus:outline-none
                                        dark:hover:text-neutral-200
                                    "
                                    aria-label="Show or hide password confirmation">

                                    <i
                                        data-lucide="eye"
                                        class="h-4 w-4"
                                        aria-hidden="true">
                                    </i>

                                </button>

                            </div>

                        </div>


                        <button
                            type="submit"
                            data-loading-text="Activating..."
                            @disabled($isExpired || $remainingAttempts <= 0)
                            class="
                                mt-5 inline-flex min-h-11 w-full
                                items-center justify-center gap-2
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
                                data-lucide="badge-check"
                                class="h-4 w-4"
                                aria-hidden="true">
                            </i>

                            Activate account

                        </button>

                    </form>


                    <div
                        class="
                            mt-5 border-t
                            border-neutral-200
                            pt-4
                            dark:border-neutral-800
                        ">

                        <div
                            class="
                                flex flex-col gap-3
                                sm:flex-row
                                sm:items-center
                                sm:justify-between
                            ">

                            <div>

                                <p
                                    class="
                                        text-sm font-medium
                                        text-neutral-800
                                        dark:text-neutral-200
                                    ">
                                    Didn't receive the code?
                                </p>

                                <p
                                    id="resend-help"
                                    class="
                                        mt-0.5 text-xs leading-5
                                        text-neutral-500
                                        dark:text-neutral-400
                                    ">

                                    @if ($resendAvailableIn > 0)

                                        You can request another code in

                                        <span
                                            id="resend-countdown"
                                            class="font-medium">
                                            {{ $resendAvailableIn }}
                                        </span>

                                        seconds.

                                    @else

                                        You can request another verification code now.

                                    @endif

                                </p>

                            </div>


                            <form
                                method="POST"
                                action="{{ route('employee.activation.resend', $challenge) }}"
                                data-lock-submit>

                                @csrf

                                <button
                                    id="resend-button"
                                    type="submit"
                                    data-loading-text="Sending..."
                                    data-resend-seconds="{{ $resendAvailableIn }}"
                                    @disabled($resendAvailableIn > 0)
                                    class="
                                        inline-flex min-h-10
                                        w-full
                                        items-center justify-center gap-2
                                        border border-neutral-300
                                        px-3 py-2
                                        text-xs font-semibold
                                        text-neutral-700
                                        transition
                                        hover:bg-neutral-50
                                        focus:outline-none
                                        focus:ring-2
                                        focus:ring-[#008080]
                                        focus:ring-offset-2
                                        disabled:cursor-not-allowed
                                        disabled:opacity-50
                                        sm:w-auto
                                        dark:border-neutral-700
                                        dark:text-neutral-200
                                        dark:hover:bg-neutral-800
                                        dark:focus:ring-offset-neutral-900
                                    ">

                                    <i
                                        data-lucide="refresh-cw"
                                        class="h-3.5 w-3.5"
                                        aria-hidden="true">
                                    </i>

                                    Resend code

                                </button>

                            </form>

                        </div>

                    </div>

                    <div
                        class="
                            mt-4 border-t
                            border-neutral-200
                            pt-4 text-center
                            dark:border-neutral-800
                        ">

                        <a
                            href="{{ route('employee.activation.channel') }}"
                            class="
                                inline-flex items-center gap-1.5
                                text-xs font-medium
                                text-[#008080]
                                transition
                                hover:underline
                                focus:outline-none
                                focus:ring-2
                                focus:ring-[#008080]
                                focus:ring-offset-2
                                dark:text-[#5EEAD4]
                                dark:focus:ring-offset-neutral-900
                            ">

                            <i
                                data-lucide="repeat-2"
                                class="h-3.5 w-3.5"
                                aria-hidden="true">
                            </i>

                            Change verification method

                        </a>

                    </div>

                </div>


                <div
                    class="
                        mt-4 flex flex-col
                        items-center justify-between
                        gap-2
                        text-xs text-neutral-500
                        sm:flex-row
                        dark:text-neutral-400
                    ">

                    <a
                        href="{{ route('login') }}"
                        class="
                            inline-flex items-center gap-1.5
                            font-medium text-[#008080]
                            transition
                            hover:underline
                            focus:outline-none
                            focus:ring-2
                            focus:ring-[#008080]
                            focus:ring-offset-2
                            dark:text-[#5EEAD4]
                            dark:focus:ring-offset-neutral-950
                        ">

                        <i
                            data-lucide="arrow-left"
                            class="h-3.5 w-3.5"
                            aria-hidden="true">
                        </i>

                        Back to login

                    </a>

                    <span>
                        Rincomm secure employee activation
                    </span>

                </div>

            </section>

        </main>

    </div>


    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function() {
                const themeToggle =
                    document.getElementById(
                        'theme-toggle'
                    );

                if (themeToggle) {
                    themeToggle.addEventListener(
                        'click',
                        function() {
                            const root =
                                document.documentElement;

                            root.classList.toggle(
                                'dark'
                            );

                            localStorage.setItem(
                                'rincomm-theme',
                                root.classList.contains(
                                    'dark'
                                )
                                    ? 'dark'
                                    : 'light'
                            );
                        }
                    );
                }


                const codeInput =
                    document.getElementById(
                        'verification-code'
                    );

                if (codeInput) {
                    codeInput.addEventListener(
                        'input',
                        function() {
                            this.value =
                                this.value
                                    .replace(/\D/g, '')
                                    .slice(0, 6);
                        }
                    );
                }


                document
                    .querySelectorAll(
                        '[data-password-toggle]'
                    )
                    .forEach(
                        function(button) {
                            button.addEventListener(
                                'click',
                                function() {
                                    const targetId =
                                        this.dataset
                                            .passwordToggle;

                                    const input =
                                        document.getElementById(
                                            targetId
                                        );

                                    if (!input) {
                                        return;
                                    }

                                    input.type =
                                        input.type ===
                                        'password'
                                            ? 'text'
                                            : 'password';
                                }
                            );
                        }
                    );


                const countdown =
                    document.getElementById(
                        'resend-countdown'
                    );

                const resendButton =
                    document.getElementById(
                        'resend-button'
                    );

                const resendHelp =
                    document.getElementById(
                        'resend-help'
                    );

                const initialResendSeconds =
                    resendButton
                        ? Number(
                            resendButton.dataset
                                .resendSeconds || 0
                        )
                        : 0;

                let remaining =
                    Number.isFinite(
                        initialResendSeconds
                    )
                        ? Math.max(
                            0,
                            Math.floor(
                                initialResendSeconds
                            )
                        )
                        : 0;

                if (
                    remaining > 0 &&
                    countdown &&
                    resendButton &&
                    resendHelp
                ) {
                    const timer =
                        window.setInterval(
                            function() {
                                remaining -= 1;

                                if (
                                    remaining <= 0
                                ) {
                                    window.clearInterval(
                                        timer
                                    );

                                    resendButton.disabled =
                                        false;

                                    resendHelp.textContent =
                                        'You can request another verification code now.';

                                    return;
                                }

                                countdown.textContent =
                                    String(
                                        remaining
                                    );
                            },
                            1000
                        );
                }
            }
        );
    </script>

</body>

</html>
