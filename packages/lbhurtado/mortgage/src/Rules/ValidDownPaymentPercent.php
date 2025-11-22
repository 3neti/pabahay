<?php

namespace LBHurtado\Mortgage\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidDownPaymentPercent implements ValidationRule
{
    protected float $min;

    protected float $max;

    public function __construct(float $min = 0.0, float $max = 1.0)
    {
        $this->min = $min;
        $this->max = $max;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value)) {
            $fail("The {$attribute} must be a valid number.");

            return;
        }

        $percent = (float) $value;

        if ($percent < $this->min) {
            $minPercent = $this->min * 100;
            $fail("The {$attribute} must be at least {$minPercent}%.");
        }

        if ($percent > $this->max) {
            $maxPercent = $this->max * 100;
            $fail("The {$attribute} cannot exceed {$maxPercent}%.");
        }
    }
}
