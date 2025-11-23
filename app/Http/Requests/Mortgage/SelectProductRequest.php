<?php

namespace App\Http\Requests\Mortgage;

use Illuminate\Foundation\Http\FormRequest;

class SelectProductRequest extends FormRequest
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
            'age' => ['required', 'integer', 'min:18', 'max:65'],
            'monthly_gross_income' => ['required', 'numeric', 'min:1'],
            'return_top_n' => ['sometimes', 'integer', 'min:1', 'max:10'],
        ];
    }
}
