<?php

namespace LBHurtado\Mortgage\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidBorrowerAge implements ValidationRule
{
    protected int $minAge;

    protected int $maxAge;

    public function __construct(?int $minAge = null, ?int $maxAge = null)
    {
        $this->minAge = $minAge ?? config('mortgage.limits.min_borrowing_age', 21);
        $this->maxAge = $maxAge ?? config('mortgage.limits.max_borrowing_age', 65);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value)) {
            $fail("The {$attribute} must be a valid number.");

            return;
        }

        $age = (int) $value;

        if ($age < $this->minAge) {
            $fail("The {$attribute} must be at least {$this->minAge} years old.");
        }

        if ($age > $this->maxAge) {
            $fail("The {$attribute} cannot exceed {$this->maxAge} years old.");
        }
    }
}
