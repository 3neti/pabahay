<?php

namespace LBHurtado\Mortgage\Data;

use Spatie\LaravelData\Data;
use Whitecube\Price\Price;

class AffordabilityComputationData extends Data
{
    public function __construct(
        public Price $max_home_price,
        public Price $max_loan_amount,
        public Price $recommended_down_payment,
        public Price $estimated_monthly_payment,
        public float $debt_to_income_ratio,
        public Price $available_down_payment,
        public Price $monthly_gross_income,
        public Price $monthly_debts,
        public string $lending_institution,
        public int $loan_term_years,
    ) {}

    /**
     * Convert to array for API responses
     */
    public function toArray(): array
    {
        return [
            'max_home_price' => $this->max_home_price->inclusive()->getAmount()->toFloat(),
            'max_loan_amount' => $this->max_loan_amount->inclusive()->getAmount()->toFloat(),
            'recommended_down_payment' => $this->recommended_down_payment->inclusive()->getAmount()->toFloat(),
            'recommended_down_payment_percent' => $this->calculateDownPaymentPercent(),
            'estimated_monthly_payment' => $this->estimated_monthly_payment->inclusive()->getAmount()->toFloat(),
            'debt_to_income_ratio' => $this->debt_to_income_ratio,
            'available_down_payment' => $this->available_down_payment->inclusive()->getAmount()->toFloat(),
            'monthly_gross_income' => $this->monthly_gross_income->inclusive()->getAmount()->toFloat(),
            'monthly_debts' => $this->monthly_debts->inclusive()->getAmount()->toFloat(),
            'lending_institution' => strtoupper($this->lending_institution),
            'loan_term_years' => $this->loan_term_years,
        ];
    }

    /**
     * Calculate down payment as percentage of home price
     */
    protected function calculateDownPaymentPercent(): float
    {
        $homePrice = $this->max_home_price->inclusive()->getAmount()->toFloat();
        
        if ($homePrice <= 0) {
            return 0.0;
        }
        
        $downPayment = $this->recommended_down_payment->inclusive()->getAmount()->toFloat();
        
        return round(($downPayment / $homePrice) * 100, 2);
    }
}
