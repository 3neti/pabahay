<?php

namespace LBHurtado\Mortgage\Calculators;

use LBHurtado\Mortgage\Data\RefinanceComputationData;
use LBHurtado\Mortgage\Factories\MoneyFactory;
use Whitecube\Price\Price;

/**
 * Calculates refinancing analysis comparing current loan vs new loan.
 * 
 * Helps answer "Should I refinance my mortgage?" by analyzing:
 * - Monthly payment savings
 * - Total interest savings over loan life
 * - Break-even point (months to recover closing costs)
 * - Lifetime cost comparison
 */
final class RefinanceCalculator
{
    public function __construct(public object $inputs) {}

    public static function fromInputs(object $inputs): static
    {
        return new static($inputs);
    }

    /**
     * Calculate refinancing analysis
     */
    public function calculate(): RefinanceComputationData
    {
        // Current loan calculations
        $currentMonthlyPayment = $this->getCurrentMonthlyPayment();
        $currentTotalInterest = $this->calculateTotalInterest(
            $this->getCurrentBalance(),
            $this->getCurrentRate(),
            $this->getCurrentTermRemaining(),
            $currentMonthlyPayment
        );
        $currentTotalCost = $this->calculateTotalCost(
            $currentMonthlyPayment,
            $this->getCurrentTermRemaining(),
            0 // No closing costs for current loan
        );
        
        // New loan calculations
        $newMonthlyPayment = $this->calculateNewMonthlyPayment();
        $newTotalInterest = $this->calculateTotalInterest(
            $this->getCurrentBalance(),
            $this->getNewRate(),
            $this->getNewTerm(),
            $newMonthlyPayment
        );
        $closingCosts = $this->getClosingCosts();
        $newTotalCost = $this->calculateTotalCost(
            $newMonthlyPayment,
            $this->getNewTerm(),
            $closingCosts->getAmount()->toFloat()
        );
        
        // Comparative analysis
        $monthlyPaymentDifference = $this->calculateDifference($currentMonthlyPayment, $newMonthlyPayment);
        $totalInterestSavings = $this->calculateDifference($currentTotalInterest, $newTotalInterest);
        $lifetimeSavings = $this->calculateDifference($currentTotalCost, $newTotalCost);
        $breakEvenMonths = $this->calculateBreakEvenPoint(
            $monthlyPaymentDifference,
            $closingCosts
        );
        
        $recommendation = $this->generateRecommendation(
            $monthlyPaymentDifference,
            $lifetimeSavings,
            $breakEvenMonths,
            $this->getNewTerm()
        );
        
        return new RefinanceComputationData(
            current_monthly_payment: $currentMonthlyPayment,
            current_total_interest: $currentTotalInterest,
            current_total_cost: $currentTotalCost,
            new_monthly_payment: $newMonthlyPayment,
            new_total_interest: $newTotalInterest,
            new_total_cost: $newTotalCost,
            closing_costs: $closingCosts,
            monthly_payment_difference: $monthlyPaymentDifference,
            total_interest_savings: $totalInterestSavings,
            lifetime_savings: $lifetimeSavings,
            break_even_months: $breakEvenMonths,
            recommendation: $recommendation,
            current_balance: $this->getCurrentBalance(),
            current_rate: $this->getCurrentRate(),
            current_term_remaining: $this->getCurrentTermRemaining(),
            new_rate: $this->getNewRate(),
            new_term: $this->getNewTerm()
        );
    }
    
    /**
     * Get current monthly payment
     */
    protected function getCurrentMonthlyPayment(): Price
    {
        // User provides current monthly payment
        $payment = $this->inputs->current_monthly_payment ?? 0;
        
        return MoneyFactory::priceWithPrecision($payment);
    }
    
