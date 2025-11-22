<?php

namespace LBHurtado\Mortgage\Calculators;

use LBHurtado\Mortgage\Data\EarlyPaymentComputationData;
use LBHurtado\Mortgage\Factories\MoneyFactory;
use Whitecube\Price\Price;

/**
 * Calculates savings from making extra/early payments on a mortgage.
 * 
 * Helps answer:
 * - "How much will I save by paying extra each month?"
 * - "When will my loan be paid off with extra payments?"
 * - "Should I make a lump sum payment?"
 */
final class EarlyPaymentCalculator
{
    public function __construct(public object $inputs) {}

    public static function fromInputs(object $inputs): static
    {
        return new static($inputs);
    }

    /**
     * Calculate early payment savings analysis
     */
    public function calculate(): EarlyPaymentComputationData
    {
        $balance = $this->getCurrentBalance();
        $rate = $this->getInterestRate();
        $termRemaining = $this->getTermRemaining();
        $monthlyPayment = $this->getMonthlyPayment();
        
        // Standard payoff (no extra payments)
        $standardPayoff = $this->calculatePayoffScenario(
            $balance,
            $rate,
            $termRemaining,
            $monthlyPayment,
            MoneyFactory::priceWithPrecision(0)
        );
        
        // With extra payments
        $extraPayment = $this->getExtraPayment();
        $extraPayoff = $this->calculatePayoffScenario(
            $balance,
            $rate,
            $termRemaining,
            $monthlyPayment,
            $extraPayment
        );
        
        // Calculate savings
        $interestSavings = $this->calculateDifference(
            $standardPayoff['total_interest'],
            $extraPayoff['total_interest']
        );
        
        $timeSavedMonths = $standardPayoff['months_to_payoff'] - $extraPayoff['months_to_payoff'];
        $timeSavedYears = round($timeSavedMonths / 12, 1);
        
        $totalSavings = $this->calculateDifference(
            $standardPayoff['total_paid'],
            $extraPayoff['total_paid']
        );
        
        return new EarlyPaymentComputationData(
            current_balance: $balance,
            interest_rate: $rate,
            monthly_payment: $monthlyPayment,
            extra_payment: $extraPayment,
            standard_months_to_payoff: $standardPayoff['months_to_payoff'],
            standard_payoff_date: $standardPayoff['payoff_date'],
            standard_total_interest: $standardPayoff['total_interest'],
            standard_total_paid: $standardPayoff['total_paid'],
            accelerated_months_to_payoff: $extraPayoff['months_to_payoff'],
            accelerated_payoff_date: $extraPayoff['payoff_date'],
            accelerated_total_interest: $extraPayoff['total_interest'],
            accelerated_total_paid: $extraPayoff['total_paid'],
            interest_savings: $interestSavings,
            time_saved_months: $timeSavedMonths,
            time_saved_years: $timeSavedYears,
            total_savings: $totalSavings
        );
    }
    
    /**
     * Calculate payoff scenario with given parameters
     */
    protected function calculatePayoffScenario(
        Price $balance,
        float $rate,
        int $termRemaining,
        Price $monthlyPayment,
        Price $extraPayment
    ): array {
        $currentBalance = $balance->getAmount()->toFloat();
        $payment = $monthlyPayment->getAmount()->toFloat() + $extraPayment->getAmount()->toFloat();
        $monthlyRate = $rate / 12;
        $totalInterest = 0;
        $months = 0;
        $maxMonths = $termRemaining;
        
        while ($currentBalance > 0 && $months < $maxMonths) {
            $interest = $currentBalance * $monthlyRate;
            $principal = min($payment - $interest, $currentBalance);
            
            if ($principal <= 0) {
                // Payment too low to make progress
                break;
            }
            
            $totalInterest += $interest;
            $currentBalance -= $principal;
            $months++;
        }
        
        $totalPaid = ($payment * $months);
        $payoffDate = date('Y-m-d', strtotime("+{$months} months"));
        
        return [
            'months_to_payoff' => $months,
            'payoff_date' => $payoffDate,
            'total_interest' => MoneyFactory::priceWithPrecision($totalInterest),
            'total_paid' => MoneyFactory::priceWithPrecision($totalPaid),
        ];
    }
    
    /**
     * Calculate difference between two prices
     */
    protected function calculateDifference(Price $amount1, Price $amount2): Price
    {
        $difference = $amount1->getAmount()->toFloat() - $amount2->getAmount()->toFloat();
        
        return MoneyFactory::priceWithPrecision($difference);
    }
    
    // Getter methods
    protected function getCurrentBalance(): Price
    {
        return MoneyFactory::priceWithPrecision($this->inputs->current_balance ?? 0);
    }
    
    protected function getInterestRate(): float
    {
        return $this->inputs->interest_rate ?? 0;
    }
    
    protected function getTermRemaining(): int
    {
        return $this->inputs->term_remaining ?? 0;
    }
    
    protected function getMonthlyPayment(): Price
    {
        return MoneyFactory::priceWithPrecision($this->inputs->monthly_payment ?? 0);
    }
    
    protected function getExtraPayment(): Price
    {
        return MoneyFactory::priceWithPrecision($this->inputs->extra_payment ?? 0);
    }
}
