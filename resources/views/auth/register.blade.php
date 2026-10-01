<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

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
                            leading-5
                            text-neutral-500
                            dark:text-neutral-400
                        ">
                        Register for the Rincomm customer portal, then verify your account by email or SMS.
                    </p>

                </div>


                {{-- Delivery / workflow error --}}
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


                {{-- Registration form --}}
                <form
                    method="POST"
                    action="{{ route('register.store') }}"
                    data-lock-submit
                    novalidate
                    class="space-y-3">

                    @csrf


                    {{-- Application flow --}}
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


                    {{-- Name --}}
                    <div
                        class="
                            grid grid-cols-1
                            gap-3
                            sm:grid-cols-2
                        ">

                        {{-- First name --}}
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


                        {{-- Last name --}}
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


                    {{-- Contact details --}}
                    <div
                        class="
                            grid grid-cols-1
                            gap-3
                            sm:grid-cols-2
                        ">

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


                        {{-- Mobile number --}}
                        <div>

                            <label
                                for="phone"
                                class="
                                    mb-1 block
                                    text-sm font-medium
                                    text-neutral-700
                                    dark:text-neutral-300
                                ">
                                Mobile Number
                            </label>

                            <input
                                id="phone"
                                name="phone"
                                type="tel"
                                value="{{ old('phone') }}"
                                autocomplete="tel"
                                inputmode="tel"
                                placeholder="0917 123 4567"
                                aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}"
                                aria-describedby="phone-help{{ $errors->has('phone') ? ' phone-error' : '' }}"
                                class="
                                    block w-full
                                    bg-white
                                    px-4 py-2.5
                                    text-base
                                    text-neutral-900
                                    outline-none
                                    transition
                                    placeholder:text-neutral-400

                                    {{ $errors->has('phone')
                                        ? 'border border-red-500 focus:border-red-500 focus:ring-1 focus:ring-red-500 dark:border-red-500'
                                        : 'border border-neutral-300 focus:border-[#008080] focus:ring-1 focus:ring-[#008080] dark:border-neutral-700'
                                    }}

                                    dark:bg-neutral-950
                                    dark:text-neutral-100
                                    dark:placeholder:text-neutral-500
                                ">

                            <p
                                id="phone-help"
                                class="
                                    mt-1 text-xs
                                    leading-5
                                    text-neutral-500
                                    dark:text-neutral-400
                                ">
                                Philippine mobile number.
                            </p>

                            <x-field-error
                                id="phone-error"
                                :message="$errors->first('phone')" />

                        </div>

                    </div>


                    {{-- Verification channel --}}
                    <fieldset>

                        <legend
                            class="
                                text-sm font-medium
                                text-neutral-700
                                dark:text-neutral-300
                            ">
                            Receive verification code by
                        </legend>

                        <p
                            id="verification-channel-help"
                            class="
                                mt-0.5 text-xs
                                leading-5
                                text-neutral-500
                                dark:text-neutral-400
                            ">
                            Choose where Rincomm should send your six-digit account verification code.
                        </p>


                        <div
                            class="
                                mt-2 grid grid-cols-1
                                gap-2
                                sm:grid-cols-2
                            ">

                            {{-- Email option --}}
                            <label
                                class="
                                    flex cursor-pointer
                                    items-start gap-3
                                    border border-neutral-300
                                    bg-white
                                    px-3 py-2.5
                                    transition
                                    hover:border-[#008080]
                                    has-[:checked]:border-[#008080]
                                    has-[:checked]:bg-[#008080]/5
                                    dark:border-neutral-700
                                    dark:bg-neutral-950
                                    dark:hover:border-teal-400
                                    dark:has-[:checked]:border-teal-500
                                    dark:has-[:checked]:bg-teal-950/20
                                ">

                                <input
                                    type="radio"
                                    name="verification_channel"
                                    value="email"
                                    @checked(old('verification_channel', 'email' )==='email' )
                                    aria-describedby="verification-channel-help"
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
                                            flex items-center gap-1.5
                                            text-sm font-medium
                                            text-neutral-800
                                            dark:text-neutral-200
                                        ">

                                        <i
                                            data-lucide="mail"
                                            class="h-4 w-4 text-[#008080] dark:text-teal-400"
                                            aria-hidden="true">
                                        </i>

                                        Email

                                    </span>

                                    <span
                                        class="
                                            mt-0.5 block
                                            text-xs leading-5
                                            text-neutral-500
                                            dark:text-neutral-400
                                        ">
                                        Send the code to your email address.
                                    </span>

                                </span>

                            </label>


                            {{-- SMS option --}}
                            <label
                                class="
                                    flex cursor-pointer
                                    items-start gap-3
                                    border border-neutral-300
                                    bg-white
                                    px-3 py-2.5
                                    transition
                                    hover:border-[#008080]
                                    has-[:checked]:border-[#008080]
                                    has-[:checked]:bg-[#008080]/5
                                    dark:border-neutral-700
                                    dark:bg-neutral-950
                                    dark:hover:border-teal-400
                                    dark:has-[:checked]:border-teal-500
                                    dark:has-[:checked]:bg-teal-950/20
                                ">

                                <input
                                    type="radio"
                                    name="verification_channel"
                                    value="sms"
                                    @checked(old('verification_channel')==='sms' )
                                    aria-describedby="verification-channel-help"
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
                                            flex items-center gap-1.5
                                            text-sm font-medium
                                            text-neutral-800
                                            dark:text-neutral-200
                                        ">

                                        <i
                                            data-lucide="message-square"
                                            class="h-4 w-4 text-[#008080] dark:text-teal-400"
                                            aria-hidden="true">
                                        </i>

                                        SMS

                                    </span>

                                    <span
                                        class="
                                            mt-0.5 block
                                            text-xs leading-5
                                            text-neutral-500
                                            dark:text-neutral-400
                                        ">
                                        Send the code to your mobile number.
                                    </span>

                                </span>

                            </label>

                        </div>

                        <x-field-error
                            id="verification-channel-error"
                            :message="$errors->first('verification_channel')" />

                    </fieldset>


                    {{-- Password fields --}}
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
                                    aria-describedby="{{ $errors->has('password') ? 'password-error' : 'password-help' }}"
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
                                id="password-help"
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


                        {{-- Confirm password --}}
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


                    {{-- Verification notice --}}
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
                                dark:text-teal-400
                            "
                            aria-hidden="true">
                        </i>

                        <span>
                            Your portal account remains pending until the verification code is successfully confirmed.
                        </span>

                    </div>


                    {{-- Submit --}}
                    <button
                        type="submit"
                        data-loading-text="Sending verification code..."
                        class="
                            inline-flex min-h-11 w-full
                            items-center justify-center gap-2
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

                        <i
                            data-lucide="send"
                            class="h-4 w-4"
                            aria-hidden="true">
                        </i>

                        Continue to Verification

                    </button>


                    {{-- Account links --}}
                    <div class="text-center">

                        <p class="text-sm text-neutral-600 dark:text-neutral-400">

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


    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggleButtons =
                document.querySelectorAll(
                    '[data-password-toggle]'
                );

            toggleButtons.forEach((toggleButton) => {
                toggleButton.addEventListener(
                    'click',
                    () => {
                        const targetId =
                            toggleButton.dataset.passwordTarget;

                        const passwordInput =
                            document.getElementById(
                                targetId
                            );

                        if (!passwordInput) {
                            return;
                        }

                        const isVisible =
                            passwordInput.type === 'text';

                        passwordInput.type =
                            isVisible ?
                            'password' :
                            'text';

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
                    }
                );
            });
        });
    </script>

</body>

</html>
