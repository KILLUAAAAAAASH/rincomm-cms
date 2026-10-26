@extends('layouts.app')

@section('title', 'Edit Hardware Asset | Rincomm CMS')
@section('page-title', 'Edit Hardware Asset')

@section('content')
<div class="mx-auto max-w-4xl space-y-4">
    <div><a class="text-sm font-medium text-[#008080] hover:underline dark:text-teal-300" href="{{ route('admin.equipment-assets.show', $equipmentAsset) }}">← {{ $equipmentAsset->asset_tag }}</a><h1 class="mt-2 text-xl font-semibold">Edit Hardware Asset</h1><p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">Updates will be recorded in the activity history.</p></div>
    <div class="border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900 sm:p-5">
        @include('admin.equipment-assets._form')
    </div>
</div>
@endsection
