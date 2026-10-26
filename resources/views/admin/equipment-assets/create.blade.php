@extends('layouts.app')

@section('title', 'Register Equipment | Rincomm CMS')
@section('page-title', 'Register Equipment')

@section('content')
<div class="mx-auto max-w-4xl space-y-4">
    <div><a class="text-sm font-medium text-[#008080] hover:underline dark:text-teal-300" href="{{ route('admin.equipment-assets.index') }}">← Hardware Assets</a><h1 class="mt-2 text-xl font-semibold">Register Equipment</h1><p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">Record one physical device per asset tag. Customer assignments are handled separately.</p></div>
    <div class="border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900 sm:p-5">
        @include('admin.equipment-assets._form')
    </div>
</div>
@endsection
