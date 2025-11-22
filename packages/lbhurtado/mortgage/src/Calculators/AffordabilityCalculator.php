<?php

namespace LBHurtado\Mortgage\Calculators;

use LBHurtado\Mortgage\Data\AffordabilityComputationData;
use LBHurtado\Mortgage\Data\Inputs\MortgageParticulars;
use LBHurtado\Mortgage\Enums\CalculatorType;
use LBHurtado\Mortgage\Factories\CalculatorFactory;
use LBHurtado\Mortgage\Factories\MoneyFactory;
use Whitecube\Price\Price;

/**
 * Calculates the maximum affordable home price based on income, debts, and down payment.
 * 
 * This calculator helps answer "How much house can I afford?" by considering:
 * - Monthly gross income
 * - Existing monthly debts
 * - Available down payment
 * - Lending institution requirements (income multiplier, DTI ratio)
 * - Desired loan term
 */
final class AffordabilityCalculator extends BaseCalculator
{
    /**
     * Calculate affordability metrics
     */
    public function calculate(): AffordabilityComputationData
    {
        $maxLoanAmount = $this->calculateMaxLoanAmount();
        $downPayment = $this->getDownPayment();
        $maxHomePrice = $maxLoanAmount->plus($downPayment);
        
        $recommendedDownPayment = $this->calculateRecommendedDownPayment($maxHomePrice);
        $estimatedMonthlyPayment = $this->calculateMonthlyPayment($maxLoanAmount);
        $debtToIncomeRatio = $this->calculateDebtToIncomeRatio($estimatedMonthlyPayment);

        return new AffordabilityComputationData(
            max_home_price: $maxHomePrice,
            max_loan_amount: $maxLoanAmount,
            recommended_down_payment: $recommendedDownPayment,
            estimated_monthly_payment: $estimatedMonthlyPayment,
            debt_to_income_ratio: $debtToIncomeRatio,
            available_down_payment: $downPayment,
            monthly_gross_income: $this->getMonthlyGrossIncome(),
            monthly_debts: $this->getMonthlyDebts(),
            lending_institution: $this->getLendingInstitution()->alias(),
            loan_term_years: $this->getLoanTerm()
        );
    }

    /**
     * Calculate maximum loan amount based on income and lending institution requirements
     */
    protected function calculateMaxLoanAmount(): Price
    {
        $monthlyIncome = $this->getMonthlyGrossIncome();
        $monthlyDebts = $this->getMonthlyDebts();
        $interestRate = $this->getMonthlyInterestRate();
        $termMonths = $this->getTermInMonths();
        
        // Calculate maximum monthly payment using debt-to-income ratio
        // Most lenders use 28-33% of gross monthly income for housing expenses
        $maxHousingPayment = $monthlyIncome->getAmount()->toFloat() * 0.33;
        
        // Subtract existing debts (conservative approach)
        $availableForHousing = max(0, $maxHousingPayment - $monthlyDebts->getAmount()->toFloat());
        
        // Calculate loan amount using present value of annuity formula
        // PV = PMT * [(1 - (1 + r)^-n) / r]
        if ($interestRate > 0) {
            $presentValueFactor = (1 - pow(1 + $interestRate, -$termMonths)) / $interestRate;
            $maxLoanAmount = $availableForHousing * $presentValueFactor;
        } else {
            // If no interest, simple multiplication
            $maxLoanAmount = $availableForHousing * $termMonths;
        }

        return MoneyFactory::priceWithPrecision($maxLoanAmount);
    }

    /**
     * Calculate recommended down payment based on lending institution requirements
     */
    protected function calculateRecommendedDownPayment(Price $homePrice): Price
    {
        $percentDp = $this->getLendingInstitution()->getPercentDownPayment()->value();
        $downPaymentAmount = $homePrice->getAmount()->toFloat() * $percentDp;
        
        return MoneyFactory::priceWithPrecision($downPaymentAmount);
    }

    /**
     * Calculate estimated monthly payment for the loan amount
     */
    protected function calculateMonthlyPayment(Price $loanAmount): Price
    {
        $principal = $loanAmount->getAmount()->toFloat();
        $rate = $this->getMonthlyInterestRate();
        $term = $this->getTermInMonths();
        
        if ($rate > 0) {
            // Monthly payment formula: M = P * [r(1 + r)^n] / [(1 + r)^n - 1]
            $monthlyPayment = $principal * ($rate * pow(1 + $rate, $term)) / (pow(1 + $rate, $term) - 1);
        } else {
            $monthlyPayment = $principal / $term;
        }

        return MoneyFactory::priceWithPrecision($monthlyPayment);
    }

    /**
     * Calculate debt-to-income ratio
     */
    protected function calculateDebtToIncomeRatio(Price $housingPayment): float
    {
        $monthlyIncome = $this->getMonthlyGrossIncome()->getAmount()->toFloat();
        $monthlyDebts = $this->getMonthlyDebts()->getAmount()->toFloat();
        $totalMonthlyObligations = $housingPayment->getAmount()->toFloat() + $monthlyDebts;
        
        if ($monthlyIncome <= 0) {
            return 0.0;
        }
        
        return round(($totalMonthlyObligations / $monthlyIncome) * 100, 2);
    }

    /**
     * Get monthly gross income
     */
    protected function getMonthlyGrossIncome(): Price
    {
        return $this->inputs->buyer()->getMonthlyGrossIncome();
    }

    /**
     * Get monthly debts from inputs (if provided)
     */
    protected function getMonthlyDebts(): Price
    {
        // Check if monthly_debts is provided in inputs, otherwise default to 0
        $debts = $this->inputs->monthly_debts ?? 0;
        
        return MoneyFactory::priceWithPrecision($debts);
    }

    /**
     * Get available down payment
     */
    protected function getDownPayment(): Price
    {
        // Use down_payment_available from inputs
        $downPayment = $this->inputs->down_payment_available ?? 0;
        
        return MoneyFactory::priceWithPrecision($downPayment);
    }

    /**
     * Get monthly interest rate
     */
    protected function getMonthlyInterestRate(): float
    {
        $annualRate = $this->getLendingInstitution()->getInterestRate()->value();
        
        return round($annualRate / 12, 15);
    }

    /**
     * Get loan term in months
     */
    protected function getTermInMonths(): int
    {
        return $this->getLoanTerm() * 12;
    }

    /**
     * Get lending institution
     */
    protected function getLendingInstitution()
    {
        return $this->inputs->property()->getLendingInstitution();
    }

    /**
     * Get loan term in years
     */
    protected function getLoanTerm(): int
    {
        // Check if override_loan_term is set in inputs
        if (isset($this->inputs->override_loan_term)) {
            return $this->inputs->override_loan_term;
        }
        
        // Use lending institution's maximum term as default
        return $this->getLendingInstitution()->maximumTerm();
    }
}
