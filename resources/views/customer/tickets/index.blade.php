@extends('layouts.customer')

@section('title', 'My Support Tickets - Rincomm')
@section('page-title', 'Support Tickets')

@section('content')
<div class="space-y-4">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-neutral-900 dark:text-white">
                My Support Tickets
            </h1>
            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                Track technical problems and account concerns.
            </p>
        </div>

        @if ($customer)
            <a href="{{ route('customer.tickets.create') }}"
               class="inline-flex min-h-10 items-center gap-2 bg-[#008080] px-4 py-2 text-sm font-semibold text-white hover:bg-[#006666]">
                <i data-lucide="plus" class="h-4 w-4"></i>
                New Ticket
            </a>
        @endif
    </div>

    @if (session('success'))
        <div role="status" class="border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    @if (! $customer)
        <section class="border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-900">
            <h2 class="text-sm font-semibold">Subscriber profile required</h2>
            <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
                Your subscriber profile must be created before you can submit support tickets.
            </p>
            <a href="{{ route('customer.application.create') }}"
               class="mt-4 inline-flex text-sm font-semibold text-[#008080] dark:text-[#5EEAD4]">
                Open service application
            </a>
        </section>
    @elseif ($tickets->isEmpty())
        <section class="border border-dashed border-neutral-300 bg-white px-5 py-12 text-center dark:border-neutral-700 dark:bg-neutral-900">
            <i data-lucide="messages-square" class="mx-auto h-8 w-8 text-neutral-400"></i>
            <h2 class="mt-3 text-sm font-semibold">No support tickets yet</h2>
            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                Submit a ticket whenever you need assistance.
            </p>
            <a href="{{ route('customer.tickets.create') }}"
               class="mt-4 inline-flex bg-[#008080] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#006666]">
                Create Ticket
            </a>
        </section>
    @else
        <section class="overflow-hidden border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
            <div class="divide-y divide-neutral-200 dark:divide-neutral-800">
                @foreach ($tickets as $ticket)
                    <a href="{{ route('customer.tickets.show', $ticket->id) }}"
                       class="flex flex-wrap items-center justify-between gap-3 px-4 py-4 transition hover:bg-neutral-50 dark:hover:bg-neutral-800/40">
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold text-[#008080] dark:text-[#5EEAD4]">
                                {{ $ticket->ticket_number ?? ('Ticket #' . $ticket->id) }}
                            </p>
                            <h2 class="mt-1 break-words text-sm font-semibold text-neutral-900 dark:text-white">
                                {{ $ticket->subject ?: 'Support Request' }}
                            </h2>
                            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                {{ $ticket->request_type === 'technical' ? 'Technical' : 'Nontechnical' }}
                                · {{ $ticket->created_at?->format('M j, Y g:i A') ?? 'Date unavailable' }}
                            </p>
                        </div>
                        <span class="border border-neutral-300 px-2.5 py-1 text-xs font-semibold text-neutral-700 dark:border-neutral-700 dark:text-neutral-200">
                            {{ str($ticket->status)->replace('_', ' ')->title() }}
                        </span>
                        <i data-lucide="chevron-right" class="h-4 w-4 text-neutral-400"></i>
                    </a>
                @endforeach
            </div>
        </section>

        <div>{{ $tickets->links() }}</div>
    @endif
</div>
@endsection