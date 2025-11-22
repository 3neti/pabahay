<?php

namespace App\Http\Controllers\Mortgage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mortgage\ComputeMortgageRequest;
use App\Http\Resources\V1\Mortgage\MortgageComputationResource;
use LBHurtado\Mortgage\Data\Inputs\MortgageInputsData;
use LBHurtado\Mortgage\Exceptions\{ComputationFailedException, InvalidInputException};
use LBHurtado\Mortgage\Services\MortgageComputationService;
use Illuminate\Http\JsonResponse;

class MortgageComputationController extends Controller
{
    public function __construct(
        protected MortgageComputationService $computationService
    ) {}

    /**
     * Compute mortgage details from input parameters.
     *
     * @param ComputeMortgageRequest $request
     * @return JsonResponse
     */
    public function compute(ComputeMortgageRequest $request): JsonResponse
    {
        try {
            // Create DTO from validated request data
            $inputs = MortgageInputsData::from($request->validated());

            // Compute mortgage using service
            $computation = $this->computationService->compute($inputs);

            // Return formatted response using API Resource
            return response()->json([
                'success' => true,
                'payload' => $this->computationService->computeAndFormat($inputs),
            ]);

        } catch (InvalidInputException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getUserMessage(),
                'errors' => $e->getErrors(),
            ], 422);

        } catch (ComputationFailedException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getUserMessage(),
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again.',
            ], 500);
        }
    }
}
