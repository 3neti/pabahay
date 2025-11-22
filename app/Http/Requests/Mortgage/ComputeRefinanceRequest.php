<?php

namespace App\Http\Requests\Mortgage;

use Illuminate\Foundation\Http\FormRequest;

class ComputeRefinanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Current loan details
            'current_balance' => ['required', 'numeric', 'min:1'],
            'current_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'current_term_remaining' => ['required', 'integer', 'min:1', 'max:360'],
            'current_monthly_payment' => ['required', 'numeric', 'min:1'],

            // New loan details
            'new_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'new_term' => ['required', 'integer', 'min:12', 'max:360'],
            'closing_costs' => ['required', 'numeric', 'min:0'],
            
            // Optional: property current value for analysis
            'property_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_balance.required' => 'Current loan balance is required.',
            'current_balance.min' => 'Current loan balance must be at least ₱1.',
            'current_rate.required' => 'Current interest rate is required.',
            'current_rate.max' => 'Interest rate must be between 0 and 100%.',
            'current_term_remaining.required' => 'Current term remaining is required.',
            'current_term_remaining.max' => 'Term remaining cannot exceed 360 months (30 years).',
            'current_monthly_payment.required' => 'Current monthly payment is required.',
            'new_rate.required' => 'New interest rate is required.',
            'new_term.required' => 'New loan term is required.',
            'new_term.min' => 'New loan term must be at least 12 months (1 year).',
            'new_term.max' => 'New loan term cannot exceed 360 months (30 years).',
            'closing_costs.required' => 'Closing costs are required.',
        ];
    }
}
