<?php

namespace LBHurtado\Mortgage\Casts;

use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;
use Whitecube\Price\Price;

class PriceCast implements Cast
{
    public function cast(
        DataProperty $property,
        mixed $value,
        array $properties,
        CreationContext $context
    ): mixed {
        if ($value instanceof Price) {
            // Ensure VAT is initialized
            if (!isset($value->vat)) {
                $value->setVat(0);
            }
            return $value;
        }

        if (is_array($value) && isset($value['amount'], $value['currency'])) {
            return Price::of($value['amount'], $value['currency'])->setVat(0);
        }

        if (is_numeric($value)) {
            return Price::of($value, 'PHP')->setVat(0); // default currency with VAT initialized
        }

        throw new \InvalidArgumentException('Cannot cast value to Price: '.print_r($value, true));
    }
}
