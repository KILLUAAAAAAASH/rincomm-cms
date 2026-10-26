@extends('layouts.app')

@section('title', 'Hardware Asset | Rincomm CMS')
@section('page-title', 'Hardware Asset Details')

@section('content')
<div class="mx-auto max-w-6xl space-y-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ route('admin.equipment-assets.index') }}" class="text-sm font-medium text-[#008080] hover:underline dark:text-teal-300">← Hardware Assets</a>
            <h1 class="mt-2 text-xl font-semibold">{{ $equipmentAsset->asset_tag }} <span class="ml-2 align-middle border border-neutral-300 px-2 py-1 text-xs font-medium dark:border-neutral-700">{{ \App\Models\EquipmentAsset::STATUSES[$equipmentAsset->status] ?? $equipmentAsset->status }}</span></h1>
            <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">{{ $equipmentAsset->name }}</p>
        </div>
        <a href="{{ route('admin.equipment-assets.edit', $equipmentAsset) }}" class="inline-flex min-h-10 items-center bg-[#008080] px-4 text-sm font-semibold text-white hover:bg-[#006666]">Edit Equipment</a>
    </div>

    @if (session('success'))
        <div role="status" class="border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">{{ session('success') }}</div>
    @endif

    <section class="border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900" aria-labelledby="assignment-heading">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-neutral-200 pb-3 dark:border-neutral-800">
            <div>
                <h2 id="assignment-heading" class="text-sm font-semibold">Subscriber Equipment Binding</h2>
                <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">The serial number is sourced from inventory; customer IP addresses are recorded per assignment.</p>
            </div>
            @if ($equipmentAsset->status === 'in_stock' && filled($equipmentAsset->serial_number) && $assignments->whereNull('released_at')->isEmpty())
                <a href="{{ route('admin.equipment-assignments.create', ['asset_id' => $equipmentAsset->id]) }}" class="inline-flex min-h-10 items-center bg-[#008080] px-4 text-sm font-semibold text-white hover:bg-[#006666]">Assign to Subscriber</a>
            @endif
        </div>
        @if ($assignments->isEmpty())
            <p class="py-4 text-sm text-neutral-500 dark:text-neutral-400">No subscriber assignments recorded for this device.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[560px] text-left text-sm">
                    <thead class="text-xs text-neutral-500 dark:text-neutral-400"><tr><th class="py-3">Subscriber</th><th>IP Address</th><th>Assigned</th><th>Status</th></tr></thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-800">
                        @foreach ($assignments as $assignment)
                            <tr>
                                <td class="py-3 pr-3 font-medium">{{ $assignment->customer->customer_code }} — {{ $assignment->customer->first_name }} {{ $assignment->customer->last_name }}</td>
                                <td class="pr-3 font-mono text-xs">{{ $assignment->ip_address ?? 'Not recorded' }}</td>
                                <td class="pr-3 text-xs">{{ $assignment->assigned_at->format('M d, Y') }}</td>
                                <td><span class="border border-neutral-300 px-2 py-1 text-xs dark:border-neutral-700">{{ $assignment->released_at ? 'Retrieved' : 'Active' }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($assignments->count() >= 50)
                <p class="text-xs text-neutral-500">Showing 50 most recent assignments.</p>
            @endif
        @endif
    </section>

    <div class="grid gap-4 lg:grid-cols-[minmax(0,2fr)_minmax(16rem,1fr)]">
        <section class="border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900" aria-labelledby="details-heading">
            <h2 id="details-heading" class="mb-4 border-b border-neutral-200 pb-3 text-sm font-semibold dark:border-neutral-800">Equipment Details</h2>
            <dl class="grid gap-x-5 gap-y-4 sm:grid-cols-2">
                <div><dt class="text-xs text-neutral-500 dark:text-neutral-400">Asset Tag</dt><dd class="mt-1 break-words text-sm font-medium">{{ $equipmentAsset->asset_tag }}</dd></div>
                <div><dt class="text-xs text-neutral-500 dark:text-neutral-400">Serial Number</dt><dd class="mt-1 break-words font-mono text-sm">{{ $equipmentAsset->serial_number ?? 'Not recorded' }}</dd></div>
                <div><dt class="text-xs text-neutral-500 dark:text-neutral-400">Category</dt><dd class="mt-1 text-sm">{{ \App\Models\EquipmentAsset::CATEGORIES[$equipmentAsset->category] ?? $equipmentAsset->category }}</dd></div>
                <div><dt class="text-xs text-neutral-500 dark:text-neutral-400">Status</dt><dd class="mt-1 text-sm">{{ \App\Models\EquipmentAsset::STATUSES[$equipmentAsset->status] ?? $equipmentAsset->status }}</dd></div>
                <div><dt class="text-xs text-neutral-500 dark:text-neutral-400">Brand</dt><dd class="mt-1 text-sm">{{ $equipmentAsset->brand ?? 'Not recorded' }}</dd></div>
                <div><dt class="text-xs text-neutral-500 dark:text-neutral-400">Model</dt><dd class="mt-1 text-sm">{{ $equipmentAsset->model ?? 'Not recorded' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs text-neutral-500 dark:text-neutral-400">Notes</dt><dd class="mt-1 whitespace-pre-wrap break-words text-sm">{{ $equipmentAsset->notes ?? 'None' }}</dd></div>
                <div><dt class="text-xs text-neutral-500 dark:text-neutral-400">Registered</dt><dd class="mt-1 text-sm">{{ $equipmentAsset->created_at->format('M d, Y h:i A') }}</dd></div>
                <div><dt class="text-xs text-neutral-500 dark:text-neutral-400">Last Updated</dt><dd class="mt-1 text-sm">{{ $equipmentAsset->updated_at->format('M d, Y h:i A') }}</dd></div>
            </dl>
        </section>

        <section class="border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900" aria-labelledby="history-heading">
            <h2 id="history-heading" class="mb-4 border-b border-neutral-200 pb-3 text-sm font-semibold dark:border-neutral-800">Activity History</h2>
            @forelse ($activities as $activity)
                <div class="border-b border-neutral-100 py-3 last:border-0 dark:border-neutral-800">
                    <p class="text-sm font-medium">{{ $activity->description }}</p>
                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">{{ $activity->actor_name ?? 'System' }} · {{ \Illuminate\Support\Carbon::parse($activity->created_at)->format('M d, Y h:i A') }}</p>
                </div>
            @empty
                <p class="text-sm text-neutral-500 dark:text-neutral-400">No activity recorded yet.</p>
            @endforelse
            @if ($activities->count() >= 20)
                <p class="mt-3 text-xs text-neutral-500 dark:text-neutral-400">Showing the 20 most recent events.</p>
            @endif
        </section>
    </div>
</div>
@endsection
