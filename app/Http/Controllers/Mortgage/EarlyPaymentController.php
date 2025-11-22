<?php

namespace App\Http\Controllers\Mortgage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mortgage\ComputeEarlyPaymentRequest;
use LBHurtado\Mortgage\Calculators\EarlyPaymentCalculator;
use Illuminate\Http\JsonResponse;

class EarlyPaymentController extends Controller
{
    public function calculate(ComputeEarlyPaymentRequest $request): JsonResponse
    {
        try {
            $inputs = (object) $request->validated();
            $earlyPayment = EarlyPaymentCalculator::fromInputs($inputs)->calculate();
            
            return response()->json([
                'success' => true,
                'data' => $earlyPayment->toArray(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while calculating early payment savings.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
