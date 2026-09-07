<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>My Profile - Rincomm</title>

    <script>
        (() => {
            const savedTheme = localStorage.getItem('rincomm-theme');

            const theme = savedTheme ??
                (window.matchMedia('(prefers-color-scheme: dark)').matches ?
                    'dark' :
                    'light');

            document.documentElement.classList.toggle(
                'dark',
                theme === 'dark'
            );
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-neutral-100 text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100">

    @php
    $dashboardUrl = match ($user->role) {
    'technician' => route('technician.dashboard'),
    'customer' => route('customer.dashboard'),
    default => route('dashboard'),
    };
    @endphp

    <!-- Global Page Loader -->
    <div
        id="page-loader"
        class="pointer-events-none fixed inset-x-0 top-0 z-[100] hidden h-1"
        aria-hidden="true">
        <div
            id="page-loader-bar"
            class="h-full bg-[#008080] transition-[width] duration-300 ease-out"
            style="width: 0%"></div>
    </div>


    <!-- Header -->
    <header
        class="
        border-b border-neutral-200
        bg-white
        dark:border-neutral-800
        dark:bg-neutral-900
    ">
        <div
            class="
            mx-auto flex min-h-16 max-w-5xl
            items-center justify-between gap-4
            px-4 py-3
            sm:px-6
            lg:px-8
        ">
            <div class="min-w-0">
                <h1 class="truncate text-lg font-semibold sm:text-xl">
                    My Profile
                </h1>

                <p class="hidden text-sm text-neutral-500 dark:text-neutral-400 sm:block">
                    Manage your Rincomm account information.
                </p>
            </div>


            <div class="flex shrink-0 items-center gap-2">

                <!-- Theme Toggle -->
                <button
                    data-theme-toggle
                    type="button"
                    class="
                    inline-flex h-10 w-10
                    items-center justify-center
                    border border-neutral-200
                    text-neutral-600
                    transition
                    hover:border-[#008080]
                    hover:bg-[#008080]/10
                    hover:text-[#008080]
                    focus:outline-none
                    focus:ring-2
                    focus:ring-[#008080]
                    focus:ring-offset-2
                    dark:border-neutral-700
                    dark:text-neutral-300
                    dark:hover:border-[#14B8A6]
                    dark:hover:bg-[#008080]/15
                    dark:hover:text-[#5EEAD4]
                    dark:focus:ring-offset-neutral-900
                "
                    aria-label="Toggle color theme"
                    title="Toggle color theme">
                    <i
                        data-theme-sun-icon
                        data-lucide="sun"
                        class="hidden h-5 w-5"
                        aria-hidden="true"></i>

                    <i
                        data-theme-moon-icon
                        data-lucide="moon"
                        class="h-5 w-5"
                        aria-hidden="true"></i>
                </button>


                <!-- Back to Dashboard -->
                <a
                    href="{{ $dashboardUrl }}"
                    class="
                    inline-flex h-10 items-center justify-center gap-2
                    bg-[#008080] px-3
                    text-sm font-semibold text-white
                    transition
                    hover:bg-[#006666]
                    focus:outline-none
                    focus:ring-2
                    focus:ring-[#008080]
                    focus:ring-offset-2
                    dark:focus:ring-offset-neutral-900
                    sm:px-4
                ">
                    <i
                        data-lucide="arrow-left"
                        class="h-4 w-4"
                        aria-hidden="true"></i>

                    <span class="hidden sm:inline">
                        Back to Dashboard
                    </span>

                    <span class="sm:hidden">
                        Back
                    </span>
                </a>

            </div>
        </div>
    </header>


    <!-- Main Content -->
    <main class="mx-auto max-w-5xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">

        <!-- Success Feedback -->
        @if (session('success'))
        <div
            role="status"
            aria-live="polite"
            class="
                mb-6 flex items-start gap-3
                border border-green-300
                bg-green-50
                px-4 py-3
                text-sm text-green-800
                dark:border-green-800
                dark:bg-green-950/40
                dark:text-green-300
                sm:text-base
            ">
            <i
                data-lucide="circle-check"
                class="mt-0.5 h-5 w-5 shrink-0"
                aria-hidden="true"></i>

            <span>
                {{ session('success') }}
            </span>
        </div>
        @endif


        <div class="grid gap-6 lg:grid-cols-3">

            <!-- Edit Profile -->
            <section
                class="
                lg:col-span-2
                border border-neutral-200
                bg-white p-5
                shadow-sm
                dark:border-neutral-800
                dark:bg-neutral-900
                sm:p-6
            ">
                <div class="mb-6">
                    <h2 class="text-xl font-semibold">
                        Profile Information
                    </h2>

                    <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                        Update your account name and email address.
                    </p>
                </div>


                <form
                    method="POST"
                    action="{{ route('profile.update') }}"
                    data-lock-submit
                    novalidate
                    class="space-y-6">
                    @csrf
                    @method('PATCH')


                    <!-- Name -->
                    <div>
                        <label
                            for="name"
                            class="mb-2 block text-sm font-medium">
                            Full Name
                        </label>

                        <div class="relative">
                            <input
                                id="name"
                                name="name"
                                type="text"
                                value="{{ old('name', $user->name) }}"
                                autocomplete="name"
                                aria-describedby="name-error"
                                @error('name') aria-invalid="true" @enderror
                                class="
                                min-h-11 w-full
                                border bg-white
                                px-3 py-2.5
                                text-base text-neutral-900
                                outline-none transition
                                placeholder:text-neutral-400
                                dark:bg-neutral-950
                                dark:text-neutral-100

                                @error('name')
                                    border-red-500
                                    ring-2 ring-red-500/20
                                    focus:border-red-500
                                    focus:ring-red-500/30
                                    dark:border-red-500
                                @else
                                    border-neutral-300
                                    focus:border-[#008080]
                                    focus:ring-2
                                    focus:ring-[#008080]/20
                                    dark:border-neutral-700
                                    dark:focus:border-[#14B8A6]
                                @enderror
                            ">
                        </div>

                        <x-field-error
                            id="name-error"
                            :message="$errors->first('name')" />
                    </div>


                    <!-- Email -->
                    <div>
                        <label
                            for="email"
                            class="mb-2 block text-sm font-medium">
                            Email Address
                        </label>

                        <div class="relative">

                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email', $user->email) }}"
                                autocomplete="email"
                                aria-describedby="email-error"
                                @error('email') aria-invalid="true" @enderror
                                class="
                                min-h-11 w-full
                                border bg-white
                                px-3 py-2.5
                                text-base text-neutral-900
                                outline-none transition
                                placeholder:text-neutral-400
                                dark:bg-neutral-950
                                dark:text-neutral-100

                                @error('email')
                                    border-red-500
                                    ring-2 ring-red-500/20
                                    focus:border-red-500
                                    focus:ring-red-500/30
                                    dark:border-red-500
                                @else
                                    border-neutral-300
                                    focus:border-[#008080]
                                    focus:ring-2
                                    focus:ring-[#008080]/20
                                    dark:border-neutral-700
                                    dark:focus:border-[#14B8A6]
                                @enderror
                            ">
                        </div>

                        <x-field-error
                            id="email-error"
                            :message="$errors->first('email')" />
                    </div>


                    <!-- Save -->
                    <div
                        class="
                        flex flex-col-reverse gap-3
                        border-t border-neutral-200
                        pt-5
                        dark:border-neutral-800
                        sm:flex-row
                        sm:items-center
                        sm:justify-end
                    ">
                        <a
                            href="{{ $dashboardUrl }}"
                            class="
                            inline-flex min-h-11
                            items-center justify-center
                            border border-neutral-300
                            px-4 py-2
                            text-sm font-semibold
                            text-neutral-700
                            transition
                            hover:bg-neutral-100
                            focus:outline-none
                            focus:ring-2
                            focus:ring-neutral-400
                            focus:ring-offset-2
                            dark:border-neutral-700
                            dark:text-neutral-200
                            dark:hover:bg-neutral-800
                            dark:focus:ring-offset-neutral-900
                        ">
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="
                            inline-flex min-h-11
                            items-center justify-center gap-2
                            bg-[#008080]
                            px-5 py-2
                            text-sm font-semibold
                            text-white
                            transition
                            hover:bg-[#006666]
                            focus:outline-none
                            focus:ring-2
                            focus:ring-[#008080]
                            focus:ring-offset-2
                            disabled:cursor-not-allowed
                            disabled:opacity-60
                            dark:focus:ring-offset-neutral-900
                        ">
                            <i
                                data-lucide="save"
                                class="h-4 w-4"
                                aria-hidden="true"></i>

                            <span>
                                Save Changes
                            </span>
                        </button>
                    </div>

                </form>
            </section>


            <!-- Account Details -->
            <aside
                class="
                h-fit border border-neutral-200
                bg-white p-5
                shadow-sm
                dark:border-neutral-800
                dark:bg-neutral-900
            ">
                <div class="mb-5 flex items-center gap-3">
                    <div
                        class="
                        flex h-11 w-11 shrink-0
                        items-center justify-center
                        bg-[#008080]/10
                        text-[#008080]
                        dark:bg-[#008080]/20
                        dark:text-[#5EEAD4]
                    ">
                        <i
                            data-lucide="shield-user"
                            class="h-5 w-5"
                            aria-hidden="true"></i>
                    </div>

                    <div>
                        <h2 class="font-semibold">
                            Account Details
                        </h2>

                        <p class="text-sm text-neutral-500 dark:text-neutral-400">
                            Read-only information
                        </p>
                    </div>
                </div>


                <dl class="space-y-4">

                    <!-- Role -->
                    <div>
                        <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                            Role
                        </dt>

                        <dd class="mt-1 flex items-center gap-2 text-sm font-semibold">
                            <i
                                data-lucide="user-cog"
                                class="h-4 w-4 text-neutral-400"
                                aria-hidden="true"></i>

                            <span>
                                {{ ucfirst($user->role) }}
                            </span>
                        </dd>
                    </div>


                    <!-- Account Status -->
                    <div>
                        <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                            Account Status
                        </dt>

                        <dd class="mt-1">
                            @if ($user->account_status === 'active')
                            <span
                                class="
                                    inline-flex items-center gap-1.5
                                    border border-green-300
                                    bg-green-50
                                    px-2.5 py-1
                                    text-sm font-medium
                                    text-green-700
                                    dark:border-green-800
                                    dark:bg-green-950/40
                                    dark:text-green-300
                                ">
                                <i
                                    data-lucide="circle-check"
                                    class="h-4 w-4"
                                    aria-hidden="true"></i>

                                Active
                            </span>
                            @else
                            <span
                                class="
                                    inline-flex items-center gap-1.5
                                    border border-neutral-300
                                    bg-neutral-100
                                    px-2.5 py-1
                                    text-sm font-medium
                                    text-neutral-700
                                    dark:border-neutral-700
                                    dark:bg-neutral-800
                                    dark:text-neutral-300
                                ">
                                <i
                                    data-lucide="circle-minus"
                                    class="h-4 w-4"
                                    aria-hidden="true"></i>

                                {{ ucfirst($user->account_status) }}
                            </span>
                            @endif
                        </dd>
                    </div>


                    <!-- Account Email -->
                    <div>
                        <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                            Current Email
                        </dt>

                        <dd class="mt-1 break-all text-sm">
                            {{ $user->email }}
                        </dd>
                    </div>

                </dl>


                <div
                    class="
                    mt-5 border-t border-neutral-200
                    pt-4
                    text-sm text-neutral-500
                    dark:border-neutral-800
                    dark:text-neutral-400
                ">
                    Role and account status can only be managed through authorized system administration.
                </div>
            </aside>

        </div>

    </main>

</body>

</html>