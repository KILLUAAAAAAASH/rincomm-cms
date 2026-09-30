@extends('layouts.app')

@section('title', 'Edit Subscriber')

@section('page-title', 'Edit Subscriber')


@section('secondary-navigation')

<a
    href="{{ route('admin.subscribers.show', $subscriber) }}"
    class="
        inline-flex min-h-10
        items-center gap-2
        text-sm font-medium
        text-[#008080]
        transition
        hover:text-[#006666]
        focus:outline-none
        focus:ring-2
        focus:ring-[#008080]/20
    ">

    <i
        data-lucide="arrow-left"
        class="h-4 w-4"
        aria-hidden="true">
    </i>

    Subscriber Details

</a>

@endsection


@section('content')

@php
$fullName = trim(
    implode(' ', array_filter([
        $subscriber->first_name,
        $subscriber->middle_name,
        $subscriber->last_name,
    ]))
);

$statusMeta = match ($subscriber->status) {
    'active' => [
        'label' => 'Active',
        'class' =>
            'border-green-200 bg-green-50 text-green-700
             dark:border-green-900 dark:bg-green-950/30 dark:text-green-300',
    ],

    'inactive' => [
        'label' => 'Inactive',
        'class' =>
            'border-gray-200 bg-gray-50 text-gray-700
             dark:border-neutral-700 dark:bg-neutral-800 dark:text-gray-300',
    ],

    'suspended' => [
        'label' => 'Suspended',
        'class' =>
            'border-red-200 bg-red-50 text-red-700
             dark:border-red-900 dark:bg-red-950/30 dark:text-red-300',
    ],

    'disconnected' => [
        'label' => 'Disconnected',
        'class' =>
            'border-red-200 bg-red-50 text-red-700
             dark:border-red-900 dark:bg-red-950/30 dark:text-red-300',
    ],

    default => [
        'label' => ucfirst($subscriber->status),
        'class' =>
            'border-amber-200 bg-amber-50 text-amber-700
             dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300',
    ],
};
@endphp


<div class="mx-auto max-w-6xl space-y-2">

    {{-- Validation feedback --}}
    @if ($errors->any())

    <div
        role="alert"
        class="
            flex items-start gap-2.5
            border border-red-200
            bg-red-50
            px-3 py-2.5
            text-sm text-red-800
            dark:border-red-900
            dark:bg-red-950/30
            dark:text-red-200
        ">

        <i
            data-lucide="triangle-alert"
            class="mt-0.5 h-4 w-4 shrink-0"
            aria-hidden="true">
        </i>


        <div>

            <p class="font-medium">
                Please correct the highlighted fields.
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


    <form
        method="POST"
        action="{{ route(
            'admin.subscribers.update',
            $subscriber
        ) }}"
        data-lock-submit
        class="
            border border-gray-200
            bg-white
            dark:border-neutral-800
            dark:bg-neutral-900
        ">

        @csrf
        @method('PATCH')


        {{-- Subscriber identity --}}
        <header
            class="
                flex flex-col gap-3
                border-b border-gray-200
                px-4 py-3
                dark:border-neutral-800
                sm:flex-row
                sm:items-center
                sm:justify-between
            ">

            <div>

                <h1
                    class="
                        text-lg font-semibold
                        text-gray-900
                        dark:text-white
                    ">
                    {{ $fullName }}
                </h1>

                <p
                    class="
                        mt-0.5
                        text-xs font-semibold
                        tracking-wide
                        text-[#008080]
                        dark:text-[#5EEAD4]
                    ">
                    {{ $subscriber->customer_code }}
                </p>

            </div>


            <span
                class="
                    inline-flex w-fit
                    items-center
                    border px-2 py-0.5
                    text-xs font-medium
                    {{ $statusMeta['class'] }}
                ">
                {{ $statusMeta['label'] }}
            </span>

        </header>


        {{-- Scope notice --}}
        <div
            class="
                flex items-start gap-2
                border-b border-gray-200
                bg-gray-50
                px-4 py-2.5
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
                Edit profile and billing information here.
                Portal email, installation address, plan, and subscriber status use their dedicated workflows.
            </p>

        </div>


        <div
            class="
                divide-y divide-gray-200
                dark:divide-neutral-800
            ">

            {{-- Personal information --}}
            <section class="p-4">

                <h2
                    class="
                        text-sm font-semibold
                        text-gray-900
                        dark:text-white
                    ">
                    Personal Information
                </h2>


                <div
                    class="
                        mt-3 grid
                        gap-x-3 gap-y-3
                        md:grid-cols-6
                    ">

                    {{-- First name --}}
                    <div class="md:col-span-2">

                        <label
                            for="first_name"
                            class="
                                text-xs font-medium
                                text-gray-700
                                dark:text-gray-300
                            ">
                            First Name
                            <span class="text-red-600">*</span>
                        </label>


                        <input
                            id="first_name"
                            name="first_name"
                            type="text"
                            value="{{ old(
                                'first_name',
                                $subscriber->first_name
                            ) }}"
                            maxlength="255"
                            required
                            autocomplete="given-name"
                            class="
                                mt-1.5 block
                                min-h-10 w-full
                                border bg-white
                                px-3 py-2
                                text-sm text-gray-900
                                outline-none transition
                                dark:bg-neutral-950
                                dark:text-white

                                @error('first_name')
                                    border-red-500
                                    focus:border-red-500
                                    focus:ring-2
                                    focus:ring-red-200
                                @else
                                    border-gray-300
                                    focus:border-[#008080]
                                    focus:ring-2
                                    focus:ring-[#008080]/20
                                    dark:border-neutral-700
                                @enderror
                            ">


                        @error('first_name')

                        <p
                            class="
                                mt-1
                                text-xs text-red-600
                                dark:text-red-400
                            ">
                            {{ $message }}
                        </p>

                        @enderror

                    </div>


                    {{-- Middle name --}}
                    <div class="md:col-span-2">

                        <label
                            for="middle_name"
                            class="
                                text-xs font-medium
                                text-gray-700
                                dark:text-gray-300
                            ">
                            Middle Name
                        </label>


                        <input
                            id="middle_name"
                            name="middle_name"
                            type="text"
                            value="{{ old(
                                'middle_name',
                                $subscriber->middle_name
                            ) }}"
                            maxlength="255"
                            autocomplete="additional-name"
                            class="
                                mt-1.5 block
                                min-h-10 w-full
                                border border-gray-300
                                bg-white
                                px-3 py-2
                                text-sm text-gray-900
                                outline-none transition
                                focus:border-[#008080]
                                focus:ring-2
                                focus:ring-[#008080]/20
                                dark:border-neutral-700
                                dark:bg-neutral-950
                                dark:text-white
                            ">

                    </div>


                    {{-- Last name --}}
                    <div class="md:col-span-2">

                        <label
                            for="last_name"
                            class="
                                text-xs font-medium
                                text-gray-700
                                dark:text-gray-300
                            ">
                            Last Name
                            <span class="text-red-600">*</span>
                        </label>


                        <input
                            id="last_name"
                            name="last_name"
                            type="text"
                            value="{{ old(
                                'last_name',
                                $subscriber->last_name
                            ) }}"
                            maxlength="255"
                            required
                            autocomplete="family-name"
                            class="
                                mt-1.5 block
                                min-h-10 w-full
                                border bg-white
                                px-3 py-2
                                text-sm text-gray-900
                                outline-none transition
                                dark:bg-neutral-950
                                dark:text-white

                                @error('last_name')
                                    border-red-500
                                    focus:border-red-500
                                    focus:ring-2
                                    focus:ring-red-200
                                @else
                                    border-gray-300
                                    focus:border-[#008080]
                                    focus:ring-2
                                    focus:ring-[#008080]/20
                                    dark:border-neutral-700
                                @enderror
                            ">


                        @error('last_name')

                        <p
                            class="
                                mt-1
                                text-xs text-red-600
                                dark:text-red-400
                            ">
                            {{ $message }}
                        </p>

                        @enderror

                    </div>


                    {{-- Phone --}}
                    <div class="md:col-span-3">

                        <label
                            for="phone"
                            class="
                                text-xs font-medium
                                text-gray-700
                                dark:text-gray-300
                            ">
                            Phone
                        </label>


                        <input
                            id="phone"
                            name="phone"
                            type="text"
                            value="{{ old(
                                'phone',
                                $subscriber->phone
                            ) }}"
                            maxlength="255"
                            autocomplete="tel"
                            placeholder="Enter subscriber phone number"
                            class="
                                mt-1.5 block
                                min-h-10 w-full
                                border border-gray-300
                                bg-white
                                px-3 py-2
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


                    {{-- Email read-only --}}
                    <div class="md:col-span-3">

                        <label
                            for="subscriber_email"
                            class="
                                text-xs font-medium
                                text-gray-700
                                dark:text-gray-300
                            ">
                            Email
                        </label>


                        <div class="relative mt-1.5">

                            <input
                                id="subscriber_email"
                                type="email"
                                value="{{ $subscriber->email
                                    ?: $subscriber->user?->email }}"
                                disabled
                                class="
                                    block min-h-10 w-full
                                    cursor-not-allowed
                                    border border-gray-200
                                    bg-gray-100
                                    px-3 py-2 pr-24
                                    text-sm text-gray-500
                                    dark:border-neutral-800
                                    dark:bg-neutral-800
                                    dark:text-gray-400
                                ">


                            <span
                                class="
                                    pointer-events-none
                                    absolute inset-y-0 right-3
                                    flex items-center
                                    text-[10px] font-semibold
                                    uppercase tracking-wide
                                    text-gray-400
                                ">
                                Read only
                            </span>

                        </div>

                    </div>

                </div>

            </section>


            {{-- Address information --}}
            <section class="p-4">

                <h2
                    class="
                        text-sm font-semibold
                        text-gray-900
                        dark:text-white
                    ">
                    Address Information
                </h2>


                <div
                    class="
                        mt-3 grid
                        gap-x-3 gap-y-3
                        md:grid-cols-6
                    ">

                    {{-- Address --}}
                    <div class="md:col-span-6">

                        <label
                            for="address"
                            class="
                                text-xs font-medium
                                text-gray-700
                                dark:text-gray-300
                            ">
                            Address
                            <span class="text-red-600">*</span>
                        </label>


                        <input
                            id="address"
                            name="address"
                            type="text"
                            value="{{ old(
                                'address',
                                $subscriber->address
                            ) }}"
                            maxlength="255"
                            required
                            autocomplete="street-address"
                            placeholder="Enter subscriber address"
                            class="
                                mt-1.5 block
                                min-h-10 w-full
                                border bg-white
                                px-3 py-2
                                text-sm text-gray-900
                                outline-none transition
                                placeholder:text-gray-400
                                dark:bg-neutral-950
                                dark:text-white
                                dark:placeholder:text-gray-500

                                @error('address')
                                    border-red-500
                                    focus:border-red-500
                                    focus:ring-2
                                    focus:ring-red-200
                                @else
                                    border-gray-300
                                    focus:border-[#008080]
                                    focus:ring-2
                                    focus:ring-[#008080]/20
                                    dark:border-neutral-700
                                @enderror
                            ">


                        @error('address')

                        <p
                            class="
                                mt-1
                                text-xs text-red-600
                                dark:text-red-400
                            ">
                            {{ $message }}
                        </p>

                        @enderror

                    </div>


                    {{-- City --}}
                    <div class="md:col-span-2">

                        <label
                            for="city"
                            class="
                                text-xs font-medium
                                text-gray-700
                                dark:text-gray-300
                            ">
                            City / Municipality
                        </label>


                        <input
                            id="city"
                            name="city"
                            type="text"
                            value="{{ old(
                                'city',
                                $subscriber->city
                            ) }}"
                            maxlength="255"
                            autocomplete="address-level2"
                            class="
                                mt-1.5 block
                                min-h-10 w-full
                                border border-gray-300
                                bg-white
                                px-3 py-2
                                text-sm text-gray-900
                                outline-none transition
                                focus:border-[#008080]
                                focus:ring-2
                                focus:ring-[#008080]/20
                                dark:border-neutral-700
                                dark:bg-neutral-950
                                dark:text-white
                            ">

                    </div>


                    {{-- Province --}}
                    <div class="md:col-span-2">

                        <label
                            for="province"
                            class="
                                text-xs font-medium
                                text-gray-700
                                dark:text-gray-300
                            ">
                            Province
                        </label>


                        <input
                            id="province"
                            name="province"
                            type="text"
                            value="{{ old(
                                'province',
                                $subscriber->province
                            ) }}"
                            maxlength="255"
                            autocomplete="address-level1"
                            class="
                                mt-1.5 block
                                min-h-10 w-full
                                border border-gray-300
                                bg-white
                                px-3 py-2
                                text-sm text-gray-900
                                outline-none transition
                                focus:border-[#008080]
                                focus:ring-2
                                focus:ring-[#008080]/20
                                dark:border-neutral-700
                                dark:bg-neutral-950
                                dark:text-white
                            ">

                    </div>


                    {{-- Postal code --}}
                    <div class="md:col-span-2">

                        <label
                            for="postal_code"
                            class="
                                text-xs font-medium
                                text-gray-700
                                dark:text-gray-300
                            ">
                            Postal Code
                        </label>


                        <input
                            id="postal_code"
                            name="postal_code"
                            type="text"
                            value="{{ old(
                                'postal_code',
                                $subscriber->postal_code
                            ) }}"
                            maxlength="255"
                            autocomplete="postal-code"
                            class="
                                mt-1.5 block
                                min-h-10 w-full
                                border border-gray-300
                                bg-white
                                px-3 py-2
                                text-sm text-gray-900
                                outline-none transition
                                focus:border-[#008080]
                                focus:ring-2
                                focus:ring-[#008080]/20
                                dark:border-neutral-700
                                dark:bg-neutral-950
                                dark:text-white
                            ">

                    </div>


                    {{-- Billing address --}}
                    <div class="md:col-span-6">

                        <label
                            for="billing_address"
                            class="
                                text-xs font-medium
                                text-gray-700
                                dark:text-gray-300
                            ">
                            Billing Address
                        </label>


                        <textarea
                            id="billing_address"
                            name="billing_address"
                            rows="2"
                            maxlength="255"
                            placeholder="Enter billing address"
                            class="
                                mt-1.5 block w-full
                                resize-none
                                border border-gray-300
                                bg-white
                                px-3 py-2
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
                            ">{{ old(
                                'billing_address',
                                $subscriber->billing_address
                            ) }}</textarea>

                    </div>

                </div>

            </section>


            {{-- Protected service information --}}
            <section class="p-4">

                <div
                    class="
                        flex flex-col gap-2
                        sm:flex-row
                        sm:items-start
                        sm:justify-between
                    ">

                    <div>

                        <h2
                            class="
                                text-sm font-semibold
                                text-gray-900
                                dark:text-white
                            ">
                            Service Location
                        </h2>

                        <p
                            class="
                                mt-0.5
                                text-xs text-gray-500
                                dark:text-gray-400
                            ">
                            Installation address is managed through Relocation.
                        </p>

                    </div>


                    <div
                        class="
                            min-w-0
                            border border-gray-200
                            bg-gray-50
                            px-3 py-2
                            dark:border-neutral-800
                            dark:bg-neutral-950
                            sm:max-w-xl
                            sm:flex-1
                        ">

                        <p
                            class="
                                text-[10px] font-semibold
                                uppercase tracking-[0.08em]
                                text-gray-500
                                dark:text-gray-400
                            ">
                            Current Installation Address
                        </p>

                        <p
                            class="
                                mt-1
                                text-sm text-gray-800
                                dark:text-gray-200
                            ">
                            {{ $subscriber->installation_address
                                ?: $subscriber->address
                                ?: 'Not recorded' }}
                        </p>

                    </div>

                </div>

            </section>

        </div>


        {{-- Actions --}}
        <footer
            class="
                flex flex-col-reverse gap-2
                border-t border-gray-200
                bg-gray-50
                px-4 py-3
                dark:border-neutral-800
                dark:bg-neutral-950
                sm:flex-row
                sm:justify-end
            ">

            <a
                href="{{ route(
                    'admin.subscribers.show',
                    $subscriber
                ) }}"
                class="
                    inline-flex min-h-10
                    items-center justify-center
                    border border-gray-300
                    px-4 py-2
                    text-sm font-medium
                    text-gray-700
                    transition
                    hover:bg-white
                    focus:outline-none
                    focus:ring-2
                    focus:ring-gray-300
                    dark:border-neutral-700
                    dark:text-gray-300
                    dark:hover:bg-neutral-800
                ">
                Cancel
            </a>


            <button
                type="submit"
                data-loading-text="Saving..."
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
                    focus:ring-[#008080]/30
                ">

                <i
                    data-lucide="save"
                    class="h-4 w-4"
                    aria-hidden="true">
                </i>

                Save Changes

            </button>

        </footer>

    </form>

</div>

@endsection
