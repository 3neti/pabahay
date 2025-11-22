<?php

namespace LBHurtado\Mortgage\Data;

use Spatie\LaravelData\Data;
use Whitecube\Price\Price;

class EquityComputationData extends Data
{
    public function __construct(
        public Price $original_loan_amount,
        public Price $current_balance,
        public Price $home_value,
        public Price $current_equity_amount,
        public float $current_equity_percent,
        public Price $monthly_payment,
        public Price $extra_payment,
        public float $appreciation_rate,
        public array $equity_projection,
        public float $target_equity_percent,
        public int $months_to_target,
    ) {}

    public function toArray(): array
    {
        return [
            'original_loan_amount' => $this->original_loan_amount->inclusive()->getAmount()->toFloat(),
            'current_balance' => $this->current_balance->inclusive()->getAmount()->toFloat(),
            'home_value' => $this->home_value->inclusive()->getAmount()->toFloat(),
            'current_equity' => [
                'amount' => $this->current_equity_amount->inclusive()->getAmount()->toFloat(),
                'percent' => $this->current_equity_percent,
            ],
            'monthly_payment' => $this->monthly_payment->inclusive()->getAmount()->toFloat(),
            'extra_payment' => $this->extra_payment->inclusive()->getAmount()->toFloat(),
            'appreciation_rate' => $this->appreciation_rate * 100, // Convert to percentage
            'equity_projection' => $this->equity_projection,
            'target' => [
                'equity_percent' => $this->target_equity_percent,
                'months_to_reach' => $this->months_to_target,
                'years_to_reach' => round($this->months_to_target / 12, 1),
            ],
        ];
    }
}
