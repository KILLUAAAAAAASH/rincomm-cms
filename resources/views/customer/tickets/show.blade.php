@extends('layouts.customer')

@section('title', 'Support Ticket - Rincomm')
@section('page-title', 'Ticket Details')

@section('content')
<div class="mx-auto w-full max-w-7xl space-y-4">

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
        <div class="flex flex-wrap items-center gap-3">
            <span class="rounded-full border border-[#008080]/20 bg-[#008080]/10 px-3 py-1 text-xs font-semibold text-[#008080] dark:border-[#5EEAD4]/20 dark:bg-[#5EEAD4]/10 dark:text-[#5EEAD4]">
                {{ str($ticket->status)->replace('_', ' ')->title() }}
            </span>
            <a href="{{ route('customer.tickets.index') }}"
               class="inline-flex items-center gap-2 rounded-lg px-2 py-2 text-sm font-semibold text-[#008080] transition hover:bg-[#008080]/5 dark:text-[#5EEAD4]">
                <i data-lucide="arrow-left" aria-hidden="true" class="h-4 w-4"></i>
                Back to My Tickets
            </a>
        </div>
    </div>

    @if (session('success'))
        <div role="status" class="border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <section class="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <div class="rincomm-customer-ticket-meta grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3">
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

        <div class="border-t border-neutral-200 px-4 py-3 dark:border-neutral-800">
            <h2 class="inline-flex items-center gap-2 text-sm font-semibold"><i data-lucide="file-text" aria-hidden="true" class="h-4 w-4 text-[#008080] dark:text-[#5EEAD4]"></i>Your Description</h2>
            <p class="mt-2 whitespace-pre-line break-words text-sm leading-5 text-neutral-700 dark:text-neutral-300">{{ $ticket->description ?: 'No description provided.' }}</p>
        </div>
    </section>

    {{-- Module 6 Feature 3: Subscriber-visible ticket conversation --}}
    <section class="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <div class="border-b border-neutral-200 px-5 py-4 dark:border-neutral-800">
            <h2 class="inline-flex items-center gap-2 text-sm font-semibold"><i data-lucide="messages-square" aria-hidden="true" class="h-4 w-4 text-[#008080] dark:text-[#5EEAD4]"></i>Support Conversation</h2>

        </div>

        <div class="space-y-3 p-4">
            @forelse ($ticket->ticketMessages as $reply)
                <article class="rounded-lg border border-neutral-200 p-3 dark:border-neutral-800">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="text-sm font-semibold text-neutral-900 dark:text-white">
                                {{ $reply->user?->name ?? 'Former User' }}
                            </p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                {{ $reply->user?->role === 'customer' ? 'Subscriber' : 'Rincomm Support' }}
                            </p>
                        </div>

                        <time class="text-xs text-neutral-500 dark:text-neutral-400">
                            {{ $reply->created_at?->format('M j, Y g:i A') ?? 'Date unavailable' }}
                        </time>
                    </div>

                    <p class="mt-3 whitespace-pre-wrap break-words text-sm leading-6 text-neutral-700 dark:text-neutral-300">{{ $reply->message }}</p>
                </article>
            @empty
                <p class="text-sm text-neutral-500 dark:text-neutral-400">
                    No replies have been posted yet.
                </p>
            @endforelse

            @if ($errors->has('ticket'))
                <p role="alert"
                   class="text-sm text-red-600 dark:text-red-400">
                    {{ $errors->first('ticket') }}
                </p>
            @endif

            @if ($ticket->status !== 'closed')
                <form method="POST"
                      action="{{ route('customer.tickets.replies.store', $ticket->id) }}"
                      data-lock-submit
                      class="space-y-3 border-t border-neutral-200 pt-4 dark:border-neutral-800">
                    @csrf

                    <label for="customer-ticket-reply"
                           class="block text-sm font-semibold">
                        Write a Reply
                    </label>

                    <textarea id="customer-ticket-reply"
                              name="message"
                              rows="3"
                              maxlength="5000"
                              required
                              class="w-full border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:border-[#008080] focus:outline-none focus:ring-1 focus:ring-[#008080] dark:border-neutral-700 dark:bg-neutral-950 dark:text-white"
                              placeholder="Write your response to Rincomm support...">{{ old('message') }}</textarea>

                    @error('message')
                        <p role="alert" class="text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror

                    <button type="submit"
                            class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#008080] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#006666]">
                        <i data-lucide="send" aria-hidden="true" class="mr-2 inline-block h-4 w-4 align-middle"></i>Send Reply
                    </button>
                </form>
            @else
                <p class="border-t border-neutral-200 pt-4 text-sm text-neutral-500 dark:border-neutral-800 dark:text-neutral-400">
                    This ticket is closed. You can review previous messages, but no further replies can be posted.
                </p>
            @endif
        </div>
    </section>
</div>
@endsection