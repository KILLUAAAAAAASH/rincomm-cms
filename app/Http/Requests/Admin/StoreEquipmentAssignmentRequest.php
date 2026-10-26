<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEquipmentAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && $this->user()->isActive()
            && in_array($this->user()->role, ['admin', 'staff'], true);
    }

    protected function prepareForValidation(): void
    {
        $ip = $this->input('ip_address');
        if (is_string($ip)) {
            $this->merge(['ip_address' => trim($ip) ?: null]);
        }
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('status', 'active')],
            'equipment_asset_id' => ['required', 'integer', Rule::exists('equipment_assets', 'id')],
            'ip_address' => ['nullable', 'ip', 'max:45'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.exists' => 'Select an active subscriber account.',
            'equipment_asset_id.exists' => 'Select an existing hardware asset.',
            'ip_address.ip' => 'Enter a valid IPv4 or IPv6 address.',
        ];
    }
}
