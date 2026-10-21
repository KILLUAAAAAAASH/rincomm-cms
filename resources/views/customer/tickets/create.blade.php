@extends('layouts.customer')

@section('title', 'New Support Ticket - Rincomm')
@section('page-title', 'New Support Ticket')

@section('content')
<div class="mx-auto w-full max-w-6xl space-y-3">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-neutral-900 dark:text-white">
                Create Support Ticket
            </h1>
            <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                Describe your concern so our support team can assist you.
            </p>
        </div>

        <a href="{{ route('customer.tickets.index') }}"
           class="text-sm font-semibold text-[#008080] dark:text-[#5EEAD4]">
            Back to Tickets
        </a>
    </div>

    @if (! $customer)
        <section class="border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
            <p class="text-sm text-neutral-600 dark:text-neutral-300">
                A subscriber profile is required before submitting a ticket.
            </p>

            <a href="{{ route('customer.application.create') }}"
               class="mt-3 inline-flex text-sm font-semibold text-[#008080] dark:text-[#5EEAD4]">
                Open service application
            </a>
        </section>
    @else

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
              action="{{ route('customer.tickets.store') }}"
              data-lock-submit
              class="border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
            @csrf

            <div class="grid gap-x-4 gap-y-3 p-4 md:grid-cols-2 lg:grid-cols-3">

                <div class="min-w-0">
                    <label for="request_type" class="mb-1 block text-xs font-semibold">
                        Ticket Category <span class="text-red-600">*</span>
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
                            aria-describedby="subscription-help"
                            class="min-h-10 w-full border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-950">
                        <option value="">No subscription selected</option>

                        @foreach ($subscriptions as $subscription)
                            <option value="{{ $subscription->id }}"
                                    @selected((string) old('subscription_id') === (string) $subscription->id)>
                                {{ $subscription->servicePlan?->name ?? 'Internet Service' }}
                                - Subscription #{{ $subscription->id }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="min-w-0 md:col-span-2 lg:col-span-3">
                    <label for="subject" class="mb-1 block text-xs font-semibold">
                        Subject <span class="text-red-600">*</span>
                    </label>

                    <input id="subject" type="text" name="subject"
                           value="{{ old('subject') }}"
                           maxlength="160" required
                           placeholder="Briefly describe your concern"
                           class="min-h-10 w-full border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-950">
                </div>

                <div class="min-w-0 md:col-span-2 lg:col-span-3">
                    <label for="description" class="mb-1 block text-xs font-semibold">
                        Description <span class="text-red-600">*</span>
                    </label>

                    <textarea id="description" name="description"
                              rows="3" maxlength="5000" required
                              placeholder="Include relevant details, when the problem started, and what you have already tried."
                              class="block min-h-24 w-full resize-y border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-950">{{ old('description') }}</textarea>
                </div>

                <div class="md:col-span-2 lg:col-span-3">
                    <p id="subscription-help"
                       class="text-xs text-neutral-500 dark:text-neutral-400">
                        An active subscription is required for technical issues.
                    </p>
                </div>

            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-neutral-200 px-4 py-3 dark:border-neutral-800">
                <p class="text-xs text-neutral-500 dark:text-neutral-400">
                    New tickets start with Open status and Normal priority.
                </p>

                <div class="flex items-center gap-2">
                    <a href="{{ route('customer.tickets.index') }}"
                       class="inline-flex min-h-10 items-center border border-neutral-300 px-4 py-2 text-sm dark:border-neutral-700">
                        Cancel
                    </a>

                    <button type="submit"
                            class="inline-flex min-h-10 items-center gap-2 bg-[#008080] px-4 py-2 text-sm font-semibold text-white hover:bg-[#006666]">
                        <i data-lucide="send" class="h-4 w-4" aria-hidden="true"></i>
                        Submit Ticket
                    </button>
                </div>
            </div>
        </form>

    @endif
</div>
@endsection

@push('scripts')
<script>
(() => {
    const category = document.getElementById('request_type');
    const issue = document.getElementById('issue_type');
    const subscription = document.getElementById('subscription_id');
    const help = document.getElementById('subscription-help');

    if (!category || !issue || !subscription) return;

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

    const refresh = (initial = false) => {
        const previous = initial ? oldIssue : issue.value;

        issue.replaceChildren(new Option('Select an issue', ''));

        (issueOptions[category.value] || []).forEach(([value, label]) => {
            issue.add(new Option(label, value));
        });

        if ([...issue.options].some(option => option.value === previous)) {
            issue.value = previous;
        }

        const technical = category.value === 'technical';
        subscription.required = technical;

        if (help) {
            help.textContent = technical
                ? 'Select the active internet subscription affected by this issue.'
                : 'Subscription is optional for billing and account concerns.';
        }
    };

    category.addEventListener('change', () => refresh(false));
    refresh(true);
})();
</script>
@endpush