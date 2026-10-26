@extends('layouts.app')

@section('title', 'Hardware Assets | Rincomm CMS')
@section('page-title', 'Hardware Assets')

@section('content')
<div class="mx-auto max-w-7xl space-y-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-neutral-900 dark:text-white">Hardware Assets</h1>
            <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">Register, find, and maintain individual network equipment records.</p>
        </div>
        <a href="{{ route('admin.equipment-assets.create') }}"
           class="inline-flex min-h-10 items-center justify-center bg-[#008080] px-4 py-2 text-sm font-semibold text-white hover:bg-[#006666] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#008080] focus-visible:ring-offset-2">
            Add Equipment
        </a>
    </div>

    @if (session('success'))
        <div role="status" class="border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">{{ session('success') }}</div>
    @endif

    <form action="{{ route('admin.equipment-assets.index') }}" method="GET" class="grid grid-cols-1 gap-3 border border-neutral-200 bg-white p-3 dark:border-neutral-800 dark:bg-neutral-900 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_12rem_12rem_auto]" role="search">
        <div>
            <label for="asset-search" class="mb-1 block text-xs font-semibold text-neutral-700 dark:text-neutral-200">Search equipment</label>
            <input id="asset-search" name="search" type="search" maxlength="120" value="{{ $filters['search'] ?? '' }}" placeholder="Tag, serial number, name, brand or model" class="min-h-10 w-full border border-neutral-300 bg-white px-3 text-sm text-neutral-900 focus:border-[#008080] focus:outline-none focus:ring-1 focus:ring-[#008080] dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100">
        </div>
        <div>
            <label for="asset-status" class="mb-1 block text-xs font-semibold text-neutral-700 dark:text-neutral-200">Status</label>
            <select id="asset-status" name="status" class="min-h-10 w-full border border-neutral-300 bg-white px-3 text-sm text-neutral-900 focus:border-[#008080] dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100">
                <option value="">All statuses</option>
                @foreach (\App\Models\EquipmentAsset::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="asset-category" class="mb-1 block text-xs font-semibold text-neutral-700 dark:text-neutral-200">Category</label>
            <select id="asset-category" name="category" class="min-h-10 w-full border border-neutral-300 bg-white px-3 text-sm text-neutral-900 focus:border-[#008080] dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100">
                <option value="">All categories</option>
                @foreach (\App\Models\EquipmentAsset::CATEGORIES as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['category'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button class="min-h-10 border border-[#008080] bg-[#008080] px-4 text-sm font-semibold text-white hover:bg-[#006666]" type="submit">Apply</button>
            <a href="{{ route('admin.equipment-assets.index') }}" class="inline-flex min-h-10 items-center border border-neutral-300 px-3 text-sm text-neutral-700 hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800">Reset</a>
        </div>
    </form>

    <div class="border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <div class="border-b border-neutral-200 px-4 py-3 text-sm text-neutral-600 dark:border-neutral-800 dark:text-neutral-400">{{ number_format($assets->total()) }} equipment {{ \Illuminate\Support\Str::plural('record', $assets->total()) }}</div>

        @if ($assets->isEmpty())
            <div class="px-4 py-12 text-center">
                <p class="text-sm font-semibold text-neutral-800 dark:text-neutral-100">No hardware assets found</p>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Change your filters or register your first equipment record.</p>
            </div>
        @else
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[850px] text-left text-sm">
                    <thead class="bg-neutral-50 text-xs uppercase tracking-wide text-neutral-600 dark:bg-neutral-950 dark:text-neutral-400">
                        <tr>
                            <th scope="col" class="px-4 py-3">Asset tag</th>
                            <th scope="col" class="px-4 py-3">Equipment</th>
                            <th scope="col" class="px-4 py-3">Serial number</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                            <th scope="col" class="px-4 py-3">Updated</th>
                            <th scope="col" class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-800">
                        @foreach ($assets as $asset)
                            <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-800/40">
                                <td class="px-4 py-3 font-semibold"><a class="text-[#008080] hover:underline dark:text-teal-300" href="{{ route('admin.equipment-assets.show', $asset) }}">{{ $asset->asset_tag }}</a></td>
                                <td class="px-4 py-3"><div class="font-medium">{{ $asset->name }}</div><div class="text-xs text-neutral-500 dark:text-neutral-400">{{ \App\Models\EquipmentAsset::CATEGORIES[$asset->category] ?? $asset->category }}{{ $asset->brand ? ' · '.$asset->brand : '' }}{{ $asset->model ? ' · '.$asset->model : '' }}</div></td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $asset->serial_number ?? 'Not recorded' }}</td>
                                <td class="px-4 py-3"><span class="inline-flex border border-neutral-300 px-2 py-1 text-xs font-medium dark:border-neutral-700">{{ \App\Models\EquipmentAsset::STATUSES[$asset->status] ?? $asset->status }}</span></td>
                                <td class="px-4 py-3 text-xs text-neutral-500 dark:text-neutral-400">{{ $asset->updated_at->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-right"><a class="font-medium text-[#008080] hover:underline dark:text-teal-300" href="{{ route('admin.equipment-assets.show', $asset) }}">View</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-neutral-200 dark:divide-neutral-800 md:hidden">
                @foreach ($assets as $asset)
                    <a href="{{ route('admin.equipment-assets.show', $asset) }}" class="block space-y-1 px-4 py-3 hover:bg-neutral-50 dark:hover:bg-neutral-800/40">
                        <div class="flex items-start justify-between gap-2"><span class="font-semibold text-[#008080] dark:text-teal-300">{{ $asset->asset_tag }}</span><span class="border border-neutral-300 px-2 py-0.5 text-xs dark:border-neutral-700">{{ \App\Models\EquipmentAsset::STATUSES[$asset->status] ?? $asset->status }}</span></div>
                        <div class="text-sm font-medium">{{ $asset->name }}</div>
                        <div class="text-xs text-neutral-500 dark:text-neutral-400">{{ \App\Models\EquipmentAsset::CATEGORIES[$asset->category] ?? $asset->category }} · SN: {{ $asset->serial_number ?? 'Not recorded' }}</div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
    {{ $assets->links() }}
</div>
@endsection
