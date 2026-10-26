<?php

namespace App\Http\Requests\Admin;

use App\Models\EquipmentAsset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveEquipmentAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && $this->user()->isActive()
            && in_array($this->user()->role, ['admin', 'staff'], true);
    }

    protected function prepareForValidation(): void
    {
        $fields = ['asset_tag', 'name', 'category', 'brand', 'model', 'serial_number', 'status', 'notes'];
        $normalized = [];

        foreach ($fields as $field) {
            $value = $this->input($field);

            if (! is_string($value)) {
                continue;
            }

            $value = trim($value);

            if (in_array($field, ['asset_tag', 'serial_number'], true)) {
                $value = mb_strtoupper($value, 'UTF-8');
            }

            $normalized[$field] = $value === '' ? null : $value;
        }

        $this->merge($normalized);
    }

    public function rules(): array
    {
        $asset = $this->route('equipmentAsset');
        $id = $asset instanceof EquipmentAsset ? $asset->getKey() : null;
        $allowedStatuses = array_keys(EquipmentAsset::STATUSES);
        if (! ($asset instanceof EquipmentAsset && $asset->status === 'deployed')) {
            $allowedStatuses = array_values(array_diff($allowedStatuses, ['deployed']));
        }

        return [
            'asset_tag' => [
                'required', 'string', 'max:40', 'regex:/^[A-Z0-9][A-Z0-9_-]*$/',
                Rule::unique('equipment_assets', 'asset_tag')->ignore($id),
            ],
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', Rule::in(array_keys(EquipmentAsset::CATEGORIES))],
            'brand' => ['nullable', 'string', 'max:80'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:120', Rule::unique('equipment_assets', 'serial_number')->ignore($id)],
            'status' => ['required', 'string', Rule::in($allowedStatuses)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'asset_tag.regex' => 'Use letters, numbers, hyphens, or underscores for the asset tag.',
            'asset_tag.unique' => 'This asset tag is already registered.',
            'serial_number.unique' => 'This serial number is already registered.',
        ];
    }
}
