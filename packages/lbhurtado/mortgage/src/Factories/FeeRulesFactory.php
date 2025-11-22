<?php

namespace LBHurtado\Mortgage\Factories;

use LBHurtado\Mortgage\Classes\LendingInstitution;
use LBHurtado\Mortgage\Contracts\FeeRulesInterface;
use LBHurtado\Mortgage\Rules\FeeRules;
use LBHurtado\Mortgage\Rules\HousingFeeRules;
use LBHurtado\Mortgage\Rules\ResidentialFeeRules;

class FeeRulesFactory
{
    public static function make(LendingInstitution $institution): FeeRulesInterface
    {
        return match ($institution->key()) {
            'hdmf' => new HousingFeeRules($institution),
            'rcbc', 'cbc' => new ResidentialFeeRules($institution),
            default => new FeeRules($institution), // fallback default rule
        };
    }
}
