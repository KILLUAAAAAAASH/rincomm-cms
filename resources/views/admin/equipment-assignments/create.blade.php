@extends('layouts.app')

@section('title', 'Assign Customer Equipment | Rincomm CMS')
@section('page-title', 'Assign Customer Equipment')

@section('content')
<div class="mx-auto max-w-4xl space-y-4">
    <div>
        <a class="text-sm font-medium text-[#008080] hover:underline dark:text-teal-300" href="{{ route('admin.equipment-assignments.index') }}">← Customer Equipment</a>
        <h1 class="mt-2 text-xl font-semibold">Assign Hardware to Subscriber</h1>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Only in-stock devices with recorded unique serial numbers can be assigned. Their inventory status changes automatically to Deployed.</p>
    </div>

    <form method="GET" action="{{ route('admin.equipment-assignments.create') }}" class="space-y-3 border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-900" role="search">
        <h2 class="text-sm font-semibold">Find a subscriber or hardware asset</h2>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label for="customer_search" class="mb-1 block text-xs font-semibold">Subscriber name or code</label>
                <input id="customer_search" type="search" name="customer_search" maxlength="120" value="{{ $filters['customer_search'] ?? '' }}" class="min-h-10 w-full border border-neutral-300 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-950" placeholder="Search active subscribers">
            </div>
            <div>
                <label for="asset_search" class="mb-1 block text-xs font-semibold">Asset tag, name or serial</label>
                <input id="asset_search" type="search" name="asset_search" maxlength="120" value="{{ $filters['asset_search'] ?? '' }}" class="min-h-10 w-full border border-neutral-300 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-950" placeholder="Search available equipment">
            </div>
        </div>
        @if (! empty($filters['customer_id'])) <input type="hidden" name="customer_id" value="{{ $filters['customer_id'] }}"> @endif
        @if (! empty($filters['asset_id'])) <input type="hidden" name="asset_id" value="{{ $filters['asset_id'] }}"> @endif
        <button type="submit" class="min-h-10 border border-[#008080] px-4 text-sm font-semibold text-[#008080] hover:bg-[#008080]/10 dark:text-teal-300">Find Matches</button>
        <p class="text-xs text-neutral-500 dark:text-neutral-400">Showing up to 40 matching subscribers and 40 available devices. Refine your search if the record is missing.</p>
    </form>

    <section class="border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
        <h2 class="mb-4 border-b border-neutral-200 pb-3 text-sm font-semibold dark:border-neutral-800">Assignment Details</h2>
        @if ($errors->any())
            <div class="mb-4 border border-red-300 bg-red-50 p-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200" role="alert">
                Please correct the highlighted fields before continuing.
            </div>
        @endif
        <form method="POST" action="{{ route('admin.equipment-assignments.store') }}" data-lock-submit class="space-y-4">
            @csrf
            <div>
                <label for="customer_id" class="mb-1 block text-sm font-semibold">Subscriber <span aria-hidden="true">*</span></label>
                <select id="customer_id" name="customer_id" required class="min-h-10 w-full border border-neutral-300 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-950" aria-invalid="{{ $errors->has('customer_id') ? 'true' : 'false' }}">
                    <option value="">Select an active subscriber</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) old('customer_id', $filters['customer_id'] ?? '') === (string) $customer->id)>{{ $customer->customer_code }} — {{ $customer->first_name }} {{ $customer->last_name }}</option>
                    @endforeach
                </select>
                @error('customer_id') <p class="mt-1 text-xs text-red-700 dark:text-red-400" role="alert">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="equipment_asset_id" class="mb-1 block text-sm font-semibold">Available Serialized Equipment <span aria-hidden="true">*</span></label>
                <select id="equipment_asset_id" name="equipment_asset_id" required class="min-h-10 w-full border border-neutral-300 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-950" aria-invalid="{{ $errors->has('equipment_asset_id') ? 'true' : 'false' }}">
                    <option value="">Select in-stock hardware</option>
                    @foreach ($assets as $asset)
                        <option value="{{ $asset->id }}" @selected((string) old('equipment_asset_id', $filters['asset_id'] ?? '') === (string) $asset->id)>{{ $asset->asset_tag }} — {{ $asset->name }} (SN {{ $asset->serial_number }})</option>
                    @endforeach
                </select>
                @error('equipment_asset_id') <p class="mt-1 text-xs text-red-700 dark:text-red-400" role="alert">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="ip_address" class="mb-1 block text-sm font-semibold">Equipment IP Address (when applicable)</label>
                <input id="ip_address" type="text" name="ip_address" maxlength="45" autocomplete="off" value="{{ old('ip_address') }}" class="min-h-10 w-full border border-neutral-300 bg-white px-3 font-mono text-sm dark:border-neutral-700 dark:bg-neutral-950" placeholder="e.g. 192.168.1.1 or IPv6">
                <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Private LAN addresses can legitimately repeat across different subscriber networks. This field is not globally unique.</p>
                @error('ip_address') <p class="mt-1 text-xs text-red-700 dark:text-red-400" role="alert">{{ $message }}</p> @enderror
            </div>
            <div class="flex flex-wrap gap-3 border-t border-neutral-200 pt-4 dark:border-neutral-800">
                <button type="submit" class="min-h-10 bg-[#008080] px-5 text-sm font-semibold text-white hover:bg-[#006666]">Save Equipment Assignment</button>
                <a href="{{ route('admin.equipment-assignments.index') }}" class="inline-flex min-h-10 items-center border border-neutral-300 px-4 text-sm dark:border-neutral-700">Cancel</a>
            </div>
        </form>
    </section>
</div>
@endsection
