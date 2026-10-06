<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Reset Password - Rincomm</title>

    <script>
        (() => {
            const savedTheme =
                localStorage.getItem('rincomm-theme');

            const useDarkTheme =
                savedTheme === 'dark' ||
                (
                    !savedTheme &&
                    window.matchMedia(
                        '(prefers-color-scheme: dark)'
                    ).matches
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

    {{-- Theme toggle --}}
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


                {{-- Header --}}
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
                            class="h-5 w-5"
                            aria-hidden="true">
                        </i>

                    </div>

                    <h1
                        class="
                            mt-4 text-2xl font-semibold
                            tracking-tight
                            text-neutral-900
                            dark:text-white
                        ">
                        Create New Password
                    </h1>

                    <p
                        class="
                            mx-auto mt-1.5
                            max-w-sm
                            text-sm leading-5
                            text-neutral-500
                            dark:text-neutral-400
                        ">
                        Your recovery code has been verified. Choose a new password for your Rincomm account.
                    </p>

                </div>


                {{-- Verification success --}}
                @if (session('success'))

                <div
                    role="status"
                    aria-live="polite"
                    class="
                            mb-4 flex items-start gap-2.5
                            border border-green-200
                            bg-green-50
                            px-4 py-3
                            text-sm
                            text-green-700
                            dark:border-green-900/60
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


                {{-- General error --}}
                @if (session('error'))

                <div
                    role="alert"
                    class="
                            mb-4 flex items-start gap-2.5
                            border border-red-200
                            bg-red-50
                            px-4 py-3
                            text-sm
                            text-red-700
                            dark:border-red-900/60
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


                {{-- Validation errors --}}
                @if ($errors->any())

                <div
                    role="alert"
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

                    <p>
                        {{ $error }}
                    </p>

                    @endforeach

                </div>

                @endif


                {{-- Reset password form --}}
                <form
                    method="POST"
                    action="{{ route('password.update', $challenge) }}"
                    data-lock-submit
                    class="space-y-4"
                    novalidate>

                    @csrf


                    {{-- New password --}}
                    <div>

                        <label
                            for="password"
                            class="
                                mb-1.5 block
                                text-sm font-medium
                                text-neutral-700
                                dark:text-neutral-300
                            ">
                            New Password
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
                                    data-lucide="lock-keyhole"
                                    class="h-4 w-4"
                                    aria-hidden="true">
                                </i>

                            </div>


                            <input
                                id="password"
                                name="password"
                                type="password"
                                required
                                autofocus
                                autocomplete="new-password"
                                placeholder="Enter new password"
                                aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                                class="
                                    block w-full
                                    border
                                    bg-white
                                    py-3 pr-12
                                    text-sm
                                    text-neutral-900
                                    outline-none
                                    transition
                                    placeholder:text-neutral-400
                                    focus:border-[#008080]
                                    focus:ring-1
                                    focus:ring-[#008080]
                                    dark:bg-neutral-950
                                    dark:text-neutral-100
                                    dark:placeholder:text-neutral-500
                                    {{ $errors->has('password')
                                        ? 'border-red-500 ring-1 ring-red-500/20 dark:border-red-500'
                                        : 'border-neutral-300 dark:border-neutral-700' }}
                                "
                                style="padding-left: 3.5rem;">


                            <button
                                type="button"
                                data-reset-password-toggle
                                data-target="password"
                                class="
                                    absolute inset-y-0 right-0
                                    inline-flex w-11
                                    items-center justify-center
                                    text-neutral-500
                                    transition
                                    hover:text-[#008080]
                                    focus:outline-none
                                    focus:ring-2
                                    focus:ring-inset
                                    focus:ring-[#008080]
                                    dark:text-neutral-400
                                    dark:hover:text-teal-400
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
                            class="
                                    mt-1.5 flex items-start gap-1.5
                                    text-xs
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

                    </div>


                    {{-- Confirm password --}}
                    <div>

                        <label
                            for="password_confirmation"
                            class="
                                mb-1.5 block
                                text-sm font-medium
                                text-neutral-700
                                dark:text-neutral-300
                            ">
                            Confirm New Password
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
                                    data-lucide="lock-keyhole"
                                    class="h-4 w-4"
                                    aria-hidden="true">
                                </i>

                            </div>


                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                required
                                autocomplete="new-password"
                                placeholder="Confirm new password"
                                class="
                                    block w-full
                                    border border-neutral-300
                                    bg-white
                                    py-3 pr-12
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


                            <button
                                type="button"
                                data-reset-password-toggle
                                data-target="password_confirmation"
                                class="
                                    absolute inset-y-0 right-0
                                    inline-flex w-11
                                    items-center justify-center
                                    text-neutral-500
                                    transition
                                    hover:text-[#008080]
                                    focus:outline-none
                                    focus:ring-2
                                    focus:ring-inset
                                    focus:ring-[#008080]
                                    dark:text-neutral-400
                                    dark:hover:text-teal-400
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


                    {{-- Security notice --}}
                    <div
                        class="
                            flex items-start gap-2.5
                            border border-neutral-200
                            bg-neutral-50
                            px-3 py-2.5
                            text-xs leading-5
                            text-neutral-600
                            dark:border-neutral-800
                            dark:bg-neutral-950
                            dark:text-neutral-400
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

                        <span>
                            Resetting your password changes your login credentials only. It does not change your Rincomm account or service status.
                        </span>

                    </div>


                    {{-- Submit --}}
                    <button
                        type="submit"
                        data-loading-text="Resetting password..."
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
                            data-lucide="key-round"
                            class="h-4 w-4"
                            aria-hidden="true">
                        </i>

                        Reset Password

                    </button>


                    {{-- Restart recovery --}}
                    <div class="text-center">

                        <a
                            href="{{ route('password.request') }}"
                            class="
                                text-sm font-medium
                                text-[#008080]
                                underline underline-offset-4
                                transition
                                hover:text-[#006666]
                                dark:text-teal-400
                                dark:hover:text-teal-300
                            ">
                            Start Password Recovery Again
                        </a>

                    </div>


                    {{-- Login --}}
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


    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function() {
                const toggleButtons =
                    document.querySelectorAll(
                        '[data-reset-password-toggle]'
                    );

                toggleButtons.forEach(
                    function(button) {
                        button.addEventListener(
                            'click',
                            function() {
                                const targetId =
                                    button.getAttribute(
                                        'data-target'
                                    );

                                const input =
                                    document.getElementById(
                                        targetId
                                    );

                                if (!input) {
                                    return;
                                }

                                const showingPassword =
                                    input.type === 'text';

                                input.type =
                                    showingPassword ?
                                    'password' :
                                    'text';

                                const icon =
                                    button.querySelector(
                                        '[data-lucide]'
                                    );

                                if (icon) {
                                    icon.setAttribute(
                                        'data-lucide',
                                        showingPassword ?
                                        'eye' :
                                        'eye-off'
                                    );

                                    if (window.lucide) {
                                        window.lucide.createIcons();
                                    }
                                }
                            }
                        );
                    }
                );
            }
        );
    </script>

</body>

</html>