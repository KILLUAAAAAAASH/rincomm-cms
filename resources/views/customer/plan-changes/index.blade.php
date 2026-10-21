@extends('layouts.customer')

@section('title', 'My Internet Plan - Rincomm Customer Portal')
@section('page-title', 'My Internet Plan')

@section('header-actions')
    <a href="{{ route('customer.dashboard') }}"
       class="inline-flex items-center gap-2 rounded-lg border border-neutral-300 px-4 py-2 text-sm font-semibold text-neutral-700 hover:border-[#008080] hover:text-[#008080] dark:border-neutral-700 dark:text-neutral-200">
        <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
        Back to Dashboard
    </a>
@endsection

@section('content')
<div class="space-y-6">
    @if (session('success'))
        <div role="status" class="rounded-xl border border-emerald-300 bg-emerald-50 px-5 py-4 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div role="alert" class="rounded-xl border border-red-300 bg-red-50 px-5 py-4 text-sm text-red-900 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div role="alert" class="rounded-xl border border-red-300 bg-red-50 px-5 py-4 text-sm text-red-900 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200">
            <ul class="list-disc pl-5 space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Current subscription</h2>
        @if (! $customer)
            <p class="mt-3 text-sm text-neutral-600 dark:text-neutral-300">Your subscriber profile has not been created yet. Complete your service application before requesting a plan change.</p>
        @elseif ($customer->status !== 'active')
            <p class="mt-3 text-sm text-neutral-600 dark:text-neutral-300">Your subscriber profile is not active. Plan changes are available after activation.</p>
        @elseif (! $subscription || ! $subscription->servicePlan)
            <p class="mt-3 text-sm text-neutral-600 dark:text-neutral-300">No active internet subscription was found. Once your service is activated, your plan details will appear here.</p>
        @else
            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div><p class="text-xs font-medium uppercase tracking-wide text-neutral-500">Current plan</p><p class="mt-1 font-bold text-neutral-900 dark:text-white">{{ $subscription->servicePlan->name }}</p></div>
                <div><p class="text-xs font-medium uppercase tracking-wide text-neutral-500">Internet speed</p><p class="mt-1 font-bold text-neutral-900 dark:text-white">{{ number_format((float) $subscription->servicePlan->speed_mbps, 0) }} Mbps</p></div>
                <div><p class="text-xs font-medium uppercase tracking-wide text-neutral-500">Contract ends</p><p class="mt-1 font-bold text-neutral-900 dark:text-white">{{ $subscription->end_date?->format('M j, Y') ?? 'Review required' }}</p></div>
                <div><p class="text-xs font-medium uppercase tracking-wide text-neutral-500">Current verified monthly rate</p>
                    @if ($rate)
                        <p class="mt-1 font-bold text-neutral-900 dark:text-white">₱{{ number_format(max(0, (float) $rate->monthly_fee - (float) $rate->discount_amount), 2) }}</p>
                        @if ((float) $rate->discount_amount > 0)
                            <p class="mt-1 text-xs text-neutral-500">After ₱{{ number_format((float) $rate->discount_amount, 2) }} discount</p>
                        @endif
                    @else
                        <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">Manual billing review required</p>
                    @endif
                </div>
            </div>
            <p class="mt-4 text-xs text-neutral-500 dark:text-neutral-400">Newly approved plan changes take effect immediately: your new plan, monthly billing rate, and renewed contract begin on the approval date. The new rate applies to future unissued invoices on your existing billing schedule. Already issued invoices remain unchanged, with no retroactive repricing or proration. Alternative prices are current catalog rates, subject to approval.</p>
            @if ($scheduledChange)
                <div class="mt-4 rounded-lg border border-teal-200 bg-teal-50 p-4 text-sm text-teal-900 dark:border-teal-900 dark:bg-teal-950/30 dark:text-teal-200" role="status">
                    <p class="font-semibold">Approved upgrade scheduled</p>
                    <p class="mt-1">{{ $scheduledChange->requestedPlan?->name ?? 'New plan' }} starts {{ $scheduledChange->effective_on?->format('M j, Y') }}. Your current plan remains active until then.</p>
                </div>
            @endif
        @endif
    </section>

    @if ($subscription && $subscription->servicePlan)
        <section class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Request a different plan</h2>
            <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-300">Upgrades may be requested during lock-in. Downgrades become available after the current contract ends. Every request needs administrator approval.</p>
            @if ($pending)
                <p class="mt-4 rounded-lg bg-amber-50 p-4 text-sm font-medium text-amber-800 dark:bg-amber-950/30 dark:text-amber-200">You already have a pending request. Wait for a decision before submitting another.</p>
            @elseif ($scheduledChange)
                <p class="mt-4 rounded-lg bg-amber-50 p-4 text-sm font-medium text-amber-800 dark:bg-amber-950/30 dark:text-amber-200">An approved plan change is awaiting its effective date. Further changes are unavailable until it takes effect.</p>
            @elseif ($rateReviewRequired)
                <p class="mt-4 rounded-lg bg-amber-50 p-4 text-sm font-medium text-amber-800 dark:bg-amber-950/30 dark:text-amber-200">Your billing history needs review before a plan change can be approved. You may still submit an upgrade request. Downgrades remain unavailable until billing verification is complete.</p>
            @elseif ($billingChangeBlocked)
                <p class="mt-4 rounded-lg bg-amber-50 p-4 text-sm font-medium text-amber-800 dark:bg-amber-950/30 dark:text-amber-200">A billing-rate change, an existing invoice, or the billing schedule needs review. You may still submit an upgrade request, but conflicts must be resolved before approval. Downgrade requests are temporarily unavailable.</p>
            @endif
            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($plans as $plan)
                    @php
                        $isUpgrade = (float) $plan->speed_mbps > (float) $subscription->servicePlan->speed_mbps;
                        // Financial-review flags block downgrades, not the submission of an upgrade request.
                        $eligible = $customer?->status === 'active'
                            && ! $pending
                            && ! $scheduledChange
                            && ($isUpgrade || ($canDowngrade && ! $rateReviewRequired && ! $billingChangeBlocked));
                    @endphp
                    <div class="rounded-xl border border-neutral-200 p-5 dark:border-neutral-700">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="font-bold text-neutral-900 dark:text-white">{{ $plan->name }}</h3>
                            <span class="rounded-full bg-neutral-100 px-2 py-1 text-xs font-semibold text-neutral-700 dark:bg-neutral-800 dark:text-neutral-200">{{ $isUpgrade ? 'Upgrade' : 'Downgrade' }}</span>
                        </div>
                        <p class="mt-4 text-2xl font-bold text-[#008080] dark:text-[#5EEAD4]">₱{{ number_format((float) $plan->monthly_fee, 2) }}<span class="text-sm font-normal text-neutral-500"> / month</span></p>
                        <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-300">{{ number_format((float) $plan->speed_mbps, 0) }} Mbps · {{ (int) $plan->duration_months }}-month contract</p>
                        @if ($eligible)
                            <form method="POST" action="{{ route('customer.plan-changes.store') }}" class="mt-5 space-y-3">
                                @csrf
                                <input type="hidden" name="requested_service_plan_id" value="{{ $plan->id }}">
                                <label for="reason-{{ $plan->id }}" class="block text-xs font-medium text-neutral-600 dark:text-neutral-300">Reason (optional)</label>
                                <textarea id="reason-{{ $plan->id }}" name="reason" rows="2" maxlength="1000" placeholder="Why would you like this plan?" class="w-full rounded-lg border border-neutral-300 bg-white p-3 text-sm text-neutral-800 focus:border-[#008080] focus:ring-[#008080] dark:border-neutral-700 dark:bg-neutral-950 dark:text-white"></textarea>
                                <button type="submit" class="w-full rounded-lg bg-[#008080] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#006666] focus:outline-none focus:ring-2 focus:ring-[#008080] focus:ring-offset-2">Request {{ $isUpgrade ? 'upgrade' : 'downgrade' }}</button>
                            </form>
                        @else
                            <p class="mt-5 rounded-lg bg-neutral-100 p-3 text-sm text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300">{{ ! $isUpgrade && ! $canDowngrade ? 'Available after your lock-in ends' : 'Requests temporarily unavailable' }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-neutral-500">No alternative public plans are available right now.</p>
                @endforelse
            </div>
        </section>
    @endif

    <section class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Plan-change request history</h2>
        <div class="mt-4 space-y-3">
            @forelse ($history as $item)
                <div class="rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="font-semibold text-neutral-900 dark:text-white">{{ ucfirst($item->request_type) }}: {{ $item->currentPlan?->name ?? 'Previous plan' }} → {{ $item->requestedPlan?->name ?? 'Requested plan' }}</p>
                            <p class="mt-1 text-xs text-neutral-500">Submitted {{ $item->created_at?->format('M j, Y g:i A') }}</p>
                        </div>
                        <span class="rounded-full border px-3 py-1 text-xs font-semibold {{ match ($item->status) { 'approved' => 'border-emerald-300 text-emerald-700 dark:text-emerald-300', 'rejected' => 'border-red-300 text-red-700 dark:text-red-300', default => 'border-amber-300 text-amber-700 dark:text-amber-300' } }}">{{ $item->status === 'approved' && $item->effective_on && ! $item->applied_at ? 'Approved - scheduled' : ucfirst($item->status) }}</span>
                        @if ($item->status === 'approved' && $item->effective_on)
                            <p class="mt-1 text-xs text-neutral-500">Effective {{ $item->effective_on->format('M j, Y') }}{{ $item->applied_at ? ' - applied' : ' - awaiting activation' }}</p>
                        @endif
                    </div>
                    @if ($item->reason)
                        <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-300"><span class="font-medium">Your reason:</span> {{ $item->reason }}</p>
                    @endif
                    @if ($item->review_notes)
                        <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-300"><span class="font-medium">Administrator remarks:</span> {{ $item->review_notes }}</p>
                    @endif
                </div>
            @empty
                <p class="text-sm text-neutral-500 dark:text-neutral-400">You have not submitted a plan-change request yet.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
