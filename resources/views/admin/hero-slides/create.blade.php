@extends('layouts.app')

@section('title', 'Add Hero Slide')

@section('page-title', 'Add Hero Slide')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-3">

        <a
            href="{{ route('admin.hero-slides.index') }}"
            class="
                inline-flex h-10 w-10
                items-center justify-center
                rounded-xl border border-gray-200
                text-gray-600 transition
                hover:border-[#008080]
                hover:text-[#008080]
                dark:border-gray-700
                dark:text-gray-300
            "
            aria-label="Back to hero slides">

            <i
                data-lucide="arrow-left"
                class="h-4 w-4">
            </i>

        </a>


        <div>

            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                Add Hero Slide
            </h1>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Add content to the homepage carousel.
            </p>

        </div>

    </div>


    {{-- Slide form --}}
    <form
        action="{{ route('admin.hero-slides.store') }}"
        method="POST"
        enctype="multipart/form-data"
        data-lock-submit
        class="space-y-6">

        @csrf

        @include('admin.hero-slides._form')


        {{-- Form actions --}}
        <div
            class="
                flex flex-col-reverse gap-3
                border-t border-gray-200 pt-6
                sm:flex-row sm:justify-end
                dark:border-gray-800
            ">

            <a
                href="{{ route('admin.hero-slides.index') }}"
                class="
                    inline-flex items-center justify-center
                    rounded-xl border border-gray-300
                    px-5 py-2.5
                    text-sm font-medium text-gray-700
                    transition
                    hover:bg-gray-50
                    dark:border-gray-700
                    dark:text-gray-300
                    dark:hover:bg-gray-800
                ">
                Cancel
            </a>


            <button
                type="submit"
                class="
                    inline-flex items-center justify-center gap-2
                    rounded-xl
                    bg-gradient-to-r from-[#006666] to-[#008080]
                    px-5 py-2.5
                    text-sm font-medium text-white
                    shadow-sm transition
                    hover:opacity-90
                ">

                <i
                    data-lucide="save"
                    class="h-4 w-4">
                </i>

                Save Slide

            </button>

        </div>

    </form>

</div>
@endsection