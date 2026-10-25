@extends('layouts.app')

@section('title', 'Support Ticket Details - Rincomm CMS')
@section('page-title', 'Ticket Details')

@section('content')
<div class="rincomm-ticket-workspace mx-auto max-w-7xl space-y-5">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold text-[#008080] dark:text-[#5EEAD4]">
                {{ $ticket->ticket_number ?? ('Ticket #' . $ticket->id) }}
            </p>
            <h1 class="mt-1 break-words text-xl font-semibold text-neutral-900 dark:text-white">
                {{ $ticket->subject ?: 'Support Request' }}
            </h1>
            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                Created {{ $ticket->created_at?->format('M j, Y g:i A') ?? 'Date unavailable' }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <span class="rounded-full border border-[#008080]/20 bg-[#008080]/10 px-3 py-1 text-xs font-semibold text-[#008080] dark:border-[#5EEAD4]/20 dark:bg-[#5EEAD4]/10 dark:text-[#5EEAD4]">
                {{ str($ticket->status)->replace('_', ' ')->title() }}
            </span>
            <a href="{{ route('admin.tickets.index') }}"
               class="inline-flex items-center gap-2 rounded-lg px-2 py-2 text-sm font-semibold text-[#008080] transition hover:bg-[#008080]/5 dark:text-[#5EEAD4]">
                <i data-lucide="arrow-left" aria-hidden="true" class="h-4 w-4"></i>
                <span>Back to Tickets</span>
            </a>
        </div>
    </div>

    @if (session('success'))
        <div role="status" class="border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif
    <style>
        #rc-conversation-replies:checked ~ #rc-panel-notes,
        #rc-conversation-notes:checked ~ #rc-panel-replies {
            display: none;
        }

        #rc-conversation-replies:checked ~ .rc-ticket-tabs label[for="rc-conversation-replies"],
        #rc-conversation-notes:checked ~ .rc-ticket-tabs label[for="rc-conversation-notes"] {
            border-bottom-color: #008080;
            color: #008080;
        }

        .dark #rc-conversation-replies:checked ~ .rc-ticket-tabs label[for="rc-conversation-replies"],
        .dark #rc-conversation-notes:checked ~ .rc-ticket-tabs label[for="rc-conversation-notes"] {
            border-bottom-color: #5EEAD4;
            color: #5EEAD4;
        }

        #rc-conversation-replies:focus-visible ~ .rc-ticket-tabs label[for="rc-conversation-replies"],
        #rc-conversation-notes:focus-visible ~ .rc-ticket-tabs label[for="rc-conversation-notes"] {
            outline: 2px solid #008080;
            outline-offset: -2px;
        }
            /* Rincomm Ticket Details: compact, full-width workspace */
        .rincomm-ticket-columns {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            align-items: start;
            gap: 1.25rem;
        }

        .rincomm-ticket-primary,
        .rincomm-ticket-aside {
            min-width: 0;
        }

        .rincomm-ticket-meta-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            column-gap: 1rem;
            row-gap: 0.75rem;
            padding: 1rem 1.25rem;
        }

        @media (min-width: 640px) {
            .rincomm-ticket-meta-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (min-width: 1024px) {
            .rincomm-ticket-columns {
                grid-template-columns: minmax(0, 1.85fr) minmax(280px, 1fr);
            }
        }

        @media (max-width: 639px) {
            .rincomm-ticket-meta-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }
            /* Module 6 Feature 3: Compact Rincomm ticket details */
        .rincomm-ticket-meta-grid {
            padding: 0.75rem 1rem;
            column-gap: 0.75rem;
            row-gap: 0.625rem;
        }

        .rincomm-ticket-columns {
            gap: 1rem;
        }

        .rincomm-ticket-primary,
        .rincomm-ticket-aside {
            min-width: 0;
        }
    </style>
        {{-- Module 6 Feature 3: Always-visible ticket information --}}
    <section class="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <div class="flex items-center gap-2 border-b border-neutral-200 px-5 py-3 dark:border-neutral-800">
            <i data-lucide="info" aria-hidden="true"
               class="h-4 w-4 text-[#008080] dark:text-[#5EEAD4]"></i>
            <h2 class="text-sm font-semibold text-neutral-900 dark:text-white">
                Ticket Information
            </h2>
        </div>
        <div class="rincomm-ticket-meta-grid">

            <div>
                <p class="text-xs text-neutral-500">Status</p>
                <p class="mt-1 text-sm font-semibold">
                    {{ str($ticket->status)->replace('_', ' ')->title() }}
                </p>
            </div>

            <div>
                <p class="text-xs text-neutral-500">Priority</p>
                <p class="mt-1 text-sm font-semibold">
                    {{ ucfirst($ticket->priority ?? 'normal') }}
                </p>
            </div>

            <div>
                <p class="text-xs text-neutral-500">Category</p>
                <p class="mt-1 text-sm font-semibold">
                    {{ $ticket->request_type === 'technical' ? 'Technical' : 'Nontechnical' }}
                </p>
            </div>

            <div>
                <p class="text-xs text-neutral-500">Specific Issue</p>
                <p class="mt-1 text-sm font-semibold">
                    {{ $ticket->issue_type ? str($ticket->issue_type)->replace('_', ' ')->title() : 'Not specified' }}
                </p>
            </div>

            <div>
                <p class="text-xs text-neutral-500">Subscriber</p>
                <p class="mt-1 text-sm font-semibold">
                    {{ trim(($ticket->customer?->first_name ?? '') . ' ' . ($ticket->customer?->last_name ?? '')) ?: 'Unknown Subscriber' }}
                </p>
                <p class="mt-1 text-xs text-neutral-500">
                    {{ $ticket->customer?->customer_code ?? '—' }}
                </p>
            </div>

            <div>
                <p class="text-xs text-neutral-500">Affected Subscription</p>
                <p class="mt-1 text-sm font-semibold">
                    @if ($ticket->subscription)
                        {{ $ticket->subscription->servicePlan?->name ?? 'Internet Service' }}
                        — #{{ $ticket->subscription->id }}
                    @else
                        Not linked
                    @endif
                </p>
            </div>

        </div>

        
    
            
            <div class="rincomm-ticket-compact-description border-t border-neutral-200 px-4 py-3 dark:border-neutral-800">
            <div class="flex items-start gap-2">
                <i data-lucide="file-text"
                   aria-hidden="true"
                   class="mt-0.5 h-4 w-4 shrink-0 text-[#008080] dark:text-[#5EEAD4]"></i>
                <div class="min-w-0">
                    <h3 class="text-xs font-semibold text-neutral-900 dark:text-white">
                        Issue Description
                    </h3>
                    <p class="mt-1 whitespace-pre-line break-words text-sm leading-5 text-neutral-700 dark:text-neutral-300">
                        {{ $ticket->description ?: 'No description provided.' }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    <div class="rincomm-ticket-columns">
        <main class="rincomm-ticket-primary min-w-0 space-y-4">
        
        <section class="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
            <div class="border-b border-neutral-200 px-5 py-4 dark:border-neutral-800">
                <div class="flex items-center gap-2">
                    <i data-lucide="messages-square" aria-hidden="true" class="h-5 w-5 text-[#008080] dark:text-[#5EEAD4]"></i>
                    <h2 class="text-sm font-semibold text-neutral-900 dark:text-white">Conversation</h2>
                </div>
            </div>

            <input class="sr-only" type="radio" name="rc-conversation-tabs" id="rc-conversation-replies" checked>
            <input class="sr-only" type="radio" name="rc-conversation-tabs" id="rc-conversation-notes">

            <div class="rc-ticket-tabs flex border-b border-neutral-200 px-5 dark:border-neutral-800">
                <label for="rc-conversation-replies"
                       class="inline-flex cursor-pointer items-center gap-2 border-b-2 border-transparent px-4 py-3 text-sm font-medium text-neutral-600 transition hover:text-[#008080] dark:text-neutral-300">
                    <i data-lucide="message-circle" aria-hidden="true" class="h-4 w-4"></i>
                    Replies
                </label>
                <label for="rc-conversation-notes"
                       class="inline-flex cursor-pointer items-center gap-2 border-b-2 border-transparent px-4 py-3 text-sm font-medium text-neutral-600 transition hover:text-[#008080] dark:text-neutral-300">
                    <i data-lucide="lock-keyhole" aria-hidden="true" class="h-4 w-4"></i>
                    Internal Staff Notes
                </label>
            </div>
            <div id="rc-panel-replies">
<div class="space-y-4 p-5">
            @forelse ($ticket->ticketMessages as $reply)
                <article class="rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="text-sm font-semibold text-neutral-900 dark:text-white">
                                {{ $reply->user?->name ?? 'Former User' }}
                            </p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                {{ ucfirst($reply->user?->role ?? 'user') }}
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
                    No customer-visible replies have been posted yet.
                </p>
            @endforelse

            @if ($ticket->status !== 'closed')
                <form method="POST"
                      action="{{ route('admin.tickets.replies.store', $ticket->id) }}"
                      data-lock-submit
                      class="space-y-3 border-t border-neutral-200 pt-4 dark:border-neutral-800">
                    @csrf

                    <label for="support-ticket-reply" class="block text-sm font-semibold">
                        Reply to Subscriber
                    </label>

                    <textarea id="support-ticket-reply"
                              name="message"
                              rows="3"
                              maxlength="5000"
                              required
                              class="w-full border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:border-[#008080] focus:outline-none focus:ring-1 focus:ring-[#008080] dark:border-neutral-700 dark:bg-neutral-950 dark:text-white"
                              placeholder="Write a reply visible to the subscriber...">{{ old('message') }}</textarea>

                    @error('message')
                        <p role="alert" class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    <button type="submit"
                            class="inline-flex min-h-10 items-center justify-center bg-[#008080] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#006666]">
                        <i data-lucide="send" aria-hidden="true" class="mr-2 inline-block h-4 w-4 align-middle"></i>Post Reply
                    </button>
                </form>
            @else
                <p class="border-t border-neutral-200 pt-4 text-sm text-neutral-500 dark:border-neutral-800 dark:text-neutral-400">
                    This ticket is closed. No further replies can be posted.
                </p>
            @endif
        </div>
            </div>
            <div id="rc-panel-notes">
<div class="space-y-4 p-5">
            @forelse ($ticket->ticketNotes as $note)
                <article class="rounded-lg border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-950">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="text-sm font-semibold">
                                {{ $note->user?->name ?? 'Former Employee' }}
                            </p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                Private note
                            </p>
                        </div>
                        <time class="text-xs text-neutral-500 dark:text-neutral-400">
                            {{ $note->created_at?->format('M j, Y g:i A') ?? 'Date unavailable' }}
                        </time>
                    </div>
                    <p class="mt-3 whitespace-pre-wrap break-words text-sm leading-6 text-neutral-700 dark:text-neutral-300">{{ $note->note }}</p>
                </article>
            @empty
                <p class="text-sm text-neutral-500 dark:text-neutral-400">
                    No internal notes have been added yet.
                </p>
            @endforelse

            @if ($ticket->status !== 'closed')
                <form method="POST"
                      action="{{ route('admin.tickets.notes.store', $ticket->id) }}"
                      data-lock-submit
                      class="space-y-3 border-t border-neutral-200 pt-4 dark:border-neutral-800">
                    @csrf

                    <label for="support-ticket-note" class="block text-sm font-semibold">
                        Add Private Internal Note
                    </label>

                    <textarea id="support-ticket-note"
                              name="note"
                              rows="3"
                              maxlength="5000"
                              required
                              class="w-full border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:border-[#008080] focus:outline-none focus:ring-1 focus:ring-[#008080] dark:border-neutral-700 dark:bg-neutral-950 dark:text-white"
                              placeholder="Write a note for Rincomm staff only...">{{ old('note') }}</textarea>

                    @error('note')
                        <p role="alert" class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    <button type="submit"
                            class="inline-flex min-h-10 items-center justify-center bg-[#008080] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#006666]">
                        <i data-lucide="lock-keyhole" aria-hidden="true" class="mr-2 inline-block h-4 w-4 align-middle"></i>Save Internal Note
                    </button>
                </form>
            @else
                <p class="border-t border-neutral-200 pt-4 text-sm text-neutral-500 dark:border-neutral-800 dark:text-neutral-400">
                    This ticket is closed. No further internal notes can be added.
                </p>
            @endif
        </div>
            </div>
        </section>
        </main>
        <aside class="rincomm-ticket-aside min-w-0 space-y-4">
    {{-- Module 6 Feature 2: Ticket Status Workflow --}}
    @php
        $nextStatus = match ($ticket->status) {
            'assigned' => 'in_progress',
            'in_progress' => 'resolved',
            'resolved' => 'closed',
            default => null,
        };

        $canAdvance = $nextStatus !== null
            && $ticket->assigned_user_id !== null
            && (
                auth()->user()->isAdmin()
                || (
                    (int) $ticket->assigned_user_id
                    === (int) auth()->id()
                )
            );

        $canAssign = in_array(
            $ticket->status,
            ['open', 'assigned'],
            true
        );
    @endphp

    <section class="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <div class="border-b border-neutral-200 px-4 py-3 dark:border-neutral-800">
            <h2 class="text-sm font-semibold">Manage Ticket Workflow</h2>
        </div>

        <div class="space-y-3 p-4">
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">
                        Current Status
                    </p>
                    <p class="mt-1 text-sm font-semibold">
                        {{ str($ticket->status)->replace('_', ' ')->title() }}
                    </p>
                </div>

                <div>
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">
                        Assigned Support Employee
                    </p>
                    <p class="mt-1 text-sm font-semibold">
                        {{ $ticket->assignedUser?->name ?? 'Unassigned' }}
                    </p>
                    @if ($ticket->assigned_at)
                        <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                            Assigned {{ $ticket->assigned_at->format('M j, Y g:i A') }}
                        </p>
                    @endif
                </div>
            </div>

            @if ($errors->any())
                <div role="alert"
                     class="border border-red-200 bg-red-50 px-3 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($canAssign)
                <form method="POST"
                      action="{{ route('admin.tickets.assignment.update', $ticket->id) }}"
                      data-lock-submit
                      class="space-y-3 border-t border-neutral-200 pt-3 dark:border-neutral-800">
                    @csrf
                    @method('PATCH')

                    <label for="ticket-assigned-user"
                           class="block text-sm font-semibold">
                        Assign Support Employee
                    </label>

                    <select id="ticket-assigned-user"
                            name="assigned_user_id"
                            required
                            class="w-full border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:border-[#008080] focus:outline-none focus:ring-1 focus:ring-[#008080] dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                        <option value="">Select active admin or staff</option>
                        @foreach ($assignableUsers as $employee)
                            <option value="{{ $employee->id }}"
                                @selected(
                                    (string) old('assigned_user_id', $ticket->assigned_user_id)
                                    === (string) $employee->id
                                )>
                                {{ $employee->name }} ({{ ucfirst($employee->role) }})
                            </option>
                        @endforeach
                    </select>

                    <button type="submit"
                            class="inline-flex min-h-10 items-center justify-center bg-[#008080] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#006666] focus:outline-none focus:ring-2 focus:ring-[#008080]/30">
                        {{ $ticket->assigned_user_id ? 'Update Assignment' : 'Assign Ticket' }}
                    </button>
                </form>
            @endif

            @if ($canAdvance)
                <form method="POST"
                      action="{{ route('admin.tickets.status.update', $ticket->id) }}"
                      data-lock-submit
                      class="space-y-3 border-t border-neutral-200 pt-3 dark:border-neutral-800">
                    @csrf
                    @method('PATCH')

                    <input type="hidden" name="status" value="{{ $nextStatus }}">

                    <p class="text-sm text-neutral-600 dark:text-neutral-300">
                        Next permitted status:
                        <span class="font-semibold">
                            {{ str($nextStatus)->replace('_', ' ')->title() }}
                        </span>
                    </p>

                    <button type="submit"
                            class="inline-flex min-h-10 items-center justify-center border border-[#008080] px-4 py-2 text-sm font-semibold text-[#008080] transition hover:bg-[#008080]/5 dark:text-[#5EEAD4]">
                        Move to {{ str($nextStatus)->replace('_', ' ')->title() }}
                    </button>
                </form>
            @elseif ($ticket->status === 'closed')
                <p class="border-t border-neutral-200 pt-4 text-sm text-neutral-500 dark:border-neutral-800 dark:text-neutral-400">
                    This ticket is closed. No further workflow changes are permitted.
                </p>
            @elseif ($ticket->status === 'assigned' || $ticket->status === 'in_progress' || $ticket->status === 'resolved')
                <p class="border-t border-neutral-200 pt-4 text-sm text-neutral-500 dark:border-neutral-800 dark:text-neutral-400">
                    Only the assigned support employee or an administrator can advance this ticket.
                </p>
            @endif
        </div>
    </section>


        
        <details class="group overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
            <summary class="flex cursor-pointer list-none items-center gap-2 px-5 py-4 text-sm font-semibold text-neutral-900 dark:text-white">
                <i data-lucide="wrench" aria-hidden="true" class="h-4 w-4 text-[#008080] dark:text-[#5EEAD4]"></i>
                <span class="flex-1">Related Job Orders ({{ $ticket->jobOrders->count() }})</span>
                <i data-lucide="chevron-down" aria-hidden="true" class="h-4 w-4 text-neutral-500 transition group-open:rotate-180"></i>
            </summary>
            <div class="border-t border-neutral-200 dark:border-neutral-800">
@forelse ($ticket->jobOrders as $jobOrder)
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-neutral-200 px-5 py-4 last:border-b-0 dark:border-neutral-800">
                <div>
                    <p class="text-sm font-semibold">
                        {{ $jobOrder->job_order_number }}
                    </p>
                    <p class="mt-1 text-xs text-neutral-500">
                        {{ str($jobOrder->job_type)->replace('_', ' ')->title() }}
                        · {{ str($jobOrder->status)->replace('_', ' ')->title() }}
                    </p>
                </div>

                <a href="{{ route('admin.job-orders.show', $jobOrder->id) }}"
                   class="text-sm font-semibold text-[#008080] hover:underline dark:text-[#5EEAD4]">
                    View Job Order
                </a>
            </div>
        @empty
            <div class="px-5 py-6 text-sm text-neutral-500 dark:text-neutral-400">
                No Job Orders are currently linked to this ticket.
            </div>
        @endforelse
            </div>
        </details>
        </aside>
    </div>
</div>
@endsection