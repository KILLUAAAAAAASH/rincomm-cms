@php
$plan = $servicePlan ?? null;

$isCustom = (bool) old(
    'is_custom',
    $plan?->is_custom ?? false
);

$isActive = (bool) old(
    'is_active',
    $plan?->is_active ?? true
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
        border border-gray-200
        bg-white
        dark:border-neutral-800
        dark:bg-neutral-900
    ">

    <div
        class="
            grid
            lg:grid-cols-3
        ">

        {{-- ====================================================
             PACKAGE INFORMATION
        ===================================================== --}}
        <section
            class="
                p-4
                lg:col-span-2
                lg:border-r
                lg:border-gray-200
                dark:lg:border-neutral-800
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
                    Package Information
                </h2>

                <p
                    class="
                        mt-0.5
                        text-xs text-gray-500
                        dark:text-gray-400
                    ">
                    Define the package name, speed, pricing, and description.
                </p>

            </div>


            <div
                class="
                    mt-3 grid gap-3
                    sm:grid-cols-2
                ">

                {{-- Package Name --}}
                <div class="sm:col-span-2">

                    <label
                        for="name"
                        class="
                            mb-1 block
                            text-xs font-medium
                            text-gray-700
                            dark:text-gray-300
                        ">
                        Package Name
                        <span class="text-red-500">*</span>
                    </label>


                    <input
                        id="name"
                        name="name"
                        type="text"
                        required
                        maxlength="255"
                        autocomplete="off"
                        value="{{ old('name', $plan?->name ?? '') }}"
                        placeholder="Example: Fiber 250"
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


                {{-- Internet Speed --}}
                <div>

                    <label
                        for="speed_mbps"
                        class="
                            mb-1 block
                            text-xs font-medium
                            text-gray-700
                            dark:text-gray-300
                        ">
                        Internet Speed
                        <span class="text-red-500">*</span>
                    </label>


                    <div class="relative">

                        <input
                            id="speed_mbps"
                            name="speed_mbps"
                            type="number"
                            required
                            min="0.01"
                            max="99999999.99"
                            step="0.01"
                            value="{{ old('speed_mbps', $plan?->speed_mbps ?? '') }}"
                            placeholder="250"
                            class="
                                w-full
                                border border-gray-300
                                bg-white
                                px-3 py-2 pr-16
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

                        <span
                            class="
                                pointer-events-none
                                absolute inset-y-0 right-3
                                flex items-center
                                text-xs font-medium
                                text-gray-500
                                dark:text-gray-400
                            ">
                            Mbps
                        </span>

                    </div>

                </div>


                {{-- Monthly Fee --}}
                <div>

                    <label
                        for="monthly_fee"
                        class="
                            mb-1 block
                            text-xs font-medium
                            text-gray-700
                            dark:text-gray-300
                        ">
                        Monthly Fee
                        <span class="text-red-500">*</span>
                    </label>


                    <div class="relative">

                        <span
                            class="
                                pointer-events-none
                                absolute inset-y-0 left-3
                                flex items-center
                                text-sm text-gray-500
                                dark:text-gray-400
                            ">
                            &#8369;
                        </span>


                        <input
                            id="monthly_fee"
                            name="monthly_fee"
                            type="number"
                            required
                            min="0"
                            max="99999999.99"
                            step="0.01"
                            value="{{ old('monthly_fee', $plan?->monthly_fee ?? '') }}"
                            placeholder="1499.00"
                            class="
                                w-full
                                border border-gray-300
                                bg-white
                                py-2 pl-8 pr-3
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


                {{-- Description --}}
                <div class="sm:col-span-2">

                    <div
                        class="
                            mb-1 flex
                            items-center justify-between
                            gap-2
                        ">

                        <label
                            for="description"
                            class="
                                text-xs font-medium
                                text-gray-700
                                dark:text-gray-300
                            ">
                            Description
                        </label>

                        <span
                            class="
                                text-[10px]
                                text-gray-400
                                dark:text-gray-500
                            ">
                            Optional
                        </span>

                    </div>


                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        maxlength="2000"
                        placeholder="Describe the package and important service information."
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
                        ">{{ old('description', $plan?->description ?? '') }}</textarea>

                    <p
                        class="
                            mt-1
                            text-[10px]
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Maximum 2,000 characters.
                    </p>

                </div>

            </div>

        </section>


        {{-- ====================================================
             PACKAGE SETTINGS
        ===================================================== --}}
        <section
            class="
                border-t border-gray-200
                p-4
                lg:border-t-0
                dark:border-neutral-800
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
                    Package Settings
                </h2>

                <p
                    class="
                        mt-0.5
                        text-xs text-gray-500
                        dark:text-gray-400
                    ">
                    Configure contract and availability.
                </p>

            </div>


            <div class="mt-3 space-y-3">

                {{-- Duration --}}
                <div>

                    <label
                        for="duration_months"
                        class="
                            mb-1 block
                            text-xs font-medium
                            text-gray-700
                            dark:text-gray-300
                        ">
                        Contract Duration
                        <span class="text-red-500">*</span>
                    </label>


                    <div class="relative">

                        <input
                            id="duration_months"
                            name="duration_months"
                            type="number"
                            required
                            min="1"
                            max="120"
                            step="1"
                            value="{{ old('duration_months', $plan?->duration_months ?? 12) }}"
                            class="
                                w-full
                                border border-gray-300
                                bg-white
                                px-3 py-2 pr-20
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

                        <span
                            class="
                                pointer-events-none
                                absolute inset-y-0 right-3
                                flex items-center
                                text-xs
                                text-gray-500
                                dark:text-gray-400
                            ">
                            months
                        </span>

                    </div>

                </div>


                {{-- Custom Plan --}}
                <div
                    class="
                        flex items-center
                        justify-between gap-3
                        border-t border-gray-100
                        pt-3
                        dark:border-neutral-800
                    ">

                    <div class="min-w-0">

                        <p
                            class="
                                text-xs font-medium
                                text-gray-800
                                dark:text-gray-200
                            ">
                            Custom Plan
                        </p>

                        <p
                            class="
                                mt-0.5
                                text-[11px] leading-4
                                text-gray-500
                                dark:text-gray-400
                            ">
                            Customer-specific package.
                        </p>

                    </div>


                    <div class="shrink-0">

                        <input
                            type="hidden"
                            name="is_custom"
                            value="0">


                        <label
                            class="
                                relative inline-flex
                                cursor-pointer items-center
                            "
                            title="Toggle custom plan">

                            <input
                                type="checkbox"
                                name="is_custom"
                                value="1"
                                class="peer sr-only"
                                @checked($isCustom)>

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


                {{-- Active Package --}}
                <div
                    class="
                        flex items-center
                        justify-between gap-3
                        border-t border-gray-100
                        pt-3
                        dark:border-neutral-800
                    ">

                    <div class="min-w-0">

                        <p
                            class="
                                text-xs font-medium
                                text-gray-800
                                dark:text-gray-200
                            ">
                            Active Package
                        </p>

                        <p
                            class="
                                mt-0.5
                                text-[11px] leading-4
                                text-gray-500
                                dark:text-gray-400
                            ">
                            Available for new subscriptions.
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
                            "
                            title="Toggle package availability">

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


            <div
                class="
                    mt-3 flex items-start gap-2
                    border-t border-gray-100
                    pt-3
                    text-[11px] leading-4
                    text-gray-500
                    dark:border-neutral-800
                    dark:text-gray-400
                ">

                <i
                    data-lucide="info"
                    class="
                        mt-0.5 h-3.5 w-3.5
                        shrink-0 text-[#008080]
                        dark:text-[#5EEAD4]
                    "
                    aria-hidden="true">
                </i>

                <p>
                    Inactive packages remain attached to existing records but cannot be selected for new subscriptions.
                </p>

            </div>

        </section>

    </div>

</div>