<?php

namespace App\Http\Requests\Mortgage;

use Illuminate\Foundation\Http\FormRequest;

class ComputeEarlyPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_balance' => ['required', 'numeric', 'min:1'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'term_remaining' => ['required', 'integer', 'min:1', 'max:360'],
            'monthly_payment' => ['required', 'numeric', 'min:1'],
            'extra_payment' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_balance.required' => 'Current balance is required.',
            'interest_rate.required' => 'Interest rate is required.',
            'term_remaining.required' => 'Term remaining is required.',
            'monthly_payment.required' => 'Monthly payment is required.',
            'extra_payment.required' => 'Extra payment amount is required.',
        ];
    }
}
