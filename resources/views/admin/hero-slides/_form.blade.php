@php
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
    role="alert"
    class="
        mb-2
        flex items-start gap-2
        border border-red-200
        bg-red-50
        px-3 py-2.5
        text-red-700
        dark:border-red-900
        dark:bg-red-950/30
        dark:text-red-300
    ">

    <i
        data-lucide="triangle-alert"
        class="mt-0.5 h-4 w-4 shrink-0"
        aria-hidden="true">
    </i>


    <div class="min-w-0">

        <p class="text-xs font-semibold">
            Please correct the following:
        </p>

        <ul
            class="
                mt-1 list-disc
                space-y-0.5 pl-4
                text-xs
            ">

            @foreach ($errors->all() as $error)

            <li>
                {{ $error }}
            </li>

            @endforeach

        </ul>

    </div>

</div>

@endif


<div
    class="
        grid gap-2
        lg:grid-cols-2
        xl:grid-cols-12
    ">

    {{-- ====================================================
         SLIDE CONTENT
    ===================================================== --}}
    <section
        class="
            border border-gray-200
            bg-white p-4
            dark:border-neutral-800
            dark:bg-neutral-900
            xl:col-span-5
        ">

        <div
            class="
                border-b border-gray-100
                pb-3
                dark:border-neutral-800
            ">

            <h2
                class="
                    text-sm font-semibold
                    text-gray-900
                    dark:text-white
                ">
                Slide Content
            </h2>

            <p
                class="
                    mt-0.5
                    text-xs text-gray-500
                    dark:text-gray-400
                ">
                Choose what information appears on the slide.
            </p>

        </div>


        <div class="mt-3 space-y-3">

            {{-- Content type --}}
            <div>

                <label
                    for="content_type"
                    class="
                        mb-1 block
                        text-xs font-medium
                        text-gray-700
                        dark:text-gray-300
                    ">
                    Content Type
                    <span class="text-red-500">*</span>
                </label>


                <select
                    id="content_type"
                    name="content_type"
                    data-hero-content-type
                    required
                    class="
                        w-full
                        border border-gray-300
                        bg-white
                        px-3 py-2
                        text-sm text-gray-900
                        outline-none
                        transition
                        focus:border-[#008080]
                        focus:ring-2
                        focus:ring-[#008080]/15
                        dark:border-neutral-700
                        dark:bg-neutral-950
                        dark:text-white
                    ">

                    <option
                        value="image_only"
                        @selected($selectedContentType === 'image_only')>
                        Image Only
                    </option>

                    <option
                        value="image_text"
                        @selected($selectedContentType === 'image_text')>
                        Image + Title + Description
                    </option>

                    <option
                        value="image_text_cta"
                        @selected($selectedContentType === 'image_text_cta')>
                        Image + Title + Description + CTA
                    </option>

                </select>

            </div>


            {{-- Title --}}
            <div data-hero-text-field>

                <label
                    for="title"
                    class="
                        mb-1 block
                        text-xs font-medium
                        text-gray-700
                        dark:text-gray-300
                    ">
                    Title
                </label>


                <input
                    id="title"
                    name="title"
                    type="text"
                    maxlength="255"
                    value="{{ old('title', $slide?->title ?? '') }}"
                    placeholder="Example: Back-to-School Fiber Promo"
                    class="
                        w-full
                        border border-gray-300
                        bg-white
                        px-3 py-2
                        text-sm text-gray-900
                        outline-none
                        transition
                        placeholder:text-gray-400
                        focus:border-[#008080]
                        focus:ring-2
                        focus:ring-[#008080]/15
                        dark:border-neutral-700
                        dark:bg-neutral-950
                        dark:text-white
                        dark:placeholder:text-gray-600
                    ">

            </div>


            {{-- Description --}}
            <div data-hero-text-field>

                <label
                    for="description"
                    class="
                        mb-1 block
                        text-xs font-medium
                        text-gray-700
                        dark:text-gray-300
                    ">
                    Description
                </label>


                <textarea
                    id="description"
                    name="description"
                    rows="3"
                    maxlength="1500"
                    placeholder="Write a short message for website visitors."
                    class="
                        w-full resize-none
                        border border-gray-300
                        bg-white
                        px-3 py-2
                        text-sm text-gray-900
                        outline-none
                        transition
                        placeholder:text-gray-400
                        focus:border-[#008080]
                        focus:ring-2
                        focus:ring-[#008080]/15
                        dark:border-neutral-700
                        dark:bg-neutral-950
                        dark:text-white
                        dark:placeholder:text-gray-600
                    ">{{ old('description', $slide?->description ?? '') }}</textarea>

                <p
                    class="
                        mt-1
                        text-[10px]
                        text-gray-500
                        dark:text-gray-400
                    ">
                    Maximum 1,500 characters.
                </p>

            </div>


            {{-- CTA --}}
            <div
                data-hero-cta-fields
                class="
                    grid gap-3
                    sm:grid-cols-2
                ">

                <div>

                    <label
                        for="cta_text"
                        class="
                            mb-1 block
                            text-xs font-medium
                            text-gray-700
                            dark:text-gray-300
                        ">
                        Button Text
                    </label>


                    <input
                        id="cta_text"
                        name="cta_text"
                        type="text"
                        maxlength="100"
                        value="{{ old('cta_text', $slide?->cta_text ?? '') }}"
                        placeholder="Example: View Plans"
                        class="
                            w-full
                            border border-gray-300
                            bg-white
                            px-3 py-2
                            text-sm text-gray-900
                            outline-none
                            transition
                            placeholder:text-gray-400
                            focus:border-[#008080]
                            focus:ring-2
                            focus:ring-[#008080]/15
                            dark:border-neutral-700
                            dark:bg-neutral-950
                            dark:text-white
                        ">

                </div>


                <div>

                    <label
                        for="cta_url"
                        class="
                            mb-1 block
                            text-xs font-medium
                            text-gray-700
                            dark:text-gray-300
                        ">
                        Button Link
                    </label>


                    <input
                        id="cta_url"
                        name="cta_url"
                        type="text"
                        maxlength="2048"
                        value="{{ old('cta_url', $slide?->cta_url ?? '') }}"
                        placeholder="#plans or https://..."
                        class="
                            w-full
                            border border-gray-300
                            bg-white
                            px-3 py-2
                            text-sm text-gray-900
                            outline-none
                            transition
                            placeholder:text-gray-400
                            focus:border-[#008080]
                            focus:ring-2
                            focus:ring-[#008080]/15
                            dark:border-neutral-700
                            dark:bg-neutral-950
                            dark:text-white
                        ">

                </div>

            </div>

        </div>

    </section>


    {{-- ====================================================
         SLIDE IMAGE
    ===================================================== --}}
    <section
        class="
            border border-gray-200
            bg-white p-4
            dark:border-neutral-800
            dark:bg-neutral-900
            xl:col-span-4
        ">

        <div
            class="
                border-b border-gray-100
                pb-3
                dark:border-neutral-800
            ">

            <h2
                class="
                    text-sm font-semibold
                    text-gray-900
                    dark:text-white
                ">
                Slide Image
            </h2>

            <p
                class="
                    mt-0.5
                    text-xs text-gray-500
                    dark:text-gray-400
                ">
                Upload the image used by the carousel.
            </p>

        </div>


        <div class="mt-3 space-y-3">

            @if ($slide && $slide->image_path)

            <div>

                <p
                    class="
                        mb-1
                        text-xs font-medium
                        text-gray-700
                        dark:text-gray-300
                    ">
                    Current Image
                </p>


                <img
                    src="{{ asset('storage/' . $slide->image_path) }}"
                    alt="{{ $slide->alt_text ?: 'Current hero image' }}"
                    class="
                        h-28 w-full
                        border border-gray-200
                        object-cover
                        dark:border-neutral-700
                    ">

            </div>

            @endif


            <div>

                <label
                    for="image"
                    class="
                        mb-1 block
                        text-xs font-medium
                        text-gray-700
                        dark:text-gray-300
                    ">

                    {{ $slide ? 'Replace Image' : 'Upload Image' }}

                    @unless ($slide)
                    <span class="text-red-500">*</span>
                    @endunless

                </label>


                <input
                    id="image"
                    name="image"
                    type="file"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    @required(!$slide)
                    class="
                        block w-full
                        border border-gray-300
                        bg-white
                        text-xs text-gray-700

                        file:mr-3
                        file:border-0
                        file:bg-gray-100
                        file:px-3
                        file:py-2
                        file:text-xs
                        file:font-medium
                        file:text-gray-700

                        dark:border-neutral-700
                        dark:bg-neutral-950
                        dark:text-gray-300
                        dark:file:bg-neutral-800
                        dark:file:text-gray-200
                    ">

                <p
                    class="
                        mt-1
                        text-[10px]
                        text-gray-500
                        dark:text-gray-400
                    ">
                    JPG, PNG, or WebP. Maximum file size: 5 MB.
                </p>

            </div>


            <div>

                <div
                    class="
                        mb-1 flex
                        items-center justify-between
                        gap-2
                    ">

                    <label
                        for="alt_text"
                        class="
                            text-xs font-medium
                            text-gray-700
                            dark:text-gray-300
                        ">
                        Alternative Text
                    </label>

                    <span
                        class="
                            text-[10px]
                            text-gray-400
                            dark:text-gray-500
                        ">
                        Accessibility
                    </span>

                </div>


                <input
                    id="alt_text"
                    name="alt_text"
                    type="text"
                    maxlength="255"
                    value="{{ old('alt_text', $slide?->alt_text ?? '') }}"
                    placeholder="Describe the image for accessibility"
                    class="
                        w-full
                        border border-gray-300
                        bg-white
                        px-3 py-2
                        text-sm text-gray-900
                        outline-none
                        transition
                        placeholder:text-gray-400
                        focus:border-[#008080]
                        focus:ring-2
                        focus:ring-[#008080]/15
                        dark:border-neutral-700
                        dark:bg-neutral-950
                        dark:text-white
                    ">

            </div>

        </div>

    </section>


    {{-- ====================================================
         PUBLISHING / SCHEDULE
    ===================================================== --}}
    <section
        class="
            border border-gray-200
            bg-white p-4
            dark:border-neutral-800
            dark:bg-neutral-900

            lg:col-span-2
            xl:col-span-3
        ">

        <div
            class="
                border-b border-gray-100
                pb-3
                dark:border-neutral-800
            ">

            <div
                class="
                    flex items-center
                    justify-between gap-3
                ">

                <div>

                    <h2
                        class="
                            text-sm font-semibold
                            text-gray-900
                            dark:text-white
                        ">
                        Publishing
                    </h2>

                    <p
                        class="
                            mt-0.5
                            text-xs text-gray-500
                            dark:text-gray-400
                        ">
                        Control visibility and ordering.
                    </p>

                </div>


                <div class="shrink-0">

                    <input
                        type="hidden"
                        name="is_active"
                        value="0">


                    <label
                        class="
                            relative inline-flex
                            cursor-pointer items-center
                            gap-2
                        "
                        title="Toggle slide visibility">

                        <span
                            class="
                                text-xs font-medium
                                text-gray-700
                                dark:text-gray-300
                            ">
                            Active
                        </span>

                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            class="peer sr-only"
                            @checked($isActive)>

                        <span
                            class="
                                relative h-5 w-9
                                rounded-full
                                bg-gray-300
                                transition
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
                                dark:bg-neutral-700
                            ">
                        </span>

                    </label>

                </div>

            </div>

        </div>


        <div class="mt-3 space-y-3">

            {{-- Category --}}
            <div>

                <label
                    for="category"
                    class="
                        mb-1 block
                        text-xs font-medium
                        text-gray-700
                        dark:text-gray-300
                    ">
                    Category
                    <span class="text-red-500">*</span>
                </label>


                <select
                    id="category"
                    name="category"
                    required
                    class="
                        w-full
                        border border-gray-300
                        bg-white
                        px-3 py-2
                        text-sm text-gray-900
                        outline-none
                        transition
                        focus:border-[#008080]
                        focus:ring-2
                        focus:ring-[#008080]/15
                        dark:border-neutral-700
                        dark:bg-neutral-950
                        dark:text-white
                    ">

                    <option
                        value="promotion"
                        @selected($selectedCategory === 'promotion')>
                        Promotion
                    </option>

                    <option
                        value="event"
                        @selected($selectedCategory === 'event')>
                        Event
                    </option>

                    <option
                        value="announcement"
                        @selected($selectedCategory === 'announcement')>
                        Announcement
                    </option>

                    <option
                        value="coverage_update"
                        @selected($selectedCategory === 'coverage_update')>
                        Coverage Update
                    </option>

                    <option
                        value="maintenance_advisory"
                        @selected($selectedCategory === 'maintenance_advisory')>
                        Maintenance Advisory
                    </option>

                </select>

            </div>


            {{-- Display order --}}
            <div>

                <label
                    for="display_order"
                    class="
                        mb-1 block
                        text-xs font-medium
                        text-gray-700
                        dark:text-gray-300
                    ">
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
                        w-full
                        border border-gray-300
                        bg-white
                        px-3 py-2
                        text-sm text-gray-900
                        outline-none
                        transition
                        focus:border-[#008080]
                        focus:ring-2
                        focus:ring-[#008080]/15
                        dark:border-neutral-700
                        dark:bg-neutral-950
                        dark:text-white
                    ">

                <p
                    class="
                        mt-1
                        text-[10px]
                        text-gray-500
                        dark:text-gray-400
                    ">
                    Lower numbers appear first.
                </p>

            </div>


            {{-- Schedule --}}
            <div
                class="
                    border-t border-gray-100
                    pt-3
                    dark:border-neutral-800
                ">

                <div
                    class="
                        flex items-center
                        justify-between gap-2
                    ">

                    <h3
                        class="
                            text-xs font-semibold
                            text-gray-900
                            dark:text-white
                        ">
                        Schedule
                    </h3>

                    <span
                        class="
                            text-[10px]
                            text-gray-400
                            dark:text-gray-500
                        ">
                        Optional
                    </span>

                </div>


                <div class="mt-2 space-y-2">

                    <div>

                        <label
                            for="starts_at"
                            class="
                                mb-1 block
                                text-[11px] font-medium
                                text-gray-700
                                dark:text-gray-300
                            ">
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
                                w-full min-w-0
                                border border-gray-300
                                bg-white
                                px-2.5 py-2
                                text-xs text-gray-900
                                outline-none
                                transition
                                focus:border-[#008080]
                                focus:ring-2
                                focus:ring-[#008080]/15
                                dark:border-neutral-700
                                dark:bg-neutral-950
                                dark:text-white
                            ">

                    </div>


                    <div>

                        <label
                            for="ends_at"
                            class="
                                mb-1 block
                                text-[11px] font-medium
                                text-gray-700
                                dark:text-gray-300
                            ">
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
                                w-full min-w-0
                                border border-gray-300
                                bg-white
                                px-2.5 py-2
                                text-xs text-gray-900
                                outline-none
                                transition
                                focus:border-[#008080]
                                focus:ring-2
                                focus:ring-[#008080]/15
                                dark:border-neutral-700
                                dark:bg-neutral-950
                                dark:text-white
                            ">

                    </div>

                </div>

            </div>

        </div>

    </section>

</div>