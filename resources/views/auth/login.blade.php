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
        min-h-dvh
        bg-neutral-100
        text-neutral-900
        dark:bg-neutral-950
        dark:text-neutral-100
    ">

    <div class="relative flex min-h-dvh flex-col">

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
                    aria-hidden="true">
                </i>

            </button>

        </div>


        <main
            class="
                flex flex-1
                items-center justify-center
                px-4 py-12
                sm:px-6
            ">

            <div class="w-full max-w-lg">

                <div
                    class="
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

                        <h1
                            class="
                                font-semibold
                                text-neutral-900
                                dark:text-white
                            ">

                            <span
                                class="
                                    block text-4xl
                                    leading-none
                                    sm:text-5xl
                                ">
                                Welcome
                            </span>

                            <span
                                class="
                                    mt-2 block
                                    text-lg leading-tight
                                    sm:text-xl
                                ">
                                to Rincomm Internet Service Provider
                            </span>

                        </h1>

                        <p
                            class="
                                mt-2 text-sm
                                text-neutral-500
                                dark:text-neutral-400
                            ">
                            Sign in to access your account.
                        </p>

                    </div>


                    {{-- Session messages --}}
                    @if (session('status'))

                        <div
                            class="
                                mt-4
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

                    @if (session('success'))

                        <div
                            class="
                                mt-4
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

                            {{ session('success') }}

                        </div>

                    @endif

                    @if (session('error'))

                        <div
                            class="
                                mt-4
                                border border-red-200
                                bg-red-50
                                px-4 py-2.5
                                text-sm text-red-700
                                dark:border-red-900
                                dark:bg-red-950/40
                                dark:text-red-300
                            "
                            role="alert">

                            {{ session('error') }}

                        </div>

                    @endif


                    @php
                        $activeLoginTab =
                            old('login_type')
                            ?? (
                                $errors->has('employee_number')
                                    ? 'employee'
                                    : 'account'
                            );
                    @endphp


                    {{-- Login tabs --}}
                    <div
                        class="
                            mt-5 grid grid-cols-2
                            border border-neutral-200
                            dark:border-neutral-700
                        "
                        role="tablist"
                        aria-label="Login type">

                        <button
                            type="button"
                            id="account-tab"
                            data-login-tab="account"
                            role="tab"
                            aria-controls="account-login-panel"
                            aria-selected="{{ $activeLoginTab === 'account' ? 'true' : 'false' }}"
                            class="
                                min-h-10 px-3 py-2
                                text-sm font-semibold
                                transition
                            ">
                            Login
                        </button>

                        <button
                            type="button"
                            id="employee-tab"
                            data-login-tab="employee"
                            role="tab"
                            aria-controls="employee-login-panel"
                            aria-selected="{{ $activeLoginTab === 'employee' ? 'true' : 'false' }}"
                            class="
                                min-h-10
                                border-l border-neutral-200
                                px-3 py-2
                                text-sm font-semibold
                                transition
                                dark:border-neutral-700
                            ">
                            Employee Login
                        </button>

                    </div>


                    {{-- Standard Login: Admin + Customer --}}
                    <div
                        id="account-login-panel"
                        data-login-panel="account"
                        role="tabpanel"
                        aria-labelledby="account-tab"
                        class="mt-5">

                        <form
                            method="POST"
                            action="{{ route('login.store') }}"
                            class="space-y-4"
                            autocomplete="on"
                            data-lock-submit
                            novalidate>

                            @csrf

                            <input
                                type="hidden"
                                name="login_type"
                                value="account">


                            <div>

                                <label
                                    for="email"
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-neutral-800
                                        dark:text-neutral-200
                                    ">
                                    Email
                                </label>

                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    value="{{ old('login_type') !== 'employee' ? old('email') : '' }}"
                                    autocomplete="username"
                                    autocapitalize="none"
                                    spellcheck="false"
                                    placeholder="you@example.com"
                                    class="
                                        block min-h-11 w-full
                                        border
                                        bg-white
                                        px-4 py-2.5
                                        text-sm text-neutral-900
                                        placeholder:text-neutral-400
                                        outline-none
                                        transition
                                        focus:border-[#008080]
                                        focus:ring-2
                                        focus:ring-[#008080]/20
                                        dark:bg-neutral-950
                                        dark:text-white
                                        dark:placeholder:text-neutral-500
                                        {{ $errors->has('email')
                                            ? 'border-red-500 ring-2 ring-red-500/20 dark:border-red-500'
                                            : 'border-neutral-300 dark:border-neutral-700' }}
                                    "
                                    aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}">

                                <x-field-error
                                    :message="$errors->first('email')" />

                            </div>


                            <div>

                                <label
                                    for="account-password"
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
                                        id="account-password"
                                        name="password"
                                        type="password"
                                        autocomplete="current-password"
                                        class="
                                            block min-h-11 w-full
                                            border
                                            bg-white
                                            px-4 py-2.5 pr-12
                                            text-sm text-neutral-900
                                            outline-none
                                            transition
                                            focus:border-[#008080]
                                            focus:ring-2
                                            focus:ring-[#008080]/20
                                            dark:bg-neutral-950
                                            dark:text-white
                                            {{ $errors->has('password') && $activeLoginTab === 'account'
                                                ? 'border-red-500 ring-2 ring-red-500/20 dark:border-red-500'
                                                : 'border-neutral-300 dark:border-neutral-700' }}
                                        ">

                                    <button
                                        type="button"
                                        data-password-toggle
                                        data-target="account-password"
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
                                            aria-hidden="true">
                                        </i>

                                    </button>

                                </div>

                                @if ($activeLoginTab === 'account')

                                    <x-field-error
                                        :message="$errors->first('password')" />

                                @endif

                            </div>


                            <div
                                class="
                                    flex items-center
                                    justify-between gap-3
                                ">

                                <label
                                    class="
                                        inline-flex
                                        items-center gap-2.5
                                        text-sm text-neutral-700
                                        dark:text-neutral-300
                                    ">

                                    <input
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
                                        {{ old('login_type') !== 'employee' && old('remember') ? 'checked' : '' }}>

                                    <span>
                                        Remember me
                                    </span>

                                </label>


                                @if (Route::has('password.request'))

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

                                @endif

                            </div>


                            <button
                                type="submit"
                                data-loading-text="Signing in..."
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

                        </form>

                    </div>


                    {{-- Employee Login: Staff + Technician --}}
                    <div
                        id="employee-login-panel"
                        data-login-panel="employee"
                        role="tabpanel"
                        aria-labelledby="employee-tab"
                        class="mt-5">

                        <form
                            method="POST"
                            action="{{ route('login.store') }}"
                            class="space-y-4"
                            autocomplete="on"
                            data-lock-submit
                            novalidate>

                            @csrf

                            <input
                                type="hidden"
                                name="login_type"
                                value="employee">


                            <div>

                                <label
                                    for="employee-number"
                                    class="
                                        mb-1.5 block
                                        text-sm font-medium
                                        text-neutral-800
                                        dark:text-neutral-200
                                    ">
                                    Employee Number
                                </label>

                                <input
                                    id="employee-number"
                                    name="employee_number"
                                    type="text"
                                    value="{{ old('login_type') === 'employee' ? old('employee_number') : '' }}"
                                    autocomplete="username"
                                    autocapitalize="characters"
                                    spellcheck="false"
                                    placeholder="EMP-0001"
                                    class="
                                        block min-h-11 w-full
                                        border
                                        bg-white
                                        px-4 py-2.5
                                        font-mono
                                        text-sm text-neutral-900
                                        placeholder:text-neutral-400
                                        outline-none
                                        transition
                                        focus:border-[#008080]
                                        focus:ring-2
                                        focus:ring-[#008080]/20
                                        dark:bg-neutral-950
                                        dark:text-white
                                        dark:placeholder:text-neutral-500
                                        {{ $errors->has('employee_number')
                                            ? 'border-red-500 ring-2 ring-red-500/20 dark:border-red-500'
                                            : 'border-neutral-300 dark:border-neutral-700' }}
                                    "
                                    aria-invalid="{{ $errors->has('employee_number') ? 'true' : 'false' }}">

                                <x-field-error
                                    :message="$errors->first('employee_number')" />

                            </div>


                            <div>

                                <label
                                    for="employee-password"
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
                                        id="employee-password"
                                        name="password"
                                        type="password"
                                        autocomplete="current-password"
                                        class="
                                            block min-h-11 w-full
                                            border
                                            bg-white
                                            px-4 py-2.5 pr-12
                                            text-sm text-neutral-900
                                            outline-none
                                            transition
                                            focus:border-[#008080]
                                            focus:ring-2
                                            focus:ring-[#008080]/20
                                            dark:bg-neutral-950
                                            dark:text-white
                                            {{ $errors->has('password') && $activeLoginTab === 'employee'
                                                ? 'border-red-500 ring-2 ring-red-500/20 dark:border-red-500'
                                                : 'border-neutral-300 dark:border-neutral-700' }}
                                        ">

                                    <button
                                        type="button"
                                        data-password-toggle
                                        data-target="employee-password"
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
                                            aria-hidden="true">
                                        </i>

                                    </button>

                                </div>

                                @if ($activeLoginTab === 'employee')

                                    <x-field-error
                                        :message="$errors->first('password')" />

                                @endif

                            </div>


                            <div
                                class="
                                    border border-neutral-200
                                    bg-neutral-50
                                    px-3 py-2.5
                                    text-xs leading-5
                                    text-neutral-600
                                    dark:border-neutral-800
                                    dark:bg-neutral-950
                                    dark:text-neutral-400
                                ">

                                <p>
                                    Staff and Technicians sign in using the
                                    Employee Number issued by the administrator.
                                    On first login, use the temporary password
                                    provided with your account to start activation,
                                    then choose Email or SMS for your verification code.
                                </p>

                            </div>


                            <label
                                class="
                                    inline-flex
                                    items-center gap-2.5
                                    text-sm text-neutral-700
                                    dark:text-neutral-300
                                ">

                                <input
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
                                    {{ old('login_type') === 'employee' && old('remember') ? 'checked' : '' }}>

                                <span>
                                    Remember me
                                </span>

                            </label>


                            <button
                                type="submit"
                                data-loading-text="Signing in..."
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

                        </form>

                    </div>


                    {{-- Registration and home --}}
                    <div
                        class="
                            mt-5 flex flex-wrap
                            items-center justify-center
                            gap-2
                            border-t border-neutral-200
                            pt-4
                            text-sm
                            dark:border-neutral-800
                        ">

                        @if (Route::has('register'))

                            <span
                                data-account-registration
                                class="
                                    inline-flex
                                    items-center gap-2
                                ">

                                <span
                                    class="
                                        text-neutral-600
                                        dark:text-neutral-400
                                    ">
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
                                    class="
                                        mx-1
                                        text-neutral-400
                                        dark:text-neutral-600
                                    "
                                    aria-hidden="true">
                                    |
                                </span>

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

                </div>


                <p
                    class="
                        mt-3 text-center text-sm
                        text-neutral-500
                        dark:text-neutral-400
                    ">
                    © 2026 Rincomm Internet Service Provider
                </p>

            </div>

        </main>

    </div>


    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function() {
                const initialTab =
                    @json($activeLoginTab);

                const tabButtons =
                    document.querySelectorAll(
                        '[data-login-tab]'
                    );

                const panels =
                    document.querySelectorAll(
                        '[data-login-panel]'
                    );

                function activateTab(tabName) {
                    tabButtons.forEach(
                        function(button) {
                            const active =
                                button.dataset.loginTab ===
                                tabName;

                            button.setAttribute(
                                'aria-selected',
                                active ? 'true' : 'false'
                            );

                            button.classList.toggle(
                                'bg-[#008080]',
                                active
                            );

                            button.classList.toggle(
                                'text-white',
                                active
                            );

                            button.classList.toggle(
                                'text-neutral-600',
                                !active
                            );

                            button.classList.toggle(
                                'dark:text-neutral-300',
                                !active
                            );
                        }
                    );

                    panels.forEach(
                        function(panel) {
                            panel.hidden =
                                panel.dataset.loginPanel !==
                                tabName;
                        }
                    );

                    const registrationBlock =
                        document.querySelector(
                            '[data-account-registration]'
                        );

                    if (registrationBlock) {
                        registrationBlock.style.display =
                            tabName === 'employee'
                                ? 'none'
                                : 'inline-flex';
                    }

                    const targetInput =
                        tabName === 'employee'
                            ? document.getElementById(
                                'employee-number'
                            )
                            : document.getElementById(
                                'email'
                            );

                    if (targetInput) {
                        targetInput.focus();
                    }
                }

                tabButtons.forEach(
                    function(button) {
                        button.addEventListener(
                            'click',
                            function() {
                                activateTab(
                                    button.dataset.loginTab
                                );
                            }
                        );
                    }
                );

                activateTab(
                    initialTab === 'employee'
                        ? 'employee'
                        : 'account'
                );


                const toggleButtons =
                    document.querySelectorAll(
                        '[data-password-toggle]'
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

                                if (! input) {
                                    return;
                                }

                                input.type =
                                    input.type === 'password'
                                        ? 'text'
                                        : 'password';
                            }
                        );
                    }
                );
            }
        );
    </script>

</body>

</html>
