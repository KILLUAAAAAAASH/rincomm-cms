@php
$compactHeroForm = $compactHeroForm ?? false;
$slide = $heroSlide ?? null;

$selectedContentType = old(
'content_type',
$slide?->content_type ?? 'image_text'
);

$selectedCategory = old(
'category',
$slide?->category ?? 'announcement'
);

$isActive = (bool) old(
'is_active',
$slide?->is_active ?? false
);
@endphp


@if ($errors->any())

<div
    class="
            mb-3 rounded-xl
            border border-red-200
            bg-red-50 p-3
            dark:border-red-900
            dark:bg-red-950/30
        ">

    <div class="flex gap-3">

        <i
            data-lucide="circle-alert"
            class="mt-0.5 h-4 w-4 shrink-0 text-red-600"
            aria-hidden="true">
        </i>


        <div>

            <p class="text-xs font-semibold text-red-700 dark:text-red-300">
                Please correct the following:
            </p>

            <ul
                class="
                        mt-1 list-disc space-y-0.5 pl-5
                        text-xs text-red-600
                        dark:text-red-400
                    ">

                @foreach ($errors->all() as $error)

                <li>
                    {{ $error }}
                </li>

                @endforeach

            </ul>

        </div>

    </div>

</div>

@endif


@if ($compactHeroForm)

