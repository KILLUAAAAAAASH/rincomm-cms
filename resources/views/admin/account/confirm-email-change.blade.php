@extends('layouts.app')
@section('title', 'Confirm Administrator Email Change')
@section('content')
<div class="mx-auto max-w-2xl px-4 py-6 sm:px-6 sm:py-8">
    <nav class="mb-4 text-sm text-neutral-500 dark:text-neutral-400">My Profile &rarr; Verify Email</nav>
    <section class="border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900 sm:p-6">
        <h1 class="text-2xl font-semibold">Confirm your new administrator email</h1>
        <p class="mt-3 text-sm text-neutral-600 dark:text-neutral-300">You opened the one-time link delivered to the new inbox. To apply this change, enter your existing administrator password again. You will be signed out and must sign in using the new address.</p>
        <dl class="mt-5 space-y-2 text-sm">
            <div><dt class="font-semibold">Current email</dt><dd class="break-all">{{ $pending->original_email }}</dd></div>
            <div><dt class="font-semibold">New email</dt><dd class="break-all">{{ $pending->requested_email }}</dd></div>
        </dl>
        <form method="POST" action="{{ route('admin.email-change.apply', ['change' => $change, 'token' => $token]) }}" class="mt-6 space-y-4" data-lock-submit>
            @csrf
            <div>
                <label for="verify_password" class="mb-2 block text-sm font-medium">Current administrator password</label>
                <input id="verify_password" name="current_password" type="password" autocomplete="current-password" required @error('current_password') aria-invalid="true" @enderror class="min-h-11 w-full border bg-white px-3 py-2.5 text-base dark:bg-neutral-950 dark:text-neutral-100 @error('current_password') border-red-500 ring-2 ring-red-500/20 @else border-neutral-300 dark:border-neutral-700 @enderror">
                @error('current_password')<p role="alert" class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="min-h-11 bg-[#008080] px-5 py-2.5 font-semibold text-white hover:bg-[#006666]">Confirm and update login email</button>
            <a href="{{ route('profile.edit') }}" class="ml-3 text-sm text-[#008080] hover:underline">Cancel</a>
        </form>
    </section>
</div>
@endsection
