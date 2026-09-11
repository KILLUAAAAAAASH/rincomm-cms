<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <meta
        name="description"
        content="Rincomm Internet Service Management System">

    <title>@yield('title', 'Rincomm')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-white text-slate-900">

    {{-- Page content --}}
    <main>
        @yield('content')
    </main>

</body>

</html>