@extends('layouts.customer')

@section('title', 'Support Ticket - Rincomm')
@section('page-title', 'Ticket Details')

@section('content')
<div class="mx-auto max-w-4xl space-y-4">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold text-[#008080] dark:text-[#5EEAD4]">
                {{ $ticket->ticket_number ?? ('Ticket #' . $ticket->id) }}
            </p>
            <h1 class="mt-1 break-words text-xl font-semibold text-neutral-900 dark:text-white">
                {{ $ticket->subject ?: 'Support Request' }}
            </h1>
            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                Submitted {{ $ticket->created_at?->format('M j, Y g:i A') ?? 'Date unavailable' }}
            </p>
        </div>
        <a href="{{ route('customer.tickets.index') }}"
           class="text-sm font-semibold text-[#008080] dark:text-[#5EEAD4]">
            Back to My Tickets
        </a>
    </div>

    @if (session('success'))
        <div role="status" class="border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <section class="border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <div class="grid gap-5 border-b border-neutral-200 p-5 dark:border-neutral-800 sm:grid-cols-2">
            <div>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Status</p>
                <p class="mt-1 text-sm font-semibold">
                    {{ str($ticket->status)->replace('_', ' ')->title() }}
                </p>
            </div>
            <div>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Priority</p>
                <p class="mt-1 text-sm font-semibold">{{ ucfirst($ticket->priority ?? 'normal') }}</p>
            </div>
            <div>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Category</p>
                <p class="mt-1 text-sm font-semibold">
                    {{ $ticket->request_type === 'technical' ? 'Technical' : 'Nontechnical' }}
                </p>
            </div>
            <div>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Issue</p>
                <p class="mt-1 text-sm font-semibold">
                    {{ $ticket->issue_type ? str($ticket->issue_type)->replace('_', ' ')->title() : 'Not specified' }}
                </p>
            </div>
            <div class="sm:col-span-2">
                <p class="text-xs text-neutral-500 dark:text-neutral-400">Affected Subscription</p>
                <p class="mt-1 text-sm font-semibold">
                    @if ($ticket->subscription)
                        {{ $ticket->servicePlan?->name ?? 'Internet Service' }}
                        — #{{ $ticket->subscription->id }}
                    @else
                        Not linked to a subscription
                    @endif
                </p>
            </div>
        </div>

        <div class="p-5">
            <h2 class="text-sm font-semibold">Your Description</h2>
            <p class="mt-3 whitespace-pre-line break-words text-sm leading-6 text-neutral-700 dark:text-neutral-300">{{ $ticket->description ?: 'No description provided.' }}</p>
        </div>
    </section>

    <p class="text-xs text-neutral-500 dark:text-neutral-400">
        Your ticket has been recorded. Replies and status updates will be available as the support workflow is implemented.
    </p>
</div>
@endsection