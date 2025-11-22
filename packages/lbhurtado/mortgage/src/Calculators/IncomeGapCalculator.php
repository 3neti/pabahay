<?php

namespace LBHurtado\Mortgage\Calculators;

use LBHurtado\Mortgage\Attributes\CalculatorFor;
use LBHurtado\Mortgage\Enums\CalculatorType;
use LBHurtado\Mortgage\Factories\CalculatorFactory;
use LBHurtado\Mortgage\Factories\MoneyFactory;
use Whitecube\Price\Price;

#[CalculatorFor(CalculatorType::INCOME_GAP)]
class IncomeGapCalculator extends BaseCalculator
{
    public function calculate(): Price
    {
        $monthly_amortization = CalculatorFactory::make(CalculatorType::AMORTIZATION, $this->inputs)->total()->base();
        $disposable_income = CalculatorFactory::make(CalculatorType::DISPOSABLE_INCOME, $this->inputs)->calculate();
        $gap = max(0, round($monthly_amortization->getAmount()->toFloat() - $disposable_income->getAmount()->toFloat(), 2));

        return MoneyFactory::price($gap);
    }

    public function toFloat(): float
    {
        return $this->calculate()->base()->getAmount()->toFloat();
    }
}
