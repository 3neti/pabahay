<?php

namespace LBHurtado\Mortgage\Services;

use LBHurtado\Mortgage\Data\AmortizationScheduleData;
use LBHurtado\Mortgage\Data\AmortizationPaymentData;
use Spatie\LaravelData\DataCollection;

class AmortizationScheduleService
{
    /**
     * Generate complete amortization schedule.
     *
     * @param float $loanAmount Principal loan amount
     * @param float $annualInterestRate Annual interest rate as decimal (e.g., 0.0625 for 6.25%)
     * @param int $termYears Loan term in years
     * @param float $monthlyPayment Fixed monthly payment amount
     * @return AmortizationScheduleData
     */
    public function generate(
        float $loanAmount,
        float $annualInterestRate,
        int $termYears,
        float $monthlyPayment
    ): AmortizationScheduleData {
        $monthlyInterestRate = $annualInterestRate / 12;
        $totalPayments = $termYears * 12;
        
        $payments = [];
        $balance = $loanAmount;
        $totalPrincipal = 0;
        $totalInterest = 0;
        
        for ($paymentNumber = 1; $paymentNumber <= $totalPayments; $paymentNumber++) {
            $interestPayment = $balance * $monthlyInterestRate;
            $principalPayment = $monthlyPayment - $interestPayment;
            
            // Adjust last payment to account for rounding
            if ($paymentNumber === $totalPayments) {
                $principalPayment = $balance;
                $monthlyPayment = $principalPayment + $interestPayment;
            }
            
            $balance -= $principalPayment;
            $totalPrincipal += $principalPayment;
            $totalInterest += $interestPayment;
            
            $payments[] = new AmortizationPaymentData(
                paymentNumber: $paymentNumber,
                payment: $monthlyPayment,
                principal: $principalPayment,
                interest: $interestPayment,
                balance: max(0, $balance), // Prevent negative balance from rounding
                cumulativePrincipal: $totalPrincipal,
                cumulativeInterest: $totalInterest
            );
        }
        
        return new AmortizationScheduleData(
            loanAmount: $loanAmount,
            interestRate: $annualInterestRate,
            termYears: $termYears,
            monthlyPayment: $monthlyPayment,
            totalPayments: $totalPayments,
            totalAmount: $totalPrincipal + $totalInterest,
            totalPrincipal: $totalPrincipal,
            totalInterest: $totalInterest,
            payments: AmortizationPaymentData::collect($payments, DataCollection::class)
        );
    }
    
    /**
     * Calculate savings from extra payments.
     *
     * @param AmortizationScheduleData $originalSchedule
     * @param float $extraMonthlyPayment Additional payment per month
     * @return array{newSchedule: AmortizationScheduleData, savedInterest: float, savedMonths: int}
     */
    public function calculateExtraPaymentSavings(
        AmortizationScheduleData $originalSchedule,
        float $extraMonthlyPayment
    ): array {
        $newMonthlyPayment = $originalSchedule->monthlyPayment + $extraMonthlyPayment;
        
        $newSchedule = $this->generate(
            loanAmount: $originalSchedule->loanAmount,
            annualInterestRate: $originalSchedule->interestRate,
            termYears: $originalSchedule->termYears,
            monthlyPayment: $newMonthlyPayment
        );
        
        return [
            'newSchedule' => $newSchedule,
            'savedInterest' => $originalSchedule->totalInterest - $newSchedule->totalInterest,
            'savedMonths' => $originalSchedule->totalPayments - $newSchedule->totalPayments,
        ];
    }
    
    /**
     * Get yearly summary of payments.
     *
     * @param AmortizationScheduleData $schedule
     * @return array
     */
    public function getYearlySummary(AmortizationScheduleData $schedule): array
    {
        $years = [];
        $currentYear = 1;
        $yearPrincipal = 0;
        $yearInterest = 0;
        $yearPayment = 0;
        
        foreach ($schedule->payments as $index => $payment) {
            $yearPrincipal += $payment->principal;
            $yearInterest += $payment->interest;
            $yearPayment += $payment->payment;
            
            // End of year or last payment
            if (($index + 1) % 12 === 0 || $index === count($schedule->payments) - 1) {
                $years[] = [
                    'year' => $currentYear,
                    'totalPayment' => $yearPayment,
                    'principal' => $yearPrincipal,
                    'interest' => $yearInterest,
                    'endingBalance' => $payment->balance,
                ];
                
                $currentYear++;
                $yearPrincipal = 0;
                $yearInterest = 0;
                $yearPayment = 0;
            }
        }
        
        return $years;
    }
}
