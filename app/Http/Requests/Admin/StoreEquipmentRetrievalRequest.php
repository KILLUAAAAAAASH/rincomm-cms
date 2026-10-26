<?php

namespace App\Http\Requests\Admin;

use App\Models\EquipmentRetrieval;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEquipmentRetrievalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && $this->user()->isActive()
            && in_array($this->user()->role, ['admin', 'staff'], true);
    }

    protected function prepareForValidation(): void
    {
        $remarks = $this->input('remarks');
        if (is_string($remarks)) {
            $this->merge(['remarks' => trim($remarks) ?: null]);
        }
        if ($this->input('job_order_id') === '') {
            $this->merge(['job_order_id' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'equipment_assignment_id' => ['required', 'integer', Rule::exists('equipment_assignments', 'id')],
            'retrieved_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'reason' => ['required', Rule::in(array_keys(EquipmentRetrieval::REASONS))],
            'condition' => ['required', Rule::in(array_keys(EquipmentRetrieval::CONDITIONS))],
            'job_order_id' => ['nullable', 'integer', Rule::exists('job_orders', 'id')],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'equipment_assignment_id.exists' => 'Select an existing customer equipment assignment.',
            'retrieved_on.before_or_equal' => 'Retrieval date cannot be in the future.',
            'job_order_id.exists' => 'Select an existing completed Job Order for this subscriber.',
        ];
    }
}
