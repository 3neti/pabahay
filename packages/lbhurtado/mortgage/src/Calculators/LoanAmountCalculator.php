<?php

namespace LBHurtado\Mortgage\Calculators;

use LBHurtado\Mortgage\Attributes\CalculatorFor;
use LBHurtado\Mortgage\Enums\CalculatorType;
use LBHurtado\Mortgage\Factories\CalculatorFactory;
use LBHurtado\Mortgage\Factories\MoneyFactory;
use LBHurtado\Mortgage\ValueObjects\FeeCollection;
use LBHurtado\Mortgage\ValueObjects\PaymentBreakdown;
use Whitecube\Price\Price;

#[CalculatorFor(CalculatorType::LOAN_AMOUNT)]
final class LoanAmountCalculator extends BaseCalculator
{
    public function calculate(): Price
    {
        $loan = PaymentBreakdown::fromInputs($this->inputs)->loanable();
        $fees = new FeeCollection(addOns: [
            'miscellaneous fee' => CalculatorFactory::make(CalculatorType::MISCELLANEOUS_FEES, $this->inputs)->toFloat(),
        ]);

        return MoneyFactory::priceWithPrecision($loan->plus($fees->totalAddOns()));
    }

    public function toFloat(): float
    {
        return $this->calculate()->base()->getAmount()->toFloat();
    }
}
