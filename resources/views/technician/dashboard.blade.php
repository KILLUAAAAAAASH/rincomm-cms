<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Dashboard - Rincomm</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-neutral-100 text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100">

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold">
            Technician Dashboard
        </h1>

        <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
            Technician portal is ready.
        </p>
    </main>

    <form method="POST" action="{{ route('logout') }}" class="mt-6">
    @csrf

    <button
        type="submit"
        data-lock-submit
        class="bg-[#008080] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#006666]"
    >
        Logout
    </button>
</form>

</body>
</html>