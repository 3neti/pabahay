<?php

namespace App\Http\Requests\Mortgage;

use Illuminate\Foundation\Http\FormRequest;

class ComputeEquityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'original_loan_amount' => ['required', 'numeric', 'min:1'],
            'current_balance' => ['required', 'numeric', 'min:0'],
            'home_value' => ['required', 'numeric', 'min:1'],
            'monthly_payment' => ['required', 'numeric', 'min:1'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'term_remaining' => ['required', 'integer', 'min:1', 'max:360'],
            'appreciation_rate' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'extra_payment' => ['nullable', 'numeric', 'min:0'],
            'target_equity_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'original_loan_amount.required' => 'Original loan amount is required.',
            'current_balance.required' => 'Current balance is required.',
            'home_value.required' => 'Home value is required.',
            'monthly_payment.required' => 'Monthly payment is required.',
            'interest_rate.required' => 'Interest rate is required.',
            'term_remaining.required' => 'Term remaining is required.',
        ];
    }
}
