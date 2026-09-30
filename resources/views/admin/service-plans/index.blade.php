@extends('layouts.app')

@section('title', 'Internet Packages')

@section('page-title', 'Internet Packages')


@section('content')

<div class="space-y-2">

    {{-- Page heading --}}
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
                Internet Packages
            </h1>

            <p
                class="
                    mt-0.5
                    text-xs text-gray-500
                    dark:text-gray-400
                ">
                Configure speed, pricing, contract duration, and availability.
            </p>

        </div>


        <a
            href="{{ route('admin.service-plans.create') }}"
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

            Add Package

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


    {{-- Compact operational note --}}
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
                shrink-0 text-[#008080]
                dark:text-[#5EEAD4]
            "
            aria-hidden="true">
        </i>

        <p>
            Active packages can be used for subscriptions and plan changes.
            Deactivation keeps existing records intact.
        </p>

    </div>


    @if ($servicePlans->isEmpty())

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
            data-lucide="wifi-off"
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
            No internet packages
        </h2>


        <p
            class="
                mt-1
                text-xs text-gray-500
                dark:text-gray-400
            ">
            Create the first package to configure service availability.
        </p>


        <a
            href="{{ route('admin.service-plans.create') }}"
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

            Add Package

        </a>

    </div>

    @else


    {{-- Desktop package table --}}
    <div
        class="
            hidden
            border border-gray-200
            bg-white
            dark:border-neutral-800
            dark:bg-neutral-900
            lg:block
        ">

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
                        Package
                    </th>


                    <th
                        class="
                            w-[13%]
                            px-3 py-2.5
                            text-left
                            text-[10px] font-semibold
                            uppercase tracking-[0.08em]
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Speed
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
                        Monthly Fee
                    </th>


                    <th
                        class="
                            w-[13%]
                            px-3 py-2.5
                            text-left
                            text-[10px] font-semibold
                            uppercase tracking-[0.08em]
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Duration
                    </th>


                    <th
                        class="
                            w-[11%]
                            px-3 py-2.5
                            text-left
                            text-[10px] font-semibold
                            uppercase tracking-[0.08em]
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Type
                    </th>


                    <th
                        class="
                            w-[11%]
                            px-3 py-2.5
                            text-left
                            text-[10px] font-semibold
                            uppercase tracking-[0.08em]
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Status
                    </th>


                    <th
                        class="
                            w-[6%]
                            px-3 py-2.5
                            text-right
                            text-[10px] font-semibold
                            uppercase tracking-[0.08em]
                            text-gray-500
                            dark:text-gray-400
                        ">
                    </th>

                </tr>

            </thead>


            <tbody
                class="
                    divide-y divide-gray-100
                    dark:divide-neutral-800
                ">

                @foreach ($servicePlans as $plan)

                <tr
                    class="
                        transition
                        hover:bg-gray-50/70
                        dark:hover:bg-neutral-800/40
                    ">

                    {{-- Package --}}
                    <td class="px-3 py-3">

                        <div class="min-w-0">

                            <p
                                class="
                                    truncate
                                    text-sm font-semibold
                                    text-gray-900
                                    dark:text-white
                                "
                                title="{{ $plan->name }}">
                                {{ $plan->name }}
                            </p>


                            <p
                                class="
                                    mt-0.5 truncate
                                    text-xs text-gray-500
                                    dark:text-gray-400
                                "
                                title="{{ $plan->description }}">
                                {{ $plan->description
                                    ?: 'No description' }}
                            </p>

                        </div>

                    </td>


                    {{-- Speed --}}
                    <td
                        class="
                            px-3 py-3
                            text-sm font-medium
                            text-gray-800
                            dark:text-gray-200
                        ">
                        {{ number_format(
                            (float) $plan->speed_mbps,
                            0
                        ) }}
                        Mbps
                    </td>


                    {{-- Fee --}}
                    <td
                        class="
                            px-3 py-3
                            text-sm font-medium
                            text-gray-800
                            dark:text-gray-200
                        ">
                        &#8369;{{ number_format(
                            (float) $plan->monthly_fee,
                            2
                        ) }}
                    </td>


                    {{-- Duration --}}
                    <td
                        class="
                            px-3 py-3
                            text-sm text-gray-600
                            dark:text-gray-300
                        ">
                        {{ $plan->duration_months }}
                        {{ $plan->duration_months === 1
                            ? 'month'
                            : 'months' }}
                    </td>


                    {{-- Type --}}
                    <td class="px-3 py-3">

                        <span
                            class="
                                inline-flex
                                border px-2 py-0.5
                                text-xs font-medium

                                {{
                                    $plan->is_custom
                                        ? 'border-violet-200 bg-violet-50 text-violet-700
                                           dark:border-violet-900 dark:bg-violet-950/30 dark:text-violet-300'
                                        : 'border-gray-200 bg-gray-50 text-gray-700
                                           dark:border-neutral-700 dark:bg-neutral-800 dark:text-gray-300'
                                }}
                            ">
                            {{ $plan->is_custom
                                ? 'Custom'
                                : 'Standard' }}
                        </span>

                    </td>


                    {{-- Status --}}
                    <td class="px-3 py-3">

                        <span
                            class="
                                inline-flex
                                items-center gap-1.5
                                border px-2 py-0.5
                                text-xs font-medium

                                {{
                                    $plan->is_active
                                        ? 'border-green-200 bg-green-50 text-green-700
                                           dark:border-green-900 dark:bg-green-950/30 dark:text-green-300'
                                        : 'border-gray-200 bg-gray-50 text-gray-600
                                           dark:border-neutral-700 dark:bg-neutral-800 dark:text-gray-400'
                                }}
                            ">

                            <span
                                class="
                                    h-1.5 w-1.5
                                    {{
                                        $plan->is_active
                                            ? 'bg-green-500'
                                            : 'bg-gray-400'
                                    }}
                                ">
                            </span>

                            {{ $plan->is_active
                                ? 'Active'
                                : 'Inactive' }}

                        </span>

                    </td>


                    {{-- Actions --}}
                    <td class="px-3 py-3 text-right">

                        <details
                            class="
                                relative
                                inline-block text-left
                            ">

                            <summary
                                aria-label="Package options"
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
                                    mt-1 w-44
                                    border border-gray-200
                                    bg-white
                                    p-1
                                    text-left
                                    shadow-lg
                                    dark:border-neutral-700
                                    dark:bg-neutral-900
                                ">

                                <a
                                    href="{{ route(
                                        'admin.service-plans.edit',
                                        $plan
                                    ) }}"
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

                                    Edit Package

                                </a>


                                <form
                                    method="POST"
                                    action="{{ route(
                                        'admin.service-plans.status',
                                        $plan
                                    ) }}"
                                    data-lock-submit>

                                    @csrf
                                    @method('PATCH')


                                    <button
                                        type="submit"
                                        class="
                                            flex w-full
                                            items-center gap-2
                                            px-3 py-2
                                            text-left text-sm
                                            transition

                                            {{
                                                $plan->is_active
                                                    ? 'text-amber-700 hover:bg-amber-50
                                                       dark:text-amber-300 dark:hover:bg-amber-950/30'
                                                    : 'text-green-700 hover:bg-green-50
                                                       dark:text-green-300 dark:hover:bg-green-950/30'
                                            }}
                                        ">

                                        <i
                                            data-lucide="{{
                                                $plan->is_active
                                                    ? 'circle-pause'
                                                    : 'circle-play'
                                            }}"
                                            class="h-4 w-4"
                                            aria-hidden="true">
                                        </i>

                                        {{ $plan->is_active
                                            ? 'Deactivate'
                                            : 'Activate' }}

                                    </button>

                                </form>

                            </div>

                        </details>

                    </td>

                </tr>

                @endforeach

            </tbody>

        </table>

    </div>


    {{-- Tablet / mobile packages --}}
    <div class="grid gap-2 lg:hidden">

        @foreach ($servicePlans as $plan)

        <article
            class="
                border border-gray-200
                bg-white
                dark:border-neutral-800
                dark:bg-neutral-900
            ">

            <div
                class="
                    flex items-start
                    justify-between gap-3
                    px-3 py-3
                ">

                <div class="min-w-0">

                    <h2
                        class="
                            truncate
                            text-sm font-semibold
                            text-gray-900
                            dark:text-white
                        ">
                        {{ $plan->name }}
                    </h2>


                    <p
                        class="
                            mt-0.5
                            text-xs text-gray-500
                            dark:text-gray-400
                        ">
                        {{ number_format(
                            (float) $plan->speed_mbps,
                            0
                        ) }}
                        Mbps
                        |
                        &#8369;{{ number_format(
                            (float) $plan->monthly_fee,
                            2
                        ) }}/month
                    </p>

                </div>


                <details class="relative shrink-0">

                    <summary
                        aria-label="Package options"
                        title="More options"
                        style="list-style: none;"
                        class="
                            inline-flex h-8 w-8
                            cursor-pointer
                            items-center justify-center
                            border border-gray-200
                            text-gray-500
                            transition
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
                            mt-1 w-44
                            border border-gray-200
                            bg-white
                            p-1
                            shadow-lg
                            dark:border-neutral-700
                            dark:bg-neutral-900
                        ">

                        <a
                            href="{{ route(
                                'admin.service-plans.edit',
                                $plan
                            ) }}"
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
                                class="h-4 w-4"
                                aria-hidden="true">
                            </i>

                            Edit Package

                        </a>


                        <form
                            method="POST"
                            action="{{ route(
                                'admin.service-plans.status',
                                $plan
                            ) }}"
                            data-lock-submit>

                            @csrf
                            @method('PATCH')


                            <button
                                type="submit"
                                class="
                                    flex w-full
                                    items-center gap-2
                                    px-3 py-2
                                    text-left text-sm

                                    {{
                                        $plan->is_active
                                            ? 'text-amber-700 hover:bg-amber-50
                                               dark:text-amber-300 dark:hover:bg-amber-950/30'
                                            : 'text-green-700 hover:bg-green-50
                                               dark:text-green-300 dark:hover:bg-green-950/30'
                                    }}
                                ">

                                <i
                                    data-lucide="{{
                                        $plan->is_active
                                            ? 'circle-pause'
                                            : 'circle-play'
                                    }}"
                                    class="h-4 w-4"
                                    aria-hidden="true">
                                </i>

                                {{ $plan->is_active
                                    ? 'Deactivate'
                                    : 'Activate' }}

                            </button>

                        </form>

                    </div>

                </details>

            </div>


            <dl
                class="
                    grid grid-cols-3
                    border-t border-gray-100
                    dark:border-neutral-800
                ">

                <div class="px-3 py-2.5">

                    <dt
                        class="
                            text-[10px] font-semibold
                            uppercase tracking-wide
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Duration
                    </dt>

                    <dd
                        class="
                            mt-1
                            text-sm text-gray-700
                            dark:text-gray-300
                        ">
                        {{ $plan->duration_months }}
                        {{ $plan->duration_months === 1
                            ? 'month'
                            : 'months' }}
                    </dd>

                </div>


                <div
                    class="
                        border-x border-gray-100
                        px-3 py-2.5
                        dark:border-neutral-800
                    ">

                    <dt
                        class="
                            text-[10px] font-semibold
                            uppercase tracking-wide
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Type
                    </dt>

                    <dd
                        class="
                            mt-1
                            text-sm text-gray-700
                            dark:text-gray-300
                        ">
                        {{ $plan->is_custom
                            ? 'Custom'
                            : 'Standard' }}
                    </dd>

                </div>


                <div class="px-3 py-2.5">

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
                            mt-1 text-sm
                            {{
                                $plan->is_active
                                    ? 'text-green-700 dark:text-green-300'
                                    : 'text-gray-600 dark:text-gray-400'
                            }}
                        ">
                        {{ $plan->is_active
                            ? 'Active'
                            : 'Inactive' }}
                    </dd>

                </div>

            </dl>


            @if ($plan->description)

            <p
                class="
                    border-t border-gray-100
                    px-3 py-2.5
                    text-xs leading-5
                    text-gray-500
                    dark:border-neutral-800
                    dark:text-gray-400
                ">
                {{ $plan->description }}
            </p>

            @endif

        </article>

        @endforeach

    </div>

    @endif

</div>

@endsection
