@extends('layouts.app')

@section('title', 'Edit Hero Slide')

@section('page-title', 'Edit Hero Slide')

@section('content')

<div class="space-y-6">

    <div class="flex items-center gap-3">

        <a
            href="{{ route('admin.hero-slides.index') }}"
            class="inline-flex h-10 w-10 items-center justify-center
                   rounded-xl border border-neutral-200
                   text-neutral-600 transition
                   hover:border-[#008080]
                   hover:text-[#008080]
                   dark:border-neutral-700
                   dark:text-neutral-300"
            aria-label="Back to hero slides"
        >
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
        </a>

        <div>
            <h1 class="text-2xl font-semibold
                       text-neutral-900
                       dark:text-neutral-100">
                Edit Hero Slide
            </h1>

            <p class="mt-1 text-sm
                      text-neutral-500
                      dark:text-neutral-400">
                Update the homepage carousel content.
            </p>
        </div>

    </div>


    <form
        action="{{ route('admin.hero-slides.update', $heroSlide) }}"
        method="POST"
        enctype="multipart/form-data"
        data-lock-submit
        class="space-y-6"
    >
        @csrf
        @method('PUT')

        @include('admin.hero-slides._form')

        <div class="flex flex-col-reverse gap-3
                    border-t border-neutral-200 pt-6
                    sm:flex-row sm:justify-end
                    dark:border-neutral-800">

            <a
                href="{{ route('admin.hero-slides.index') }}"
                class="inline-flex items-center justify-center
                       rounded-xl border border-neutral-300
                       px-5 py-2.5 text-sm font-medium
                       text-neutral-700 transition
                       hover:bg-neutral-50
                       dark:border-neutral-700
                       dark:text-neutral-300
                       dark:hover:bg-neutral-800"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="inline-flex items-center justify-center gap-2
                       rounded-xl bg-[#008080]
                       px-5 py-2.5 text-sm font-medium
                       text-white shadow-sm transition
                       hover:bg-[#006666]"
            >
                <i data-lucide="save" class="h-4 w-4"></i>

                Save Changes
            </button>

        </div>

    </form>

</div>

@endsection