{{-- Compact edit layout --}}
<div
    class="
            grid gap-3
            md:grid-cols-2

            xl:h-full
            xl:min-h-0
            xl:grid-cols-12
        ">

    {{-- Slide content --}}
    <section
        class="
                rounded-2xl border border-gray-200
                bg-white p-3 shadow-sm
                dark:border-gray-800
                dark:bg-gray-900

                xl:col-span-5
            ">

        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
            Slide Content
        </h2>

        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
            Choose what information appears on this slide.
        </p>


        <div class="mt-2 space-y-2">

            {{-- Content type --}}
            <div>

                <label
                    for="content_type"
                    class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Content Type
                </label>

                <select
                    id="content_type"
                    name="content_type"
                    data-hero-content-type
                    required
                    class="
                            w-full rounded-lg
                            border border-gray-300
                            bg-white px-3 py-1.5
                            text-xs text-gray-900
                            outline-none transition
                            focus:border-[#008080]
                            focus:ring-2
                            focus:ring-[#008080]/20
                            dark:border-gray-700
                            dark:bg-gray-800
                            dark:text-white
                        ">

                    <option
                        value="image_only"
                        @selected($selectedContentType==='image_only' )>
                        Image Only
                    </option>

                    <option
                        value="image_text"
                        @selected($selectedContentType==='image_text' )>
                        Image + Title + Description
                    </option>

                    <option
                        value="image_text_cta"
                        @selected($selectedContentType==='image_text_cta' )>
                        Image + Title + Description + CTA
                    </option>

                </select>

            </div>


            {{-- Title --}}
            <div data-hero-text-field>

                <label
                    for="title"
                    class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Title
                </label>

                <input
                    id="title"
                    name="title"
                    type="text"
                    value="{{ old('title', $slide?->title ?? '') }}"
                    maxlength="255"
                    placeholder="Example: Back-to-School Fiber Promo"
                    class="
                            w-full rounded-lg
                            border border-gray-300
                            bg-white px-3 py-1.5
                            text-xs text-gray-900
                            outline-none transition
                            focus:border-[#008080]
                            focus:ring-2
                            focus:ring-[#008080]/20
                            dark:border-gray-700
                            dark:bg-gray-800
                            dark:text-white
                        ">

            </div>


            {{-- Description --}}
            <div data-hero-text-field>

                <label
                    for="description"
                    class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    rows="2"
                    maxlength="1500"
                    placeholder="Write a short message for website visitors."
                    class="
                            w-full resize-none rounded-lg
                            border border-gray-300
                            bg-white px-3 py-1.5
                            text-xs text-gray-900
                            outline-none transition
                            focus:border-[#008080]
                            focus:ring-2
                            focus:ring-[#008080]/20
                            dark:border-gray-700
                            dark:bg-gray-800
                            dark:text-white
                        ">{{ old('description', $slide?->description ?? '') }}</textarea>

            </div>


            {{-- CTA --}}
            <div
                data-hero-cta-fields
                class="grid gap-2 sm:grid-cols-2">

                <div>

                    <label
                        for="cta_text"
                        class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Button Text
                    </label>

                    <input
                        id="cta_text"
                        name="cta_text"
                        type="text"
                        value="{{ old('cta_text', $slide?->cta_text ?? '') }}"
                        maxlength="100"
                        placeholder="Example: View Plans"
                        class="
                                w-full rounded-lg
                                border border-gray-300
                                bg-white px-3 py-1.5
                                text-xs text-gray-900
                                outline-none transition
                                focus:border-[#008080]
                                focus:ring-2
                                focus:ring-[#008080]/20
                                dark:border-gray-700
                                dark:bg-gray-800
                                dark:text-white
                            ">

                </div>


                <div>

                    <label
                        for="cta_url"
                        class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Button Link
                    </label>

                    <input
                        id="cta_url"
                        name="cta_url"
                        type="text"
                        value="{{ old('cta_url', $slide?->cta_url ?? '') }}"
                        maxlength="2048"
                        placeholder="#plans or https://..."
                        class="
                                w-full rounded-lg
                                border border-gray-300
                                bg-white px-3 py-1.5
                                text-xs text-gray-900
                                outline-none transition
                                focus:border-[#008080]
                                focus:ring-2
                                focus:ring-[#008080]/20
                                dark:border-gray-700
                                dark:bg-gray-800
                                dark:text-white
                            ">

                </div>

            </div>

        </div>

    </section>


    {{-- Slide image --}}
    <section
        class="
                rounded-2xl border border-gray-200
                bg-white p-3 shadow-sm
                dark:border-gray-800
                dark:bg-gray-900

                xl:col-span-4
            ">

        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
            Slide Image
        </h2>

        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
            Upload or replace the carousel image.
        </p>


        <div class="mt-2 space-y-2">

            @if ($slide && $slide->image_path)

            <div>

                <p class="mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">
                    Current Image
                </p>

                <img
                    src="{{ asset('storage/' . $slide->image_path) }}"
                    alt="{{ $slide->alt_text ?: 'Current hero image' }}"
                    class="
                                h-24 w-full rounded-lg object-cover
                                2xl:h-28
                            ">

            </div>

            @endif


            <div>

                <label
                    for="image"
                    class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                    {{ $slide ? 'Replace Image' : 'Upload Image' }}
                </label>

                <input
                    id="image"
                    name="image"
                    type="file"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    @required(!$slide)
                    class="
                            block w-full rounded-lg
                            border border-gray-300
                            bg-white
                            text-xs text-gray-700

                            file:mr-2
                            file:border-0
                            file:bg-gray-100
                            file:px-3
                            file:py-2
                            file:text-xs
                            file:font-medium
                            file:text-gray-700

                            dark:border-gray-700
                            dark:bg-gray-800
                            dark:text-gray-300
                            dark:file:bg-gray-700
                            dark:file:text-gray-200
                        ">

                <p class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                    JPG, PNG, or WebP. Maximum file size: 5 MB.
                </p>

            </div>


            <div>

                <label
                    for="alt_text"
                    class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Alternative Text
                </label>

                <input
                    id="alt_text"
                    name="alt_text"
                    type="text"
                    value="{{ old('alt_text', $slide?->alt_text ?? '') }}"
                    maxlength="255"
                    placeholder="Describe the image for accessibility"
                    class="
                            w-full rounded-lg
                            border border-gray-300
                            bg-white px-3 py-1.5
                            text-xs text-gray-900
                            outline-none transition
                            focus:border-[#008080]
                            focus:ring-2
                            focus:ring-[#008080]/20
                            dark:border-gray-700
                            dark:bg-gray-800
                            dark:text-white
                        ">

            </div>

        </div>

    </section>


    {{-- Publishing and schedule --}}
    <section
        class="
                rounded-2xl border border-gray-200
                bg-white p-3 shadow-sm
                dark:border-gray-800
                dark:bg-gray-900

                md:col-span-2
                xl:col-span-3
            ">

        {{-- Publishing --}}
        <div>

            <div class="flex items-center justify-between gap-2">

                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
                    Publishing
                </h2>


                <div class="flex shrink-0 items-center gap-2">

                    <span class="text-[11px] font-medium text-gray-700 dark:text-gray-300">
                        Active
                    </span>

                    <input
                        type="hidden"
                        name="is_active"
                        value="0">

                    <label class="relative inline-flex cursor-pointer items-center">

                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            class="peer sr-only"
                            @checked($isActive)>

                        <span
                            class="
                                    h-5 w-9 rounded-full
                                    bg-gray-300 transition
                                    after:absolute
                                    after:left-[2px]
                                    after:top-[2px]
                                    after:h-4
                                    after:w-4
                                    after:rounded-full
                                    after:bg-white
                                    after:transition-all
                                    after:content-['']
                                    peer-checked:bg-[#008080]
                                    peer-checked:after:translate-x-full
                                    dark:bg-gray-700
                                ">
                        </span>

                    </label>

                </div>

            </div>


            <div class="mt-2 grid grid-cols-2 gap-2">

                <div class="min-w-0">

                    <label
                        for="category"
                        class="mb-1 block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                        Category
                    </label>

                    <select
                        id="category"
                        name="category"
                        required
                        class="
                                w-full min-w-0 rounded-lg
                                border border-gray-300
                                bg-white px-2 py-1.5
                                text-[11px] text-gray-900
                                outline-none transition
                                focus:border-[#008080]
                                focus:ring-2
                                focus:ring-[#008080]/20
                                dark:border-gray-700
                                dark:bg-gray-800
                                dark:text-white
                            ">

                        <option value="promotion" @selected($selectedCategory==='promotion' )>
                            Promotion
                        </option>

                        <option value="event" @selected($selectedCategory==='event' )>
                            Event
                        </option>

                        <option value="announcement" @selected($selectedCategory==='announcement' )>
                            Announcement
                        </option>

                        <option value="coverage_update" @selected($selectedCategory==='coverage_update' )>
                            Coverage Update
                        </option>

                        <option value="maintenance_advisory" @selected($selectedCategory==='maintenance_advisory' )>
                            Maintenance Advisory
                        </option>

                    </select>

                </div>


                <div class="min-w-0">

                    <label
                        for="display_order"
                        class="mb-1 block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                        Display Order
                    </label>

                    <input
                        id="display_order"
                        name="display_order"
                        type="number"
                        min="0"
                        max="999"
                        value="{{ old('display_order', $slide?->display_order ?? 0) }}"
                        class="
                                w-full min-w-0 rounded-lg
                                border border-gray-300
                                bg-white px-2 py-1.5
                                text-[11px] text-gray-900
                                outline-none transition
                                focus:border-[#008080]
                                focus:ring-2
                                focus:ring-[#008080]/20
                                dark:border-gray-700
                                dark:bg-gray-800
                                dark:text-white
                            ">

                </div>

            </div>

        </div>


        {{-- Schedule --}}
        <div class="mt-2 border-t border-gray-200 pt-2 dark:border-gray-800">

            <div class="flex items-center justify-between">

                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
                    Schedule
                </h2>

                <span class="text-[10px] text-gray-500 dark:text-gray-400">
                    Optional
                </span>

            </div>


            <div class="mt-2 space-y-2">

                <div>

                    <label
                        for="starts_at"
                        class="mb-1 block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                        Start Date & Time
                    </label>

                    <input
                        id="starts_at"
                        name="starts_at"
                        type="datetime-local"
                        value="{{ old(
                                'starts_at',
                                $slide?->starts_at
                                    ? $slide->starts_at->format('Y-m-d\TH:i')
                                    : ''
                            ) }}"
                        class="
                                w-full min-w-0 rounded-lg
                                border border-gray-300
                                bg-white px-2 py-1.5
                                text-[11px] text-gray-900
                                outline-none transition
                                focus:border-[#008080]
                                focus:ring-2
                                focus:ring-[#008080]/20
                                dark:border-gray-700
                                dark:bg-gray-800
                                dark:text-white
                            ">

                </div>


                <div>

                    <label
                        for="ends_at"
                        class="mb-1 block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                        End Date & Time
                    </label>

                    <input
                        id="ends_at"
                        name="ends_at"
                        type="datetime-local"
                        value="{{ old(
                                'ends_at',
                                $slide?->ends_at
                                    ? $slide->ends_at->format('Y-m-d\TH:i')
                                    : ''
                            ) }}"
                        class="
                                w-full min-w-0 rounded-lg
                                border border-gray-300
                                bg-white px-2 py-1.5
                                text-[11px] text-gray-900
                                outline-none transition
                                focus:border-[#008080]
                                focus:ring-2
                                focus:ring-[#008080]/20
                                dark:border-gray-700
                                dark:bg-gray-800
                                dark:text-white
                            ">

                </div>

            </div>

        </div>

    </section>

</div>

@else

{{-- Standard create layout --}}
<div class="grid gap-6 lg:grid-cols-3">

    {{-- Main fields --}}
    <div class="space-y-6 lg:col-span-2">

        {{-- Slide content --}}
        <section
            class="
                    rounded-2xl border border-gray-200
                    bg-white p-5 shadow-sm
                    dark:border-gray-800
                    dark:bg-gray-900
                ">

            <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                Slide Content
            </h2>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Choose what information will appear on this carousel slide.
            </p>


            <div class="mt-5 space-y-5">

                <div>

                    <label
                        for="content_type"
                        class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Content Type
                    </label>

                    <select
                        id="content_type"
                        name="content_type"
                        data-hero-content-type
                        required
                        class="
                                w-full rounded-xl
                                border border-gray-300
                                bg-white px-3.5 py-2.5
                                text-sm text-gray-900
                                outline-none transition
                                focus:border-[#008080]
                                focus:ring-2
                                focus:ring-[#008080]/20
                                dark:border-gray-700
                                dark:bg-gray-800
                                dark:text-white
                            ">

                        <option value="image_only" @selected($selectedContentType==='image_only' )>
                            Image Only
                        </option>

                        <option value="image_text" @selected($selectedContentType==='image_text' )>
                            Image + Title + Description
                        </option>

                        <option value="image_text_cta" @selected($selectedContentType==='image_text_cta' )>
                            Image + Title + Description + CTA
                        </option>

                    </select>

                </div>


                <div data-hero-text-field>

                    <label
                        for="title"
                        class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Title
                    </label>

                    <input
                        id="title"
                        name="title"
                        type="text"
                        value="{{ old('title', $slide?->title ?? '') }}"
                        maxlength="255"
                        placeholder="Example: Back-to-School Fiber Promo"
                        class="
                                w-full rounded-xl
                                border border-gray-300
                                bg-white px-3.5 py-2.5
                                text-sm text-gray-900
                                outline-none transition
                                focus:border-[#008080]
                                focus:ring-2
                                focus:ring-[#008080]/20
                                dark:border-gray-700
                                dark:bg-gray-800
                                dark:text-white
                            ">

                </div>


                <div data-hero-text-field>

                    <label
                        for="description"
                        class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        maxlength="1500"
                        placeholder="Write a short message for website visitors."
                        class="
                                w-full resize-y rounded-xl
                                border border-gray-300
                                bg-white px-3.5 py-2.5
                                text-sm text-gray-900
                                outline-none transition
                                focus:border-[#008080]
                                focus:ring-2
                                focus:ring-[#008080]/20
                                dark:border-gray-700
                                dark:bg-gray-800
                                dark:text-white
                            ">{{ old('description', $slide?->description ?? '') }}</textarea>

                </div>


                <div
                    data-hero-cta-fields
                    class="grid gap-5 sm:grid-cols-2">

                    <div>

                        <label
                            for="cta_text"
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Button Text
                        </label>

                        <input
                            id="cta_text"
                            name="cta_text"
                            type="text"
                            value="{{ old('cta_text', $slide?->cta_text ?? '') }}"
                            maxlength="100"
                            placeholder="Example: View Plans"
                            class="
                                    w-full rounded-xl
                                    border border-gray-300
                                    bg-white px-3.5 py-2.5
                                    text-sm text-gray-900
                                    outline-none transition
                                    focus:border-[#008080]
                                    focus:ring-2
                                    focus:ring-[#008080]/20
                                    dark:border-gray-700
                                    dark:bg-gray-800
                                    dark:text-white
                                ">

                    </div>


                    <div>

                        <label
                            for="cta_url"
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Button Link
                        </label>

                        <input
                            id="cta_url"
                            name="cta_url"
                            type="text"
                            value="{{ old('cta_url', $slide?->cta_url ?? '') }}"
                            maxlength="2048"
                            placeholder="#plans or https://..."
                            class="
                                    w-full rounded-xl
                                    border border-gray-300
                                    bg-white px-3.5 py-2.5
                                    text-sm text-gray-900
                                    outline-none transition
                                    focus:border-[#008080]
                                    focus:ring-2
                                    focus:ring-[#008080]/20
                                    dark:border-gray-700
                                    dark:bg-gray-800
                                    dark:text-white
                                ">

                    </div>

                </div>

            </div>

        </section>


        {{-- Slide image --}}
        <section
            class="
                    rounded-2xl border border-gray-200
                    bg-white p-5 shadow-sm
                    dark:border-gray-800
                    dark:bg-gray-900
                ">

            <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                Slide Image
            </h2>


            <div class="mt-5 space-y-5">

                @if ($slide && $slide->image_path)

                <div>

                    <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                        Current Image
                    </p>

                    <img
                        src="{{ asset('storage/' . $slide->image_path) }}"
                        alt="{{ $slide->alt_text ?: 'Current hero image' }}"
                        class="max-h-64 w-full rounded-xl object-cover">

                </div>

                @endif


                <div>

                    <label
                        for="image"
                        class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $slide ? 'Replace Image' : 'Upload Image' }}
                    </label>

                    <input
                        id="image"
                        name="image"
                        type="file"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        @required(!$slide)
                        class="
                                block w-full rounded-xl
                                border border-gray-300
                                bg-white
                                text-sm text-gray-700
                                file:mr-4
                                file:border-0
                                file:bg-gray-100
                                file:px-4
                                file:py-3
                                file:text-sm
                                file:font-medium
                                file:text-gray-700
                                dark:border-gray-700
                                dark:bg-gray-800
                                dark:text-gray-300
                                dark:file:bg-gray-700
                                dark:file:text-gray-200
                            ">

                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        JPG, PNG, or WebP. Maximum file size: 5 MB.
                    </p>

                </div>


                <div>

                    <label
                        for="alt_text"
                        class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Alternative Text
                    </label>

                    <input
                        id="alt_text"
                        name="alt_text"
                        type="text"
                        value="{{ old('alt_text', $slide?->alt_text ?? '') }}"
                        maxlength="255"
                        placeholder="Describe the image for accessibility"
                        class="
                                w-full rounded-xl
                                border border-gray-300
                                bg-white px-3.5 py-2.5
                                text-sm text-gray-900
                                outline-none transition
                                focus:border-[#008080]
                                focus:ring-2
                                focus:ring-[#008080]/20
                                dark:border-gray-700
                                dark:bg-gray-800
                                dark:text-white
                            ">

                </div>

            </div>

        </section>

    </div>


    {{-- Settings --}}
    <div class="space-y-6">

        {{-- Publishing --}}
        <section
            class="
                    rounded-2xl border border-gray-200
                    bg-white p-5 shadow-sm
                    dark:border-gray-800
                    dark:bg-gray-900
                ">

            <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                Publishing
            </h2>


            <div class="mt-5 space-y-5">

                <div class="flex items-center justify-between gap-4">

                    <div>

                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Active
                        </p>

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Show this slide when its schedule allows.
                        </p>

                    </div>


                    <div>

                        <input
                            type="hidden"
                            name="is_active"
                            value="0">

                        <label class="relative inline-flex cursor-pointer items-center">

                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                class="peer sr-only"
                                @checked($isActive)>

                            <span
                                class="
                                        h-6 w-11 rounded-full
                                        bg-gray-300 transition
                                        after:absolute
                                        after:left-[2px]
                                        after:top-[2px]
                                        after:h-5
                                        after:w-5
                                        after:rounded-full
                                        after:bg-white
                                        after:transition-all
                                        after:content-['']
                                        peer-checked:bg-[#008080]
                                        peer-checked:after:translate-x-full
                                        dark:bg-gray-700
                                    ">
                            </span>

                        </label>

                    </div>

                </div>


                <div>

                    <label
                        for="category"
                        class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Category
                    </label>

                    <select
                        id="category"
                        name="category"
                        required
                        class="
                                w-full rounded-xl
                                border border-gray-300
                                bg-white px-3.5 py-2.5
                                text-sm text-gray-900
                                outline-none transition
                                focus:border-[#008080]
                                focus:ring-2
                                focus:ring-[#008080]/20
                                dark:border-gray-700
                                dark:bg-gray-800
                                dark:text-white
                            ">

                        <option value="promotion" @selected($selectedCategory==='promotion' )>
                            Promotion
                        </option>

                        <option value="event" @selected($selectedCategory==='event' )>
                            Event
                        </option>

                        <option value="announcement" @selected($selectedCategory==='announcement' )>
                            Announcement
                        </option>

                        <option value="coverage_update" @selected($selectedCategory==='coverage_update' )>
                            Coverage Update
                        </option>

                        <option value="maintenance_advisory" @selected($selectedCategory==='maintenance_advisory' )>
                            Maintenance Advisory
                        </option>

                    </select>

                </div>


                <div>

                    <label
                        for="display_order"
                        class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Display Order
                    </label>

                    <input
                        id="display_order"
                        name="display_order"
                        type="number"
                        min="0"
                        max="999"
                        value="{{ old('display_order', $slide?->display_order ?? 0) }}"
                        class="
                                w-full rounded-xl
                                border border-gray-300
                                bg-white px-3.5 py-2.5
                                text-sm text-gray-900
                                outline-none transition
                                focus:border-[#008080]
                                focus:ring-2
                                focus:ring-[#008080]/20
                                dark:border-gray-700
                                dark:bg-gray-800
                                dark:text-white
                            ">

                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Lower numbers appear first.
                    </p>

                </div>

            </div>

        </section>


        {{-- Schedule --}}
        <section
            class="
                    rounded-2xl border border-gray-200
                    bg-white p-5 shadow-sm
                    dark:border-gray-800
                    dark:bg-gray-900
                ">

            <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                Schedule
            </h2>

            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Both fields are optional.
            </p>


            <div class="mt-5 space-y-5">

                <div>

                    <label
                        for="starts_at"
                        class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Start Date & Time
                    </label>

                    <input
                        id="starts_at"
                        name="starts_at"
                        type="datetime-local"
                        value="{{ old(
                                'starts_at',
                                $slide?->starts_at
                                    ? $slide->starts_at->format('Y-m-d\TH:i')
                                    : ''
                            ) }}"
                        class="
                                w-full min-w-0 rounded-xl
                                border border-gray-300
                                bg-white px-3.5 py-2.5
                                text-sm text-gray-900
                                outline-none transition
                                focus:border-[#008080]
                                focus:ring-2
                                focus:ring-[#008080]/20
                                dark:border-gray-700
                                dark:bg-gray-800
                                dark:text-white
                            ">

                </div>


                <div>

                    <label
                        for="ends_at"
                        class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        End Date & Time
                    </label>

                    <input
                        id="ends_at"
                        name="ends_at"
                        type="datetime-local"
                        value="{{ old(
                                'ends_at',
                                $slide?->ends_at
                                    ? $slide->ends_at->format('Y-m-d\TH:i')
                                    : ''
                            ) }}"
                        class="
                                w-full min-w-0 rounded-xl
                                border border-gray-300
                                bg-white px-3.5 py-2.5
                                text-sm text-gray-900
                                outline-none transition
                                focus:border-[#008080]
                                focus:ring-2
                                focus:ring-[#008080]/20
                                dark:border-gray-700
                                dark:bg-gray-800
                                dark:text-white
                            ">

                </div>

            </div>

        </section>

    </div>

</div>

@endif