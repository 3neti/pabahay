<?php

namespace App\Http\Controllers\Mortgage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mortgage\ComputeMortgageRequest;
use App\Http\Resources\V1\Mortgage\MortgageComputationResource;
use LBHurtado\Mortgage\Data\Inputs\MortgageInputsData;
use LBHurtado\Mortgage\Services\{LendingInstitutionService, MortgageComputationService};
use Illuminate\Http\JsonResponse;

class ComparisonController extends Controller
{
    public function __construct(
        protected MortgageComputationService $computationService,
        protected LendingInstitutionService $institutionService
    ) {}

    /**
     * Compare mortgages across all lending institutions.
     *
     * @param ComputeMortgageRequest $request
     * @return JsonResponse
     */
    public function compare(ComputeMortgageRequest $request): JsonResponse
    {
        try {
            $availableInstitutions = $this->institutionService->getAvailableInstitutions();
            $comparisons = [];
            $bestOptions = [
                'lowestMonthlyPayment' => null,
                'lowestTotalInterest' => null,
                'lowestCashOut' => null,
                'mostQualified' => [],
            ];

            foreach ($availableInstitutions as $institutionKey) {
                try {
                    // Override lending institution for each comparison
                    $data = array_merge($request->validated(), [
                        'lending_institution' => $institutionKey,
                    ]);

                    $inputs = MortgageInputsData::from($data);
                    $result = $this->computationService->compute($inputs);

                    $computation = [
                        'institution' => $institutionKey,
                        'institutionName' => $this->institutionService->getInstitutionName($institutionKey),
                        'result' => new MortgageComputationResource($result),
                    ];

                    $comparisons[] = $computation;

                    // Track best options
                    if ($result->qualification['qualifies']) {
                        $bestOptions['mostQualified'][] = $institutionKey;

                        if (!$bestOptions['lowestMonthlyPayment'] || 
                            $result->monthly_amortization < $bestOptions['lowestMonthlyPayment']['value']) {
                            $bestOptions['lowestMonthlyPayment'] = [
                                'institution' => $institutionKey,
                                'value' => $result->monthly_amortization,
                            ];
                        }

                        if (!$bestOptions['lowestTotalInterest'] || 
                            $this->calculateTotalInterest($result) < $bestOptions['lowestTotalInterest']['value']) {
                            $bestOptions['lowestTotalInterest'] = [
                                'institution' => $institutionKey,
                                'value' => $this->calculateTotalInterest($result),
                            ];
                        }

                        if (!$bestOptions['lowestCashOut'] || 
                            $result->cash_out < $bestOptions['lowestCashOut']['value']) {
                            $bestOptions['lowestCashOut'] = [
                                'institution' => $institutionKey,
                                'value' => $result->cash_out,
                            ];
                        }
                    }

                } catch (\Exception $e) {
                    // Log error but continue with other institutions
                    \Log::warning("Failed to compute for {$institutionKey}: " . $e->getMessage());
                    
                    $comparisons[] = [
                        'institution' => $institutionKey,
                        'institutionName' => $this->institutionService->getInstitutionName($institutionKey),
                        'error' => 'Computation failed',
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'comparisons' => $comparisons,
                'bestOptions' => $bestOptions,
                'comparisonCount' => count($comparisons),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate comparison: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate total interest paid over loan term.
     */
    private function calculateTotalInterest($result): float
    {
        $totalPayment = $result->monthly_amortization * ($result->balance_payment_term * 12);
        return $totalPayment - $result->loanable_amount;
    }
}
