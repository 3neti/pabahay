<?php

namespace LBHurtado\Mortgage\Services;

use Illuminate\Support\Str;
use LBHurtado\Mortgage\Data\Inputs\MortgageInputsData;
use LBHurtado\Mortgage\Data\MortgageComputationData;
use LBHurtado\Mortgage\Models\LoanProfile;

class LoanProfileService
{
    /**
     * Create a new loan profile from computation result.
     */
    public function createFromComputation(
        MortgageComputationData $computation,
        array $additionalData = []
    ): LoanProfile {
        return LoanProfile::create([
            'lending_institution' => $computation->lending_institution->key(),
            'total_contract_price' => $computation->total_contract_price->base()->getAmount()->toFloat(),
            'inputs' => [], // MortgageParticulars is not serializable, store empty for now
            'computation' => $this->serializeComputation($computation),
            'qualified' => $computation->qualifies,
            'required_equity' => $computation->required_equity->getAmount()->toFloat(),
            'income_gap' => $computation->income_gap->getAmount()->toFloat(),
            'suggested_down_payment_percent' => $computation->percent_down_payment_remedy->value(),
            'reason' => $computation->qualifies
                ? 'Sufficient disposable income'
                : 'Disposable income below amortization',
            ...$additionalData,
        ]);
    }

    /**
     * Create loan profile from inputs and computation service.
     */
    public function createFromInputs(
        MortgageInputsData $inputs,
        MortgageComputationService $computationService,
        array $additionalData = [],
        array $rawInputs = []
    ): LoanProfile {
        $computation = $computationService->compute($inputs);

        // Store raw inputs if provided
        if (!empty($rawInputs)) {
            $additionalData['inputs'] = $rawInputs;
        }

        return $this->createFromComputation($computation, $additionalData);
    }

    /**
     * Find loan profile by reference code.
     */
    public function findByReferenceCode(string $referenceCode): ?LoanProfile
    {
        return LoanProfile::where('reference_code', $referenceCode)->first();
    }

    /**
     * Reserve loan profile for a period.
     */
    public function reserve(LoanProfile $profile, ?\DateTimeInterface $until = null): LoanProfile
    {
        $profile->update([
            'reserved_at' => now(),
            'reserved_until' => $until ?? now()->addDays(7),
        ]);

        return $profile->fresh();
    }

    /**
     * Check if loan profile reservation is still valid.
     */
    public function isReservationValid(LoanProfile $profile): bool
    {
        if (! $profile->reserved_at) {
            return false;
        }

        if (! $profile->reserved_until) {
            return true;
        }

        return now()->lte($profile->reserved_until);
    }

    /**
     * Release loan profile reservation.
     */
    public function releaseReservation(LoanProfile $profile): LoanProfile
    {
        $profile->update([
            'reserved_at' => null,
            'reserved_until' => null,
        ]);

        return $profile->fresh();
    }

    /**
     * Generate unique reference code.
     */
    protected function generateReferenceCode(): string
    {
        do {
            $code = 'LP-'.strtoupper(Str::random(8));
        } while (LoanProfile::where('reference_code', $code)->exists());

        return $code;
    }

    /**
     * Serialize computation data for storage.
     */
    protected function serializeComputation(MortgageComputationData $computation): array
    {
        return [
            'lending_institution' => [
                'key' => $computation->lending_institution->key(),
                'name' => $computation->lending_institution->name(),
            ],
            'interest_rate' => $computation->interest_rate->value(),
            'percent_down_payment' => $computation->percent_down_payment->value(),
            'percent_miscellaneous_fees' => $computation->percent_miscellaneous_fees->value(),
            'total_contract_price' => $computation->total_contract_price->base()->getAmount()->toFloat(),
            'balance_payment_term' => $computation->balance_payment_term,
            'monthly_disposable_income' => $computation->monthly_disposable_income->getAmount()->toFloat(),
            'present_value' => $computation->present_value->getAmount()->toFloat(),
            'loanable_amount' => $computation->loanable_amount->getAmount()->toFloat(),
            'required_equity' => $computation->required_equity->getAmount()->toFloat(),
            'monthly_amortization' => $computation->monthly_amortization->getAmount()->toFloat(),
            'miscellaneous_fees' => $computation->miscellaneous_fees->getAmount()->toFloat(),
            'add_on_fees' => $computation->add_on_fees->getAmount()->toFloat(),
            'cash_out' => $computation->cash_out->getAmount()->toFloat(),
            'income_gap' => $computation->income_gap->getAmount()->toFloat(),
            'required_income' => $computation->required_income->getAmount()->toFloat(),
        ];
    }

    /**
     * Get qualified loan profiles.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getQualifiedProfiles(int $limit = 10)
    {
        return LoanProfile::where('qualified', true)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get profiles by lending institution.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProfilesByInstitution(string $institutionKey, int $limit = 10)
    {
        return LoanProfile::where('lending_institution', $institutionKey)
            ->latest()
            ->limit($limit)
            ->get();
    }
}
