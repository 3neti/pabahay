<?php

namespace App\Http\Controllers\Mortgage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mortgage\ComputeMortgageRequest;
use App\Http\Resources\V1\Mortgage\LoanProfileResource;
use App\Mail\MortgageComputationMail;
use Illuminate\Support\Facades\Mail;
use LBHurtado\Mortgage\Data\Inputs\MortgageInputsData;
use LBHurtado\Mortgage\Services\{LoanProfileService, MortgageComputationService};
use Illuminate\Http\{JsonResponse, Request};

class LoanProfileController extends Controller
{
    public function __construct(
        protected LoanProfileService $profileService,
        protected MortgageComputationService $computationService
    ) {}

    /**
     * Store a new loan profile from computation.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(ComputeMortgageRequest $request): JsonResponse
    {
        // Additional validation for optional borrower fields
        $request->validate([
            'borrower_name' => 'nullable|string',
            'borrower_email' => 'nullable|email',
            'send_email' => 'boolean',
        ]);

        try {
            $inputs = MortgageInputsData::from($request->all());
            
            $profile = $this->profileService->createFromInputs(
                $inputs,
                $this->computationService,
                [],
                $request->only([
                    'lending_institution',
                    'total_contract_price',
                    'age',
                    'monthly_gross_income',
                    'co_borrower_age',
                    'co_borrower_income',
                    'additional_income',
                    'percent_down_payment',
                    'percent_miscellaneous_fee',
                    'processing_fee',
                    'add_mri',
                    'add_fi',
                ])
            );

            // Update borrower information if provided
            if ($request->has('borrower_name')) {
                $profile->borrower_name = $request->input('borrower_name');
            }
            if ($request->has('borrower_email')) {
                $profile->borrower_email = $request->input('borrower_email');
            }
            $profile->save();

            // Send email if requested
            if ($request->boolean('send_email') && $profile->borrower_email) {
                Mail::to($profile->borrower_email)
                    ->send(new MortgageComputationMail($profile));
            }

            return response()->json([
                'success' => true,
                'payload' => new LoanProfileResource($profile),
                'message' => 'Loan profile created successfully.',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create loan profile: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get loan profile by reference code.
     *
     * @param string $referenceCode
     * @return JsonResponse
     */
    public function show(string $referenceCode): JsonResponse
    {
        $profile = $this->profileService->findByReferenceCode($referenceCode);

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Loan profile not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'payload' => new LoanProfileResource($profile),
        ]);
    }
}
