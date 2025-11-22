<?php

namespace LBHurtado\Mortgage\Services;

use Illuminate\Support\Carbon;
use LBHurtado\Mortgage\Exceptions\MaximumBorrowingAgeBreached;
use LBHurtado\Mortgage\Exceptions\MinimumBorrowingAgeNotMet;

class BorrowingRulesService
{
    public function __construct(
        protected AgeService $ageService
    ) {}

    public function getMinimumAge(): int
    {
        return config('mortgage.limits.min_borrowing_age', 21);
    }

    public function getMaximumAge(): int
    {
        return config('mortgage.limits.max_borrowing_age', 65);
    }

    public function validateBirthdate(Carbon $birthdate): void
    {
        $age = (int) floor($this->ageService->getAgeInFloat($birthdate));

        if ($age < $this->getMinimumAge()) {
            throw new MinimumBorrowingAgeNotMet("Age {$age} is below minimum of {$this->getMinimumAge()}.");
        }

        if ($age > $this->getMaximumAge()) {
            throw new MaximumBorrowingAgeBreached("Age {$age} exceeds maximum of {$this->getMaximumAge()}.");
        }
    }

    public function calculateAge(Carbon $birthdate): float
    {
        return $this->ageService->getAgeInFloat($birthdate);
    }
}
