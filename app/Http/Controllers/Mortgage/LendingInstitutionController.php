<?php

namespace App\Http\Controllers\Mortgage;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Mortgage\LendingInstitutionResource;
use LBHurtado\Mortgage\Services\LendingInstitutionService;
use Illuminate\Http\{JsonResponse, Resources\Json\AnonymousResourceCollection};

class LendingInstitutionController extends Controller
{
    public function __construct(
        protected LendingInstitutionService $institutionService
    ) {}

    /**
     * Get all available lending institutions.
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $institutions = $this->institutionService->getAllInstitutions();

        return LendingInstitutionResource::collection($institutions);
    }

    /**
     * Get specific lending institution details.
     *
     * @param string $key
     * @return JsonResponse
     */
    public function show(string $key): JsonResponse
    {
        if (!$this->institutionService->exists($key)) {
            return response()->json([
                'success' => false,
                'message' => 'Lending institution not found.',
            ], 404);
        }

        $institution = $this->institutionService->getInstitution($key);

        return response()->json([
            'success' => true,
            'data' => new LendingInstitutionResource($institution),
        ]);
    }
}
