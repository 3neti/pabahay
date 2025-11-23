<?php

namespace LBHurtado\Mortgage\Data\Inputs;

use LBHurtado\Mortgage\Casts\LendingInstitutionCast;
use LBHurtado\Mortgage\Casts\PercentCast;
use LBHurtado\Mortgage\Casts\PriceCast;
use LBHurtado\Mortgage\Classes\LendingInstitution;
use LBHurtado\Mortgage\Data\Transformers\LendingInstitutionToStringTransformer;
use LBHurtado\Mortgage\Data\Transformers\PercentToFloatTransformer;
use LBHurtado\Mortgage\Data\Transformers\PriceToFloatTransformer;
use LBHurtado\Mortgage\ValueObjects\Percent;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Data;
use Whitecube\Price\Price;

class MortgageInputsData extends Data
{
    public function __construct(
        #[WithTransformer(LendingInstitutionToStringTransformer::class)]
        #[WithCast(LendingInstitutionCast::class)]
        public LendingInstitution $lending_institution,
        #[WithTransformer(PriceToFloatTransformer::class)]
        #[WithCast(PriceCast::class)]
        public Price $total_contract_price,
        public int $age,
        #[WithTransformer(PriceToFloatTransformer::class)]
        #[WithCast(PriceCast::class)]
        public Price $monthly_gross_income,
        public ?int $co_borrower_age,
        #[WithTransformer(PriceToFloatTransformer::class)]
        #[WithCast(PriceCast::class)]
        public ?Price $co_borrower_income,
        #[WithTransformer(PriceToFloatTransformer::class)]
        #[WithCast(PriceCast::class)]
        public ?Price $additional_income,
        #[WithTransformer(PercentToFloatTransformer::class)]
        #[WithCast(PercentCast::class)]
        public ?Percent $balance_payment_interest,
        #[WithTransformer(PercentToFloatTransformer::class)]
        #[WithCast(PercentCast::class)]
        public ?Percent $percent_down_payment,
        #[WithTransformer(PercentToFloatTransformer::class)]
        #[WithCast(PercentCast::class)]
        public ?Percent $percent_miscellaneous_fee,
        #[WithTransformer(PriceToFloatTransformer::class)]
        #[WithCast(PriceCast::class)]
        public ?Price $processing_fee,
        public bool $add_mri,
        public bool $add_fi,
        public ?int $desired_loan_term = null,
    ) {}
}
