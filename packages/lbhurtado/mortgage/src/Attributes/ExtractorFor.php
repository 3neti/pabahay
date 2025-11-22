<?php

namespace LBHurtado\Mortgage\Attributes;

use Attribute;
use LBHurtado\Mortgage\Enums\ExtractorType;

#[Attribute(Attribute::TARGET_CLASS)]
class ExtractorFor
{
    public function __construct(public ExtractorType $type) {}
}
