<?php

namespace App\Http\Controllers\Mortgage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mortgage\ComputeEquityRequest;
use LBHurtado\Mortgage\Calculators\EquityCalculator;
use Illuminate\Http\JsonResponse;

class EquityController extends Controller
{
    public function calculate(ComputeEquityRequest $request): JsonResponse
    {
        try {
            $inputs = (object) $request->validated();
            $equity = EquityCalculator::fromInputs($inputs)->calculate();
            
            return response()->json([
                'success' => true,
                'data' => $equity->toArray(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while calculating equity.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
