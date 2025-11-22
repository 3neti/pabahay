<?php

namespace App\Http\Resources\V1\Mortgage;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LBHurtado\Mortgage\Data\MortgageComputationData;

class MortgageComputationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var MortgageComputationData $computation */
        $computation = $this->resource;

        return [
            'inputs' => $computation->inputs->toArray(),
            'lending_institution' => [
                'key' => $computation->lending_institution->key(),
                'name' => $computation->lending_institution->name(),
                'alias' => $computation->lending_institution->alias(),
                'type' => $computation->lending_institution->type(),
            ],
            'rates' => [
                'interest_rate' => $computation->interest_rate->value(),
                'percent_down_payment' => $computation->percent_down_payment->value(),
                'percent_miscellaneous_fees' => $computation->percent_miscellaneous_fees->value(),
                'income_requirement_multiplier' => $computation->income_requirement_multiplier->value(),
            ],
            'amounts' => [
                'total_contract_price' => $computation->total_contract_price->base()->getAmount()->toFloat(),
                'loanable_amount' => $computation->loanable_amount->getAmount()->toFloat(),
                'required_equity' => $computation->required_equity->getAmount()->toFloat(),
                'monthly_amortization' => $computation->monthly_amortization->getAmount()->toFloat(),
                'monthly_disposable_income' => $computation->monthly_disposable_income->getAmount()->toFloat(),
                'present_value' => $computation->present_value->getAmount()->toFloat(),
                'miscellaneous_fees' => $computation->miscellaneous_fees->getAmount()->toFloat(),
                'add_on_fees' => $computation->add_on_fees->getAmount()->toFloat(),
                'cash_out' => $computation->cash_out->getAmount()->toFloat(),
                'required_income' => $computation->required_income->getAmount()->toFloat(),
                'income_gap' => $computation->income_gap->getAmount()->toFloat(),
            ],
            'terms' => [
                'balance_payment_term' => $computation->balance_payment_term,
            ],
            'qualification' => [
                'qualifies' => $computation->qualifies,
                'income_gap' => $computation->income_gap->getAmount()->toFloat(),
                'required_equity' => $computation->required_equity->getAmount()->toFloat(),
                'suggested_down_payment_percent' => $computation->percent_down_payment_remedy->value(),
                'reason' => $computation->qualifies
                    ? 'Sufficient disposable income'
                    : 'Disposable income below amortization',
            ],
        ];
    }
}
