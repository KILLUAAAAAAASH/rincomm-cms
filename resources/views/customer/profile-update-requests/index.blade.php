<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Profile Update Requests - Rincomm</title>
    <script>
        (() => {
            const theme = localStorage.getItem('rincomm-theme') ??
                (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.classList.toggle('dark', theme === 'dark');
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-neutral-100 text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100">
    <main class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Profile update requests</h1>
                <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">Submit a change to your subscriber contact or billing details for Rincomm staff to review.</p>
            </div>
            <a href="{{ route('profile.edit') }}" class="rounded bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">Back to profile</a>
        </div>

        @if (session('success'))
            <p role="status" class="border border-green-300 bg-green-50 p-3 text-sm text-green-900 dark:border-green-800 dark:bg-green-950 dark:text-green-200">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <div role="alert" class="border border-red-300 bg-red-50 p-3 text-sm text-red-900 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                <strong>Please correct the following:</strong>
                <ul class="ml-5 mt-2 list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @if (!$customer)
            <section class="border border-amber-300 bg-amber-50 p-5 dark:border-amber-800 dark:bg-neutral-900">
                Your subscriber profile has not been approved or created yet. You can request changes after your service application is processed.
            </section>
        @else
            @php($pending = $requests->contains('status', 'pending'))
            <section class="border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900 sm:p-6">
                <h2 class="text-lg font-semibold">Request updated details</h2>
                <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">Submitting a request does not change your subscriber record. Installation address changes use the relocation process; login email is not changed here.</p>
                @if ($pending)
                    <p class="mt-4 border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">A request is already pending. Staff must review it before you can submit another.</p>
                @else
                    <form method="POST" action="{{ route('customer.profile-update-requests.store') }}" class="mt-5 grid gap-4 sm:grid-cols-2">
                        @csrf
                        @foreach (['phone' => 'Contact phone', 'address' => 'Address', 'city' => 'City / municipality', 'province' => 'Province', 'postal_code' => 'Postal code', 'billing_address' => 'Billing address'] as $field => $label)
                            <div class="space-y-1 {{ $field === 'address' || $field === 'billing_address' ? 'sm:col-span-2' : '' }}">
                                <label class="block text-sm font-medium" for="{{ $field }}">{{ $label }}</label>
                                <input id="{{ $field }}" name="{{ $field }}" type="text" maxlength="255" value="{{ old($field, $customer->{$field}) }}" {{ $field === 'address' ? 'required' : '' }} class="min-h-11 w-full rounded border border-neutral-300 bg-white px-3 text-base text-neutral-900 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100">
                                @error($field)<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                        <div class="space-y-1 sm:col-span-2">
                            <label for="reason" class="block text-sm font-medium">Reason for change (optional)</label>
                            <textarea id="reason" name="reason" maxlength="500" rows="3" class="w-full rounded border border-neutral-300 bg-white px-3 py-2 dark:border-neutral-700 dark:bg-neutral-950">{{ old('reason') }}</textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <button type="submit" class="rounded bg-teal-700 px-5 py-3 font-semibold text-white hover:bg-teal-800">Submit for staff review</button>
                        </div>
                    </form>
                @endif
            </section>
            <section class="border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900 sm:p-6">
                <h2 class="text-lg font-semibold">Request history</h2>
                @forelse ($requests as $profileRequest)
                    <article class="mt-4 space-y-2 border-t border-neutral-200 pt-4 dark:border-neutral-700">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <strong>Request #{{ $profileRequest->id }}</strong>
                            <span class="text-sm font-semibold">{{ ucfirst($profileRequest->status) }}</span>
                        </div>
                        <p class="text-sm text-neutral-600 dark:text-neutral-400">Submitted {{ $profileRequest->created_at->format('Y-m-d H:i') }}</p>
                        @if ($profileRequest->reason)<p class="text-sm">Reason: {{ $profileRequest->reason }}</p>@endif
                        <dl class="grid gap-2 text-sm sm:grid-cols-2">
                            @foreach ($profileRequest->requested_values as $field => $value)
                                @if (($profileRequest->current_values[$field] ?? null) !== $value)
                                    <div><dt class="font-medium">{{ ucwords(str_replace('_', ' ', $field)) }}</dt><dd>{{ $profileRequest->current_values[$field] ?? '(empty)' }} → {{ $value ?? '(empty)' }}</dd></div>
                                @endif
                            @endforeach
                        </dl>
                        @if ($profileRequest->review_notes)<p class="text-sm">Staff notes: {{ $profileRequest->review_notes }}</p>@endif
                    </article>
                @empty
                    <p class="mt-3 text-sm text-neutral-600 dark:text-neutral-400">No profile update requests yet.</p>
                @endforelse
                <div class="mt-5">{{ $requests->links() }}</div>
            </section>
        @endif
    </main>
</body>
</html>
