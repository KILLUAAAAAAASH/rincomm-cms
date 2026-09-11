@extends('layouts.app')

@section('title', 'Edit Hero Slide')

@section('page-title', 'Edit Hero Slide')

@section('content')

<div
    class="
        space-y-3
        xl:flex
        xl:h-[calc(100dvh-7rem)]
        xl:flex-col
        xl:space-y-0
    ">

    {{-- Header --}}
    <div class="flex shrink-0 items-center gap-3 xl:mb-3">

        <a
            href="{{ route('admin.hero-slides.index') }}"
            class="
                inline-flex h-9 w-9 shrink-0
                items-center justify-center
                rounded-xl border border-neutral-200
                text-neutral-600 transition
                hover:border-[#008080]
                hover:text-[#008080]
                dark:border-neutral-700
                dark:text-neutral-300
            "
            aria-label="Back to hero slides">

            <i
                data-lucide="arrow-left"
                class="h-4 w-4">
            </i>

        </a>


        <div class="min-w-0">

            <h1 class="truncate text-xl font-semibold text-neutral-900 dark:text-neutral-100">
                Edit Hero Slide
            </h1>

            <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                Update the homepage carousel content.
            </p>

        </div>

    </div>


    {{-- Slide form --}}
    <form
        action="{{ route('admin.hero-slides.update', $heroSlide) }}"
        method="POST"
        enctype="multipart/form-data"
        data-lock-submit
        class="
            space-y-3
            xl:flex
            xl:min-h-0
            xl:flex-1
            xl:flex-col
            xl:space-y-0
        ">

        @csrf
        @method('PUT')


        <div class="xl:min-h-0 xl:flex-1">

            @include('admin.hero-slides._form', [
            'compactHeroForm' => true,
            ])

        </div>


        {{-- Form actions --}}
        <div
            class="
                flex shrink-0 flex-col-reverse gap-2
                border-t border-neutral-200
                pt-3
                sm:flex-row
                sm:justify-end
                xl:mt-3
                dark:border-neutral-800
            ">

            <a
                href="{{ route('admin.hero-slides.index') }}"
                class="
                    inline-flex min-h-9
                    items-center justify-center
                    rounded-xl border border-neutral-300
                    px-5 py-2
                    text-sm font-medium text-neutral-700
                    transition
                    hover:bg-neutral-50
                    dark:border-neutral-700
                    dark:text-neutral-300
                    dark:hover:bg-neutral-800
                ">
                Cancel
            </a>


            <button
                type="submit"
                class="
                    inline-flex min-h-9
                    items-center justify-center gap-2
                    rounded-xl bg-[#008080]
                    px-5 py-2
                    text-sm font-medium text-white
                    shadow-sm transition
                    hover:bg-[#006666]
                ">

                <i
                    data-lucide="save"
                    class="h-4 w-4">
                </i>

                Save Changes

            </button>

        </div>

    </form>

</div>

@endsection