@extends('layouts.app')

@section('title', 'Create Employee')

@section('page-title', 'Create Employee')

@section('content')

<div class="mx-auto max-w-4xl space-y-3">

    {{-- Creation result --}}
    @if (session('created_employee'))

        @php
            $createdEmployee = session('created_employee');
        @endphp

        <section
            class="
                border border-green-200
                bg-white
                dark:border-green-900
                dark:bg-neutral-900
            ">

            <div
                class="
                    flex items-start gap-3
                    border-b border-green-100
                    bg-green-50
                    px-4 py-3
                    dark:border-green-900
                    dark:bg-green-950/30
                ">

                <div
                    class="
                        flex h-9 w-9 shrink-0
                        items-center justify-center
                        bg-green-100
                        dark:bg-green-950
                    ">

                    <i
                        data-lucide="circle-check"
                        class="h-5 w-5 text-green-700 dark:text-green-300"
                        aria-hidden="true">
                    </i>

                </div>

                <div class="min-w-0">

                    <h2 class="text-sm font-semibold text-green-800 dark:text-green-200">
                        Employee account created successfully
                    </h2>

                    <p class="mt-1 text-xs leading-5 text-green-700 dark:text-green-300">
                        Give the employee their Employee Number and default password.
                        They must complete first-time SMS verification before normal system access is allowed.
                    </p>

                </div>

            </div>

            <div class="grid gap-px bg-gray-200 sm:grid-cols-2 dark:bg-neutral-800">

                <div class="bg-white p-4 dark:bg-neutral-900">

                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Employee
                    </p>

                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $createdEmployee['name'] }}
                    </p>

                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ ucfirst($createdEmployee['role']) }}
                    </p>

                </div>

                <div class="bg-white p-4 dark:bg-neutral-900">

                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Employee Number
                    </p>

                    <div class="mt-1 flex items-center gap-2">

                        <code
                            id="created-employee-number"
                            class="
                                text-base font-semibold
                                text-[#008080]
                                dark:text-[#5EEAD4]
                            ">
                            {{ $createdEmployee['employee_number'] }}
                        </code>

                        <button
                            type="button"
                            data-copy-value="{{ $createdEmployee['employee_number'] }}"
                            class="
                                inline-flex h-8 w-8
                                items-center justify-center
                                rounded-lg
                                text-gray-500
                                transition
                                hover:bg-gray-100
                                hover:text-[#008080]
                                dark:text-gray-400
                                dark:hover:bg-neutral-800
                                dark:hover:text-[#5EEAD4]
                            "
                            aria-label="Copy employee number">

                            <i
                                data-lucide="copy"
                                class="h-4 w-4"
                                aria-hidden="true">
                            </i>

                        </button>

                    </div>

                </div>

                @if (! empty($createdEmployee['technician_code']))

                    <div class="bg-white p-4 dark:bg-neutral-900">

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            Technician Code
                        </p>

                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $createdEmployee['technician_code'] }}
                        </p>

                    </div>

                @endif

                <div class="bg-white p-4 dark:bg-neutral-900">

                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Default Password
                    </p>

                    <div class="mt-1 flex items-center gap-2">

                        <code
                            class="
                                text-base font-semibold
                                text-gray-900
                                dark:text-white
                            ">
                            {{ $createdEmployee['default_password'] }}
                        </code>

                        <button
                            type="button"
                            data-copy-value="{{ $createdEmployee['default_password'] }}"
                            class="
                                inline-flex h-8 w-8
                                items-center justify-center
                                rounded-lg
                                text-gray-500
                                transition
                                hover:bg-gray-100
                                hover:text-[#008080]
                                dark:text-gray-400
                                dark:hover:bg-neutral-800
                                dark:hover:text-[#5EEAD4]
                            "
                            aria-label="Copy default password">

                            <i
                                data-lucide="copy"
                                class="h-4 w-4"
                                aria-hidden="true">
                            </i>

                        </button>

                    </div>

                </div>

            </div>

            <div
                class="
                    flex items-start gap-2
                    border-t border-amber-200
                    bg-amber-50
                    px-4 py-3
                    text-xs leading-5
                    text-amber-800
                    dark:border-amber-900
                    dark:bg-amber-950/30
                    dark:text-amber-300
                ">

                <i
                    data-lucide="triangle-alert"
                    class="mt-0.5 h-4 w-4 shrink-0"
                    aria-hidden="true">
                </i>

                <span>
                    Default password is <strong>12345678</strong>.
                    The employee must replace it during first-time activation.
                </span>

            </div>

        </section>

    @endif


    {{-- Page header --}}
    <div
        class="
            flex flex-col gap-3
            border border-gray-200
            bg-white px-4 py-4
            sm:flex-row sm:items-center sm:justify-between
            dark:border-neutral-800
            dark:bg-neutral-900
        ">

        <div class="min-w-0">

            <div class="flex items-center gap-2">

                <div
                    class="
                        flex h-9 w-9 shrink-0
                        items-center justify-center
                        bg-[#008080]/10
                        text-[#008080]
                        dark:bg-[#008080]/20
                        dark:text-[#5EEAD4]
                    ">

                    <i
                        data-lucide="user-plus"
                        class="h-5 w-5"
                        aria-hidden="true">
                    </i>

                </div>

                <div>

                    <h1 class="text-base font-semibold text-gray-900 dark:text-white">
                        Create Employee Account
                    </h1>

                    <p class="mt-0.5 text-xs leading-5 text-gray-500 dark:text-gray-400">
                        Create a Staff or Technician account for internal Rincomm access.
                    </p>

                </div>

            </div>

        </div>

        <a
            href="{{ route('admin.users.index') }}"
            class="
                inline-flex min-h-10 shrink-0
                items-center justify-center gap-2
                rounded-xl
                border border-gray-300
                px-3 py-2
                text-sm font-medium
                text-gray-700
                transition
                hover:bg-gray-50
                focus:outline-none
                focus:ring-2
                focus:ring-gray-400
                focus:ring-offset-2
                dark:border-neutral-700
                dark:text-gray-200
                dark:hover:bg-neutral-800
                dark:focus:ring-offset-neutral-900
            ">

            <i
                data-lucide="arrow-left"
                class="h-4 w-4"
                aria-hidden="true">
            </i>

            Back to Users

        </a>

    </div>


    {{-- Employee form --}}
    <form
        method="POST"
        action="{{ route('admin.users.store') }}"
        data-lock-submit
        class="
            border border-gray-200
            bg-white
            dark:border-neutral-800
            dark:bg-neutral-900
        ">

        @csrf

        <div
            class="
                border-b border-gray-200
                px-4 py-3
                dark:border-neutral-800
            ">

            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
                Employee Information
            </h2>

            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Employee Number and default password are generated automatically.
            </p>

        </div>


        <div class="grid gap-5 p-4 sm:grid-cols-2">

            {{-- Full name --}}
            <div class="sm:col-span-2">

                <label
                    for="name"
                    class="block text-sm font-medium text-gray-800 dark:text-gray-200">
                    Full Name
                    <span class="text-red-600 dark:text-red-400">*</span>
                </label>

                <input
                    id="name"
                    name="name"
                    type="text"
                    maxlength="255"
                    required
                    autofocus
                    value="{{ old('name') }}"
                    placeholder="Enter employee full name"
                    class="
                        mt-1.5 block min-h-11 w-full
                        border
                        {{ $errors->has('name')
                            ? 'border-red-500 ring-2 ring-red-500/20'
                            : 'border-gray-300 focus:border-[#008080] focus:ring-2 focus:ring-[#008080]/20' }}
                        bg-white
                        px-3 py-2.5
                        text-sm text-gray-900
                        outline-none transition
                        placeholder:text-gray-400
                        dark:bg-neutral-950
                        dark:text-gray-100
                        dark:placeholder:text-gray-500
                        {{ $errors->has('name') ? 'dark:border-red-500' : 'dark:border-neutral-700' }}
                    ">

                <x-field-error :message="$errors->first('name')" />

            </div>


            {{-- Email --}}
            <div>

                <label
                    for="email"
                    class="block text-sm font-medium text-gray-800 dark:text-gray-200">
                    Email Address
                    <span class="text-red-600 dark:text-red-400">*</span>
                </label>

                <input
                    id="email"
                    name="email"
                    type="email"
                    maxlength="255"
                    required
                    value="{{ old('email') }}"
                    placeholder="employee@rincomm.com"
                    autocomplete="off"
                    class="
                        mt-1.5 block min-h-11 w-full
                        border
                        {{ $errors->has('email')
                            ? 'border-red-500 ring-2 ring-red-500/20'
                            : 'border-gray-300 focus:border-[#008080] focus:ring-2 focus:ring-[#008080]/20' }}
                        bg-white
                        px-3 py-2.5
                        text-sm text-gray-900
                        outline-none transition
                        placeholder:text-gray-400
                        dark:bg-neutral-950
                        dark:text-gray-100
                        dark:placeholder:text-gray-500
                        {{ $errors->has('email') ? 'dark:border-red-500' : 'dark:border-neutral-700' }}
                    ">

                <x-field-error :message="$errors->first('email')" />

            </div>


            {{-- Mobile --}}
            <div>

                <label
                    for="phone"
                    class="block text-sm font-medium text-gray-800 dark:text-gray-200">
                    Mobile Number
                    <span class="text-red-600 dark:text-red-400">*</span>
                </label>

                <input
                    id="phone"
                    name="phone"
                    type="tel"
                    maxlength="20"
                    required
                    value="{{ old('phone') }}"
                    placeholder="09XXXXXXXXX"
                    autocomplete="off"
                    class="
                        mt-1.5 block min-h-11 w-full
                        border
                        {{ $errors->has('phone')
                            ? 'border-red-500 ring-2 ring-red-500/20'
                            : 'border-gray-300 focus:border-[#008080] focus:ring-2 focus:ring-[#008080]/20' }}
                        bg-white
                        px-3 py-2.5
                        text-sm text-gray-900
                        outline-none transition
                        placeholder:text-gray-400
                        dark:bg-neutral-950
                        dark:text-gray-100
                        dark:placeholder:text-gray-500
                        {{ $errors->has('phone') ? 'dark:border-red-500' : 'dark:border-neutral-700' }}
                    ">

                <p class="mt-1.5 text-xs leading-5 text-gray-500 dark:text-gray-400">
                    Used for first-time SMS OTP verification and account recovery.
                </p>

                <x-field-error :message="$errors->first('phone')" />

            </div>


            {{-- Role --}}
            <div>

                <label
                    for="role"
                    class="block text-sm font-medium text-gray-800 dark:text-gray-200">
                    Employee Role
                    <span class="text-red-600 dark:text-red-400">*</span>
                </label>

                <select
                    id="role"
                    name="role"
                    required
                    data-employee-role
                    class="
                        mt-1.5 block min-h-11 w-full
                        border
                        {{ $errors->has('role')
                            ? 'border-red-500 ring-2 ring-red-500/20'
                            : 'border-gray-300 focus:border-[#008080] focus:ring-2 focus:ring-[#008080]/20' }}
                        bg-white
                        px-3 py-2.5
                        text-sm text-gray-900
                        outline-none transition
                        dark:bg-neutral-950
                        dark:text-gray-100
                        {{ $errors->has('role') ? 'dark:border-red-500' : 'dark:border-neutral-700' }}
                    ">

                    <option value="">
                        Select employee role
                    </option>

                    <option value="staff" @selected(old('role') === 'staff')>
                        Staff
                    </option>

                    <option value="technician" @selected(old('role') === 'technician')>
                        Technician
                    </option>

                </select>

                <x-field-error :message="$errors->first('role')" />

            </div>


            {{-- Technician specialization --}}
            <div
                data-technician-specialization
                class="{{ old('role') === 'technician' ? '' : 'hidden' }}">

                <label
                    for="specialization"
                    class="block text-sm font-medium text-gray-800 dark:text-gray-200">
                    Specialization
                </label>

                <input
                    id="specialization"
                    name="specialization"
                    type="text"
                    maxlength="120"
                    value="{{ old('specialization') }}"
                    placeholder="e.g. Fiber Installation and Repair"
                    class="
                        mt-1.5 block min-h-11 w-full
                        border
                        {{ $errors->has('specialization')
                            ? 'border-red-500 ring-2 ring-red-500/20'
                            : 'border-gray-300 focus:border-[#008080] focus:ring-2 focus:ring-[#008080]/20' }}
                        bg-white
                        px-3 py-2.5
                        text-sm text-gray-900
                        outline-none transition
                        placeholder:text-gray-400
                        dark:bg-neutral-950
                        dark:text-gray-100
                        dark:placeholder:text-gray-500
                        {{ $errors->has('specialization') ? 'dark:border-red-500' : 'dark:border-neutral-700' }}
                    ">

                <x-field-error :message="$errors->first('specialization')" />

            </div>

        </div>


        {{-- Account defaults --}}
        <div
            class="
                mx-4 mb-4
                border border-gray-200
                bg-gray-50
                p-3
                dark:border-neutral-800
                dark:bg-neutral-950
            ">

            <div class="flex items-start gap-3">

                <i
                    data-lucide="shield-check"
                    class="mt-0.5 h-5 w-5 shrink-0 text-[#008080]"
                    aria-hidden="true">
                </i>

                <div>

                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                        First-time account activation
                    </p>

                    <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">
                        The system generates the Employee Number automatically.
                        The default password is <strong class="text-gray-700 dark:text-gray-200">12345678</strong>.
                        The account remains Pending Verification until the employee completes SMS OTP verification
                        and sets a new password.
                    </p>

                </div>

            </div>

        </div>


        {{-- Actions --}}
        <div
            class="
                flex flex-col-reverse gap-2
                border-t border-gray-200
                px-4 py-3
                sm:flex-row sm:justify-end
                dark:border-neutral-800
            ">

            <a
                href="{{ route('admin.users.index') }}"
                class="
                    inline-flex min-h-11
                    items-center justify-center
                    rounded-xl
                    border border-gray-300
                    px-4 py-2.5
                    text-sm font-medium
                    text-gray-700
                    transition
                    hover:bg-gray-50
                    focus:outline-none
                    focus:ring-2
                    focus:ring-gray-400
                    focus:ring-offset-2
                    dark:border-neutral-700
                    dark:text-gray-200
                    dark:hover:bg-neutral-800
                    dark:focus:ring-offset-neutral-900
                ">
                Cancel
            </a>

            <button
                type="submit"
                data-loading-text="Creating Employee..."
                class="
                    inline-flex min-h-11
                    items-center justify-center gap-2
                    rounded-xl
                    bg-[#008080]
                    px-4 py-2.5
                    text-sm font-semibold
                    text-white
                    transition
                    hover:bg-[#006666]
                    focus:outline-none
                    focus:ring-2
                    focus:ring-[#008080]
                    focus:ring-offset-2
                    dark:focus:ring-offset-neutral-900
                ">

                <i
                    data-lucide="user-plus"
                    class="h-4 w-4"
                    aria-hidden="true">
                </i>

                Create Employee

            </button>

        </div>

    </form>

</div>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        const roleSelect =
            document.querySelector(
                '[data-employee-role]'
            );

        const specialization =
            document.querySelector(
                '[data-technician-specialization]'
            );

        function syncSpecialization() {
            if (!roleSelect || !specialization) {
                return;
            }

            specialization.classList.toggle(
                'hidden',
                roleSelect.value !== 'technician'
            );
        }

        if (roleSelect) {
            roleSelect.addEventListener(
                'change',
                syncSpecialization
            );

            syncSpecialization();
        }

        document
            .querySelectorAll('[data-copy-value]')
            .forEach(function (button) {
                button.addEventListener(
                    'click',
                    async function () {
                        const value =
                            button.getAttribute(
                                'data-copy-value'
                            );

                        if (!value) {
                            return;
                        }

                        try {
                            await navigator.clipboard.writeText(
                                value
                            );

                            const icon =
                                button.querySelector(
                                    '[data-lucide]'
                                );

                            if (icon) {
                                icon.setAttribute(
                                    'data-lucide',
                                    'check'
                                );

                                if (window.lucide) {
                                    window.lucide.createIcons();
                                }

                                window.setTimeout(
                                    function () {
                                        icon.setAttribute(
                                            'data-lucide',
                                            'copy'
                                        );

                                        if (window.lucide) {
                                            window.lucide.createIcons();
                                        }
                                    },
                                    1200
                                );
                            }
                        } catch (error) {
                            // Clipboard availability varies by browser/context.
                        }
                    }
                );
            });
    });
</script>

@endsection