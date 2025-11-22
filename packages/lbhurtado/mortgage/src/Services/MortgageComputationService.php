<?php

namespace LBHurtado\Mortgage\Services;

use Illuminate\Support\Facades\Log;
use LBHurtado\Mortgage\Data\Inputs\MortgageInputsData;
use LBHurtado\Mortgage\Data\MortgageComputationData;
use LBHurtado\Mortgage\Factories\MortgageParticularsFactory;

class MortgageComputationService
{
    public function __construct(
        protected LoanQualificationService $qualificationService
    ) {}

    /**
     * Compute mortgage details from input data.
     */
    public function compute(MortgageInputsData $inputs): MortgageComputationData
    {
        Log::info('Starting mortgage computation', [
            'lending_institution' => $inputs->lending_institution,
            'total_contract_price' => $inputs->total_contract_price,
        ]);

        try {
            // Create mortgage particulars from inputs
            $particulars = MortgageParticularsFactory::fromData($inputs);

            // Compute mortgage data
            $computation = MortgageComputationData::fromParticulars($particulars);

            Log::info('Mortgage computation completed', [
                'qualified' => $computation->qualifies,
                'monthly_amortization' => $computation->monthly_amortization?->getAmount()?->toFloat() ?? 0,
            ]);

            return $computation;

        } catch (\Exception $e) {
            Log::error('Mortgage computation failed', [
                'error' => $e->getMessage(),
                'inputs' => $inputs->toArray(),
            ]);

            throw $e;
        }
    }

    /**
     * Compute mortgage from array of inputs.
     */
    public function computeFromArray(array $data): MortgageComputationData
    {
        $inputs = MortgageInputsData::from($data);

        return $this->compute($inputs);
    }

    /**
     * Compute mortgage and return simplified array result.
     */
    public function computeAndFormat(MortgageInputsData $inputs): array
    {
        $computation = $this->compute($inputs);

        return [
            'inputs' => $computation->inputs->toArray(),
            'lending_institution' => [
                'key' => $computation->lending_institution->key(),
                'name' => $computation->lending_institution->name(),
                'alias' => $computation->lending_institution->alias(),
            ],
            'interest_rate' => $computation->interest_rate->value(),
            'percent_down_payment' => $computation->percent_down_payment->value(),
            'percent_miscellaneous_fees' => $computation->percent_miscellaneous_fees->value(),
            'total_contract_price' => $computation->total_contract_price->base()->getAmount()->toFloat(),
            'balance_payment_term' => $computation->balance_payment_term,
            'income_requirement_multiplier' => $computation->income_requirement_multiplier->value(),
            'monthly_disposable_income' => $computation->monthly_disposable_income->getAmount()->toFloat(),
            'present_value' => $computation->present_value->getAmount()->toFloat(),
            'loanable_amount' => $computation->loanable_amount->getAmount()->toFloat(),
            'required_equity' => $computation->required_equity->getAmount()->toFloat(),
            'monthly_amortization' => $computation->monthly_amortization->getAmount()->toFloat(),
            'miscellaneous_fees' => $computation->miscellaneous_fees->getAmount()->toFloat(),
            'add_on_fees' => $computation->add_on_fees->getAmount()->toFloat(),
            'cash_out' => $computation->cash_out->getAmount()->toFloat(),
            'income_gap' => $computation->income_gap->getAmount()->toFloat(),
            'percent_down_payment_remedy' => $computation->percent_down_payment_remedy->value(),
            'required_income' => $computation->required_income->getAmount()->toFloat(),
            'qualifies' => $computation->qualifies,
            'qualification' => $this->qualificationService->formatQualificationResult($computation),
        ];
    }

    /**
     * Validate inputs before computation.
     */
    public function validateInputs(MortgageInputsData $inputs): bool
    {
        // Basic validation - will be enhanced with Validation Layer in later task
        return $inputs->total_contract_price > 0
            && $inputs->age >= 18
            && $inputs->monthly_gross_income > 0;
    }
}
