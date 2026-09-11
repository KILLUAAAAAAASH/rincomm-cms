<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <meta
        name="description"
        content="Rincomm Internet Service Management System">

    <title>{{ config('app.name', 'Rincomm') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-neutral-100 text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100">

    <main
        class="
            flex min-h-screen
            items-center justify-center
            px-4 py-8
            sm:px-6
        ">

        <div class="w-full max-w-xl">

            {{-- Welcome card --}}
            <section
                class="
                    border border-neutral-200
                    bg-white
                    p-6
                    text-center
                    shadow-sm
                    dark:border-neutral-800
                    dark:bg-neutral-900
                    sm:p-8
                ">

                <div
                    class="
                        mx-auto flex h-14 w-14
                        items-center justify-center
                        rounded-xl
                        bg-[#008080]
                        text-white
                    ">

                    <i
                        data-lucide="wifi"
                        class="h-7 w-7">
                    </i>

                </div>


                <h1 class="mt-5 text-2xl font-semibold text-neutral-950 dark:text-white sm:text-3xl">
                    Rincomm
                </h1>

                <p class="mt-2 text-sm leading-6 text-neutral-600 dark:text-neutral-400 sm:text-base">
                    Internet Service Management System
                </p>


                {{-- Account actions --}}
                @if (Route::has('login'))

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">

                    @auth

                    <a
                        href="{{ url('/dashboard') }}"
                        class="
                                    inline-flex min-h-11
                                    items-center justify-center
                                    rounded-lg
                                    bg-[#008080]
                                    px-5 py-2.5
                                    text-sm font-semibold text-white
                                    transition
                                    hover:bg-[#006666]
                                    focus:outline-none
                                    focus:ring-2
                                    focus:ring-[#008080]
                                    focus:ring-offset-2
                                    dark:focus:ring-offset-neutral-900
                                ">
                        Go to Dashboard
                    </a>

                    @else

                    <a
                        href="{{ route('login') }}"
                        class="
                                    inline-flex min-h-11
                                    items-center justify-center
                                    rounded-lg
                                    bg-[#008080]
                                    px-5 py-2.5
                                    text-sm font-semibold text-white
                                    transition
                                    hover:bg-[#006666]
                                    focus:outline-none
                                    focus:ring-2
                                    focus:ring-[#008080]
                                    focus:ring-offset-2
                                    dark:focus:ring-offset-neutral-900
                                ">
                        Login
                    </a>


                    @if (Route::has('register'))

                    <a
                        href="{{ route('register') }}"
                        class="
                                        inline-flex min-h-11
                                        items-center justify-center
                                        rounded-lg
                                        border border-neutral-300
                                        bg-white
                                        px-5 py-2.5
                                        text-sm font-semibold text-neutral-700
                                        transition
                                        hover:border-[#008080]
                                        hover:text-[#008080]
                                        focus:outline-none
                                        focus:ring-2
                                        focus:ring-[#008080]
                                        focus:ring-offset-2
                                        dark:border-neutral-700
                                        dark:bg-neutral-950
                                        dark:text-neutral-200
                                        dark:hover:border-teal-400
                                        dark:hover:text-teal-400
                                        dark:focus:ring-offset-neutral-900
                                    ">
                        Register
                    </a>

                    @endif

                    @endauth

                </div>

                @endif


                {{-- Public site --}}
                @if (Route::has('home'))

                <div class="mt-5">

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
                        Visit Rincomm Website
                    </a>

                </div>

                @endif

            </section>

        </div>

    </main>

</body>

</html>