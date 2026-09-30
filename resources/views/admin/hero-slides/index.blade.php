@extends('layouts.app')

@section('title', 'Hero Slides')

@section('page-title', 'Hero Slides')

@section('content')

<div class="space-y-2">

    {{-- Page header --}}
    <div
        class="
            flex flex-col gap-3
            sm:flex-row
            sm:items-center
            sm:justify-between
        ">

        <div>

            <h1
                class="
                    text-xl font-semibold
                    tracking-tight
                    text-gray-900
                    dark:text-white
                ">
                Hero Slides
            </h1>

            <p
                class="
                    mt-0.5
                    text-xs text-gray-500
                    dark:text-gray-400
                ">
                Manage homepage promotions, announcements, events, and service advisories.
            </p>

        </div>


        <a
            href="{{ route('admin.hero-slides.create') }}"
            class="
                inline-flex min-h-10
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
                data-lucide="plus"
                class="h-4 w-4"
                aria-hidden="true">
            </i>

            Add Slide

        </a>

    </div>


    {{-- Success feedback --}}
    @if (session('success'))

    <div
        role="status"
        class="
            flex items-start gap-2
            border border-green-200
            bg-green-50
            px-3 py-2.5
            text-sm text-green-800
            dark:border-green-900
            dark:bg-green-950/30
            dark:text-green-200
        ">

        <i
            data-lucide="circle-check"
            class="mt-0.5 h-4 w-4 shrink-0"
            aria-hidden="true">
        </i>

        <p>
            {{ session('success') }}
        </p>

    </div>

    @endif


    {{-- Carousel information --}}
    <div
        class="
            flex items-start gap-2
            border border-gray-200
            bg-gray-50
            px-3 py-2.5
            text-xs text-gray-600
            dark:border-neutral-800
            dark:bg-neutral-950
            dark:text-gray-400
        ">

        <i
            data-lucide="info"
            class="
                mt-0.5 h-3.5 w-3.5
                shrink-0
                text-[#008080]
                dark:text-[#5EEAD4]
            "
            aria-hidden="true">
        </i>

        <p>
            Up to 5 slides can be active at the same time.
            The homepage displays only slides that are active and currently scheduled.
        </p>

    </div>


    @if ($heroSlides->isEmpty())

    {{-- Empty state --}}
    <div
        class="
            border border-dashed
            border-gray-300
            bg-white
            px-4 py-10
            text-center
            dark:border-neutral-700
            dark:bg-neutral-900
        ">

        <i
            data-lucide="images"
            class="
                mx-auto h-5 w-5
                text-gray-400
            "
            aria-hidden="true">
        </i>

        <h2
            class="
                mt-3
                text-sm font-semibold
                text-gray-900
                dark:text-white
            ">
            No hero slides
        </h2>

        <p
            class="
                mt-1
                text-xs text-gray-500
                dark:text-gray-400
            ">
            Add the first homepage carousel slide.
        </p>

        <a
            href="{{ route('admin.hero-slides.create') }}"
            class="
                mt-4 inline-flex min-h-9
                items-center justify-center
                gap-2
                border border-[#008080]
                px-3 py-2
                text-sm font-medium
                text-[#008080]
                transition
                hover:bg-[#008080]/5
                dark:text-[#5EEAD4]
            ">

            <i
                data-lucide="plus"
                class="h-4 w-4"
                aria-hidden="true">
            </i>

            Add Slide

        </a>

    </div>

    @else


    {{-- Desktop table --}}
    <div
        class="
            hidden
            border border-gray-200
            bg-white
            dark:border-neutral-800
            dark:bg-neutral-900
            xl:block
        ">

        <div class="overflow-visible">

            <table class="w-full table-fixed">

                <thead
                    class="
                        border-b border-gray-200
                        bg-gray-50
                        dark:border-neutral-800
                        dark:bg-neutral-950
                    ">

                    <tr>

                        <th
                            class="
                                w-[31%]
                                px-3 py-2.5
                                text-left
                                text-[10px] font-semibold
                                uppercase tracking-[0.08em]
                                text-gray-500
                                dark:text-gray-400
                            ">
                            Slide
                        </th>

                        <th
                            class="
                                w-[14%]
                                px-3 py-2.5
                                text-left
                                text-[10px] font-semibold
                                uppercase tracking-[0.08em]
                                text-gray-500
                                dark:text-gray-400
                            ">
                            Category
                        </th>

                        <th
                            class="
                                w-[26%]
                                px-3 py-2.5
                                text-left
                                text-[10px] font-semibold
                                uppercase tracking-[0.08em]
                                text-gray-500
                                dark:text-gray-400
                            ">
                            Schedule
                        </th>

                        <th
                            class="
                                w-[8%]
                                px-3 py-2.5
                                text-center
                                text-[10px] font-semibold
                                uppercase tracking-[0.08em]
                                text-gray-500
                                dark:text-gray-400
                            ">
                            Order
                        </th>

                        <th
                            class="
                                w-[15%]
                                px-3 py-2.5
                                text-left
                                text-[10px] font-semibold
                                uppercase tracking-[0.08em]
                                text-gray-500
                                dark:text-gray-400
                            ">
                            Status
                        </th>

                        <th class="w-[6%] px-3 py-2.5"></th>

                    </tr>

                </thead>


                <tbody
                    class="
                        divide-y divide-gray-100
                        dark:divide-neutral-800
                    ">

                    @foreach ($heroSlides as $slide)

                    @php
                        if (! $slide->is_active) {
                            $slideStatus = 'Inactive';

                            $statusClasses =
                                'border-gray-200 bg-gray-50 text-gray-600 ' .
                                'dark:border-neutral-700 dark:bg-neutral-800 dark:text-gray-400';

                            $dotClasses = 'bg-gray-400';
                        } elseif ($slide->starts_at && $slide->starts_at->isFuture()) {
                            $slideStatus = 'Scheduled';

                            $statusClasses =
                                'border-blue-200 bg-blue-50 text-blue-700 ' .
                                'dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-300';

                            $dotClasses = 'bg-blue-500';
                        } elseif ($slide->ends_at && $slide->ends_at->isPast()) {
                            $slideStatus = 'Expired';

                            $statusClasses =
                                'border-amber-200 bg-amber-50 text-amber-700 ' .
                                'dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300';

                            $dotClasses = 'bg-amber-500';
                        } else {
                            $slideStatus = 'Published';

                            $statusClasses =
                                'border-green-200 bg-green-50 text-green-700 ' .
                                'dark:border-green-900 dark:bg-green-950/30 dark:text-green-300';

                            $dotClasses = 'bg-green-500';
                        }
                    @endphp


                    <tr
                        class="
                            transition
                            hover:bg-gray-50/70
                            dark:hover:bg-neutral-800/40
                        ">

                        {{-- Slide --}}
                        <td class="px-3 py-3">

                            <div class="flex min-w-0 items-center gap-3">

                                <img
                                    src="{{ asset('storage/' . $slide->image_path) }}"
                                    alt="{{ $slide->alt_text ?: $slide->title ?: 'Hero slide' }}"
                                    class="
                                        h-12 w-20 shrink-0
                                        object-cover
                                    ">

                                <div class="min-w-0">

                                    <p
                                        class="
                                            truncate
                                            text-sm font-semibold
                                            text-gray-900
                                            dark:text-white
                                        "
                                        title="{{ $slide->title ?: 'Image Only Slide' }}">
                                        {{ $slide->title ?: 'Image Only Slide' }}
                                    </p>

                                    <p
                                        class="
                                            mt-0.5 truncate
                                            text-xs text-gray-500
                                            dark:text-gray-400
                                        ">
                                        {{ \Illuminate\Support\Str::headline($slide->content_type) }}
                                    </p>

                                </div>

                            </div>

                        </td>


                        {{-- Category --}}
                        <td class="px-3 py-3">

                            <span
                                class="
                                    inline-flex
                                    border border-gray-200
                                    bg-gray-50
                                    px-2 py-0.5
                                    text-xs font-medium
                                    text-gray-700
                                    dark:border-neutral-700
                                    dark:bg-neutral-800
                                    dark:text-gray-300
                                ">
                                {{ \Illuminate\Support\Str::headline($slide->category) }}
                            </span>

                        </td>


                        {{-- Schedule --}}
                        <td
                            class="
                                px-3 py-3
                                text-xs
                                text-gray-600
                                dark:text-gray-300
                            ">

                            @if ($slide->starts_at || $slide->ends_at)

                                @if ($slide->starts_at)

                                <p>
                                    <span class="text-gray-400">
                                        From:
                                    </span>

                                    {{ $slide->starts_at->format('M d, Y h:i A') }}
                                </p>

                                @endif


                                @if ($slide->ends_at)

                                <p class="mt-0.5">
                                    <span class="text-gray-400">
                                        Until:
                                    </span>

                                    {{ $slide->ends_at->format('M d, Y h:i A') }}
                                </p>

                                @endif

                            @else

                            <span class="text-gray-400">
                                No schedule
                            </span>

                            @endif

                        </td>


                        {{-- Order --}}
                        <td
                            class="
                                px-3 py-3
                                text-center
                                text-sm font-medium
                                text-gray-700
                                dark:text-gray-300
                            ">
                            {{ $slide->display_order }}
                        </td>


                        {{-- Status --}}
                        <td class="px-3 py-3">

                            <span
                                class="
                                    inline-flex
                                    items-center gap-1.5
                                    border
                                    px-2 py-0.5
                                    text-xs font-medium
                                    {{ $statusClasses }}
                                ">

                                <span
                                    class="
                                        h-1.5 w-1.5
                                        {{ $dotClasses }}
                                    ">
                                </span>

                                {{ $slideStatus }}

                            </span>

                        </td>


                        {{-- Actions --}}
                        <td class="px-3 py-3 text-right">

                            <details class="relative inline-block text-left">

                                <summary
                                    aria-label="Slide options"
                                    title="More options"
                                    style="list-style: none;"
                                    class="
                                        inline-flex h-8 w-8
                                        cursor-pointer
                                        items-center justify-center
                                        border border-gray-200
                                        text-gray-500
                                        transition
                                        hover:border-[#008080]/40
                                        hover:bg-gray-50
                                        hover:text-[#008080]
                                        dark:border-neutral-700
                                        dark:text-gray-400
                                        dark:hover:bg-neutral-800
                                        dark:hover:text-[#5EEAD4]
                                        [&::-webkit-details-marker]:hidden
                                    ">

                                    <i
                                        data-lucide="ellipsis-vertical"
                                        class="h-4 w-4"
                                        aria-hidden="true">
                                    </i>

                                </summary>


                                <div
                                    class="
                                        absolute right-0 z-40
                                        mt-1 w-40
                                        border border-gray-200
                                        bg-white
                                        p-1
                                        text-left
                                        shadow-lg
                                        dark:border-neutral-700
                                        dark:bg-neutral-900
                                    ">

                                    <a
                                        href="{{ route('admin.hero-slides.edit', $slide) }}"
                                        class="
                                            flex items-center gap-2
                                            px-3 py-2
                                            text-sm text-gray-700
                                            transition
                                            hover:bg-gray-50
                                            dark:text-gray-200
                                            dark:hover:bg-neutral-800
                                        ">

                                        <i
                                            data-lucide="pencil"
                                            class="h-4 w-4"
                                            aria-hidden="true">
                                        </i>

                                        Edit Slide

                                    </a>


                                    <button
                                        type="button"
                                        data-delete-slide
                                        data-delete-url="{{ route('admin.hero-slides.destroy', $slide) }}"
                                        data-delete-title="{{ $slide->title ?: 'Image Only Slide' }}"
                                        class="
                                            flex w-full
                                            items-center gap-2
                                            px-3 py-2
                                            text-left text-sm
                                            text-red-600
                                            transition
                                            hover:bg-red-50
                                            dark:text-red-400
                                            dark:hover:bg-red-950/30
                                        ">

                                        <i
                                            data-lucide="trash-2"
                                            class="h-4 w-4"
                                            aria-hidden="true">
                                        </i>

                                        Delete Slide

                                    </button>

                                </div>

                            </details>

                        </td>

                    </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>


    {{-- Tablet / mobile --}}
    <div class="grid gap-2 xl:hidden">

        @foreach ($heroSlides as $slide)

        @php
            if (! $slide->is_active) {
                $slideStatus = 'Inactive';
                $mobileStatusClass = 'text-gray-600 dark:text-gray-400';
            } elseif ($slide->starts_at && $slide->starts_at->isFuture()) {
                $slideStatus = 'Scheduled';
                $mobileStatusClass = 'text-blue-700 dark:text-blue-300';
            } elseif ($slide->ends_at && $slide->ends_at->isPast()) {
                $slideStatus = 'Expired';
                $mobileStatusClass = 'text-amber-700 dark:text-amber-300';
            } else {
                $slideStatus = 'Published';
                $mobileStatusClass = 'text-green-700 dark:text-green-300';
            }
        @endphp


        <article
            class="
                border border-gray-200
                bg-white
                dark:border-neutral-800
                dark:bg-neutral-900
            ">

            <img
                src="{{ asset('storage/' . $slide->image_path) }}"
                alt="{{ $slide->alt_text ?: $slide->title ?: 'Hero slide' }}"
                class="h-36 w-full object-cover">


            <div class="p-3">

                <div
                    class="
                        flex items-start
                        justify-between gap-3
                    ">

                    <div class="min-w-0">

                        <h2
                            class="
                                truncate
                                text-sm font-semibold
                                text-gray-900
                                dark:text-white
                            ">
                            {{ $slide->title ?: 'Image Only Slide' }}
                        </h2>

                        <p
                            class="
                                mt-0.5
                                text-xs text-gray-500
                                dark:text-gray-400
                            ">
                            {{ \Illuminate\Support\Str::headline($slide->content_type) }}
                        </p>

                    </div>


                    <details class="relative shrink-0">

                        <summary
                            aria-label="Slide options"
                            title="More options"
                            style="list-style: none;"
                            class="
                                inline-flex h-8 w-8
                                cursor-pointer
                                items-center justify-center
                                border border-gray-200
                                text-gray-500
                                dark:border-neutral-700
                                dark:text-gray-400
                                [&::-webkit-details-marker]:hidden
                            ">

                            <i
                                data-lucide="ellipsis-vertical"
                                class="h-4 w-4"
                                aria-hidden="true">
                            </i>

                        </summary>


                        <div
                            class="
                                absolute right-0 z-40
                                mt-1 w-40
                                border border-gray-200
                                bg-white p-1
                                shadow-lg
                                dark:border-neutral-700
                                dark:bg-neutral-900
                            ">

                            <a
                                href="{{ route('admin.hero-slides.edit', $slide) }}"
                                class="
                                    flex items-center gap-2
                                    px-3 py-2
                                    text-sm text-gray-700
                                    hover:bg-gray-50
                                    dark:text-gray-200
                                    dark:hover:bg-neutral-800
                                ">

                                <i
                                    data-lucide="pencil"
                                    class="h-4 w-4">
                                </i>

                                Edit Slide

                            </a>


                            <button
                                type="button"
                                data-delete-slide
                                data-delete-url="{{ route('admin.hero-slides.destroy', $slide) }}"
                                data-delete-title="{{ $slide->title ?: 'Image Only Slide' }}"
                                class="
                                    flex w-full
                                    items-center gap-2
                                    px-3 py-2
                                    text-left text-sm
                                    text-red-600
                                    hover:bg-red-50
                                    dark:text-red-400
                                    dark:hover:bg-red-950/30
                                ">

                                <i
                                    data-lucide="trash-2"
                                    class="h-4 w-4">
                                </i>

                                Delete Slide

                            </button>

                        </div>

                    </details>

                </div>


                <dl
                    class="
                        mt-3 grid grid-cols-3
                        border-t border-gray-100
                        dark:border-neutral-800
                    ">

                    <div class="py-2.5 pr-2">

                        <dt
                            class="
                                text-[10px] font-semibold
                                uppercase tracking-wide
                                text-gray-500
                                dark:text-gray-400
                            ">
                            Category
                        </dt>

                        <dd
                            class="
                                mt-1
                                text-xs text-gray-700
                                dark:text-gray-300
                            ">
                            {{ \Illuminate\Support\Str::headline($slide->category) }}
                        </dd>

                    </div>


                    <div
                        class="
                            border-x border-gray-100
                            px-2 py-2.5
                            dark:border-neutral-800
                        ">

                        <dt
                            class="
                                text-[10px] font-semibold
                                uppercase tracking-wide
                                text-gray-500
                                dark:text-gray-400
                            ">
                            Order
                        </dt>

                        <dd
                            class="
                                mt-1 text-xs
                                text-gray-700
                                dark:text-gray-300
                            ">
                            {{ $slide->display_order }}
                        </dd>

                    </div>


                    <div class="py-2.5 pl-2">

                        <dt
                            class="
                                text-[10px] font-semibold
                                uppercase tracking-wide
                                text-gray-500
                                dark:text-gray-400
                            ">
                            Status
                        </dt>

                        <dd
                            class="
                                mt-1 text-xs font-medium
                                {{ $mobileStatusClass }}
                            ">
                            {{ $slideStatus }}
                        </dd>

                    </div>

                </dl>


                @if ($slide->starts_at || $slide->ends_at)

                <div
                    class="
                        border-t border-gray-100
                        pt-2.5
                        text-xs
                        text-gray-500
                        dark:border-neutral-800
                        dark:text-gray-400
                    ">

                    @if ($slide->starts_at)

                    <p>
                        From:
                        {{ $slide->starts_at->format('M d, Y h:i A') }}
                    </p>

                    @endif


                    @if ($slide->ends_at)

                    <p class="mt-0.5">
                        Until:
                        {{ $slide->ends_at->format('M d, Y h:i A') }}
                    </p>

                    @endif

                </div>

                @endif

            </div>

        </article>

        @endforeach

    </div>

    @endif


    {{-- Delete confirmation modal --}}
    <div
        id="hero-slide-delete-modal"
        class="
            fixed inset-0 z-50
            hidden items-center
            justify-center p-4
        "
        aria-hidden="true"
        role="dialog"
        aria-modal="true">

        <div
            id="hero-slide-delete-overlay"
            class="absolute inset-0 bg-black/50">
        </div>


        <div
            class="
                relative z-10
                w-full max-w-md
                border border-gray-200
                bg-white p-5
                shadow-xl
                dark:border-neutral-700
                dark:bg-neutral-900
            ">

            <div
                class="
                    flex h-9 w-9
                    items-center justify-center
                    bg-red-50
                    text-red-600
                    dark:bg-red-950/30
                    dark:text-red-400
                ">

                <i
                    data-lucide="trash-2"
                    class="h-4 w-4"
                    aria-hidden="true">
                </i>

            </div>


            <h2
                class="
                    mt-3
                    text-base font-semibold
                    text-gray-900
                    dark:text-white
                ">
                Delete Hero Slide?
            </h2>


            <p
                class="
                    mt-2
                    text-sm leading-6
                    text-gray-500
                    dark:text-gray-400
                ">

                You are about to delete

                <span
                    id="hero-slide-delete-title"
                    class="
                        font-medium
                        text-gray-700
                        dark:text-gray-200
                    ">
                </span>.

                This action cannot be undone.

            </p>


            <form
                id="hero-slide-delete-form"
                method="POST"
                data-lock-submit
                class="
                    mt-5 flex
                    flex-col-reverse gap-2
                    sm:flex-row
                    sm:justify-end
                ">

                @csrf
                @method('DELETE')


                <button
                    id="hero-slide-delete-cancel"
                    type="button"
                    class="
                        min-h-9
                        border border-gray-300
                        px-4 py-2
                        text-sm font-medium
                        text-gray-700
                        transition
                        hover:bg-gray-50
                        dark:border-neutral-700
                        dark:text-gray-300
                        dark:hover:bg-neutral-800
                    ">
                    Cancel
                </button>


                <button
                    type="submit"
                    class="
                        inline-flex min-h-9
                        items-center justify-center
                        gap-2
                        bg-red-600
                        px-4 py-2
                        text-sm font-semibold
                        text-white
                        transition
                        hover:bg-red-700
                    ">

                    <i
                        data-lucide="trash-2"
                        class="h-4 w-4"
                        aria-hidden="true">
                    </i>

                    Delete Slide

                </button>

            </form>

        </div>

    </div>

</div>

@endsection