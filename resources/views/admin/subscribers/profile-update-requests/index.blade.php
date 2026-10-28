@extends('layouts.app')
@section('title', 'Profile Update Requests')
@section('content')
<div class="mx-auto max-w-6xl px-4 py-7">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Subscriber profile update requests</h1>
            <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">Subscribers → Requests. Review contact and billing changes; do not change login or installation information here.</p>
        </div>
        <a class="rounded border px-4 py-2 hover:bg-neutral-200 dark:hover:bg-neutral-800" href="{{ route('admin.subscribers.index') }}">Back to Subscribers</a>
    </div>
    @if(session('success')) <div role="status" class="mb-4 rounded border border-green-500 p-3 text-green-700 dark:text-green-300">{{ session('success') }}</div> @endif
    @if($errors->any()) <div role="alert" class="mb-4 rounded border border-red-500 p-3 text-red-700 dark:text-red-300">{{ $errors->first() }}</div> @endif
    <nav aria-label="Request filters" class="mb-5 flex flex-wrap gap-2">
        @foreach(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','all'=>'All'] as $key=>$caption)
            <a href="{{ route('admin.profile-update-requests.index', ['status'=>$key]) }}" class="rounded border px-3 py-2 text-sm {{ $filter === $key ? 'bg-teal-700 text-white' : 'bg-white dark:bg-neutral-900' }}">{{ $caption }}</a>
        @endforeach
    </nav>
    <div class="space-y-4">
    @forelse ($items as $item)
        <article class="rounded border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="font-semibold">Request #{{ $item->id }} — {{ $item->customer?->customer_code ?? 'Customer' }}</h2>
                    <p class="text-sm text-neutral-600 dark:text-neutral-400">{{ $item->created_at?->format('Y-m-d H:i') }} · {{ ucfirst($item->status) }}</p>
                </div>
                @if($item->customer)
                    <a href="{{ route('admin.subscribers.show', $item->customer) }}" class="text-sm font-semibold text-teal-700 underline dark:text-teal-300">View subscriber</a>
                @endif
            </div>
            @if($item->reason) <p class="mb-3 text-sm">Customer reason: {{ $item->reason }}</p> @endif
            <dl class="mb-4 grid gap-3 text-sm sm:grid-cols-2">
                @foreach((array)$item->requested_values as $field=>$to)
                    @if(in_array($field, ['phone','address','city','province','postal_code','billing_address'], true) && ($item->current_values[$field] ?? null) !== $to)
                        <div class="border-l-2 border-teal-600 pl-3">
                            <dt class="font-semibold">{{ ucwords(str_replace('_',' ',$field)) }}</dt>
                            <dd class="break-words text-neutral-600 dark:text-neutral-300">{{ $item->current_values[$field] ?? '(empty)' }} → {{ $to ?: '(empty)' }}</dd>
                        </div>
                    @endif
                @endforeach
            </dl>
            @if($item->status === 'pending')
                <div class="grid gap-4 md:grid-cols-2">
                    <form method="POST" action="{{ route('admin.profile-update-requests.approve', $item) }}" class="rounded border border-teal-600 p-3">
                        @csrf
                        <label for="approve_notes_{{ $item->id }}" class="mb-1 block text-sm font-medium">Approval notes (minimum 10 characters)</label>
                        <textarea id="approve_notes_{{ $item->id }}" name="review_notes" required minlength="10" maxlength="1000" rows="3" class="mb-2 w-full rounded border bg-white p-2 text-neutral-900 dark:bg-neutral-950 dark:text-white"></textarea>
                        <button type="submit" class="rounded bg-teal-700 px-4 py-2 font-semibold text-white">Approve and apply changes</button>
                    </form>
                    <form method="POST" action="{{ route('admin.profile-update-requests.reject', $item) }}" class="rounded border border-red-600 p-3">
                        @csrf
                        <label for="reject_notes_{{ $item->id }}" class="mb-1 block text-sm font-medium">Rejection reason (minimum 10 characters)</label>
                        <textarea id="reject_notes_{{ $item->id }}" name="review_notes" required minlength="10" maxlength="1000" rows="3" class="mb-2 w-full rounded border bg-white p-2 text-neutral-900 dark:bg-neutral-950 dark:text-white"></textarea>
                        <button type="submit" class="rounded bg-red-700 px-4 py-2 font-semibold text-white">Reject request</button>
                    </form>
                </div>
            @else
                <p class="text-sm text-neutral-600 dark:text-neutral-300">Review notes: {{ $item->review_notes ?: '(none)' }}</p>
            @endif
        </article>
    @empty
        <p class="rounded border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">No profile update requests match this filter.</p>
    @endforelse
    </div>
    <div class="mt-5">{{ $items->links() }}</div>
</div>
@endsection
