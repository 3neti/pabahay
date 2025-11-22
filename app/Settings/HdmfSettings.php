<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class HdmfSettings extends Settings
{
    public string $name;
    public string $alias;
    public string $type;
    public float $interest_rate;
    public float $percent_dp;
    public float $percent_mf;
    public int $borrowing_age_minimum;
    public int $borrowing_age_maximum;
    public int $borrowing_age_offset;
    public int $maximum_term;
    public int $maximum_paying_age;
    public float $buffer_margin;
    public float $income_requirement_multiplier;
    public float $loanable_value_multiplier;

    public static function group(): string
    {
        return 'hdmf';
    }
    
    public static function cacheKey(): string
    {
        return 'mortgage_settings_hdmf';
    }
}
