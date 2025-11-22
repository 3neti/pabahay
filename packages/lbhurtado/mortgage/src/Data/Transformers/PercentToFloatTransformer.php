<?php

namespace LBHurtado\Mortgage\Data\Transformers;

use LBHurtado\Mortgage\ValueObjects\Percent;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Support\Transformation\TransformationContext;
use Spatie\LaravelData\Transformers\Transformer;

class PercentToFloatTransformer implements Transformer
{
    public function transform(DataProperty $property, mixed $value, TransformationContext $context): mixed
    {
        // Check if the value is an instance of the Price class
        if ($value instanceof Percent) {
            // Return the float representation of the percent object
            return $value->value();
        }

        // Return the value unchanged if it's not of type Price
        return $value;
    }
}
