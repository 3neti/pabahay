<?php

namespace LBHurtado\Mortgage\Extractors;

use LBHurtado\Mortgage\Attributes\ExtractorFor;
use LBHurtado\Mortgage\Enums\ExtractorType;
use LBHurtado\Mortgage\ValueObjects\Percent;

#[ExtractorFor(ExtractorType::INTEREST_RATE)]
class InterestRateExtractor extends BaseExtractor
{
    public function extract(): Percent
    {
        return $this->inputs->buyer()->getInterestRate()
            ?? ($this->inputs->order()->getInterestRate() ?? $this->inputs->property()->getInterestRate());
    }
}
