<?php

namespace App\Http\Controllers\Mortgage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mortgage\ComputeAffordabilityRequest;
use LBHurtado\Mortgage\Calculators\AffordabilityCalculator;
use LBHurtado\Mortgage\Data\Inputs\MortgageInputsData;
use LBHurtado\Mortgage\Factories\MortgageParticularsFactory;
use Illuminate\Http\JsonResponse;

class AffordabilityController extends Controller
{
    /**
     * Calculate affordability - "How much house can I afford?"
     *
     * @param ComputeAffordabilityRequest $request
     * @return JsonResponse
     */
    public function calculate(ComputeAffordabilityRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            // Convert affordability inputs to mortgage inputs format
            $mortgageInputsData = $this->convertToMortgageInputs($validated);
            
            // Create mortgage particulars
            $particulars = MortgageParticularsFactory::fromData($mortgageInputsData);
            
            // Add custom affordability fields to particulars (as public properties)
            $particulars->monthly_debts = $validated['monthly_debts'] ?? 0;
            $particulars->down_payment_available = $validated['down_payment_available'];
            
            // Override loan term if provided
            if (isset($validated['loan_term'])) {
                $particulars->override_loan_term = $validated['loan_term'];
            }
            
            // Calculate affordability
            $affordability = AffordabilityCalculator::fromInputs($particulars)->calculate();
            
            return response()->json([
                'success' => true,
                'data' => $affordability->toArray(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while calculating affordability.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Convert affordability request to mortgage inputs format
     */
    protected function convertToMortgageInputs(array $data): MortgageInputsData
    {
        // Use a minimal property price for calculation purposes
        // The actual max home price will be calculated by AffordabilityCalculator
        $minPropertyPrice = 100000;
        
        return MortgageInputsData::from([
            'lending_institution' => $data['lending_institution'],
            'total_contract_price' => $minPropertyPrice,
            'age' => $data['age'],
            'monthly_gross_income' => $data['monthly_gross_income'],
            'co_borrower_age' => $data['co_borrower_age'] ?? null,
            'co_borrower_income' => $data['co_borrower_income'] ?? null,
            'additional_income' => $data['additional_income'] ?? null,
            'balance_payment_interest' => null, // Use lending institution default
            'percent_down_payment' => null, // Use lending institution default
            'percent_miscellaneous_fee' => null, // Use lending institution default
            'processing_fee' => null,
            'add_mri' => false,
            'add_fi' => false,
        ]);
    }
}
