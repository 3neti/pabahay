<?php

namespace App\Http\Requests\Mortgage;

use Illuminate\Foundation\Http\FormRequest;
use LBHurtado\Mortgage\Rules\ValidBorrowerAge;

class ComputeAffordabilityRequest extends FormRequest
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

            // Income and debts
            'monthly_gross_income' => ['required', 'numeric', 'min:1'],
            'monthly_debts' => ['nullable', 'numeric', 'min:0'],
            'additional_income' => ['nullable', 'numeric', 'min:0'],
            
            // Borrower age (required for lending institution qualification rules)
            'age' => ['required', 'integer', new ValidBorrowerAge()],

            // Down payment available
            'down_payment_available' => ['required', 'numeric', 'min:0'],

            // Loan parameters
            'loan_term' => ['nullable', 'integer', 'min:5', 'max:30'],
            
            // Optional co-borrower
            'co_borrower_age' => ['nullable', 'integer', function ($attribute, $value, $fail) {
                if ($value > 0) {
                    $validator = new ValidBorrowerAge();
                    $validator->validate($attribute, $value, $fail);
                }
            }],
            'co_borrower_income' => ['nullable', 'numeric', 'min:0'],
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
            'monthly_gross_income.required' => 'Monthly gross income is required.',
            'monthly_gross_income.min' => 'Monthly gross income must be at least ₱1.',
            'down_payment_available.required' => 'Down payment available is required.',
            'down_payment_available.min' => 'Down payment must be at least ₱0.',
            'age.required' => 'Buyer age is required.',
            'loan_term.min' => 'Loan term must be at least 5 years.',
            'loan_term.max' => 'Loan term cannot exceed 30 years.',
        ];
    }
}
