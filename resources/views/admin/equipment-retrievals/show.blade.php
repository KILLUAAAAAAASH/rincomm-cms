@extends('layouts.app')

@section('title', 'Equipment Retrieval Record | Rincomm CMS')
@section('page-title', 'Equipment Retrieval Details')

@section('content')
@php
    $assignment = $equipmentRetrieval->assignment;
    $asset = $assignment->equipmentAsset;
    $customer = $assignment->customer;
@endphp
<div class="mx-auto max-w-5xl space-y-4">
    <div>
        <a href="{{ route('admin.equipment-retrievals.index') }}" class="text-sm font-medium text-[#008080] hover:underline dark:text-teal-300">← Equipment Retrieval</a>
        <h1 class="mt-2 text-xl font-semibold">Retrieval #{{ $equipmentRetrieval->id }}</h1>
        <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">Confirmed return record. Original subscriber assignment and IP history have been preserved.</p>
    </div>
    @if (session('success'))
        <p role="status" class="border border-teal-300 bg-teal-50 px-4 py-3 text-sm dark:border-teal-800 dark:bg-teal-950">{{ session('success') }}</p>
    @endif
    <section class="border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
        <h2 class="border-b border-neutral-200 pb-3 text-sm font-semibold dark:border-neutral-800">Retrieved device and subscriber</h2>
        <dl class="mt-4 grid gap-4 sm:grid-cols-2">
            <div><dt class="text-xs text-neutral-500">Subscriber</dt><dd class="mt-1 text-sm font-medium">{{ $customer->customer_code }} — {{ $customer->first_name }} {{ $customer->last_name }}</dd></div>
            <div><dt class="text-xs text-neutral-500">Equipment</dt><dd class="mt-1 text-sm"><a class="font-semibold text-[#008080] hover:underline dark:text-teal-300" href="{{ route('admin.equipment-assets.show', $asset) }}">{{ $asset->asset_tag }} — {{ $asset->name }}</a></dd></div>
            <div><dt class="text-xs text-neutral-500">Serial number</dt><dd class="mt-1 font-mono text-sm">{{ $asset->serial_number ?? 'Not recorded' }}</dd></div>
            <div><dt class="text-xs text-neutral-500">Previously assigned IP</dt><dd class="mt-1 font-mono text-sm">{{ $assignment->ip_address ?? 'Not recorded' }}</dd></div>
            <div><dt class="text-xs text-neutral-500">Assigned on</dt><dd class="mt-1 text-sm">{{ $assignment->assigned_at->format('M d, Y') }}</dd></div>
            <div><dt class="text-xs text-neutral-500">Physically retrieved on</dt><dd class="mt-1 text-sm">{{ $equipmentRetrieval->retrieved_on->format('M d, Y') }}</dd></div>
            <div><dt class="text-xs text-neutral-500">Reason</dt><dd class="mt-1 text-sm">{{ \App\Models\EquipmentRetrieval::REASONS[$equipmentRetrieval->reason] ?? $equipmentRetrieval->reason }}</dd></div>
            <div><dt class="text-xs text-neutral-500">Condition at return</dt><dd class="mt-1 text-sm">{{ \App\Models\EquipmentRetrieval::CONDITIONS[$equipmentRetrieval->condition] ?? $equipmentRetrieval->condition }}</dd></div>
            <div><dt class="text-xs text-neutral-500">Processed by</dt><dd class="mt-1 text-sm">{{ $equipmentRetrieval->retrievedBy?->name ?? 'Former staff member / system' }}</dd></div>
            <div><dt class="text-xs text-neutral-500">Related Job Order</dt><dd class="mt-1 text-sm">
                @if ($equipmentRetrieval->jobOrder)
                    <a href="{{ route('admin.job-orders.show', $equipmentRetrieval->jobOrder) }}" class="font-semibold text-[#008080] hover:underline dark:text-teal-300">{{ $equipmentRetrieval->jobOrder->job_order_number }}</a>
                @else
                    None recorded
                @endif
            </dd></div>
            <div class="sm:col-span-2"><dt class="text-xs text-neutral-500">Remarks</dt><dd class="mt-1 whitespace-pre-wrap break-words text-sm">{{ $equipmentRetrieval->remarks ?? 'No remarks recorded' }}</dd></div>
        </dl>
        <p class="mt-4 border-t border-neutral-200 pt-3 text-xs text-neutral-500 dark:border-neutral-800">The asset's current inventory status may change after this retrieval (for example, after a new assignment). The condition shown above is the preserved return-time record.</p>
    </section>
</div>
@endsection
