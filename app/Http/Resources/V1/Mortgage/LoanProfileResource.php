<?php

namespace App\Http\Resources\V1\Mortgage;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LBHurtado\Mortgage\Models\LoanProfile;

class LoanProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var LoanProfile $profile */
        $profile = $this->resource;

        // Merge computation details at the top level for easier access
        $computation = $profile->computation ?? [];
        
        return [
            'id' => $profile->id,
            'reference_code' => $profile->reference_code,
            'lending_institution' => $profile->lending_institution,
            'total_contract_price' => $profile->total_contract_price,
            'qualified' => $profile->qualified,
            'required_equity' => $profile->required_equity,
            'income_gap' => $profile->income_gap,
            'suggested_down_payment_percent' => $profile->suggested_down_payment_percent,
            'percent_down_payment_remedy' => $profile->suggested_down_payment_percent,
            'reason' => $profile->reason,
            'borrower_name' => $profile->borrower_name,
            'borrower_email' => $profile->borrower_email,
            'reserved_at' => $profile->reserved_at?->toISOString(),
            'reserved_until' => $profile->reserved_until?->toISOString(),
            'inputs' => $profile->inputs,
            'computation' => $computation,
            // Include computed fields at top level for convenience
            'monthly_amortization' => $computation['monthly_amortization'] ?? null,
            'balance_payment_term' => $computation['balance_payment_term'] ?? null,
            'loanable_amount' => $computation['loanable_amount'] ?? null,
            'interest_rate' => $computation['interest_rate'] ?? null,
            'percent_down_payment' => $computation['percent_down_payment'] ?? null,
            'miscellaneous_fees' => $computation['miscellaneous_fees'] ?? null,
            'cash_out' => $computation['cash_out'] ?? null,
            'monthly_disposable_income' => $computation['monthly_disposable_income'] ?? null,
            'qualification' => [
                'qualifies' => $profile->qualified,
                'reason' => $profile->reason,
            ],
            'age' => $profile->inputs['age'] ?? null,
            'monthly_gross_income' => $profile->inputs['monthly_gross_income'] ?? null,
            'co_borrower_age' => $profile->inputs['co_borrower_age'] ?? null,
            'co_borrower_income' => $profile->inputs['co_borrower_income'] ?? null,
            'add_mri' => $profile->inputs['add_mri'] ?? false,
            'add_fi' => $profile->inputs['add_fi'] ?? false,
            'processing_fee' => $profile->inputs['processing_fee'] ?? null,
            'percent_miscellaneous_fee' => $profile->inputs['percent_miscellaneous_fee'] ?? null,
            'additional_income' => $profile->inputs['additional_income'] ?? null,
            'created_at' => $profile->created_at->toISOString(),
            'updated_at' => $profile->updated_at->toISOString(),
        ];
    }
}
