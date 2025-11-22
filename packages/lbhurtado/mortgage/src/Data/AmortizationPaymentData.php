<?php

namespace LBHurtado\Mortgage\Data;

use Spatie\LaravelData\Data;

class AmortizationPaymentData extends Data
{
    public function __construct(
        public int $paymentNumber,
        public float $payment,
        public float $principal,
        public float $interest,
        public float $balance,
        public float $cumulativePrincipal,
        public float $cumulativeInterest,
    ) {}
}
