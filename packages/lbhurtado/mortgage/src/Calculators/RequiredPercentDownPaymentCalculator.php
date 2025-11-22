<?php

namespace LBHurtado\Mortgage\Calculators;

use LBHurtado\Mortgage\Attributes\CalculatorFor;
use LBHurtado\Mortgage\Enums\CalculatorType;
use LBHurtado\Mortgage\Enums\ExtractorType;
use LBHurtado\Mortgage\Factories\CalculatorFactory;
use LBHurtado\Mortgage\Factories\ExtractorFactory;
use LBHurtado\Mortgage\ValueObjects\Percent;

#[CalculatorFor(CalculatorType::REQUIRED_PERCENT_DOWN_PAYMENT)]
class RequiredPercentDownPaymentCalculator extends BaseCalculator
{
    public function calculate(): Percent
    {
        // Retrieve original order percent dp
        $original_percent_dp = $this->inputs->order()->getPercentDownPayment();

        // Override the down payment to 0%
        $this->inputs->order()->setPercentDownPayment(0.0);

        $required_equity = CalculatorFactory::make(CalculatorType::EQUITY, $this->inputs)->calculate()->toPrice()
            ->base()
            ->getAmount()
            ->toFloat();
        $tcp = ExtractorFactory::make(ExtractorType::TOTAL_CONTRACT_PRICE, $this->inputs)->extract()
            ->base()
            ->getAmount()
            ->toFloat();

        $percent_dp = ExtractorFactory::make(ExtractorType::PERCENT_DOWN_PAYMENT, $this->inputs)->extract()->value();

        // Determine the suggested down payment as the next whole percent (e.g., 30.01% becomes 31%), or 0% if the ratio is negligible (< 1%)
        $ratio = $required_equity / $tcp;
        $calculated_percent = ceil($ratio * 100);
        $dp_as_percent = $percent_dp * 100;
        
        $percent = $ratio < 0.01
            ? 0.00
            : min(max($calculated_percent, $dp_as_percent), 100); // Clamp between 0 and 100

        // Replace with the original percent dp
        $this->inputs->order()->setPercentDownPayment($original_percent_dp);

        return Percent::ofPercent($percent); // ← this handles 31% → 0.31 automatically
    }
}
