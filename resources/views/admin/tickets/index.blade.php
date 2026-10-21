@extends('layouts.app')

@section('title', 'Support Tickets - Rincomm CMS')
@section('page-title', 'Support Tickets')

@section('content')
<div class="space-y-4">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-neutral-900 dark:text-white">
                Support Tickets
            </h1>
            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                Review technical issues and subscriber account concerns.
            </p>
        </div>

        <a href="{{ route('admin.tickets.create') }}"
           class="inline-flex min-h-10 items-center gap-2 bg-[#008080] px-4 py-2 text-sm font-semibold text-white hover:bg-[#006666]">
            <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>
            Create Ticket
        </a>
    </div>

    @if (session('success'))
        <div role="status" class="border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <form method="GET"
      action="{{ route('admin.tickets.index') }}"
      data-list-filters
      class="border border-gray-200 bg-white p-3 dark:border-neutral-800 dark:bg-neutral-900">

    <div class="flex flex-col gap-3 lg:flex-row lg:items-center">

        <div class="relative min-w-0 flex-1">
            <label for="ticket-search" class="sr-only">
                Search support tickets
            </label>

            <i data-lucide="search"
               class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500 dark:text-gray-400"
               aria-hidden="true"></i>

            <input id="ticket-search"
                   type="search"
                   name="search"
                   value="{{ $search }}"
                   data-list-search
                   autocomplete="off"
                   placeholder="Ticket number, subject, or subscriber..."
                   class="min-h-11 w-full border border-gray-300 bg-white py-2 pl-10 pr-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-500 focus:border-[#008080] focus:ring-2 focus:ring-[#008080]/20 dark:border-neutral-700 dark:bg-neutral-950 dark:text-gray-100">
        </div>

        <div class="lg:w-40">
            <label for="ticket-status" class="sr-only">
                Filter by ticket status
            </label>

            <select id="ticket-status"
                    name="status"
                    data-list-filter
                    class="min-h-11 w-full border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 outline-none transition focus:border-[#008080] focus:ring-2 focus:ring-[#008080]/20 dark:border-neutral-700 dark:bg-neutral-950 dark:text-gray-100">
                <option value="">All statuses</option>

                @foreach ($statuses as $item)
                    <option value="{{ $item }}" @selected($status === $item)>
                        {{ str($item)->replace('_', ' ')->title() }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="lg:w-44">
            <label for="ticket-category" class="sr-only">
                Filter by ticket category
            </label>

            <select id="ticket-category"
                    name="request_type"
                    data-list-filter
                    class="min-h-11 w-full border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 outline-none transition focus:border-[#008080] focus:ring-2 focus:ring-[#008080]/20 dark:border-neutral-700 dark:bg-neutral-950 dark:text-gray-100">
                <option value="">All categories</option>
                <option value="technical" @selected($requestType === 'technical')>
                    Technical
                </option>
                <option value="non_technical" @selected($requestType === 'non_technical')>
                    Nontechnical
                </option>
            </select>
        </div>

        <div class="lg:w-44">
            <label for="ticket-sort" class="sr-only">
                Sort support tickets
            </label>

            <select id="ticket-sort"
                    name="sort"
                    data-list-filter
                    class="min-h-11 w-full border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 outline-none transition focus:border-[#008080] focus:ring-2 focus:ring-[#008080]/20 dark:border-neutral-700 dark:bg-neutral-950 dark:text-gray-100">
                <option value="newest" @selected($sort === 'newest')>Newest first</option>
                <option value="oldest" @selected($sort === 'oldest')>Oldest first</option>
                <option value="priority_high" @selected($sort === 'priority_high')>Highest priority</option>
                <option value="priority_low" @selected($sort === 'priority_low')>Lowest priority</option>
                <option value="ticket_asc" @selected($sort === 'ticket_asc')>Ticket number A-Z</option>
                <option value="ticket_desc" @selected($sort === 'ticket_desc')>Ticket number Z-A</option>
            </select>
        </div>

        @if ($search !== '' || $status !== '' || $requestType !== '' || $sort !== 'newest')
            <a href="{{ route('admin.tickets.index') }}"
               data-list-clear
               class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-neutral-700 dark:text-gray-200 dark:hover:bg-neutral-800">
                <i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i>
                Clear
            </a>
        @endif

    </div>
</form>

    <div data-list-results aria-live="polite" aria-busy="false">
    @if ($tickets->isEmpty())
        <section class="border border-dashed border-neutral-300 bg-white px-5 py-12 text-center dark:border-neutral-700 dark:bg-neutral-900">
            <i data-lucide="ticket" class="mx-auto h-7 w-7 text-neutral-400" aria-hidden="true"></i>
            <h2 class="mt-3 text-sm font-semibold">No matching support tickets</h2>
            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                Try different filters or create a support ticket.
            </p>
        </section>
    @else
        <div class="overflow-x-auto border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
            <table class="w-full min-w-[780px] text-left">
                <thead class="border-b border-neutral-200 bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-950">
                    <tr class="text-[11px] font-semibold uppercase tracking-wide text-neutral-500">
                        <th class="px-4 py-3">Ticket</th>
                        <th class="px-4 py-3">Subscriber</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Priority</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Created</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-800">
                    @foreach ($tickets as $ticket)
                        <tr class="text-sm hover:bg-neutral-50 dark:hover:bg-neutral-800/40">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-[#008080] dark:text-[#5EEAD4]">
                                    {{ $ticket->ticket_number ?? ('#' . $ticket->id) }}
                                </p>
                                <p class="mt-1 max-w-64 truncate text-xs text-neutral-600 dark:text-neutral-300">
                                    {{ $ticket->subject ?: 'Support Request' }}
                                </p>
                            </td>

                            <td class="px-4 py-3">
                                <p class="font-medium">
                                    {{ trim(($ticket->customer?->first_name ?? '') . ' ' . ($ticket->customer?->last_name ?? '')) ?: 'Unknown Subscriber' }}
                                </p>
                                <p class="mt-1 text-xs text-neutral-500">
                                    {{ $ticket->customer?->customer_code ?? '—' }}
                                </p>
                            </td>

                            <td class="px-4 py-3 text-neutral-600 dark:text-neutral-300">
                                {{ $ticket->request_type === 'technical' ? 'Technical' : 'Nontechnical' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ ucfirst($ticket->priority ?? 'normal') }}
                            </td>

                            <td class="px-4 py-3">
                                <span class="inline-flex border border-neutral-300 px-2 py-1 text-xs font-semibold dark:border-neutral-700">
                                    {{ str($ticket->status)->replace('_', ' ')->title() }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-xs text-neutral-500">
                                {{ $ticket->created_at?->format('M j, Y') ?? '—' }}
                            </td>

                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.tickets.show', $ticket->id) }}"
                                   class="font-semibold text-[#008080] hover:underline dark:text-[#5EEAD4]">
                                    View
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div>{{ $tickets->links() }}</div>
    @endif
    </div>

</div>
@endsection