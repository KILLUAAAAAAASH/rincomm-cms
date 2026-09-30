<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'discount_amount' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
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
            'discount_amount.required' =>
            'Please enter the promotional discount amount.',

            'discount_amount.numeric' =>
            'The promotional discount must be a valid amount.',

            'discount_amount.min' =>
            'The promotional discount cannot be negative.',

            'discount_amount.max' =>
            'The promotional discount amount is too large.',

            'reason.required' =>
            'Please provide a reason for the promotional discount.',

            'reason.max' =>
            'The discount reason cannot exceed 1000 characters.',
        ];
    }
}
