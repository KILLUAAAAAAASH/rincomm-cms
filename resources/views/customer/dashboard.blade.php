<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Portal - Rincomm</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-neutral-100 text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100">

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        @if (session('success'))
        <div
            role="status"
            aria-live="polite"
            class="mb-6 flex items-start gap-3 border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950/40 dark:text-green-300 sm:text-base">
            <i
                data-lucide="circle-check"
                class="mt-0.5 h-5 w-5 shrink-0"></i>

            <span>
                {{ session('success') }}
            </span>
        </div>
        @endif

        @if (session('error'))
        <div
            role="alert"
            aria-live="assertive"
            class="
            mb-6 flex items-start gap-3
            border border-red-300
            bg-red-50
            px-4 py-3
            text-sm text-red-800
            dark:border-red-900
            dark:bg-red-950/40
            dark:text-red-200
            sm:text-base
        ">
            <i
                data-lucide="triangle-alert"
                class="mt-0.5 h-5 w-5 shrink-0"></i>

            <span>
                {{ session('error') }}
            </span>
        </div>
        @endif

        <h1 class="text-2xl font-semibold">
            Customer Portal
        </h1>

        <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
            Customer portal is ready.
        </p>
    </main>

    <form method="POST" action="{{ route('logout') }}" class="mt-6">
        @csrf

        <button
            type="submit"
            data-lock-submit
            class="bg-[#008080] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#006666]">
            Logout
        </button>
    </form>

</body>

</html>