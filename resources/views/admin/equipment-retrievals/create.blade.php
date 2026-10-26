@extends('layouts.app')

@section('title', 'Record Equipment Retrieval | Rincomm CMS')
@section('page-title', 'Record Equipment Retrieval')

@section('content')
<div class="mx-auto max-w-5xl space-y-4">
    <div>
        <a href="{{ route('admin.equipment-retrievals.index') }}" class="text-sm font-medium text-[#008080] hover:underline dark:text-teal-300">← Equipment Retrieval</a>
        <h1 class="mt-2 text-xl font-semibold">Record Physical Equipment Retrieval</h1>
        <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">Log a device only after Rincomm has physically received it. A disconnection or completed Job Order alone does not prove retrieval.</p>
    </div>

    <section class="border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
        <h2 class="mb-3 text-sm font-semibold">1. Choose the active equipment assignment</h2>
        <form method="GET" action="{{ route('admin.equipment-retrievals.create') }}" role="search" class="flex flex-wrap items-end gap-3">
            <div class="min-w-[240px] flex-1">
                <label for="search" class="mb-1 block text-xs font-semibold">Subscriber name or code, asset tag, serial</label>
                <input type="search" id="search" name="search" maxlength="120" value="{{ $filters['search'] ?? '' }}" placeholder="Find equipment currently assigned to subscribers" class="min-h-10 w-full border border-neutral-300 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-950">
            </div>
            <button type="submit" class="min-h-10 bg-[#008080] px-4 text-sm font-semibold text-white hover:bg-[#006666]">Find Matches</button>
            <a href="{{ route('admin.equipment-retrievals.create') }}" class="inline-flex min-h-10 items-center border border-neutral-300 px-4 text-sm dark:border-neutral-700">Reset</a>
        </form>
        @if (isset($filters['assignment_id']) && ! $selected)
            <p role="alert" class="mt-3 border-l-2 border-amber-600 bg-amber-50 p-3 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-100">That assignment is no longer eligible for retrieval. Select a currently assigned, deployed device.</p>
        @endif
        <p class="my-3 text-xs text-neutral-500 dark:text-neutral-400">Showing up to 40 active assignments. Narrow your search if needed; select the correct device below.</p>
        @if ($assignments->isEmpty())
            <p role="status" class="border-l-2 border-amber-600 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-100">No active deployed equipment matches. Try another subscriber, or review <a href="{{ route('admin.equipment-assignments.index') }}" class="underline">Customer Equipment</a>.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[580px] text-left text-sm">
                    <thead class="bg-neutral-50 text-xs uppercase text-neutral-500 dark:bg-neutral-950"><tr><th class="px-3 py-2">Subscriber</th><th class="px-3 py-2">Asset</th><th class="px-3 py-2">Serial</th><th class="px-3 py-2">Action</th></tr></thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-800">
                        @foreach ($assignments as $assignment)
                            <tr class="{{ $selected?->id === $assignment->id ? 'bg-[#008080]/10 dark:bg-[#008080]/20' : '' }}">
                                <td class="px-3 py-3">{{ $assignment->customer->customer_code }} — {{ $assignment->customer->first_name }} {{ $assignment->customer->last_name }}</td>
                                <td class="px-3 py-3 font-medium">{{ $assignment->equipmentAsset->asset_tag }}</td>
                                <td class="px-3 py-3 font-mono text-xs">{{ $assignment->equipmentAsset->serial_number ?? 'Not recorded' }}</td>
                                <td class="px-3 py-3"><a class="font-semibold text-[#008080] hover:underline dark:text-teal-300" href="{{ route('admin.equipment-retrievals.create', ['assignment_id' => $assignment->id, 'search' => $filters['search'] ?? null]) }}#retrieval-form">{{ $selected?->id === $assignment->id ? 'Selected' : 'Select' }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @if ($selected)
        <section id="retrieval-form" class="border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900" aria-labelledby="retrieval-title">
            <h2 id="retrieval-title" class="mb-4 border-b border-neutral-200 pb-3 text-sm font-semibold dark:border-neutral-800">2. Confirm physical return and condition</h2>
            @if ($errors->any())
                <div role="alert" class="mb-4 border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-800 dark:bg-red-950 dark:text-red-100">Review the fields below. Nothing has been retrieved yet.</div>
            @endif
            <div class="mb-4 grid gap-3 bg-neutral-50 p-3 text-sm dark:bg-neutral-950 sm:grid-cols-2">
                <p><span class="block text-xs text-neutral-500">Subscriber</span><strong>{{ $selected->customer->customer_code }} — {{ $selected->customer->first_name }} {{ $selected->customer->last_name }}</strong></p>
                <p><span class="block text-xs text-neutral-500">Hardware</span><strong>{{ $selected->equipmentAsset->asset_tag }} — {{ $selected->equipmentAsset->name }}</strong></p>
                <p><span class="block text-xs text-neutral-500">Serial number</span><span class="font-mono">{{ $selected->equipmentAsset->serial_number ?? 'Not recorded' }}</span></p>
                <p><span class="block text-xs text-neutral-500">Assigned / IP</span>{{ $selected->assigned_at->format('M d, Y') }} · {{ $selected->ip_address ?? 'No IP recorded' }}</p>
            </div>
            <form method="POST" action="{{ route('admin.equipment-retrievals.store') }}" class="space-y-4" data-lock-submit>
                @csrf
                <input type="hidden" name="equipment_assignment_id" value="{{ $selected->id }}">
                @error('equipment_assignment_id') <p class="text-xs text-red-700 dark:text-red-400" role="alert">{{ $message }}</p> @enderror
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="retrieved_on" class="mb-1 block text-sm font-semibold">Date physically received *</label>
                        <input type="date" id="retrieved_on" name="retrieved_on" required max="{{ now()->toDateString() }}" min="{{ $selected->assigned_at->toDateString() }}" value="{{ old('retrieved_on', now()->toDateString()) }}" class="min-h-10 w-full border border-neutral-300 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-950">
                        @error('retrieved_on') <p role="alert" class="mt-1 text-xs text-red-700 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="reason" class="mb-1 block text-sm font-semibold">Retrieval reason *</label>
                        <select id="reason" name="reason" required class="min-h-10 w-full border border-neutral-300 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-950">
                            <option value="">Select a reason</option>
                            @foreach (\App\Models\EquipmentRetrieval::REASONS as $value => $label)
                                <option value="{{ $value }}" @selected(old('reason') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('reason') <p role="alert" class="mt-1 text-xs text-red-700 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label for="condition" class="mb-1 block text-sm font-semibold">Verified returned-device condition *</label>
                    <select id="condition" name="condition" required class="min-h-10 w-full border border-neutral-300 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-950">
                        <option value="">Select physical condition</option>
                        @foreach (\App\Models\EquipmentRetrieval::CONDITIONS as $value => $label)
                            <option value="{{ $value }}" @selected(old('condition') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Only tested usable devices return to In Stock. Damaged or untested devices stay Under Maintenance.</p>
                    @error('condition') <p role="alert" class="mt-1 text-xs text-red-700 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="job_order_id" class="mb-1 block text-sm font-semibold">Related completed Job Order (optional)</label>
                    <select id="job_order_id" name="job_order_id" class="min-h-10 w-full border border-neutral-300 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-950">
                        <option value="">No Job Order reference</option>
                        @foreach ($jobOrders as $job)
                            <option value="{{ $job->id }}" @selected((string) old('job_order_id') === (string) $job->id)>{{ $job->job_order_number }} — {{ \Illuminate\Support\Str::headline($job->job_type) }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Only completed Job Orders for this subscriber are offered. Job completion alone does not release equipment.</p>
                    @error('job_order_id') <p role="alert" class="mt-1 text-xs text-red-700 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="remarks" class="mb-1 block text-sm font-semibold">Remarks / return evidence notes</label>
                    <textarea id="remarks" name="remarks" rows="3" maxlength="1000" class="w-full border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-950" placeholder="Who returned the equipment, visual check, condition details...">{{ old('remarks') }}</textarea>
                    @error('remarks') <p role="alert" class="mt-1 text-xs text-red-700 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
                <div class="border-l-2 border-amber-600 bg-amber-50 p-3 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-100">Confirm only after the physical device is in Rincomm's custody. This closes the assignment and updates equipment availability. Lost or unreturned equipment must remain assigned; do not falsely log a retrieval.</div>
                <div class="flex flex-wrap gap-3 border-t border-neutral-200 pt-4 dark:border-neutral-800">
                    <button type="submit" class="min-h-10 bg-[#008080] px-5 text-sm font-semibold text-white hover:bg-[#006666]">Confirm Equipment Retrieval</button>
                    <a href="{{ route('admin.equipment-retrievals.index') }}" class="inline-flex min-h-10 items-center border border-neutral-300 px-4 text-sm dark:border-neutral-700">Cancel</a>
                </div>
            </form>
        </section>
    @endif
</div>
@endsection
