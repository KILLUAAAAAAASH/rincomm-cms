@extends('layouts.app')

@section('title', 'Support Ticket Details - Rincomm CMS')
@section('page-title', 'Ticket Details')

@section('content')
<div class="mx-auto max-w-5xl space-y-4">

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

        <a href="{{ route('admin.tickets.index') }}"
           class="text-sm font-semibold text-[#008080] dark:text-[#5EEAD4]">
            Back to Tickets
        </a>
    </div>

    @if (session('success'))
        <div role="status" class="border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <section class="border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <div class="grid gap-5 border-b border-neutral-200 p-5 dark:border-neutral-800 sm:grid-cols-2 lg:grid-cols-3">

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

        <div class="p-5">
            <h2 class="text-sm font-semibold">Issue Description</h2>
            <p class="mt-3 whitespace-pre-line break-words text-sm leading-6 text-neutral-700 dark:text-neutral-300">
                {{ $ticket->description ?: 'No description provided.' }}
            </p>
        </div>
    </section>

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

    <section class="border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <div class="border-b border-neutral-200 px-5 py-4 dark:border-neutral-800">
            <h2 class="text-sm font-semibold">Manage Ticket Workflow</h2>
            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                Assign an active support employee and advance the ticket through the permitted workflow.
            </p>
        </div>

        <div class="space-y-5 p-5">
            <div class="grid gap-4 sm:grid-cols-2">
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
                      class="space-y-3 border-t border-neutral-200 pt-4 dark:border-neutral-800">
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
                      class="space-y-3 border-t border-neutral-200 pt-4 dark:border-neutral-800">
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

    <section class="border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <div class="border-b border-neutral-200 px-5 py-4 dark:border-neutral-800">
            <h2 class="text-sm font-semibold">Related Job Orders</h2>
            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                Field work must be created separately from support ticket submission.
            </p>
        </div>

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
    </section>



</div>
@endsection