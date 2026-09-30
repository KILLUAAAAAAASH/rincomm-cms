@extends('layouts.app')

@section('title', 'Add Hero Slide')

@section('page-title', 'Add Hero Slide')

@section('content')

<div class="mx-auto max-w-7xl space-y-2">

    {{-- Header --}}
    <div class="flex items-center gap-3">

        <a
            href="{{ route('admin.hero-slides.index') }}"
            class="
                inline-flex h-9 w-9 shrink-0
                items-center justify-center
                border border-gray-300
                text-gray-600
                transition
                hover:border-[#008080]
                hover:text-[#008080]
                dark:border-neutral-700
                dark:text-gray-300
                dark:hover:border-[#14B8A6]
                dark:hover:text-[#5EEAD4]
            "
            aria-label="Back to Hero Slides"
            title="Back to Hero Slides">

            <i
                data-lucide="arrow-left"
                class="h-4 w-4"
                aria-hidden="true">
            </i>

        </a>


        <div class="min-w-0">

            <h1
                class="
                    text-xl font-semibold
                    tracking-tight
                    text-gray-900
                    dark:text-white
                ">
                Add Hero Slide
            </h1>

            <p
                class="
                    mt-0.5
                    text-xs text-gray-500
                    dark:text-gray-400
                ">
                Add content to the homepage carousel.
            </p>

        </div>

    </div>


    <form
        action="{{ route('admin.hero-slides.store') }}"
        method="POST"
        enctype="multipart/form-data"
        data-lock-submit
        class="space-y-2">

        @csrf


        @include('admin.hero-slides._form')


        {{-- Actions --}}
        <div
            class="
                flex flex-col-reverse gap-2
                border border-gray-200
                bg-gray-50
                px-4 py-3
                sm:flex-row
                sm:justify-end
                dark:border-neutral-800
                dark:bg-neutral-950
            ">

            <a
                href="{{ route('admin.hero-slides.index') }}"
                class="
                    inline-flex min-h-9
                    items-center justify-center
                    border border-gray-300
                    bg-white
                    px-4 py-2
                    text-sm font-medium
                    text-gray-700
                    transition
                    hover:bg-gray-50
                    dark:border-neutral-700
                    dark:bg-neutral-900
                    dark:text-gray-300
                    dark:hover:bg-neutral-800
                ">
                Cancel
            </a>


            <button
                type="submit"
                class="
                    inline-flex min-h-9
                    items-center justify-center
                    gap-2
                    bg-[#008080]
                    px-4 py-2
                    text-sm font-semibold
                    text-white
                    transition
                    hover:bg-[#006666]
                    focus:outline-none
                    focus:ring-2
                    focus:ring-[#008080]/20
                ">

                <i
                    data-lucide="save"
                    class="h-4 w-4"
                    aria-hidden="true">
                </i>

                Save Slide

            </button>

        </div>

    </form>

</div>

@endsection