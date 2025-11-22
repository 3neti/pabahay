<?php

namespace App\Http\Controllers\Mortgage;

use App\Http\Controllers\Controller;
use Illuminate\Http\{JsonResponse, Request};
use LBHurtado\Mortgage\Services\AmortizationScheduleService;

class AmortizationScheduleController extends Controller
{
    public function __construct(
        protected AmortizationScheduleService $scheduleService
    ) {}

    /**
     * Generate amortization schedule.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'loan_amount' => 'required|numeric|min:1',
            'interest_rate' => 'required|numeric|min:0|max:1',
            'term_years' => 'required|integer|min:1|max:50',
            'monthly_payment' => 'required|numeric|min:1',
            'extra_payment' => 'nullable|numeric|min:0',
            'view' => 'nullable|in:monthly,yearly',
        ]);

        try {
            $schedule = $this->scheduleService->generate(
                loanAmount: $validated['loan_amount'],
                annualInterestRate: $validated['interest_rate'],
                termYears: $validated['term_years'],
                monthlyPayment: $validated['monthly_payment']
            );

            $response = [
                'success' => true,
                'schedule' => $schedule->toArray(),
            ];

            // Add yearly summary if requested
            if (($validated['view'] ?? 'monthly') === 'yearly') {
                $response['yearlySummary'] = $this->scheduleService->getYearlySummary($schedule);
            }

            // Calculate extra payment savings if provided
            if (isset($validated['extra_payment']) && $validated['extra_payment'] > 0) {
                $savings = $this->scheduleService->calculateExtraPaymentSavings(
                    $schedule,
                    $validated['extra_payment']
                );
                
                $response['extraPaymentAnalysis'] = [
                    'extraMonthlyPayment' => $validated['extra_payment'],
                    'newMonthlyPayment' => $savings['newSchedule']->monthlyPayment,
                    'savedInterest' => $savings['savedInterest'],
                    'savedMonths' => $savings['savedMonths'],
                    'newTotalInterest' => $savings['newSchedule']->totalInterest,
                    'newTotalPayments' => $savings['newSchedule']->totalPayments,
                ];
            }

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate amortization schedule: ' . $e->getMessage(),
            ], 500);
        }
    }
}
