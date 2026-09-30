@extends('layouts.app')

@section('title', 'Edit Internet Package')

@section('page-title', 'Edit Internet Package')

@section('content')

<div class="mx-auto max-w-6xl space-y-2">

    {{-- Header --}}
    <div class="flex items-center gap-3">

        <a
            href="{{ route('admin.service-plans.index') }}"
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
            aria-label="Back to Internet Packages"
            title="Back to Internet Packages">

            <i
                data-lucide="arrow-left"
                class="h-4 w-4"
                aria-hidden="true">
            </i>

        </a>


        <div class="min-w-0">

            <h1
                class="
                    truncate
                    text-xl font-semibold
                    tracking-tight
                    text-gray-900
                    dark:text-white
                ">
                Edit Internet Package
            </h1>

            <p
                class="
                    mt-0.5 truncate
                    text-xs text-gray-500
                    dark:text-gray-400
                ">
                Update {{ $servicePlan->name }} package configuration.
            </p>

        </div>

    </div>


    <form
        action="{{ route('admin.service-plans.update', $servicePlan) }}"
        method="POST"
        data-lock-submit>

        @csrf
        @method('PATCH')


        @include('admin.service-plans._form')


        {{-- Actions --}}
        <div
            class="
                flex flex-col-reverse
                gap-2
                border-x border-b
                border-gray-200
                bg-gray-50
                px-4 py-3
                sm:flex-row
                sm:justify-end
                dark:border-neutral-800
                dark:bg-neutral-950
            ">

            <a
                href="{{ route('admin.service-plans.index') }}"
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

                Save Changes

            </button>

        </div>

    </form>

</div>

@endsection