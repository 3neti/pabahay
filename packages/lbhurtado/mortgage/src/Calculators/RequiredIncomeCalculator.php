<?php

namespace LBHurtado\Mortgage\Calculators;

use Brick\Math\RoundingMode;
use LBHurtado\Mortgage\Attributes\CalculatorFor;
use LBHurtado\Mortgage\Enums\CalculatorType;
use LBHurtado\Mortgage\Enums\ExtractorType;
use LBHurtado\Mortgage\Factories\CalculatorFactory;
use LBHurtado\Mortgage\Factories\ExtractorFactory;
use Whitecube\Price\Price;

#[CalculatorFor(CalculatorType::REQUIRED_INCOME)]
class RequiredIncomeCalculator extends BaseCalculator
{
    public function calculate(): Price
    {
        $amortization = CalculatorFactory::make(CalculatorType::AMORTIZATION, $this->inputs)->total();
        $income_requirement_multiplier = ExtractorFactory::make(ExtractorType::INCOME_REQUIREMENT_MULTIPLIER, $this->inputs)->value();

        return $amortization->dividedBy($income_requirement_multiplier, RoundingMode::HALF_UP);
    }
}
