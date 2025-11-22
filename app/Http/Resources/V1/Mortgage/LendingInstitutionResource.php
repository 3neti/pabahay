<?php

namespace App\Http\Resources\V1\Mortgage;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LBHurtado\Mortgage\Classes\LendingInstitution;

class LendingInstitutionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var LendingInstitution $institution */
        $institution = $this->resource;

        return [
            'key' => $institution->key(),
            'name' => $institution->name(),
            'alias' => $institution->alias(),
            'type' => $institution->type(),
            'borrowing_age' => [
                'minimum' => $institution->minimumAge(),
                'maximum' => $institution->maximumAge(),
            ],
            'terms' => [
                'maximum_term' => $institution->maximumTerm(),
                'maximum_paying_age' => $institution->maximumPayingAge(),
            ],
            'rates' => [
                'interest_rate' => $institution->getInterestRate()?->value(),
                'percent_down_payment' => $institution->getPercentDownPayment()->value(),
                'percent_miscellaneous_fees' => $institution->getPercentMiscellaneousFees()->value(),
                'income_requirement_multiplier' => $institution->getIncomeRequirementMultiplier()?->value(),
                'buffer_margin' => $institution->getBufferMargin()->value(),
            ],
            'loanable_value_multiplier' => $institution->getLoanableValueMultiplier(),
        ];
    }
}
