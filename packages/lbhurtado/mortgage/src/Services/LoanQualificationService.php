<?php

namespace LBHurtado\Mortgage\Services;

use LBHurtado\Mortgage\Data\MortgageComputationData;

class LoanQualificationService
{
    /**
     * Determine if loan qualifies based on computation.
     */
    public function qualifies(MortgageComputationData $computation): bool
    {
        return $computation->qualifies;
    }

    /**
     * Get qualification reason.
     */
    public function getQualificationReason(MortgageComputationData $computation): string
    {
        if ($computation->qualifies) {
            return 'Sufficient disposable income';
        }

        $incomeGap = $computation->income_gap->getAmount()->toFloat();
        $requiredEquity = $computation->required_equity->getAmount()->toFloat();

        if ($incomeGap > 0 && $requiredEquity > 0) {
            return 'Insufficient income and equity required';
        }

        if ($incomeGap > 0) {
            return 'Disposable income below amortization';
        }

        if ($requiredEquity > 0) {
            return 'Additional equity required';
        }

        return 'Does not qualify';
    }

    /**
     * Get suggested remedies for non-qualifying loans.
     */
    public function getSuggestedRemedies(MortgageComputationData $computation): array
    {
        if ($computation->qualifies) {
            return [];
        }

        $remedies = [];

        $incomeGap = $computation->income_gap->getAmount()->toFloat();
        if ($incomeGap > 0) {
            $remedies[] = [
                'type' => 'additional_income',
                'description' => 'Add co-borrower or increase monthly gross income',
                'amount_needed' => $incomeGap,
            ];
        }

        $suggestedDownPayment = $computation->percent_down_payment_remedy->value();
        $currentDownPayment = $computation->percent_down_payment->value();
        if ($suggestedDownPayment > $currentDownPayment) {
            $remedies[] = [
                'type' => 'increase_down_payment',
                'description' => 'Increase down payment percentage',
                'current_percent' => $currentDownPayment,
                'suggested_percent' => $suggestedDownPayment,
                'additional_amount' => $computation->total_contract_price->base()->getAmount()->toFloat()
                    * ($suggestedDownPayment - $currentDownPayment),
            ];
        }

        $requiredEquity = $computation->required_equity->getAmount()->toFloat();
        if ($requiredEquity > 0) {
            $remedies[] = [
                'type' => 'additional_equity',
                'description' => 'Additional equity required',
                'amount_needed' => $requiredEquity,
            ];
        }

        return $remedies;
    }

    /**
     * Format qualification result for API response.
     */
    public function formatQualificationResult(MortgageComputationData $computation): array
    {
        return [
            'qualifies' => $computation->qualifies,
            'reason' => $this->getQualificationReason($computation),
            'income_gap' => $computation->income_gap->getAmount()->toFloat(),
            'required_equity' => $computation->required_equity->getAmount()->toFloat(),
            'suggested_down_payment_percent' => $computation->percent_down_payment_remedy->value(),
            'remedies' => $this->getSuggestedRemedies($computation),
            'mortgage' => [
                'monthly_amortization' => $computation->monthly_amortization->getAmount()->toFloat(),
                'balance_payment_term' => $computation->balance_payment_term,
                'loanable_amount' => $computation->loanable_amount->getAmount()->toFloat(),
            ],
        ];
    }

    /**
     * Check if buyer age qualifies for given term.
     */
    public function checkAgeQualification(int $age, int $termYears, int $maxPayingAge = 65): bool
    {
        $ageAtEndOfTerm = $age + $termYears;

        return $ageAtEndOfTerm <= $maxPayingAge;
    }

    /**
     * Calculate maximum term allowed for given age.
     */
    public function calculateMaxTermForAge(int $age, int $maxPayingAge = 65): int
    {
        return max(0, $maxPayingAge - $age);
    }
}
