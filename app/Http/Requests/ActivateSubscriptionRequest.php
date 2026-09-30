<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => [
                'required',
                'date',
            ],

            'lock_in_months' => [
                'required',
                'integer',
                'min:1',
                'max:120',
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
            'start_date.required' =>
            'Please select the subscription start date.',

            'start_date.date' =>
            'The subscription start date must be a valid date.',

            'lock_in_months.required' =>
            'Please enter the contract lock-in period.',

            'lock_in_months.integer' =>
            'The lock-in period must be a whole number of months.',

            'lock_in_months.min' =>
            'The lock-in period must be at least 1 month.',

            'reason.required' =>
            'Please provide a reason for activating this subscription.',

            'reason.max' =>
            'The activation reason cannot exceed 1000 characters.',
        ];
    }
}
