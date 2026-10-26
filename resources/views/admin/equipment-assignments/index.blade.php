@extends('layouts.app')

@section('title', 'Customer Equipment | Rincomm CMS')
@section('page-title', 'Customer Equipment')

@section('content')
<div class="mx-auto max-w-7xl space-y-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold">Customer Equipment</h1>
            <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">See assigned hardware, IP addresses, and equipment assignment history.</p>
        </div>
        <a href="{{ route('admin.equipment-assignments.create') }}" class="inline-flex min-h-10 items-center bg-[#008080] px-4 py-2 text-sm font-semibold text-white hover:bg-[#006666]">Assign Equipment</a>
    </div>

    <form method="GET" action="{{ route('admin.equipment-assignments.index') }}" role="search" class="flex flex-wrap items-end gap-3 border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
        <div class="min-w-[230px] flex-1">
            <label for="search" class="mb-1 block text-xs font-semibold">Search subscriber, serial, asset tag or IP</label>
            <input id="search" name="search" type="search" maxlength="120" value="{{ $filters['search'] ?? '' }}" class="min-h-10 w-full border border-neutral-300 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-950" placeholder="Subscriber name, code, IP, asset or serial">
        </div>
        <div>
            <label for="state" class="mb-1 block text-xs font-semibold">Assignment state</label>
            <select id="state" name="state" class="min-h-10 border border-neutral-300 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-950">
                <option value="">All assignments</option>
                <option value="active" @selected(($filters['state'] ?? '') === 'active')>Currently assigned</option>
                <option value="history" @selected(($filters['state'] ?? '') === 'history')>Retrieved / history</option>
            </select>
        </div>
        <button type="submit" class="min-h-10 bg-[#008080] px-4 text-sm font-semibold text-white hover:bg-[#006666]">Search</button>
        <a class="inline-flex min-h-10 items-center border border-neutral-300 px-3 text-sm dark:border-neutral-700" href="{{ route('admin.equipment-assignments.index') }}">Reset</a>
    </form>

    <div class="border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <p class="border-b border-neutral-200 px-4 py-3 text-sm text-neutral-500 dark:border-neutral-800">{{ number_format($assignments->total()) }} assignment records</p>
        @if ($assignments->isEmpty())
            <p class="px-4 py-10 text-center text-sm text-neutral-500 dark:text-neutral-400">No customer equipment assignments found. Select “Assign Equipment” to bind a device.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[850px] text-left text-sm">
                    <thead class="bg-neutral-50 text-xs uppercase tracking-wide text-neutral-500 dark:bg-neutral-950"><tr>
                        <th scope="col" class="px-4 py-3">Subscriber</th><th scope="col" class="px-4 py-3">Equipment</th><th scope="col" class="px-4 py-3">Serial Number</th><th scope="col" class="px-4 py-3">IP Address</th><th scope="col" class="px-4 py-3">Date</th><th scope="col" class="px-4 py-3">State</th>
                    </tr></thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-800">
                        @foreach ($assignments as $assignment)
                            <tr>
                                <td class="px-4 py-3"><div class="font-semibold">{{ $assignment->customer->customer_code }}</div><div class="text-xs text-neutral-500">{{ $assignment->customer->first_name }} {{ $assignment->customer->last_name }}</div></td>
                                <td class="px-4 py-3"><a href="{{ route('admin.equipment-assets.show', $assignment->equipmentAsset) }}" class="font-medium text-[#008080] hover:underline dark:text-teal-300">{{ $assignment->equipmentAsset->asset_tag }}</a><div class="text-xs text-neutral-500">{{ $assignment->equipmentAsset->name }}</div></td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $assignment->equipmentAsset->serial_number }}</td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $assignment->ip_address ?? 'Not recorded' }}</td>
                                <td class="px-4 py-3 text-xs">{{ $assignment->assigned_at->format('M d, Y') }}</td>
                                <td class="px-4 py-3"><span class="border border-neutral-300 px-2 py-1 text-xs dark:border-neutral-700">{{ $assignment->released_at ? 'Retrieved' : 'Assigned' }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    {{ $assignments->links() }}
</div>
@endsection
