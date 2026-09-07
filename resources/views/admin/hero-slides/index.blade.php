@extends('layouts.app')

@section('title', 'Hero Slides')

@section('page-title', 'Hero Slides')

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                Hero Slides
            </h1>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Manage homepage promotions, events, announcements, and service advisories.
            </p>
        </div>

        <a
            href="{{ route('admin.hero-slides.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-xl
                   bg-gradient-to-r from-[#006666] to-[#008080]
                   px-4 py-2.5 text-sm font-medium text-white
                   shadow-sm transition hover:opacity-90"
        >
            <i data-lucide="plus" class="h-4 w-4"></i>
            Add Slide
        </a>
    </div>


    {{-- Success Message --}}
    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3
                    text-sm text-green-700
                    dark:border-green-900 dark:bg-green-950/40 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif


    {{-- Maximum Slide Information --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm
                dark:border-neutral-800 dark:bg-neutral-900">
        <div class="flex items-start gap-3">
            <div class="mt-0.5 rounded-lg bg-teal-50 p-2 dark:bg-teal-950/40">
                <i
                    data-lucide="image"
                    class="h-5 w-5 text-[#008080]"
                ></i>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">
                    Homepage Carousel
                </p>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    A maximum of 5 slides can be active at the same time.
                    The homepage automatically displays only active and currently scheduled slides.
                </p>
            </div>
        </div>
    </div>


    @if ($heroSlides->isEmpty())

        {{-- Empty State --}}
        <div class="rounded-2xl border border-dashed border-gray-300
                    bg-white px-6 py-14 text-center shadow-sm
                    dark:border-gray-700 dark:bg-neutral-900">

            <div class="mx-auto flex h-12 w-12 items-center justify-center
                        rounded-xl bg-gray-100 dark:bg-neutral-800">
                <i
                    data-lucide="images"
                    class="h-6 w-6 text-gray-500 dark:text-gray-400"
                ></i>
            </div>

            <h2 class="mt-4 text-base font-semibold text-gray-900 dark:text-white">
                No hero slides yet
            </h2>

            <p class="mx-auto mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">
                Add your first slide for promotions, announcements,
                events, coverage updates, or maintenance advisories.
            </p>

            <a
                href="{{ route('admin.hero-slides.create') }}"
                class="mt-5 inline-flex items-center gap-2 rounded-xl
                       border border-[#008080] px-4 py-2.5 text-sm
                       font-medium text-[#008080] transition
                       hover:bg-teal-50 dark:hover:bg-teal-950/30"
            >
                <i data-lucide="plus" class="h-4 w-4"></i>
                Add First Slide
            </a>
        </div>

    @else

        {{-- Desktop Table --}}
        <div class="hidden overflow-hidden rounded-2xl border border-gray-200
                    bg-white shadow-sm md:block
                    dark:border-neutral-800 dark:bg-neutral-900">

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">

                    <thead class="bg-gray-50 dark:bg-neutral-800/60">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase
                                       tracking-wide text-gray-500 dark:text-gray-400">
                                Slide
                            </th>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase
                                       tracking-wide text-gray-500 dark:text-gray-400">
                                Category
                            </th>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase
                                       tracking-wide text-gray-500 dark:text-gray-400">
                                Schedule
                            </th>

                            <th class="px-5 py-3 text-center text-xs font-semibold uppercase
                                       tracking-wide text-gray-500 dark:text-gray-400">
                                Order
                            </th>

                            <th class="px-5 py-3 text-center text-xs font-semibold uppercase
                                       tracking-wide text-gray-500 dark:text-gray-400">
                                Status
                            </th>

                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase
                                       tracking-wide text-gray-500 dark:text-gray-400">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">

                        @foreach ($heroSlides as $slide)
                            <tr class="transition hover:bg-gray-50/70 dark:hover:bg-gray-800/40">

                                {{-- Image + Title --}}
                                <td class="px-5 py-4">
                                    <div class="flex min-w-[260px] items-center gap-4">

                                        <img
                                            src="{{ asset('storage/' . $slide->image_path) }}"
                                            alt="{{ $slide->alt_text ?: $slide->title ?: 'Hero slide' }}"
                                            class="h-16 w-24 rounded-xl object-cover"
                                        >

                                        <div class="min-w-0">
                                            <p class="max-w-xs truncate text-sm font-medium
                                                      text-gray-900 dark:text-white">
                                                {{ $slide->title ?: 'Image Only Slide' }}
                                            </p>

                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                {{ \Illuminate\Support\Str::headline($slide->content_type) }}
                                            </p>
                                        </div>
                                    </div>
                                </td>


                                {{-- Category --}}
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full bg-gray-100
                                                 px-2.5 py-1 text-xs font-medium
                                                 text-gray-700
                                                 dark:bg-neutral-800 dark:text-gray-300">
                                        {{ \Illuminate\Support\Str::headline($slide->category) }}
                                    </span>
                                </td>


                                {{-- Schedule --}}
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    @if ($slide->starts_at || $slide->ends_at)

                                        @if ($slide->starts_at)
                                            <div>
                                                From:
                                                {{ $slide->starts_at->format('M d, Y h:i A') }}
                                            </div>
                                        @endif

                                        @if ($slide->ends_at)
                                            <div class="mt-1">
                                                Until:
                                                {{ $slide->ends_at->format('M d, Y h:i A') }}
                                            </div>
                                        @endif

                                    @else
                                        <span class="text-gray-400">
                                            No schedule
                                        </span>
                                    @endif
                                </td>


                                {{-- Display Order --}}
                                <td class="px-5 py-4 text-center text-sm font-medium
                                           text-gray-700 dark:text-gray-300">
                                    {{ $slide->display_order }}
                                </td>


                                {{-- Status --}}
                                <td class="px-5 py-4 text-center">
                                    @php
                                       if (!$slide->is_active) {
                                              $slideStatus = 'Inactive';
                                              $statusClasses = 'bg-neutral-100 text-neutral-600
                                              dark:bg-neutral-800 dark:text-neutral-400';
                                              $dotClasses = 'bg-neutral-400';
                                              } elseif ($slide->starts_at && $slide->starts_at->isFuture()) {
                                              $slideStatus = 'Scheduled';
                                               $statusClasses = 'bg-blue-50 text-blue-700
                                              dark:bg-blue-950/40 dark:text-blue-300';
                                              $dotClasses = 'bg-blue-500';
                                              } elseif ($slide->ends_at && $slide->ends_at->isPast()) {
                                             $slideStatus = 'Expired';
                                              $statusClasses = 'bg-amber-50 text-amber-700
                                              dark:bg-amber-950/40 dark:text-amber-300';
                                               $dotClasses = 'bg-amber-500';
                                  } else {
                                            $slideStatus = 'Published';
                                            $statusClasses = 'bg-green-50 text-green-700
                                            dark:bg-green-950/40 dark:text-green-300';
                                            $dotClasses = 'bg-green-500';
                                        }
                                    @endphp

<span
    class="inline-flex items-center gap-1.5 rounded-full
           px-2.5 py-1 text-xs font-medium
           {{ $statusClasses }}"
>
    <span class="h-1.5 w-1.5 rounded-full {{ $dotClasses }}"></span>

    {{ $slideStatus }}
</span>
                                </td>


                                {{-- Actions --}}
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">

                                        <a
                                            href="{{ route('admin.hero-slides.edit', $slide) }}"
                                            class="inline-flex h-9 w-9 items-center justify-center
                                                   rounded-lg border border-gray-200
                                                   text-gray-600 transition hover:border-[#008080]
                                                   hover:text-[#008080]
                                                   dark:border-gray-700 dark:text-gray-300"
                                            title="Edit slide"
                                        >
                                            <i data-lucide="pencil" class="h-4 w-4"></i>
                                        </a>

                                        <button
                                            type="button"
                                            data-delete-slide
                                            data-delete-url="{{ route('admin.hero-slides.destroy', $slide) }}"
                                            data-delete-title="{{ $slide->title ?: 'Image Only Slide' }}"
                                            class="inline-flex h-9 w-9 items-center justify-center
                                                   rounded-lg border border-gray-200 text-red-600
                                                   transition hover:border-red-300 hover:bg-red-50
                                                   dark:border-gray-700 dark:hover:bg-red-950/30"
                                            title="Delete slide"
                                        >
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </button>

                                    </div>
                                </td>

                            </tr>
                        @endforeach

                    </tbody>
                </table>
            </div>
        </div>


        {{-- Mobile Cards --}}
        <div class="grid gap-4 md:hidden">

            @foreach ($heroSlides as $slide)
                <article class="overflow-hidden rounded-2xl border border-gray-200
                                bg-white shadow-sm
                                dark:border-neutral-800 dark:bg-neutral-900">

                    <img
                        src="{{ asset('storage/' . $slide->image_path) }}"
                        alt="{{ $slide->alt_text ?: $slide->title ?: 'Hero slide' }}"
                        class="h-44 w-full object-cover"
                    >

                    <div class="space-y-4 p-4">

                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="truncate text-base font-semibold
                                           text-gray-900 dark:text-white">
                                    {{ $slide->title ?: 'Image Only Slide' }}
                                </h2>

                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ \Illuminate\Support\Str::headline($slide->content_type) }}
                                </p>
                            </div>

                            @if ($slide->is_active)
                                <span class="shrink-0 rounded-full bg-green-50 px-2.5 py-1
                                             text-xs font-medium text-green-700
                                             dark:bg-green-950/40 dark:text-green-300">
                                    Active
                                </span>
                            @else
                                <span class="shrink-0 rounded-full bg-gray-100 px-2.5 py-1
                                             text-xs font-medium text-gray-600
                                             dark:bg-neutral-800 dark:text-gray-400">
                                    Inactive
                                </span>
                            @endif
                        </div>


                        <div class="grid grid-cols-2 gap-3 text-sm">

                            <div>
                                <p class="text-xs text-gray-400">Category</p>
                                <p class="mt-1 text-gray-700 dark:text-gray-300">
                                    {{ \Illuminate\Support\Str::headline($slide->category) }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-400">Display Order</p>
                                <p class="mt-1 text-gray-700 dark:text-gray-300">
                                    {{ $slide->display_order }}
                                </p>
                            </div>

                        </div>


                        @if ($slide->starts_at || $slide->ends_at)
                            <div class="border-t border-gray-100 pt-3 text-xs
                                        text-gray-500 dark:border-neutral-800
                                        dark:text-gray-400">

                                @if ($slide->starts_at)
                                    <p>
                                        From:
                                        {{ $slide->starts_at->format('M d, Y h:i A') }}
                                    </p>
                                @endif

                                @if ($slide->ends_at)
                                    <p class="mt-1">
                                        Until:
                                        {{ $slide->ends_at->format('M d, Y h:i A') }}
                                    </p>
                                @endif

                            </div>
                        @endif


                        <div class="flex gap-2 border-t border-gray-100 pt-4
                                    dark:border-neutral-800">

                            <a
                                href="{{ route('admin.hero-slides.edit', $slide) }}"
                                class="inline-flex flex-1 items-center justify-center gap-2
                                       rounded-xl border border-gray-200 px-3 py-2.5
                                       text-sm font-medium text-gray-700 transition
                                       hover:border-[#008080] hover:text-[#008080]
                                       dark:border-gray-700 dark:text-gray-300"
                            >
                                <i data-lucide="pencil" class="h-4 w-4"></i>
                                Edit
                            </a>

                            <button
                                type="button"
                                data-delete-slide
                                data-delete-url="{{ route('admin.hero-slides.destroy', $slide) }}"
                                data-delete-title="{{ $slide->title ?: 'Image Only Slide' }}"
                                class="inline-flex flex-1 items-center justify-center gap-2
                                       rounded-xl border border-red-200 px-3 py-2.5
                                       text-sm font-medium text-red-600 transition
                                       hover:bg-red-100
                                       dark:border-red-900 dark:hover:bg-red-950/30"
                            >
                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                Delete
                            </button>

                        </div>

                    </div>
                </article>
            @endforeach

        </div>

    @endif


    {{-- Delete Confirmation Modal --}}
    <div
        id="hero-slide-delete-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center p-4"
        aria-hidden="true"
    >
        <div
            id="hero-slide-delete-overlay"
            class="absolute inset-0 bg-black/50"
        ></div>

        <div class="relative z-10 w-full max-w-md rounded-2xl
                    bg-white p-6 shadow-xl dark:bg-neutral-900">

            <div class="flex h-11 w-11 items-center justify-center
                        rounded-xl bg-red-50 dark:bg-red-950/40">
                <i data-lucide="trash-2" class="h-5 w-5 text-red-600"></i>
            </div>

            <h2 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">
                Delete Hero Slide?
            </h2>

            <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">
                You are about to delete
                <span
                    id="hero-slide-delete-title"
                    class="font-medium text-gray-700 dark:text-gray-200"
                ></span>.
                This action cannot be undone.
            </p>

            <form
                id="hero-slide-delete-form"
                method="POST"
                data-lock-submit
                class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"
            >
                @csrf
                @method('DELETE')

                <button
                    id="hero-slide-delete-cancel"
                    type="button"
                    class="rounded-xl border border-gray-200 px-4 py-2.5
                           text-sm font-medium text-gray-700
                           hover:bg-gray-50
                           dark:border-gray-700 dark:text-gray-300
                           dark:hover:bg-gray-800"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2
                           rounded-xl bg-red-600 px-4 py-2.5
                           text-sm font-medium text-white hover:bg-red-700"
                >
                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                    Delete Slide
                </button>
            </form>

        </div>
    </div>

</div>
@endsection