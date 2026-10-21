@extends('layouts.app')

@section('title', 'Create Support Ticket - Rincomm CMS')
@section('page-title', 'Create Ticket')

@section('secondary-navigation')
<nav class="flex items-center gap-2 py-2 text-xs sm:text-sm">
    <a href="{{ route('admin.tickets.index') }}"
       class="px-3 py-1.5 text-neutral-600 hover:text-[#008080] dark:text-neutral-300">
        Support Tickets
    </a>
    <span class="px-3 py-1.5 font-semibold text-[#008080] dark:text-[#5EEAD4]">
        New Ticket
    </span>
</nav>
@endsection

@section('content')
<div class="mx-auto w-full max-w-6xl space-y-3">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-neutral-900 dark:text-white">
                Create Support Ticket
            </h1>
            <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                Record a technical issue or account concern for a subscriber.
            </p>
        </div>

        <a href="{{ route('admin.tickets.index') }}"
           class="text-sm font-semibold text-[#008080] dark:text-[#5EEAD4]">
            Back to Tickets
        </a>
    </div>

    @if ($errors->any())
        <div role="alert"
             class="border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200">
            <p class="font-semibold">Please correct the following:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ route('admin.tickets.store') }}"
          data-lock-submit
          class="border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        @csrf

        <div class="grid gap-x-4 gap-y-3 p-4 md:grid-cols-2 lg:grid-cols-3">

            <div class="min-w-0">
                <label for="customer_id" class="mb-1 block text-xs font-semibold">
                    Subscriber <span class="text-red-600">*</span>
                </label>
                <select id="customer_id" name="customer_id" required
                        class="min-h-10 w-full border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-950">
                    <option value="">Select a subscriber</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}"
                                @selected((string) old('customer_id') === (string) $customer->id)>
                            {{ $customer->customer_code }}
                            - {{ trim($customer->first_name . ' ' . ($customer->middle_name ?? '') . ' ' . $customer->last_name) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-0">
                <label for="request_type" class="mb-1 block text-xs font-semibold">
                    Category <span class="text-red-600">*</span>
                </label>
                <select id="request_type" name="request_type" required
                        class="min-h-10 w-full border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-950">
                    <option value="technical"
                            @selected(old('request_type', 'technical') === 'technical')>
                        Technical Issue
                    </option>
                    <option value="non_technical"
                            @selected(old('request_type') === 'non_technical')>
                        Nontechnical Concern
                    </option>
                </select>
            </div>

            <div class="min-w-0">
                <label for="issue_type" class="mb-1 block text-xs font-semibold">
                    Specific Issue <span class="text-red-600">*</span>
                </label>
                <select id="issue_type" name="issue_type" required
                        data-old-value="{{ old('issue_type') }}"
                        class="min-h-10 w-full border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-950">
                </select>
            </div>

            <div class="min-w-0">
                <label for="subscription_id" class="mb-1 block text-xs font-semibold">
                    Affected Subscription
                </label>
                <select id="subscription_id" name="subscription_id"
                        data-old-value="{{ old('subscription_id') }}"
                        aria-describedby="subscription-help"
                        class="min-h-10 w-full border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-950">
                    <option value="">No subscription selected</option>
                    @foreach ($customers as $customer)
                        @foreach ($customer->subscriptions as $subscription)
                            <option value="{{ $subscription->id }}"
                                    data-customer-id="{{ $customer->id }}">
                                {{ $subscription->servicePlan?->name ?? 'Internet Service' }}
                                - #{{ $subscription->id }}
                            </option>
                        @endforeach
                    @endforeach
                </select>
            </div>

            <div class="min-w-0">
                <label for="priority" class="mb-1 block text-xs font-semibold">
                    Priority
                </label>
                <select id="priority" name="priority"
                        class="min-h-10 w-full border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-950">
                    @foreach (['low', 'normal', 'high', 'urgent'] as $priority)
                        <option value="{{ $priority }}"
                                @selected(old('priority', 'normal') === $priority)>
                            {{ ucfirst($priority) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-0">
                <label for="subject" class="mb-1 block text-xs font-semibold">
                    Subject <span class="text-red-600">*</span>
                </label>
                <input id="subject" name="subject" type="text"
                       value="{{ old('subject') }}"
                       maxlength="160" required
                       placeholder="Brief summary of the concern"
                       class="min-h-10 w-full border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-950">
            </div>

            <div class="min-w-0 md:col-span-2 lg:col-span-3">
                <label for="description" class="mb-1 block text-xs font-semibold">
                    Description <span class="text-red-600">*</span>
                </label>
                <textarea id="description" name="description"
                          rows="3" maxlength="5000" required
                          placeholder="Describe the issue, symptoms, and relevant details."
                          class="block min-h-20 w-full resize-y border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-950">{{ old('description') }}</textarea>
            </div>

            <div class="md:col-span-2 lg:col-span-3">
                <p id="subscription-help"
                   class="text-xs text-neutral-500 dark:text-neutral-400">
                    Technical tickets require an active subscription belonging to the selected subscriber.
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-neutral-200 px-4 py-3 dark:border-neutral-800">
            <p class="text-xs text-neutral-500 dark:text-neutral-400">
                New tickets start as Open. No Job Order is created automatically.
            </p>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.tickets.index') }}"
                   class="inline-flex min-h-10 items-center border border-neutral-300 px-4 py-2 text-sm dark:border-neutral-700">
                    Cancel
                </a>
                <button type="submit"
                        class="inline-flex min-h-10 items-center gap-2 bg-[#008080] px-4 py-2 text-sm font-semibold text-white hover:bg-[#006666]">
                    <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>
                    Create Ticket
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const customer = document.getElementById('customer_id');
    const category = document.getElementById('request_type');
    const issue = document.getElementById('issue_type');
    const subscription = document.getElementById('subscription_id');
    const help = document.getElementById('subscription-help');

    if (!customer || !category || !issue || !subscription) return;

    const issueOptions = {
        technical: [
            ['no_internet', 'No Internet'],
            ['slow_connection', 'Slow Connection'],
            ['intermittent_connection', 'Intermittent Connection'],
            ['other', 'Other Technical Issue']
        ],
        non_technical: [
            ['billing', 'Billing Concern'],
            ['account', 'Account Concern'],
            ['other', 'Other Nontechnical Concern']
        ]
    };

    const oldIssue = issue.dataset.oldValue || '';
    const oldSubscription = subscription.dataset.oldValue || '';

    const refreshIssues = (initial = false) => {
        const previous = initial ? oldIssue : issue.value;

        issue.replaceChildren(new Option('Select an issue', ''));

        (issueOptions[category.value] || []).forEach(([value, label]) => {
            issue.add(new Option(label, value));
        });

        if ([...issue.options].some(option => option.value === previous)) {
            issue.value = previous;
        }

        subscription.required = category.value === 'technical';

        if (help) {
            help.textContent = subscription.required
                ? 'Technical tickets require an active subscription belonging to this subscriber.'
                : 'Optional for billing and account concerns.';
        }
    };

    const refreshSubscriptions = (initial = false) => {
        const previous = initial ? oldSubscription : subscription.value;

        [...subscription.options].forEach((option) => {
            if (!option.value) return;

            const allowed = option.dataset.customerId === customer.value;
            option.hidden = !allowed;
            option.disabled = !allowed;
        });

        const valid = [...subscription.options].some(
            option => option.value === previous && !option.disabled
        );

        subscription.value = valid ? previous : '';
    };

    customer.addEventListener('change', () => refreshSubscriptions());
    category.addEventListener('change', () => refreshIssues());

    refreshSubscriptions(true);
    refreshIssues(true);
})();
</script>
@endpush