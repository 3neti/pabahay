<?php

namespace LBHurtado\Mortgage\Attributes;

use Attribute;
use LBHurtado\Mortgage\Enums\CalculatorType;

#[Attribute(Attribute::TARGET_CLASS)]
class CalculatorFor
{
    public function __construct(public CalculatorType $type) {}
}
