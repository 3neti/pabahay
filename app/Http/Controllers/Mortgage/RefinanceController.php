<?php

namespace App\Http\Controllers\Mortgage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mortgage\ComputeRefinanceRequest;
use LBHurtado\Mortgage\Calculators\RefinanceCalculator;
use LBHurtado\Mortgage\Data\Inputs\MortgageParticulars;
use Illuminate\Http\JsonResponse;

class RefinanceController extends Controller
{
    /**
     * Calculate refinancing analysis
     *
     * @param ComputeRefinanceRequest $request
     * @return JsonResponse
     */
    public function calculate(ComputeRefinanceRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            // Create a simple object to hold refinance data
            // RefinanceCalculator doesn't need full MortgageParticulars structure
            $inputs = (object) [
                'current_balance' => $validated['current_balance'],
                'current_rate' => $validated['current_rate'],
                'current_term_remaining' => $validated['current_term_remaining'],
                'current_monthly_payment' => $validated['current_monthly_payment'],
                'new_rate' => $validated['new_rate'],
                'new_term' => $validated['new_term'],
                'closing_costs' => $validated['closing_costs'],
            ];
            
            if (isset($validated['property_value'])) {
                $inputs->property_value = $validated['property_value'];
            }
            
            // Calculate refinance analysis
            $refinance = RefinanceCalculator::fromInputs($inputs)->calculate();
            
            return response()->json([
                'success' => true,
                'data' => $refinance->toArray(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while calculating refinance analysis.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
