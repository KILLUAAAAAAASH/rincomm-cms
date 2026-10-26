@extends('layouts.app')

@section('title', 'Subscriber Network Account')
@section('page-title', 'Subscriber Network Account')

@section('secondary-navigation')
<a href="{{ route('admin.subscribers.show', $subscriber) }}"
   class="inline-flex min-h-10 items-center gap-2 text-sm font-medium text-[#008080] hover:underline dark:text-[#5EEAD4]">
    <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
    Subscriber Details
</a>
@endsection

@section('content')
<div class="mx-auto max-w-3xl space-y-4">
    <header class="rounded-md border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
        <div class="flex items-start gap-3">
            <i data-lucide="network" class="mt-0.5 h-6 w-6 text-[#008080] dark:text-[#5EEAD4]" aria-hidden="true"></i>
            <div>
                <h1 class="text-lg font-semibold text-neutral-900 dark:text-white">PPPoE Network Account</h1>
                <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                    Subscriber {{ $subscriber->customer_code }} &mdash; {{ $subscriber->first_name }} {{ $subscriber->last_name }}
                </p>
                <p class="mt-2 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                    This associates the subscriber with a PPPoE username on the primary MikroTik router.
                    It does not create a router account, change network access, or report live connectivity yet.
                </p>
            </div>
        </div>
    </header>

    @if (session('success'))
        <p role="status" class="rounded-md border border-[#008080]/30 bg-[#008080]/5 px-4 py-3 text-sm text-[#006666] dark:text-[#5EEAD4]">
            {{ session('success') }}
        </p>
    @endif

    @if ($errors->any())
        <div role="alert" class="rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200">
            Please check the PPPoE username and try again.
        </div>
    @endif

    <section class="rounded-md border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-semibold text-neutral-900 dark:text-white">Primary Router Mapping</h2>
            <span class="rounded-md border border-neutral-200 px-2 py-1 text-xs text-neutral-600 dark:border-neutral-700 dark:text-neutral-300">
                {{ $networkAccount ? 'Assigned' : 'Not assigned' }}
            </span>
        </div>

        <form method="POST" action="{{ route('admin.subscribers.network-account.update', $subscriber) }}" class="space-y-4">
            @csrf
            @method('PATCH')
            <div>
                <label for="pppoe_username" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">
                    PPPoE Username <span class="text-red-600">*</span>
                </label>
                <input id="pppoe_username" name="pppoe_username" type="text" required maxlength="120"
                       autocomplete="off" spellcheck="false" autocapitalize="off"
                       value="{{ old('pppoe_username', $networkAccount?->pppoe_username) }}"
                       aria-invalid="{{ $errors->has('pppoe_username') ? 'true' : 'false' }}"
                       class="mt-1.5 block min-h-10 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 focus:border-[#008080] focus:outline-none focus:ring-2 focus:ring-[#008080]/20 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white" />
                @error('pppoe_username')
                    <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                <p class="mt-1.5 text-xs text-neutral-500 dark:text-neutral-400">
                    Enter the exact PPPoE login configured by the network administrator. Do not enter a password.
                </p>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-md bg-[#008080] px-4 py-2 text-sm font-semibold text-white hover:bg-[#006666] focus:outline-none focus:ring-2 focus:ring-[#008080]/30">
                    Save Network Mapping
                </button>
            </div>
        </form>

        @if ($networkAccount)
            <div class="mt-4 border-t border-neutral-200 pt-4 dark:border-neutral-800">
                <form method="POST" action="{{ route('admin.subscribers.network-account.destroy', $subscriber) }}"
                      onsubmit="return confirm('Remove this network mapping? This will not disconnect the subscriber from the router.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex min-h-10 items-center rounded-md border border-red-300 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-900 dark:text-red-300 dark:hover:bg-red-950/30">
                        Remove Mapping
                    </button>
                </form>
            </div>
        @endif
    </section>
</div>
@endsection
