<?php

namespace LBHurtado\Mortgage\Calculators;

use LBHurtado\Mortgage\Data\EquityComputationData;
use LBHurtado\Mortgage\Factories\MoneyFactory;
use Whitecube\Price\Price;

/**
 * Calculates home equity over time with appreciation and extra payments.
 * 
 * Helps answer:
 * - "How much equity do I have?"
 * - "When will I reach X% equity?"
 * - "How do extra payments affect my equity?"
 */
final class EquityCalculator
{
    public function __construct(public object $inputs) {}

    public static function fromInputs(object $inputs): static
    {
        return new static($inputs);
    }

    /**
     * Calculate equity analysis
     */
    public function calculate(): EquityComputationData
    {
        $originalLoanAmount = $this->getOriginalLoanAmount();
        $currentBalance = $this->getCurrentBalance();
        $homeValue = $this->getHomeValue();
        $monthlyPayment = $this->getMonthlyPayment();
        $rate = $this->getInterestRate();
        $termRemaining = $this->getTermRemaining();
        $appreciationRate = $this->getAppreciationRate();
        $extraPayment = $this->getExtraPayment();
        
        // Current equity calculation
        $currentEquityAmount = $this->calculateEquityAmount($homeValue, $currentBalance);
        $currentEquityPercent = $this->calculateEquityPercent($homeValue, $currentBalance);
        
        // Project equity over time (next 10 years or until paid off)
        $projectionYears = min(10, ceil($termRemaining / 12));
        $equityProjection = $this->projectEquity(
            $currentBalance,
            $homeValue,
            $monthlyPayment,
            $rate,
            $appreciationRate,
            $extraPayment,
            $projectionYears
        );
        
        // Calculate time to reach target equity (e.g., 20% for PMI removal)
        $targetEquityPercent = $this->getTargetEquityPercent();
        $monthsToTarget = $this->calculateMonthsToTargetEquity(
            $currentBalance,
            $homeValue,
            $monthlyPayment,
            $rate,
            $appreciationRate,
            $extraPayment,
            $targetEquityPercent
        );
        
        return new EquityComputationData(
            original_loan_amount: $originalLoanAmount,
            current_balance: $currentBalance,
            home_value: $homeValue,
            current_equity_amount: $currentEquityAmount,
            current_equity_percent: $currentEquityPercent,
            monthly_payment: $monthlyPayment,
            extra_payment: $extraPayment,
            appreciation_rate: $appreciationRate,
            equity_projection: $equityProjection,
            target_equity_percent: $targetEquityPercent,
            months_to_target: $monthsToTarget
        );
    }
    
    /**
     * Calculate current equity amount
     */
    protected function calculateEquityAmount(Price $homeValue, Price $balance): Price
    {
        $equity = $homeValue->getAmount()->toFloat() - $balance->getAmount()->toFloat();
        
        return MoneyFactory::priceWithPrecision(max(0, $equity));
    }
    
    /**
     * Calculate current equity percentage
     */
    protected function calculateEquityPercent(Price $homeValue, Price $balance): float
    {
        $value = $homeValue->getAmount()->toFloat();
        
        if ($value <= 0) {
            return 0.0;
        }
        
        $equity = $value - $balance->getAmount()->toFloat();
        
        return round((max(0, $equity) / $value) * 100, 2);
    }
    
    /**
     * Project equity growth over time
     */
    protected function projectEquity(
        Price $balance,
        Price $homeValue,
        Price $monthlyPayment,
        float $rate,
        float $appreciationRate,
        Price $extraPayment,
        int $years
    ): array {
        $projection = [];
        $currentBalance = $balance->getAmount()->toFloat();
        $currentValue = $homeValue->getAmount()->toFloat();
        $payment = $monthlyPayment->getAmount()->toFloat() + $extraPayment->getAmount()->toFloat();
        $monthlyRate = $rate / 12;
        $monthlyAppreciation = $appreciationRate / 12;
        
        for ($year = 1; $year <= $years; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                if ($currentBalance <= 0) {
                    break 2;
                }
                
                // Calculate interest and principal
                $interest = $currentBalance * $monthlyRate;
                $principal = min($payment - $interest, $currentBalance);
                $currentBalance -= $principal;
                
                // Appreciate home value
                $currentValue *= (1 + $monthlyAppreciation);
            }
            
            $equity = max(0, $currentValue - $currentBalance);
            $equityPercent = $currentValue > 0 ? ($equity / $currentValue) * 100 : 0;
            
            $projection[] = [
                'year' => $year,
                'balance' => round($currentBalance, 2),
                'home_value' => round($currentValue, 2),
                'equity_amount' => round($equity, 2),
                'equity_percent' => round($equityPercent, 2),
            ];
        }
        
        return $projection;
    }
    
    /**
     * Calculate months to reach target equity percentage
     */
    protected function calculateMonthsToTargetEquity(
        Price $balance,
        Price $homeValue,
        Price $monthlyPayment,
        float $rate,
        float $appreciationRate,
        Price $extraPayment,
        float $targetPercent
    ): int {
        $currentBalance = $balance->getAmount()->toFloat();
        $currentValue = $homeValue->getAmount()->toFloat();
        $payment = $monthlyPayment->getAmount()->toFloat() + $extraPayment->getAmount()->toFloat();
        $monthlyRate = $rate / 12;
        $monthlyAppreciation = $appreciationRate / 12;
        $months = 0;
        $maxMonths = 360; // 30 years max
        
        // Check if already at target
        $currentEquityPercent = $this->calculateEquityPercent($homeValue, $balance);
        if ($currentEquityPercent >= $targetPercent) {
            return 0;
        }
        
        while ($months < $maxMonths) {
            if ($currentBalance <= 0) {
                break;
            }
            
            // Calculate payment breakdown
            $interest = $currentBalance * $monthlyRate;
            $principal = min($payment - $interest, $currentBalance);
            $currentBalance -= $principal;
            
            // Appreciate home value
            $currentValue *= (1 + $monthlyAppreciation);
            
            $months++;
            
            // Check if target reached
            $equity = max(0, $currentValue - $currentBalance);
            $equityPercent = $currentValue > 0 ? ($equity / $currentValue) * 100 : 0;
            
            if ($equityPercent >= $targetPercent) {
                return $months;
            }
        }
        
        return $months; // Never reached or maxed out
    }
    
    // Getter methods
    protected function getOriginalLoanAmount(): Price
    {
        return MoneyFactory::priceWithPrecision($this->inputs->original_loan_amount ?? 0);
    }
    
    protected function getCurrentBalance(): Price
    {
        return MoneyFactory::priceWithPrecision($this->inputs->current_balance ?? 0);
    }
    
    protected function getHomeValue(): Price
    {
        return MoneyFactory::priceWithPrecision($this->inputs->home_value ?? 0);
    }
    
    protected function getMonthlyPayment(): Price
    {
        return MoneyFactory::priceWithPrecision($this->inputs->monthly_payment ?? 0);
    }
    
    protected function getInterestRate(): float
    {
        return $this->inputs->interest_rate ?? 0;
    }
    
    protected function getTermRemaining(): int
    {
        return $this->inputs->term_remaining ?? 0;
    }
    
    protected function getAppreciationRate(): float
    {
        return $this->inputs->appreciation_rate ?? 0.03; // Default 3% annual
    }
    
    protected function getExtraPayment(): Price
    {
        return MoneyFactory::priceWithPrecision($this->inputs->extra_payment ?? 0);
    }
    
    protected function getTargetEquityPercent(): float
    {
        return $this->inputs->target_equity_percent ?? 20.0; // Default 20% (PMI removal)
    }
}