    /**
     * Calculate new monthly payment
     */
    protected function calculateNewMonthlyPayment(): Price
    {
        $principal = $this->getCurrentBalance()->getAmount()->toFloat();
        $rate = $this->getNewRate() / 12; // Monthly rate
        $term = $this->getNewTerm(); // Already in months from input
        
        if ($rate > 0) {
            // Monthly payment formula: M = P * [r(1 + r)^n] / [(1 + r)^n - 1]
            $monthlyPayment = $principal * ($rate * pow(1 + $rate, $term)) / (pow(1 + $rate, $term) - 1);
        } else {
            $monthlyPayment = $principal / $term;
        }
        
        return MoneyFactory::priceWithPrecision($monthlyPayment);
    }
    
    /**
     * Calculate total interest paid over loan term
     */
    protected function calculateTotalInterest(Price $balance, float $rate, int $termMonths, Price $monthlyPayment): Price
    {
        $totalPaid = $monthlyPayment->getAmount()->toFloat() * $termMonths;
        $principal = $balance->getAmount()->toFloat();
        $totalInterest = $totalPaid - $principal;
        
        return MoneyFactory::priceWithPrecision(max(0, $totalInterest));
    }
    
    /**
     * Calculate total cost including closing costs
     */
    protected function calculateTotalCost(Price $monthlyPayment, int $termMonths, float $closingCosts): Price
    {
        $totalPayments = $monthlyPayment->getAmount()->toFloat() * $termMonths;
        $totalCost = $totalPayments + $closingCosts;
        
        return MoneyFactory::priceWithPrecision($totalCost);
    }
    
    /**
     * Calculate difference between two prices
     */
    protected function calculateDifference(Price $amount1, Price $amount2): Price
    {
        $difference = $amount1->getAmount()->toFloat() - $amount2->getAmount()->toFloat();
        
        return MoneyFactory::priceWithPrecision($difference);
    }
    
    /**
     * Calculate break-even point in months
     */
    protected function calculateBreakEvenPoint(Price $monthlySavings, Price $closingCosts): int
    {
        $savings = $monthlySavings->getAmount()->toFloat();
        $costs = $closingCosts->getAmount()->toFloat();
        
        if ($savings <= 0 || $costs <= 0) {
            return 0; // No break-even if not saving money or no closing costs
        }
        
        return (int) ceil($costs / $savings);
    }
    
    /**
     * Generate recommendation based on analysis
     */
    protected function generateRecommendation(
        Price $monthlySavings,
        Price $lifetimeSavings,
        int $breakEvenMonths,
        int $newTermMonths
    ): string {
        $savings = $monthlySavings->getAmount()->toFloat();
        $lifetime = $lifetimeSavings->getAmount()->toFloat();
        
        // Not saving money (either monthly or lifetime negative)
        if ($savings <= 0 || $lifetime <= 0) {
            return 'not_recommended';
        }
        
        // Break-even is too long (more than 1/3 of new loan term)
        if ($breakEvenMonths > ($newTermMonths / 3)) {
            return 'caution';
        }
        
        // Good savings and reasonable break-even
        if ($savings > 0 && $lifetime > 0 && $breakEvenMonths <= ($newTermMonths / 3)) {
            return 'recommended';
        }
        
        return 'caution';
    }
    
    /**
     * Get current loan balance
     */
    protected function getCurrentBalance(): Price
    {
        $balance = $this->inputs->current_balance ?? 0;
        
        return MoneyFactory::priceWithPrecision($balance);
    }
    
    /**
     * Get current interest rate (annual, as fraction)
     */
    protected function getCurrentRate(): float
    {
        return $this->inputs->current_rate ?? 0;
    }
    
    /**
     * Get current term remaining in months
     */
    protected function getCurrentTermRemaining(): int
    {
        return $this->inputs->current_term_remaining ?? 0;
    }
    
    /**
     * Get new interest rate (annual, as fraction)
     */
    protected function getNewRate(): float
    {
        return $this->inputs->new_rate ?? 0;
    }
    
    /**
     * Get new loan term in months
     */
    protected function getNewTerm(): int
    {
        return $this->inputs->new_term ?? 0;
    }
    
    /**
     * Get closing costs for refinancing
     */
    protected function getClosingCosts(): Price
    {
        $costs = $this->inputs->closing_costs ?? 0;
        
        return MoneyFactory::priceWithPrecision($costs);
    }
}
