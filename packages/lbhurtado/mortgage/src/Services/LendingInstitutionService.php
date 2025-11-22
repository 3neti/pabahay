<?php

namespace LBHurtado\Mortgage\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use LBHurtado\Mortgage\Classes\LendingInstitution;

class LendingInstitutionService
{
    /**
     * Get all available lending institutions.
     */
    public function getAllInstitutions(): Collection
    {
        return Cache::remember('lending_institutions.all', 3600, function () {
            return collect(LendingInstitution::keys())
                ->map(fn ($key) => $this->getInstitution($key));
        });
    }

    /**
     * Get lending institution by key.
     */
    public function getInstitution(string $key): LendingInstitution
    {
        return new LendingInstitution($key);
    }

    /**
     * Get lending institution details as array.
     */
    public function getInstitutionDetails(string $key): array
    {
        $institution = $this->getInstitution($key);

        return [
            'key' => $institution->key(),
            'name' => $institution->name(),
            'alias' => $institution->alias(),
            'type' => $institution->type(),
            'borrowing_age' => [
                'minimum' => $institution->minimumAge(),
                'maximum' => $institution->maximumAge(),
            ],
            'maximum_term' => $institution->maximumTerm(),
            'maximum_paying_age' => $institution->maximumPayingAge(),
            'interest_rate' => $institution->getInterestRate()?->value(),
            'percent_down_payment' => $institution->getPercentDownPayment()->value(),
            'percent_miscellaneous_fees' => $institution->getPercentMiscellaneousFees()->value(),
            'loanable_value_multiplier' => $institution->getLoanableValueMultiplier(),
            'buffer_margin' => $institution->getBufferMargin()->value(),
            'income_requirement_multiplier' => $institution->getIncomeRequirementMultiplier()?->value(),
        ];
    }

    /**
     * Get all institutions formatted for API response.
     */
    public function getAllInstitutionsFormatted(): array
    {
        return $this->getAllInstitutions()
            ->map(fn ($institution) => $this->getInstitutionDetails($institution->key()))
            ->values()
            ->toArray();
    }

    /**
     * Check if lending institution exists.
     */
    public function exists(string $key): bool
    {
        return in_array($key, LendingInstitution::keys());
    }

    /**
     * Get default lending institution.
     */
    public function getDefaultInstitution(): LendingInstitution
    {
        $defaultKey = config('mortgage.default_lending_institution', 'hdmf');

        return $this->getInstitution($defaultKey);
    }

    /**
     * Calculate maximum allowable term for buyer age and institution.
     */
    public function calculateMaxTerm(string $institutionKey, int $buyerAge, ?int $overridePayingAge = null): int
    {
        $institution = $this->getInstitution($institutionKey);
        $maxPayingAge = $overridePayingAge ?? $institution->maximumPayingAge();
        $maxTermByAge = $maxPayingAge - $buyerAge;
        $maxTermByInstitution = $institution->maximumTerm();

        return min($maxTermByAge, $maxTermByInstitution);
    }

    /**
     * Get institutions suitable for given buyer age and term.
     */
    public function getInstitutionsForAgeAndTerm(int $buyerAge, int $desiredTerm): Collection
    {
        return $this->getAllInstitutions()
            ->filter(function ($institution) use ($buyerAge, $desiredTerm) {
                $maxTerm = $this->calculateMaxTerm($institution->key(), $buyerAge);

                return $maxTerm >= $desiredTerm;
            });
    }

    /**
     * Clear cached lending institution data.
     */
    public function clearCache(): void
    {
        Cache::forget('lending_institutions.all');
    }
}
