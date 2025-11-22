<?php

namespace LBHurtado\Mortgage\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class AmortizationScheduleData extends Data
{
    public function __construct(
        public float $loanAmount,
        public float $interestRate,
        public int $termYears,
        public float $monthlyPayment,
        public int $totalPayments,
        public float $totalAmount,
        public float $totalPrincipal,
        public float $totalInterest,
        #[DataCollectionOf(AmortizationPaymentData::class)]
        public DataCollection $payments,
    ) {}
}
