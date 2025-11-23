<?php

namespace Database\Seeders;

use App\Models\LendingInstitution;
use Illuminate\Database\Seeder;

class LendingInstitutionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $config = config('mortgage.lending_institutions');

        $institutions = [
            [
                'code' => 'hdmf',
                'name' => $config['hdmf']['name'],
                'alias' => $config['hdmf']['alias'],
                'type' => $config['hdmf']['type'],
                'is_active' => true,
                'interest_rate' => $config['hdmf']['interest_rate'],
                'percent_dp' => $config['hdmf']['percent_dp'],
                'percent_mf' => $config['hdmf']['percent_mf'],
                'processing_fee' => 10000,
                'default_add_mri' => true,
                'default_add_fi' => true,
                'borrowing_age_minimum' => $config['hdmf']['borrowing_age']['minimum'],
                'borrowing_age_maximum' => $config['hdmf']['borrowing_age']['maximum'],
                'borrowing_age_offset' => $config['hdmf']['borrowing_age']['offset'],
                'maximum_term' => $config['hdmf']['maximum_term'],
                'maximum_paying_age' => $config['hdmf']['maximum_paying_age'],
                'buffer_margin' => $config['hdmf']['buffer_margin'],
                'income_requirement_multiplier' => $config['hdmf']['income_requirement_multiplier'],
                'loanable_value_multiplier' => $config['hdmf']['loanable_value_multiplier'],
                'description' => 'Government financial institution providing affordable housing loans with favorable terms.',
            ],
            [
                'code' => 'rcbc',
                'name' => $config['rcbc']['name'],
                'alias' => $config['rcbc']['alias'],
                'type' => $config['rcbc']['type'],
                'is_active' => true,
                'interest_rate' => $config['rcbc']['interest_rate'],
                'percent_dp' => $config['rcbc']['percent_dp'],
                'percent_mf' => $config['rcbc']['percent_mf'],
                'processing_fee' => 10000,
                'default_add_mri' => false,
                'default_add_fi' => false,
                'borrowing_age_minimum' => $config['rcbc']['borrowing_age']['minimum'],
                'borrowing_age_maximum' => $config['rcbc']['borrowing_age']['maximum'],
                'borrowing_age_offset' => $config['rcbc']['borrowing_age']['offset'],
                'maximum_term' => $config['rcbc']['maximum_term'],
                'maximum_paying_age' => $config['rcbc']['maximum_paying_age'],
                'buffer_margin' => $config['rcbc']['buffer_margin'],
                'income_requirement_multiplier' => $config['rcbc']['income_requirement_multiplier'],
                'loanable_value_multiplier' => $config['rcbc']['loanable_value_multiplier'],
                'description' => 'Universal bank offering competitive housing loan packages.',
            ],
            [
                'code' => 'cbc',
                'name' => $config['cbc']['name'],
                'alias' => $config['cbc']['alias'],
                'type' => $config['cbc']['type'],
                'is_active' => true,
                'interest_rate' => $config['cbc']['interest_rate'],
                'percent_dp' => $config['cbc']['percent_dp'],
                'percent_mf' => $config['cbc']['percent_mf'],
                'processing_fee' => 10000,
                'default_add_mri' => false,
                'default_add_fi' => false,
                'borrowing_age_minimum' => $config['cbc']['borrowing_age']['minimum'],
                'borrowing_age_maximum' => $config['cbc']['borrowing_age']['maximum'],
                'borrowing_age_offset' => $config['cbc']['borrowing_age']['offset'],
                'maximum_term' => $config['cbc']['maximum_term'],
                'maximum_paying_age' => $config['cbc']['maximum_paying_age'],
                'buffer_margin' => $config['cbc']['buffer_margin'],
                'income_requirement_multiplier' => $config['cbc']['income_requirement_multiplier'],
                'loanable_value_multiplier' => $config['cbc']['loanable_value_multiplier'],
                'description' => 'Universal bank with flexible housing loan terms and competitive rates.',
            ],
        ];

        foreach ($institutions as $institution) {
            LendingInstitution::updateOrCreate(
                ['code' => $institution['code']],
                $institution
            );
        }
    }
}
