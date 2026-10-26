@extends('layouts.app')

@section('title', 'Equipment Retrieval | Rincomm CMS')
@section('page-title', 'Equipment Retrieval')

@section('content')
<div class="mx-auto max-w-7xl space-y-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold">Equipment Retrieval</h1>
            <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">Record confirmed physical returns, equipment condition, and associated field work. Historical assignments remain intact.</p>
        </div>
        <a href="{{ route('admin.equipment-retrievals.create') }}" class="inline-flex min-h-10 items-center bg-[#008080] px-4 py-2 text-sm font-semibold text-white hover:bg-[#006666]">Record Retrieval</a>
    </div>
    @if (session('success'))
        <p role="status" class="border border-teal-300 bg-teal-50 px-4 py-3 text-sm dark:border-teal-800 dark:bg-teal-950">{{ session('success') }}</p>
    @endif
    <form method="GET" action="{{ route('admin.equipment-retrievals.index') }}" role="search" class="flex flex-wrap items-end gap-3 border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
        <div class="min-w-[230px] flex-1">
            <label for="search" class="mb-1 block text-xs font-semibold">Find a retrieval</label>
            <input id="search" name="search" type="search" maxlength="120" value="{{ $filters['search'] ?? '' }}" placeholder="Subscriber, customer code, asset tag or serial" class="min-h-10 w-full border border-neutral-300 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-950">
        </div>
        <button type="submit" class="min-h-10 bg-[#008080] px-4 text-sm font-semibold text-white hover:bg-[#006666]">Search</button>
        <a href="{{ route('admin.equipment-retrievals.index') }}" class="inline-flex min-h-10 items-center border border-neutral-300 px-4 text-sm dark:border-neutral-700">Reset</a>
    </form>
    <section class="border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <p class="border-b border-neutral-200 px-4 py-3 text-sm text-neutral-500 dark:border-neutral-800">{{ number_format($retrievals->total()) }} retrieval records</p>
        @if ($retrievals->isEmpty())
            <p class="px-4 py-10 text-center text-sm text-neutral-500 dark:text-neutral-400">No equipment retrievals found. Use Record Retrieval after a device has been physically returned.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="bg-neutral-50 text-xs uppercase tracking-wide text-neutral-500 dark:bg-neutral-950"><tr>
                        <th scope="col" class="px-4 py-3">Date</th><th scope="col" class="px-4 py-3">Subscriber</th><th scope="col" class="px-4 py-3">Asset / Serial</th><th scope="col" class="px-4 py-3">Reason</th><th scope="col" class="px-4 py-3">Condition</th><th scope="col" class="px-4 py-3">Action</th>
                    </tr></thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-800">
                        @foreach ($retrievals as $retrieval)
                            <tr>
                                <td class="px-4 py-3 text-xs">{{ $retrieval->retrieved_on->format('M d, Y') }}</td>
                                <td class="px-4 py-3"><span class="font-semibold">{{ $retrieval->assignment->customer->customer_code }}</span><span class="block text-xs text-neutral-500">{{ $retrieval->assignment->customer->first_name }} {{ $retrieval->assignment->customer->last_name }}</span></td>
                                <td class="px-4 py-3"><a href="{{ route('admin.equipment-assets.show', $retrieval->assignment->equipmentAsset) }}" class="font-semibold text-[#008080] hover:underline dark:text-teal-300">{{ $retrieval->assignment->equipmentAsset->asset_tag }}</a><span class="block font-mono text-xs text-neutral-500">{{ $retrieval->assignment->equipmentAsset->serial_number }}</span></td>
                                <td class="px-4 py-3 text-xs">{{ \App\Models\EquipmentRetrieval::REASONS[$retrieval->reason] ?? $retrieval->reason }}</td>
                                <td class="px-4 py-3 text-xs">{{ \App\Models\EquipmentRetrieval::CONDITIONS[$retrieval->condition] ?? $retrieval->condition }}</td>
                                <td class="px-4 py-3"><a href="{{ route('admin.equipment-retrievals.show', $retrieval) }}" class="font-semibold text-[#008080] hover:underline dark:text-teal-300">View</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
    {{ $retrievals->links() }}
</div>
@endsection
