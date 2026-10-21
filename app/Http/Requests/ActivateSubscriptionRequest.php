<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Admin/Staff role authorization is enforced by the route middleware.
        return true;
    }

    public function rules(): array
    {
        $today = now('Asia/Manila')->toDateString();

        return [
            'start_date' => [
                'required',
                'date_format:Y-m-d',
                Rule::in([$today]),
            ],
            'lock_in_months' => [
                'required',
                'integer',
                'min:1',
                'max:120',
            ],
            'installation_job_order_id' => [
                'required',
                'integer',
                'exists:job_orders,id',
            ],
            'readiness_confirmed' => [
                'required',
                'accepted',
            ],
            'reason' => [
                'required',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.required' => 'Please select the subscription start date.',
            'start_date.date_format' => 'The subscription start date must use YYYY-MM-DD.',
            'start_date.in' => 'Normal activation must start today (Philippine time). Historical and future activations require a separate process.',
            'lock_in_months.required' => 'Please enter the contract lock-in period.',
            'lock_in_months.integer' => 'The lock-in period must be a whole number of months.',
            'lock_in_months.min' => 'The lock-in period must be at least 1 month.',
            'lock_in_months.max' => 'The lock-in period cannot exceed 120 months.',
            'installation_job_order_id.required' => 'Please select the completed installation Job Order.',
            'installation_job_order_id.exists' => 'The selected installation Job Order could not be found.',
            'readiness_confirmed.accepted' => 'You must confirm the installation evidence and applicable service readiness.',
            'reason.required' => 'Please provide a reason for activating this subscription.',
            'reason.max' => 'The activation reason cannot exceed 1000 characters.',
        ];
    }
}
