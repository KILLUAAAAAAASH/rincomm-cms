@extends('layouts.app')

@section('title', 'Subscribers')

@section('page-title', 'Subscribers')

@section('content')

@php
$featureKey = request('feature');

$featureContext = [
    'customer-information' => [
        'label' => 'Customer Profile',
        'description' => 'Select a subscriber to manage profile information.',
        'icon' => 'user',
    ],
    'customer-account-status' => [
        'label' => 'Account Status',
        'description' => 'Select a subscriber to manage account status.',
        'icon' => 'settings-2',
    ],
    'customer-documents' => [
        'label' => 'Customer Documents',
        'description' => 'Select a subscriber to manage documents.',
        'icon' => 'file-text',
    ],
    'relocation-transfer' => [
        'label' => 'Relocation',
        'description' => 'Select a subscriber to manage relocation.',
        'icon' => 'map-pinned',
    ],
    'plan-change' => [
        'label' => 'Plan Change',
        'description' => 'Select a subscriber to manage a plan change.',
        'icon' => 'settings-2',
    ],
][$featureKey] ?? null;
@endphp


<div class="mx-auto max-w-[1600px] space-y-4">

    {{-- Page heading --}}
    <section
        class="
            flex flex-col gap-3
            sm:flex-row
            sm:items-end
            sm:justify-between
        ">

        <div class="min-w-0">

            <div class="flex items-center gap-2">

                <div
                    class="
                        flex h-9 w-9 shrink-0
                        items-center justify-center
                        border border-[#008080]/15
                        bg-[#008080]/10
                        text-[#008080]
                        dark:border-[#14B8A6]/20
                        dark:bg-[#008080]/20
                        dark:text-[#5EEAD4]
                    ">

                    <i
                        data-lucide="users"
                        class="h-4.5 w-4.5"
                        aria-hidden="true">
                    </i>

                </div>


                <div class="min-w-0">

                    <h1
                        class="
                            truncate
                            text-xl font-semibold
                            text-gray-900
                            dark:text-white
                            sm:text-2xl
                        ">
                        Subscribers
                    </h1>

                    <p
                        class="
                            mt-0.5
                            text-sm
                            text-gray-500
                            dark:text-gray-400
                        ">
                        Find and manage subscriber accounts.
                    </p>

                </div>

            </div>

        </div>


        <div
            class="
                inline-flex w-fit items-center gap-2
                border border-gray-200
                bg-white
                px-3 py-2
                text-xs text-gray-500
                dark:border-neutral-800
                dark:bg-neutral-900
                dark:text-gray-400
            ">

            <i
                data-lucide="users"
                class="h-3.5 w-3.5 text-[#008080] dark:text-[#5EEAD4]"
                aria-hidden="true">
            </i>

            <span>
                {{ number_format($subscribers->count()) }}
                {{ $subscribers->count() === 1 ? 'subscriber' : 'subscribers' }}
            </span>

        </div>

    </section>


    {{-- Feature context --}}
    @if ($featureContext)

    <section
        class="
            flex items-center gap-3
            border border-[#008080]/20
            bg-[#008080]/5
            px-4 py-3
            dark:border-[#14B8A6]/20
            dark:bg-[#008080]/10
        ">

        <div
            class="
                flex h-8 w-8 shrink-0
                items-center justify-center
                border border-[#008080]/10
                bg-white
                text-[#008080]
                dark:border-neutral-700
                dark:bg-neutral-900
                dark:text-[#5EEAD4]
            ">

            <i
                data-lucide="{{ $featureContext['icon'] }}"
                class="h-4 w-4"
                aria-hidden="true">
            </i>

        </div>


        <div class="min-w-0">

            <p
                class="
                    text-sm font-semibold
                    text-gray-900
                    dark:text-white
                ">
                {{ $featureContext['label'] }}
            </p>

            <p
                class="
                    truncate
                    text-xs text-gray-500
                    dark:text-gray-400
                ">
                {{ $featureContext['description'] }}
            </p>

        </div>

    </section>

    @endif


    {{-- Search and filter toolbar --}}
    <form
        method="GET"
        action="{{ route('admin.subscribers.index') }}"
        class="
            border border-gray-200
            bg-white
            p-3
            dark:border-neutral-800
            dark:bg-neutral-900
        ">

        @if ($featureContext)

        <input
            type="hidden"
            name="feature"
            value="{{ $featureKey }}">

        @endif


        <div
            class="
                flex flex-col gap-2
                md:flex-row
                md:items-center
            ">

            {{-- Search --}}
            <div class="relative min-w-0 flex-1">

                <label
                    for="subscriber-search"
                    class="sr-only">
                    Search subscribers
                </label>


                <i
                    data-lucide="search"
                    class="
                        pointer-events-none
                        absolute left-3 top-1/2
                        h-4 w-4
                        -translate-y-1/2
                        text-gray-400
                        dark:text-gray-500
                    "
                    aria-hidden="true">
                </i>


                <input
                    id="subscriber-search"
                    type="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search customer code, name, email, or phone"
                    autocomplete="off"
                    class="
                        min-h-10 w-full
                        border border-gray-300
                        bg-white
                        py-2 pl-10 pr-3
                        text-sm text-gray-900
                        outline-none transition
                        placeholder:text-gray-400
                        focus:border-[#008080]
                        focus:ring-2
                        focus:ring-[#008080]/20
                        dark:border-neutral-700
                        dark:bg-neutral-950
                        dark:text-white
                        dark:placeholder:text-gray-500
                    ">

            </div>


            {{-- Status --}}
            <div class="md:w-44 md:shrink-0">

                <label
                    for="subscriber-status"
                    class="sr-only">
                    Filter by status
                </label>


                <select
                    id="subscriber-status"
                    name="status"
                    class="
                        min-h-10 w-full
                        border border-gray-300
                        bg-white
                        px-3 py-2
                        text-sm text-gray-800
                        outline-none transition
                        focus:border-[#008080]
                        focus:ring-2
                        focus:ring-[#008080]/20
                        dark:border-neutral-700
                        dark:bg-neutral-950
                        dark:text-gray-100
                    ">

                    <option value="">
                        All Status
                    </option>


                    @foreach ($statuses as $subscriberStatus)

                    <option
                        value="{{ $subscriberStatus }}"
                        @selected($status === $subscriberStatus)>

                        {{ ucfirst($subscriberStatus) }}

                    </option>

                    @endforeach

                </select>

            </div>


            {{-- Apply --}}
            <button
                type="submit"
                class="
                    inline-flex min-h-10 shrink-0
                    items-center justify-center gap-2
                    bg-[#008080]
                    px-4 py-2
                    text-sm font-semibold
                    text-white
                    transition
                    hover:bg-[#006666]
                    focus:outline-none
                    focus:ring-2
                    focus:ring-[#008080]/30
                ">

                <i
                    data-lucide="list-filter"
                    class="h-4 w-4"
                    aria-hidden="true">
                </i>

                Filter

            </button>


            {{-- Clear --}}
            @if ($search !== '' || $status !== '')

            <a
                href="{{ route(
                    'admin.subscribers.index',
                    $featureContext
                        ? ['feature' => $featureKey]
                        : []
                ) }}"
                aria-label="Clear subscriber filters"
                title="Clear filters"
                class="
                    inline-flex h-10 w-10 shrink-0
                    items-center justify-center
                    border border-gray-300
                    text-gray-500
                    transition
                    hover:bg-gray-50
                    hover:text-gray-900
                    focus:outline-none
                    focus:ring-2
                    focus:ring-gray-300
                    dark:border-neutral-700
                    dark:text-gray-400
                    dark:hover:bg-neutral-800
                    dark:hover:text-white
                ">

                <i
                    data-lucide="x"
                    class="h-4 w-4"
                    aria-hidden="true">
                </i>

            </a>

            @endif

        </div>

    </form>


    {{-- Results --}}
    @if ($subscribers->isEmpty())

    <section
        class="
            border border-dashed border-gray-300
            bg-white
            px-6 py-14
            text-center
            dark:border-neutral-700
            dark:bg-neutral-900
        ">

        <div
            class="
                mx-auto flex h-12 w-12
                items-center justify-center
                border border-gray-200
                bg-gray-100
                text-gray-400
                dark:border-neutral-700
                dark:bg-neutral-800
                dark:text-gray-500
            ">

            <i
                data-lucide="users"
                class="h-5 w-5"
                aria-hidden="true">
            </i>

        </div>


        <h2
            class="
                mt-4
                text-sm font-semibold
                text-gray-900
                dark:text-white
            ">
            No subscribers found
        </h2>


        <p
            class="
                mx-auto mt-1
                max-w-sm
                text-sm text-gray-500
                dark:text-gray-400
            ">

            @if ($search !== '' || $status !== '')

            Try changing your search or filter.

            @else

            Approved subscriber accounts will appear here.

            @endif

        </p>

    </section>

    @else


    {{-- Desktop subscriber table --}}
    <section
        class="
            hidden
            border border-gray-200
            bg-white
            lg:block
            dark:border-neutral-800
            dark:bg-neutral-900
        ">

        <div class="px-4 py-3">

            <div class="flex items-center justify-between gap-4">

                <div>

                    <h2
                        class="
                            text-sm font-semibold
                            text-gray-900
                            dark:text-white
                        ">
                        Subscriber Directory
                    </h2>

                    <p
                        class="
                            mt-0.5
                            text-xs text-gray-500
                            dark:text-gray-400
                        ">
                        Select a name or use More options.
                    </p>

                </div>


                @if ($search !== '' || $status !== '')

                <span
                    class="
                        border border-[#008080]/15
                        bg-[#008080]/10
                        px-2.5 py-1
                        text-xs font-medium
                        text-[#008080]
                        dark:border-[#008080]/20
                        dark:bg-[#008080]/20
                        dark:text-[#5EEAD4]
                    ">
                    Filtered
                </span>

                @endif

            </div>

        </div>


        <div class="border-t border-gray-100 dark:border-neutral-800">

            <table class="w-full table-fixed">

                <thead class="bg-gray-50/80 dark:bg-neutral-800/60">

                    <tr>

                        <th
                            scope="col"
                            class="
                                w-[18%]
                                px-4 py-3
                                text-left
                                text-[11px] font-semibold
                                uppercase tracking-wide
                                text-gray-500
                                dark:text-gray-400
                            ">
                            Customer Code
                        </th>


                        <th
                            scope="col"
                            class="
                                w-[24%]
                                px-4 py-3
                                text-left
                                text-[11px] font-semibold
                                uppercase tracking-wide
                                text-gray-500
                                dark:text-gray-400
                            ">
                            Name
                        </th>


                        <th
                            scope="col"
                            class="
                                w-[24%]
                                px-4 py-3
                                text-left
                                text-[11px] font-semibold
                                uppercase tracking-wide
                                text-gray-500
                                dark:text-gray-400
                            ">
                            Contact
                        </th>


                        <th
                            scope="col"
                            class="
                                w-[18%]
                                px-4 py-3
                                text-left
                                text-[11px] font-semibold
                                uppercase tracking-wide
                                text-gray-500
                                dark:text-gray-400
                            ">
                            Location
                        </th>


                        <th
                            scope="col"
                            class="
                                w-[11%]
                                px-4 py-3
                                text-left
                                text-[11px] font-semibold
                                uppercase tracking-wide
                                text-gray-500
                                dark:text-gray-400
                            ">
                            Status
                        </th>


                        <th
                            scope="col"
                            class="
                                w-[5%]
                                px-3 py-3
                                text-center
                                text-[11px] font-semibold
                                uppercase tracking-wide
                                text-gray-500
                                dark:text-gray-400
                            ">

                            <span class="sr-only">
                                Actions
                            </span>

                        </th>

                    </tr>

                </thead>


                <tbody
                    class="
                        divide-y divide-gray-100
                        dark:divide-neutral-800
                    ">

                    @foreach ($subscribers as $subscriber)

                    @php
                    $statusDisplay = match ($subscriber->status) {
                        'pending' => [
                            'label' => 'Pending',
                            'icon' => 'clock-3',
                            'class' =>
                                'bg-amber-50 text-amber-700
                                 dark:bg-amber-950/40 dark:text-amber-300',
                        ],
                        'active' => [
                            'label' => 'Active',
                            'icon' => 'circle-check',
                            'class' =>
                                'bg-green-50 text-green-700
                                 dark:bg-green-950/40 dark:text-green-300',
                        ],
                        'inactive' => [
                            'label' => 'Inactive',
                            'icon' => 'circle-minus',
                            'class' =>
                                'bg-gray-100 text-gray-600
                                 dark:bg-neutral-800 dark:text-gray-300',
                        ],
                        'suspended' => [
                            'label' => 'Suspended',
                            'icon' => 'circle-pause',
                            'class' =>
                                'bg-red-50 text-red-700
                                 dark:bg-red-950/40 dark:text-red-300',
                        ],
                        'disconnected' => [
                            'label' => 'Disconnected',
                            'icon' => 'wifi-off',
                            'class' =>
                                'bg-gray-100 text-gray-600
                                 dark:bg-neutral-800 dark:text-gray-300',
                        ],
                        default => [
                            'label' => ucfirst($subscriber->status),
                            'icon' => 'circle-help',
                            'class' =>
                                'bg-gray-100 text-gray-600
                                 dark:bg-neutral-800 dark:text-gray-300',
                        ],
                    };

                    $fullName = trim(
                        implode(' ', array_filter([
                            $subscriber->first_name,
                            $subscriber->middle_name,
                            $subscriber->last_name,
                        ]))
                    );

                    $location = implode(', ', array_filter([
                        $subscriber->city,
                        $subscriber->province,
                    ]));

                    $detailUrl = $featureContext
                        ? route(
                            'admin.subscribers.show',
                            [
                                'subscriber' => $subscriber,
                                'feature' => $featureKey,
                            ]
                        ) . '#' . $featureKey
                        : route(
                            'admin.subscribers.show',
                            $subscriber
                        );

                    $profileUrl = route(
                        'admin.subscribers.show',
                        [
                            'subscriber' => $subscriber,
                            'feature' => 'customer-information',
                        ]
                    ) . '#customer-information';

                    $documentsUrl = route(
                        'admin.subscribers.show',
                        [
                            'subscriber' => $subscriber,
                            'feature' => 'customer-documents',
                        ]
                    ) . '#customer-documents';

                    $relocationUrl = route(
                        'admin.subscribers.show',
                        [
                            'subscriber' => $subscriber,
                            'feature' => 'relocation-transfer',
                        ]
                    ) . '#relocation-transfer';

                    $planChangeUrl = route(
                        'admin.subscribers.show',
                        [
                            'subscriber' => $subscriber,
                            'feature' => 'plan-change',
                        ]
                    ) . '#plan-change';
                    @endphp


                    <tr
                        class="
                            group
                            transition
                            hover:bg-[#008080]/[0.035]
                            dark:hover:bg-[#008080]/[0.07]
                        ">

                        {{-- Code --}}
                        <td class="px-4 py-3.5 align-middle">

                            <span
                                class="
                                    truncate
                                    text-xs font-semibold
                                    text-[#008080]
                                    dark:text-[#5EEAD4]
                                ">
                                {{ $subscriber->customer_code }}
                            </span>

                        </td>


                        {{-- Name --}}
                        <td class="px-4 py-3.5 align-middle">

                            <a
                                href="{{ $detailUrl }}"
                                class="
                                    block truncate
                                    text-sm font-semibold
                                    text-gray-900
                                    transition
                                    hover:text-[#008080]
                                    focus:outline-none
                                    focus:text-[#008080]
                                    dark:text-white
                                    dark:hover:text-[#5EEAD4]
                                "
                                title="{{ $fullName }}">

                                {{ $fullName }}

                            </a>

                        </td>


                        {{-- Contact --}}
                        <td class="px-4 py-3.5 align-middle">

                            <div class="min-w-0">

                                <p
                                    class="
                                        truncate
                                        text-sm text-gray-700
                                        dark:text-gray-300
                                    "
                                    title="{{ $subscriber->email ?: 'No email recorded' }}">

                                    {{ $subscriber->email ?: 'No email recorded' }}

                                </p>

                                @if ($subscriber->phone)

                                <p
                                    class="
                                        mt-0.5 truncate
                                        text-xs text-gray-400
                                    ">
                                    {{ $subscriber->phone }}
                                </p>

                                @endif

                            </div>

                        </td>


                        {{-- Location --}}
                        <td class="px-4 py-3.5 align-middle">

                            <div
                                class="
                                    flex min-w-0
                                    items-center gap-1.5
                                    text-sm text-gray-600
                                    dark:text-gray-300
                                ">

                                <i
                                    data-lucide="map-pin"
                                    class="
                                        h-3.5 w-3.5 shrink-0
                                        text-gray-400
                                    "
                                    aria-hidden="true">
                                </i>

                                <span
                                    class="truncate"
                                    title="{{ $location ?: 'Not specified' }}">

                                    {{ $location ?: 'Not specified' }}

                                </span>

                            </div>

                        </td>


                        {{-- Status --}}
                        <td class="px-4 py-3.5 align-middle">

                            <span
                                class="
                                    inline-flex
                                    items-center gap-1.5
                                    whitespace-nowrap
                                    rounded-full
                                    px-2.5 py-1
                                    text-xs font-medium
                                    {{ $statusDisplay['class'] }}
                                ">

                                <i
                                    data-lucide="{{ $statusDisplay['icon'] }}"
                                    class="h-3 w-3"
                                    aria-hidden="true">
                                </i>

                                {{ $statusDisplay['label'] }}

                            </span>

                        </td>


                        {{-- More options --}}
                        <td
                            class="
                                relative
                                px-3 py-3.5
                                text-center
                                align-middle
                            ">

                            <details
                                class="
                                    relative
                                    inline-block
                                    text-left
                                ">

                                <summary
                                    aria-label="More options for {{ $fullName }}"
                                    title="More options"
                                    style="list-style: none;"
                                    class="
                                        inline-flex h-8 w-8
                                        cursor-pointer
                                        items-center justify-center
                                        text-gray-500
                                        transition
                                        hover:bg-gray-100
                                        hover:text-gray-900
                                        focus:outline-none
                                        focus:ring-2
                                        focus:ring-[#008080]/30
                                        dark:text-gray-400
                                        dark:hover:bg-neutral-800
                                        dark:hover:text-white
                                        [&::-webkit-details-marker]:hidden
                                    ">

                                    <span
                                        class="
                                            text-lg font-semibold
                                            leading-none
                                        "
                                        aria-hidden="true">
                                        &#8942;
                                    </span>

                                </summary>


                                <div
                                    class="
                                        absolute right-0 z-40
                                        mt-2 w-56
                                        overflow-hidden
                                        border border-gray-200
                                        bg-white
                                        p-1.5
                                        text-left
                                        shadow-xl
                                        dark:border-neutral-700
                                        dark:bg-neutral-900
                                    ">

                                    <a
                                        href="{{ $detailUrl }}"
                                        class="
                                            flex items-center gap-2.5
                                            px-3 py-2
                                            text-sm font-medium
                                            text-gray-700
                                            transition
                                            hover:bg-[#008080]/10
                                            hover:text-[#008080]
                                            dark:text-gray-200
                                            dark:hover:bg-[#008080]/15
                                            dark:hover:text-[#5EEAD4]
                                        ">

                                        <i
                                            data-lucide="eye"
                                            class="h-4 w-4 shrink-0"
                                            aria-hidden="true">
                                        </i>

                                        Open subscriber

                                    </a>


                                    <a
                                        href="{{ route(
                                            'admin.subscribers.edit',
                                            $subscriber
                                        ) }}"
                                        class="
                                            flex items-center gap-2.5
                                            px-3 py-2
                                            text-sm
                                            text-gray-600
                                            transition
                                            hover:bg-gray-50
                                            hover:text-gray-900
                                            dark:text-gray-300
                                            dark:hover:bg-neutral-800
                                            dark:hover:text-white
                                        ">

                                        <i
                                            data-lucide="pencil"
                                            class="h-4 w-4 shrink-0"
                                            aria-hidden="true">
                                        </i>

                                        Edit profile

                                    </a>


                                    <div
                                        class="
                                            my-1
                                            border-t border-gray-100
                                            dark:border-neutral-800
                                        ">
                                    </div>


                                    <a
                                        href="{{ $documentsUrl }}"
                                        class="
                                            flex items-center gap-2.5
                                            px-3 py-2
                                            text-sm
                                            text-gray-600
                                            transition
                                            hover:bg-gray-50
                                            hover:text-gray-900
                                            dark:text-gray-300
                                            dark:hover:bg-neutral-800
                                            dark:hover:text-white
                                        ">

                                        <i
                                            data-lucide="file-text"
                                            class="h-4 w-4 shrink-0"
                                            aria-hidden="true">
                                        </i>

                                        Documents

                                    </a>


                                    <a
                                        href="{{ $relocationUrl }}"
                                        class="
                                            flex items-center gap-2.5
                                            px-3 py-2
                                            text-sm
                                            text-gray-600
                                            transition
                                            hover:bg-gray-50
                                            hover:text-gray-900
                                            dark:text-gray-300
                                            dark:hover:bg-neutral-800
                                            dark:hover:text-white
                                        ">

                                        <i
                                            data-lucide="map-pinned"
                                            class="h-4 w-4 shrink-0"
                                            aria-hidden="true">
                                        </i>

                                        Relocation

                                    </a>


                                    <a
                                        href="{{ $planChangeUrl }}"
                                        class="
                                            flex items-center gap-2.5
                                            px-3 py-2
                                            text-sm
                                            text-gray-600
                                            transition
                                            hover:bg-gray-50
                                            hover:text-gray-900
                                            dark:text-gray-300
                                            dark:hover:bg-neutral-800
                                            dark:hover:text-white
                                        ">

                                        <i
                                            data-lucide="settings-2"
                                            class="h-4 w-4 shrink-0"
                                            aria-hidden="true">
                                        </i>

                                        Change plan

                                    </a>

                                </div>

                            </details>

                        </td>

                    </tr>

                    @endforeach

                </tbody>

            </table>

        </div>


        <footer
            class="
                flex items-center justify-between
                border-t border-gray-100
                px-4 py-3
                dark:border-neutral-800
            ">

            <p
                class="
                    text-xs text-gray-500
                    dark:text-gray-400
                ">
                Showing {{ number_format($subscribers->count()) }}
                {{ $subscribers->count() === 1 ? 'record' : 'records' }}
            </p>


            <p
                class="
                    hidden text-xs
                    text-gray-400
                    xl:block
                ">
                Select a name to open subscriber details.
            </p>

        </footer>

    </section>


    {{-- Mobile / tablet cards --}}
    <section class="grid gap-3 lg:hidden">

        @foreach ($subscribers as $subscriber)

        @php
        $statusDisplay = match ($subscriber->status) {
            'pending' => [
                'label' => 'Pending',
                'icon' => 'clock-3',
                'class' =>
                    'bg-amber-50 text-amber-700
                     dark:bg-amber-950/40 dark:text-amber-300',
            ],
            'active' => [
                'label' => 'Active',
                'icon' => 'circle-check',
                'class' =>
                    'bg-green-50 text-green-700
                     dark:bg-green-950/40 dark:text-green-300',
            ],
            'inactive' => [
                'label' => 'Inactive',
                'icon' => 'circle-minus',
                'class' =>
                    'bg-gray-100 text-gray-600
                     dark:bg-neutral-800 dark:text-gray-300',
            ],
            'suspended' => [
                'label' => 'Suspended',
                'icon' => 'circle-pause',
                'class' =>
                    'bg-red-50 text-red-700
                     dark:bg-red-950/40 dark:text-red-300',
            ],
            'disconnected' => [
                'label' => 'Disconnected',
                'icon' => 'wifi-off',
                'class' =>
                    'bg-gray-100 text-gray-600
                     dark:bg-neutral-800 dark:text-gray-300',
            ],
            default => [
                'label' => ucfirst($subscriber->status),
                'icon' => 'circle-help',
                'class' =>
                    'bg-gray-100 text-gray-600
                     dark:bg-neutral-800 dark:text-gray-300',
            ],
        };

        $fullName = trim(
            implode(' ', array_filter([
                $subscriber->first_name,
                $subscriber->middle_name,
                $subscriber->last_name,
            ]))
        );

        $location = implode(', ', array_filter([
            $subscriber->city,
            $subscriber->province,
        ]));

        $detailUrl = $featureContext
            ? route(
                'admin.subscribers.show',
                [
                    'subscriber' => $subscriber,
                    'feature' => $featureKey,
                ]
            ) . '#' . $featureKey
            : route(
                'admin.subscribers.show',
                $subscriber
            );

        $documentsUrl = route(
            'admin.subscribers.show',
            [
                'subscriber' => $subscriber,
                'feature' => 'customer-documents',
            ]
        ) . '#customer-documents';

        $relocationUrl = route(
            'admin.subscribers.show',
            [
                'subscriber' => $subscriber,
                'feature' => 'relocation-transfer',
            ]
        ) . '#relocation-transfer';

        $planChangeUrl = route(
            'admin.subscribers.show',
            [
                'subscriber' => $subscriber,
                'feature' => 'plan-change',
            ]
        ) . '#plan-change';
        @endphp


        <article
            class="
                border border-gray-200
                bg-white
                p-4
                dark:border-neutral-800
                dark:bg-neutral-900
            ">

            <div class="flex items-start gap-3">

                <div class="min-w-0 flex-1">

                    <p
                        class="
                            text-xs font-semibold
                            text-[#008080]
                            dark:text-[#5EEAD4]
                        ">
                        {{ $subscriber->customer_code }}
                    </p>


                    <a
                        href="{{ $detailUrl }}"
                        class="
                            mt-1 block truncate
                            text-base font-semibold
                            text-gray-900
                            hover:text-[#008080]
                            dark:text-white
                            dark:hover:text-[#5EEAD4]
                        ">

                        {{ $fullName }}

                    </a>

                </div>


                <span
                    class="
                        inline-flex shrink-0
                        items-center gap-1
                        rounded-full
                        px-2.5 py-1
                        text-[11px] font-medium
                        {{ $statusDisplay['class'] }}
                    ">

                    <i
                        data-lucide="{{ $statusDisplay['icon'] }}"
                        class="h-3 w-3"
                        aria-hidden="true">
                    </i>

                    {{ $statusDisplay['label'] }}

                </span>


                <details class="relative shrink-0">

                    <summary
                        aria-label="More options for {{ $fullName }}"
                        title="More options"
                        style="list-style: none;"
                        class="
                            inline-flex h-8 w-8
                            cursor-pointer
                            items-center justify-center
                            text-gray-500
                            hover:bg-gray-100
                            dark:text-gray-400
                            dark:hover:bg-neutral-800
                            [&::-webkit-details-marker]:hidden
                        ">

                        <span
                            class="text-lg font-semibold leading-none"
                            aria-hidden="true">
                            &#8942;
                        </span>

                    </summary>


                    <div
                        class="
                            absolute right-0 z-40
                            mt-2 w-52
                            overflow-hidden
                            border border-gray-200
                            bg-white
                            p-1.5
                            shadow-xl
                            dark:border-neutral-700
                            dark:bg-neutral-900
                        ">

                        <a
                            href="{{ $detailUrl }}"
                            class="
                                flex items-center gap-2
                                px-3 py-2
                                text-sm text-gray-700
                                hover:bg-[#008080]/10
                                hover:text-[#008080]
                                dark:text-gray-200
                                dark:hover:bg-[#008080]/15
                                dark:hover:text-[#5EEAD4]
                            ">

                            <i
                                data-lucide="eye"
                                class="h-4 w-4"
                                aria-hidden="true">
                            </i>

                            Open subscriber

                        </a>


                        <a
                            href="{{ route(
                                'admin.subscribers.edit',
                                $subscriber
                            ) }}"
                            class="
                                flex items-center gap-2
                                px-3 py-2
                                text-sm text-gray-600
                                hover:bg-gray-50
                                dark:text-gray-300
                                dark:hover:bg-neutral-800
                            ">

                            <i
                                data-lucide="pencil"
                                class="h-4 w-4"
                                aria-hidden="true">
                            </i>

                            Edit profile

                        </a>


                        <a
                            href="{{ $documentsUrl }}"
                            class="
                                flex items-center gap-2
                                px-3 py-2
                                text-sm text-gray-600
                                hover:bg-gray-50
                                dark:text-gray-300
                                dark:hover:bg-neutral-800
                            ">

                            <i
                                data-lucide="file-text"
                                class="h-4 w-4"
                                aria-hidden="true">
                            </i>

                            Documents

                        </a>


                        <a
                            href="{{ $relocationUrl }}"
                            class="
                                flex items-center gap-2
                                px-3 py-2
                                text-sm text-gray-600
                                hover:bg-gray-50
                                dark:text-gray-300
                                dark:hover:bg-neutral-800
                            ">

                            <i
                                data-lucide="map-pinned"
                                class="h-4 w-4"
                                aria-hidden="true">
                            </i>

                            Relocation

                        </a>


                        <a
                            href="{{ $planChangeUrl }}"
                            class="
                                flex items-center gap-2
                                px-3 py-2
                                text-sm text-gray-600
                                hover:bg-gray-50
                                dark:text-gray-300
                                dark:hover:bg-neutral-800
                            ">

                            <i
                                data-lucide="settings-2"
                                class="h-4 w-4"
                                aria-hidden="true">
                            </i>

                            Change plan

                        </a>

                    </div>

                </details>

            </div>


            <div
                class="
                    mt-4 grid gap-3
                    border-t border-gray-100
                    pt-4
                    sm:grid-cols-2
                    dark:border-neutral-800
                ">

                <div class="min-w-0">

                    <div
                        class="
                            flex items-center gap-1.5
                            text-xs text-gray-400
                        ">

                        <i
                            data-lucide="mail"
                            class="h-3.5 w-3.5 shrink-0"
                            aria-hidden="true">
                        </i>

                        Contact

                    </div>

                    <p
                        class="
                            mt-1 truncate
                            text-sm text-gray-700
                            dark:text-gray-300
                        ">
                        {{ $subscriber->email ?: 'No email recorded' }}
                    </p>

                </div>


                <div class="min-w-0">

                    <div
                        class="
                            flex items-center gap-1.5
                            text-xs text-gray-400
                        ">

                        <i
                            data-lucide="map-pin"
                            class="h-3.5 w-3.5 shrink-0"
                            aria-hidden="true">
                        </i>

                        Location

                    </div>

                    <p
                        class="
                            mt-1 truncate
                            text-sm text-gray-700
                            dark:text-gray-300
                        ">
                        {{ $location ?: 'Not specified' }}
                    </p>

                </div>

            </div>

        </article>

        @endforeach

    </section>

    @endif

</div>

@endsection
