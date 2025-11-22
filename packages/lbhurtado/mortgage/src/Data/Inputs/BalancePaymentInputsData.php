<?php

namespace LBHurtado\Mortgage\Data\Inputs;

use LBHurtado\Mortgage\Contracts\BuyerInterface;
use LBHurtado\Mortgage\Contracts\OrderInterface;
use LBHurtado\Mortgage\Contracts\PropertyInterface;
use LBHurtado\Mortgage\Data\Transformers\PercentToFloatTransformer;
use LBHurtado\Mortgage\ValueObjects\Percent;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Data;

/** TODO: deprecate this */
class BalancePaymentInputsData extends Data
{
    public function __construct(
        public int $bp_term,
        #[WithTransformer(PercentToFloatTransformer::class)]
        public Percent $bp_interest_rate,
    ) {}

    public static function fromBooking(BuyerInterface $buyer, PropertyInterface $property, OrderInterface $order): static
    {
        return new static(
            bp_term: 0,
            bp_interest_rate: Percent::ofFraction(0)
        );
    }
}
