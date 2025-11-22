<?php

namespace LBHurtado\Mortgage\Data;

use Spatie\LaravelData\Data;
use Whitecube\Price\Price;

class EarlyPaymentComputationData extends Data
{
    public function __construct(
        public Price $current_balance,
        public float $interest_rate,
        public Price $monthly_payment,
        public Price $extra_payment,
        public int $standard_months_to_payoff,
        public string $standard_payoff_date,
        public Price $standard_total_interest,
        public Price $standard_total_paid,
        public int $accelerated_months_to_payoff,
        public string $accelerated_payoff_date,
        public Price $accelerated_total_interest,
        public Price $accelerated_total_paid,
        public Price $interest_savings,
        public int $time_saved_months,
        public float $time_saved_years,
        public Price $total_savings,
    ) {}

    public function toArray(): array
    {
        return [
            'current_balance' => $this->current_balance->inclusive()->getAmount()->toFloat(),
            'interest_rate' => $this->interest_rate * 100, // Convert to percentage
            'monthly_payment' => $this->monthly_payment->inclusive()->getAmount()->toFloat(),
            'extra_payment' => $this->extra_payment->inclusive()->getAmount()->toFloat(),
            'standard_scenario' => [
                'months_to_payoff' => $this->standard_months_to_payoff,
                'years_to_payoff' => round($this->standard_months_to_payoff / 12, 1),
                'payoff_date' => $this->standard_payoff_date,
                'total_interest' => $this->standard_total_interest->inclusive()->getAmount()->toFloat(),
                'total_paid' => $this->standard_total_paid->inclusive()->getAmount()->toFloat(),
            ],
            'accelerated_scenario' => [
                'months_to_payoff' => $this->accelerated_months_to_payoff,
                'years_to_payoff' => round($this->accelerated_months_to_payoff / 12, 1),
                'payoff_date' => $this->accelerated_payoff_date,
                'total_interest' => $this->accelerated_total_interest->inclusive()->getAmount()->toFloat(),
                'total_paid' => $this->accelerated_total_paid->inclusive()->getAmount()->toFloat(),
            ],
            'savings' => [
                'interest_savings' => $this->interest_savings->inclusive()->getAmount()->toFloat(),
                'time_saved_months' => $this->time_saved_months,
                'time_saved_years' => $this->time_saved_years,
                'total_savings' => $this->total_savings->inclusive()->getAmount()->toFloat(),
            ],
        ];
    }
}
