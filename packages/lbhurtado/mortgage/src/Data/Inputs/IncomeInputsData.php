<?php

namespace LBHurtado\Mortgage\Data\Inputs;

use LBHurtado\Mortgage\Contracts\BuyerInterface;
use LBHurtado\Mortgage\Contracts\OrderInterface;
use LBHurtado\Mortgage\Contracts\PropertyInterface;
use LBHurtado\Mortgage\Data\Transformers\PercentToFloatTransformer;
use LBHurtado\Mortgage\Data\Transformers\PriceToFloatTransformer;
use LBHurtado\Mortgage\ValueObjects\Percent;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Data;
use Whitecube\Price\Price;

/** TODO: deprecate this  */
class IncomeInputsData extends Data
{
    public function __construct(
        #[WithTransformer(PriceToFloatTransformer::class)]
        public Price $gross_monthly_income,
        #[WithTransformer(PercentToFloatTransformer::class)]
        public ?Percent $income_requirement_multiplier = null
    ) {}

    public static function fromBooking(BuyerInterface $buyer, PropertyInterface $property, OrderInterface $order): static
    {
        return new static(
            gross_monthly_income: $buyer->getMonthlyGrossIncome(),// @deprecated
            income_requirement_multiplier: $order->getIncomeRequirementMultiplier() ?? ($property->getIncomeRequirementMultiplier() ?? $buyer->getIncomeRequirementMultiplier()),
        );
    }
}
