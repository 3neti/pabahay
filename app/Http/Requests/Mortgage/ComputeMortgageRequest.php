<?php

namespace App\Http\Requests\Mortgage;

use Illuminate\Foundation\Http\FormRequest;
use LBHurtado\Mortgage\Rules\{ValidBorrowerAge, ValidDownPaymentPercent};

class ComputeMortgageRequest extends FormRequest
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
            // Lending institution
            'lending_institution' => ['required', 'string', 'in:hdmf,rcbc,cbc'],

            // Total contract price
            'total_contract_price' => ['required', 'numeric', 'min:100000'],

            // Buyer details
            'age' => ['required', 'integer', new ValidBorrowerAge()],
            'monthly_gross_income' => ['required', 'numeric', 'min:1'],
            'additional_income' => ['nullable', 'numeric', 'min:0'],

            // Co-borrower details (optional)
            'co_borrower_age' => ['nullable', 'integer', function ($attribute, $value, $fail) {
                if ($value > 0) {
                    $validator = new ValidBorrowerAge();
                    $validator->validate($attribute, $value, $fail);
                }
            }],
            'co_borrower_income' => ['nullable', 'numeric', 'min:0'],

            // Loan parameters (optional overrides)
            'balance_payment_interest' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'percent_down_payment' => ['nullable', 'numeric', new ValidDownPaymentPercent()],
            'percent_miscellaneous_fee' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'processing_fee' => ['nullable', 'numeric', 'min:0'],

            // Add-ons
            'add_mri' => ['nullable', 'boolean'],
            'add_fi' => ['nullable', 'boolean'],
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
            'lending_institution.required' => 'Please select a lending institution.',
            'lending_institution.in' => 'Invalid lending institution. Must be one of: HDMF, RCBC, CBC.',
            'total_contract_price.required' => 'Total contract price is required.',
            'total_contract_price.min' => 'Total contract price must be at least ₱100,000.',
            'age.required' => 'Buyer age is required.',
            'monthly_gross_income.required' => 'Monthly gross income is required.',
            'monthly_gross_income.min' => 'Monthly gross income must be at least ₱1.',
        ];
    }
}
