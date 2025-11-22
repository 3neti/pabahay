<?php

namespace LBHurtado\Mortgage\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use LBHurtado\Mortgage\Classes\LendingInstitution;

class ValidLoanTerm implements ValidationRule
{
    protected ?int $buyerAge;

    protected ?string $lendingInstitution;

    public function __construct(?int $buyerAge = null, ?string $lendingInstitution = null)
    {
        $this->buyerAge = $buyerAge;
        $this->lendingInstitution = $lendingInstitution;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value) || $value <= 0) {
            $fail("The {$attribute} must be a positive number.");

            return;
        }

        $term = (int) $value;

        // If lending institution is provided, check against its maximum term
        if ($this->lendingInstitution) {
            try {
                $institution = new LendingInstitution($this->lendingInstitution);
                $maxInstitutionTerm = $institution->maximumTerm();

                if ($term > $maxInstitutionTerm) {
                    $fail("The {$attribute} cannot exceed {$maxInstitutionTerm} years for {$institution->alias()}.");

                    return;
                }

                // If buyer age is provided, check age-based term limit
                if ($this->buyerAge !== null) {
                    $maxPayingAge = $institution->maximumPayingAge();
                    $maxTermByAge = $maxPayingAge - $this->buyerAge;

                    if ($term > $maxTermByAge) {
                        $fail("The {$attribute} cannot exceed {$maxTermByAge} years based on borrower age of {$this->buyerAge}.");
                    }
                }
            } catch (\Exception $e) {
                // Invalid lending institution, skip validation
            }
        }
    }
}
