<?php

namespace LBHurtado\Mortgage\Data;

use Spatie\LaravelData\Data;
use Whitecube\Price\Price;

class RefinanceComputationData extends Data
{
    public function __construct(
        public Price $current_monthly_payment,
        public Price $current_total_interest,
        public Price $current_total_cost,
        public Price $new_monthly_payment,
        public Price $new_total_interest,
        public Price $new_total_cost,
        public Price $closing_costs,
        public Price $monthly_payment_difference,
        public Price $total_interest_savings,
        public Price $lifetime_savings,
        public int $break_even_months,
        public string $recommendation,
        public Price $current_balance,
        public float $current_rate,
        public int $current_term_remaining,
        public float $new_rate,
        public int $new_term,
    ) {}

    /**
     * Convert to array for API responses
     */
    public function toArray(): array
    {
        return [
            'current_loan' => [
                'balance' => $this->current_balance->inclusive()->getAmount()->toFloat(),
                'rate' => $this->current_rate * 100, // Convert to percentage
                'term_remaining_months' => $this->current_term_remaining,
                'monthly_payment' => $this->current_monthly_payment->inclusive()->getAmount()->toFloat(),
                'total_interest' => $this->current_total_interest->inclusive()->getAmount()->toFloat(),
                'total_cost' => $this->current_total_cost->inclusive()->getAmount()->toFloat(),
            ],
            'new_loan' => [
                'balance' => $this->current_balance->inclusive()->getAmount()->toFloat(),
                'rate' => $this->new_rate * 100, // Convert to percentage
                'term_months' => $this->new_term,
                'monthly_payment' => $this->new_monthly_payment->inclusive()->getAmount()->toFloat(),
                'total_interest' => $this->new_total_interest->inclusive()->getAmount()->toFloat(),
                'total_cost' => $this->new_total_cost->inclusive()->getAmount()->toFloat(),
                'closing_costs' => $this->closing_costs->inclusive()->getAmount()->toFloat(),
            ],
            'analysis' => [
                'monthly_payment_difference' => $this->monthly_payment_difference->inclusive()->getAmount()->toFloat(),
                'total_interest_savings' => $this->total_interest_savings->inclusive()->getAmount()->toFloat(),
                'lifetime_savings' => $this->lifetime_savings->inclusive()->getAmount()->toFloat(),
                'break_even_months' => $this->break_even_months,
                'break_even_years' => round($this->break_even_months / 12, 1),
                'recommendation' => $this->recommendation,
                'recommendation_text' => $this->getRecommendationText(),
            ],
        ];
    }

    /**
     * Get human-readable recommendation text
     */
    protected function getRecommendationText(): string
    {
        return match($this->recommendation) {
            'recommended' => 'Refinancing is recommended. You will save money and break even within a reasonable timeframe.',
            'caution' => 'Refinancing may be beneficial but requires careful consideration. Break-even period is relatively long.',
            'not_recommended' => 'Refinancing is not recommended. You will not save money with the proposed terms.',
            default => 'Unable to determine recommendation.',
        };
    }
}
