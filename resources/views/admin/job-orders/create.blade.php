@extends('layouts.app')

@section('title', 'Create Job Order')
@section('page-title', 'Create Job Order')

@section('content')

<div class="mx-auto max-w-4xl space-y-4">

    <section class="flex items-start gap-3">

        <a
            href="{{ route('admin.job-orders.index') }}"
            class="
                inline-flex h-9 w-9 shrink-0 items-center justify-center
                border border-gray-300 text-gray-600
                hover:border-[#008080]
                hover:text-[#008080]
                dark:border-neutral-700 dark:text-gray-300
            "
            aria-label="Back to Job Orders">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
        </a>

        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white sm:text-2xl">
                Create Job Order
            </h1>

            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                Record the field work requirement before technician scheduling and assignment.
            </p>
        </div>

    </section>


    @if ($errors->any())

    <section
        class="
            border border-red-200 bg-red-50
            px-4 py-3 text-sm text-red-700
            dark:border-red-900
            dark:bg-red-950/40
            dark:text-red-300
        ">

        <div class="flex items-start gap-2">
            <i data-lucide="circle-alert" class="mt-0.5 h-4 w-4 shrink-0"></i>

            <div>
                <p class="font-semibold">
                    Please correct the highlighted fields.
                </p>

                <ul class="mt-1 list-disc space-y-0.5 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

    </section>

    @endif


    <form
        method="POST"
        action="{{ route('admin.job-orders.store') }}"
        data-lock-submit
        class="
            border border-gray-200 bg-white
            dark:border-neutral-800 dark:bg-neutral-900
        ">

        @csrf

        <div class="border-b border-gray-100 px-4 py-3 dark:border-neutral-800">

            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
                Work Order Information
            </h2>

            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                Assignment and scheduling are handled in the next field operations step.
            </p>

        </div>


        <div class="grid gap-5 p-4 sm:p-5">

            <div>

                <label
                    for="customer_id"
                    class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">
                    Subscriber
                    <span class="text-red-500">*</span>
                </label>

                <select
                    id="customer_id"
                    name="customer_id"
                    required
                    class="
                        min-h-10 w-full
                        border bg-white px-3 py-2 text-sm
                        text-gray-900 outline-none
                        focus:border-[#008080]
                        focus:ring-2 focus:ring-[#008080]/20
                        dark:bg-neutral-950 dark:text-white
                        {{ $errors->has('customer_id')
                            ? 'border-red-400 dark:border-red-700'
                            : 'border-gray-300 dark:border-neutral-700' }}
                    ">

                    <option value="">Select subscriber</option>

                    @foreach ($customers as $customer)

                    @php
                    $fullName = trim(implode(' ', array_filter([
                        $customer->first_name,
                        $customer->middle_name,
                        $customer->last_name,
                    ])));
                    @endphp

                    <option
                        value="{{ $customer->id }}"
                        @selected((string) old('customer_id') === (string) $customer->id)>
                        {{ $customer->customer_code }} — {{ $fullName }}
                    </option>

                    @endforeach

                </select>

                @error('customer_id')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

            </div>


            <div>

                <label
                    for="service_request_id"
                    class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">
                    Related Service Request
                    <span class="font-normal text-gray-400">(optional)</span>
                </label>

                <select
                    id="service_request_id"
                    name="service_request_id"
                    class="
                        min-h-10 w-full
                        border bg-white px-3 py-2 text-sm
                        text-gray-900 outline-none
                        focus:border-[#008080]
                        focus:ring-2 focus:ring-[#008080]/20
                        dark:bg-neutral-950 dark:text-white
                        {{ $errors->has('service_request_id')
                            ? 'border-red-400 dark:border-red-700'
                            : 'border-gray-300 dark:border-neutral-700' }}
                    ">

                    <option value="">
                        No related Service Request
                    </option>

                    @foreach ($serviceRequests as $serviceRequest)

                    <option
                        value="{{ $serviceRequest->id }}"
                        data-customer-id="{{ $serviceRequest->customer_id }}"
                        @selected((string) old('service_request_id') === (string) $serviceRequest->id)>
                        {{ $serviceRequest->ticket_number }}
                        — {{ \Illuminate\Support\Str::headline($serviceRequest->request_type) }}
                        — {{ \Illuminate\Support\Str::headline($serviceRequest->status) }}
                    </option>

                    @endforeach

                </select>

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Only requests belonging to the selected subscriber can be linked.
                </p>

                @error('service_request_id')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

            </div>


            <div>

                <label
                    for="job_type"
                    class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">
                    Job Type
                    <span class="text-red-500">*</span>
                </label>

                <select
                    id="job_type"
                    name="job_type"
                    required
                    class="
                        min-h-10 w-full
                        border bg-white px-3 py-2 text-sm
                        text-gray-900 outline-none
                        focus:border-[#008080]
                        focus:ring-2 focus:ring-[#008080]/20
                        dark:bg-neutral-950 dark:text-white
                        {{ $errors->has('job_type')
                            ? 'border-red-400 dark:border-red-700'
                            : 'border-gray-300 dark:border-neutral-700' }}
                    ">

                    <option value="">Select Job Order type</option>

                    @foreach ($jobTypes as $value => $label)
                        <option
                            value="{{ $value }}"
                            @selected(old('job_type') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach

                </select>

                @error('job_type')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

            </div>


            <div>

                <label
                    for="description"
                    class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200">
                    Work Description / Instructions
                    <span class="text-red-500">*</span>
                </label>

                <textarea
                    id="description"
                    name="description"
                    rows="7"
                    maxlength="5000"
                    required
                    placeholder="Describe the work to be performed, relevant issue, site details, or technician instructions."
                    class="
                        w-full resize-y
                        border bg-white px-3 py-2.5 text-sm
                        text-gray-900 outline-none
                        focus:border-[#008080]
                        focus:ring-2 focus:ring-[#008080]/20
                        dark:bg-neutral-950 dark:text-white
                        {{ $errors->has('description')
                            ? 'border-red-400 dark:border-red-700'
                            : 'border-gray-300 dark:border-neutral-700' }}
                    ">{{ old('description') }}</textarea>

                @error('description')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

            </div>


            <div
                class="
                    border border-[#008080]/15
                    bg-[#008080]/5
                    px-4 py-3
                    dark:border-[#14B8A6]/20
                    dark:bg-[#008080]/10
                ">

                <div class="flex items-start gap-2">

                    <i
                        data-lucide="info"
                        class="mt-0.5 h-4 w-4 shrink-0 text-[#008080] dark:text-[#5EEAD4]">
                    </i>

                    <p class="text-xs leading-5 text-gray-600 dark:text-gray-300">
                        The Job Order will be created as
                        <strong>Pending</strong>.
                        Technician assignment and schedule will remain empty until the
                        Technician Team Scheduling / Assignment workflow.
                    </p>

                </div>

            </div>

        </div>


        <footer
            class="
                flex flex-col-reverse gap-2
                border-t border-gray-100
                px-4 py-3
                sm:flex-row sm:justify-end
                dark:border-neutral-800
            ">

            <a
                href="{{ route('admin.job-orders.index') }}"
                class="
                    inline-flex min-h-10 items-center justify-center
                    border border-gray-300 px-4 py-2
                    text-sm font-semibold text-gray-700
                    hover:bg-gray-50
                    dark:border-neutral-700
                    dark:text-gray-300
                    dark:hover:bg-neutral-800
                ">
                Cancel
            </a>

            <button
                type="submit"
                class="
                    inline-flex min-h-10 items-center justify-center gap-2
                    bg-[#008080] px-4 py-2
                    text-sm font-semibold text-white
                    transition hover:bg-[#006666]
                    disabled:cursor-not-allowed
                    disabled:opacity-60
                ">
                <i data-lucide="clipboard-plus" class="h-4 w-4"></i>
                Create Job Order
            </button>

        </footer>

    </form>

</div>


<script>
document.addEventListener('DOMContentLoaded', () => {
    const customerSelect = document.getElementById('customer_id');
    const serviceRequestSelect = document.getElementById('service_request_id');

    if (!customerSelect || !serviceRequestSelect) {
        return;
    }

    const options = [
        ...serviceRequestSelect.querySelectorAll('option[data-customer-id]')
    ];

    const filterServiceRequests = () => {
        const customerId = customerSelect.value;
        const selectedValue = serviceRequestSelect.value;

        options.forEach((option) => {
            const matches =
                customerId !== '' &&
                option.dataset.customerId === customerId;

            option.hidden = !matches;
            option.disabled = !matches;
        });

        const selectedOption = serviceRequestSelect
            .querySelector(`option[value="${selectedValue}"]`);

        if (
            selectedValue !== '' &&
            (
                !selectedOption ||
                selectedOption.disabled
            )
        ) {
            serviceRequestSelect.value = '';
        }
    };

    customerSelect.addEventListener(
        'change',
        filterServiceRequests
    );

    filterServiceRequests();
});
</script>

@endsection
