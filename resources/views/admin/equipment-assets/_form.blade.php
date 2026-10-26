@php
    $editing = isset($equipmentAsset);
    $asset = $editing ? $equipmentAsset : null;
    $isDeployed = $editing && $asset->activeAssignment()->exists();
    $inputClass = 'min-h-10 w-full border border-neutral-300 bg-white px-3 text-sm text-neutral-900 focus:border-[#008080] focus:outline-none focus:ring-1 focus:ring-[#008080] dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100';
@endphp

<form method="POST" action="{{ $editing ? route('admin.equipment-assets.update', $asset) : route('admin.equipment-assets.store') }}" data-lock-submit class="space-y-4">
    @csrf
    @if ($editing) @method('PATCH') @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="asset_tag" class="mb-1 block text-sm font-semibold">Asset Tag <span aria-hidden="true">*</span></label>
            <input id="asset_tag" name="asset_tag" value="{{ old('asset_tag', $asset?->asset_tag) }}" maxlength="40" required autocomplete="off" @readonly($isDeployed) class="{{ $inputClass }} @error('asset_tag') border-red-600 ring-1 ring-red-600 @enderror" aria-invalid="{{ $errors->has('asset_tag') ? 'true' : 'false' }}" @error('asset_tag') aria-describedby="asset_tag-error" @enderror>
            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Unique identifier; letters, numbers, hyphens and underscores only.</p>
            @error('asset_tag') <p id="asset_tag-error" class="mt-1 text-xs text-red-700 dark:text-red-400" role="alert">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="name" class="mb-1 block text-sm font-semibold">Equipment Name <span aria-hidden="true">*</span></label>
            <input id="name" name="name" value="{{ old('name', $asset?->name) }}" maxlength="120" required class="{{ $inputClass }} @error('name') border-red-600 ring-1 ring-red-600 @enderror">
            @error('name') <p class="mt-1 text-xs text-red-700 dark:text-red-400" role="alert">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="category" class="mb-1 block text-sm font-semibold">Category <span aria-hidden="true">*</span></label>
            <select id="category" name="category" required class="{{ $inputClass }} @error('category') border-red-600 ring-1 ring-red-600 @enderror">
                <option value="">Select category</option>
                @foreach (\App\Models\EquipmentAsset::CATEGORIES as $value => $label)
                    <option value="{{ $value }}" @selected(old('category', $asset?->category) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('category') <p class="mt-1 text-xs text-red-700 dark:text-red-400" role="alert">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="serial_number" class="mb-1 block text-sm font-semibold">Serial Number</label>
            <input id="serial_number" name="serial_number" value="{{ old('serial_number', $asset?->serial_number) }}" maxlength="120" autocomplete="off" @readonly($isDeployed) class="{{ $inputClass }} @error('serial_number') border-red-600 ring-1 ring-red-600 @enderror">
            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Optional, but must be unique when provided.</p>
            @error('serial_number') <p class="mt-1 text-xs text-red-700 dark:text-red-400" role="alert">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="brand" class="mb-1 block text-sm font-semibold">Brand</label>
            <input id="brand" name="brand" value="{{ old('brand', $asset?->brand) }}" maxlength="80" class="{{ $inputClass }} @error('brand') border-red-600 ring-1 ring-red-600 @enderror">
            @error('brand') <p class="mt-1 text-xs text-red-700 dark:text-red-400" role="alert">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="model" class="mb-1 block text-sm font-semibold">Model</label>
            <input id="model" name="model" value="{{ old('model', $asset?->model) }}" maxlength="100" class="{{ $inputClass }} @error('model') border-red-600 ring-1 ring-red-600 @enderror">
            @error('model') <p class="mt-1 text-xs text-red-700 dark:text-red-400" role="alert">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="status" class="mb-1 block text-sm font-semibold">Condition / Status <span aria-hidden="true">*</span></label>
            <select id="status" name="status" required @disabled($isDeployed) class="{{ $inputClass }} @error('status') border-red-600 ring-1 ring-red-600 @enderror">
                @foreach (\App\Models\EquipmentAsset::STATUSES as $value => $label)
                    @continue($value === 'deployed' && ! $isDeployed)
                    <option value="{{ $value }}" @selected(old('status', $asset?->status ?? 'in_stock') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @if ($isDeployed)
                <input type="hidden" name="status" value="deployed">
                <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Status, asset tag and serial number are locked while assigned. Equipment retrieval is managed separately.</p>
            @endif
            @error('status') <p class="mt-1 text-xs text-red-700 dark:text-red-400" role="alert">{{ $message }}</p> @enderror
        </div>
        <div class="sm:col-span-2">
            <label for="notes" class="mb-1 block text-sm font-semibold">Notes</label>
            <textarea id="notes" name="notes" maxlength="2000" rows="3" class="{{ $inputClass }} py-2 @error('notes') border-red-600 ring-1 ring-red-600 @enderror">{{ old('notes', $asset?->notes) }}</textarea>
            @error('notes') <p class="mt-1 text-xs text-red-700 dark:text-red-400" role="alert">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3 border-t border-neutral-200 pt-4 dark:border-neutral-800">
        <button type="submit" class="min-h-10 bg-[#008080] px-5 py-2 text-sm font-semibold text-white hover:bg-[#006666] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#008080] focus-visible:ring-offset-2">{{ $editing ? 'Save Changes' : 'Register Equipment' }}</button>
        <a href="{{ $editing ? route('admin.equipment-assets.show', $asset) : route('admin.equipment-assets.index') }}" class="inline-flex min-h-10 items-center border border-neutral-300 px-4 text-sm text-neutral-700 hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800">Cancel</a>
    </div>
</form>
