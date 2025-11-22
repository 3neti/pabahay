<?php

namespace LBHurtado\Mortgage\ValueObjects;

use LBHurtado\Mortgage\Factories\MoneyFactory;
use Whitecube\Price\Price;

final class Equity
{
    public function __construct(
        public readonly Price $amount
    ) {}

    public static function zero(): self
    {
        return new self(MoneyFactory::priceZero());
    }

    public function isZero(): bool
    {
        return $this->amount->base()->isZero();
    }

    public function greaterThan(Price $other): bool
    {
        return $this->amount->base()->isGreaterThan($other->base());
    }

    public function asPercentOf(Price $base): float
    {
        if ($base->base()->isZero()) {
            return 0.0;
        }

        $equity = $this->amount->base()->getAmount()->toFloat();
        $baseAmount = $base->base()->getAmount()->toFloat();

        return round(($equity / $baseAmount) * 100, 2);
    }

    public function toPrice(): Price
    {
        return $this->amount;
    }
}
