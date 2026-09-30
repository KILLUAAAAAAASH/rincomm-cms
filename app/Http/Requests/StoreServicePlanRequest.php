<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServicePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:service_plans,name',
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'speed_mbps' => [
                'required',
                'numeric',
                'min:0.01',
                'max:99999999.99',
            ],

            'monthly_fee' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'duration_months' => [
                'required',
                'integer',
                'min:1',
                'max:120',
            ],

            'is_custom' => [
                'required',
                'boolean',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' =>
            'Please enter a name for the internet package.',

            'name.unique' =>
            'An internet package with this name already exists.',

            'speed_mbps.required' =>
            'Please enter the internet speed.',

            'speed_mbps.numeric' =>
            'Internet speed must be a valid number.',

            'speed_mbps.min' =>
            'Internet speed must be greater than zero.',

            'monthly_fee.required' =>
            'Please enter the monthly fee.',

            'monthly_fee.numeric' =>
            'Monthly fee must be a valid amount.',

            'monthly_fee.min' =>
            'Monthly fee cannot be negative.',

            'duration_months.required' =>
            'Please enter the plan duration.',

            'duration_months.integer' =>
            'Plan duration must be a whole number of months.',

            'duration_months.min' =>
            'Plan duration must be at least 1 month.',
        ];
    }
}
