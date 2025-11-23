<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductSelectionRulesSeeder extends Seeder
{
    /**
     * Seed the product selection rules JSON file.
     */
    public function run(): void
    {
        $rules = [
            [
                'name' => 'Premium buyers get most expensive affordable product',
                'priority' => 100,
                'active' => true,
                'condition' => [
                    'monthly_gross_income' => ['operator' => '>=', 'value' => 100000],
                ],
                'action' => [
                    'sort_by' => 'price',
                    'direction' => 'desc',
                ],
            ],
            [
                'name' => 'Young buyers prefer HDMF for long term',
                'priority' => 90,
                'active' => true,
                'condition' => [
                    'age' => ['operator' => '<=', 'value' => 35],
                ],
                'action' => [
                    'prefer_institution' => 'hdmf',
                    'sort_by' => 'monthly_payment',
                    'direction' => 'asc',
                ],
            ],
            [
                'name' => 'Mid-career professionals get banks',
                'priority' => 80,
                'active' => true,
                'condition' => [
                    'age' => ['operator' => 'between', 'value' => [36, 50]],
                    'monthly_gross_income' => ['operator' => '>=', 'value' => 60000],
                ],
                'action' => [
                    'prefer_institution' => 'rcbc',
                    'sort_by' => 'price',
                    'direction' => 'desc',
                ],
            ],
            [
                'name' => 'Default: cheapest qualified product',
                'priority' => 1,
                'active' => true,
                'condition' => [],
                'action' => [
                    'sort_by' => 'monthly_payment',
                    'direction' => 'asc',
                ],
            ],
        ];

        $rulesJson = json_encode($rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Write to storage/app directory
        Storage::put('product_selection_rules.json', $rulesJson);

        Log::info('ProductSelectionRulesSeeder: Rules file created', [
            'path' => Storage::path('product_selection_rules.json'),
            'rules_count' => count($rules),
        ]);

        $this->command->info('Product selection rules file created successfully.');
    }
}
