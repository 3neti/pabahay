<?php

use LBHurtado\Mortgage\Enums\Property\DevelopmentForm;
use LBHurtado\Mortgage\Enums\Property\DevelopmentType;
use LBHurtado\Mortgage\Enums\Property\HousingType;
use Brick\Math\RoundingMode;

return [
    'defaults' => [
        'buyer' => [
            'birthdate' => now()->subYears(30)->toDateString(),
            'gross_monthly_income' => 15000,
            'regional' => false,
            'interest_rate' => env('DEFAULT_INTEREST_RATE'),
            'down_payment_term' => env('DEFAULT_DOWN_PAYMENT_TERM'),
        ],
        'calculator' => [
            'total_contract_price' => env('CALCULATOR_DEFAULT_TCP', 850000),
            'age' => env('CALCULATOR_DEFAULT_AGE', 30),
            'monthly_gross_income' => env('CALCULATOR_DEFAULT_GMI', 25000),
        ],
    ],
    'limits' => [
        'min_borrowing_age' => 21,
        'max_borrowing_age' => 65,
    ],
    'default_regional' => env('DEFAULT_REGIONAL_BORROWER', false),
    'lending_institutions' => [
        'hdmf' => [
            'name' => 'Home Development Mutual Fund',
            'alias' => 'Pag-IBIG',
            'type' => 'government financial institution',
            'borrowing_age' => [
                'minimum' => 18,
                'maximum' => 60,
                'offset' => 0,
            ],
            'maximum_term' => 30,
            'maximum_paying_age' => 70,
            'buffer_margin' => 0.1,
            'income_requirement_multiplier' => 0.35,
            'interest_rate' => 0.0625,  // 6.25% - varies by price (3% for ≤750k, 6.25% for >750k)
            'percent_dp' => 0.0,
            'loanable_value_multiplier' => 1.0,
            'percent_mf' => 0.0,
        ],
        'rcbc' => [
            'name' => 'Rizal Commercial Banking Corporation',
            'alias' => 'RCBC',
            'type' => 'universal bank',
            'borrowing_age' => [
                'minimum' => 18,
                'maximum' => 60,
                'offset' => -1,
            ],
            'maximum_term' => 20,
            'maximum_paying_age' => 65,
            'buffer_margin' => 0.15,
            'income_requirement_multiplier' => 0.35,
            'interest_rate' => 0.08,  // 8%
            'percent_dp' => 0.10,
            'loanable_value_multiplier' => 0.9,
            'percent_mf' => 0.085,
        ],
        'cbc' => [
            'name' => 'China Banking Corporation',
            'alias' => 'CBC',
            'type' => 'universal bank',
            'borrowing_age' => [
                'minimum' => 18,
                'maximum' => 60,
                'offset' => -1,
            ],
            'maximum_term' => 20,
            'maximum_paying_age' => 65,
            'buffer_margin' => 0.15,
            'income_requirement_multiplier' => 0.35,
            'interest_rate' => 0.07,  // 7%
            'percent_dp' => 0.10,
            'loanable_value_multiplier' => 0.9,
            'percent_mf' => 0.085,
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | Product Selection Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how products are automatically selected for buyers.
    |
    | engine: Selection engine to use
    |   - 'rules': Use JSON rules engine (default, flexible)
    |   - 'simple': Use simple preference config (cheapest/most_expensive)
    |
    | preference: Fallback when rules engine disabled or no rules match
    |   - 'cheapest': Select product with lowest monthly payment
    |   - 'most_expensive': Select product with highest monthly payment
    |
    | rules_file: Path to JSON rules file
    | cache_rules: Whether to cache rules (recommended for production)
    |
    | To change via environment variables:
    |   PRODUCT_SELECTION_ENGINE=simple
    |   PRODUCT_SELECTION_PREFERENCE=most_expensive
    |
    */
    'product_selection' => [
        'engine' => env('PRODUCT_SELECTION_ENGINE', 'rules'),
        'preference' => env('PRODUCT_SELECTION_PREFERENCE', 'cheapest'),
        'rules_file' => storage_path('app/product_selection_rules.json'),
        'cache_rules' => env('CACHE_PRODUCT_RULES', true),
    ],
    
    'products' => [
        [
            'id' => 'product-a',
            'name' => 'Product A',
            'lending_institution' => 'hdmf',
            'price' => 850_000,
        ],
        [
            'id' => 'product-b',
            'name' => 'Product B',
            'lending_institution' => 'hdmf',
            'price' => 1_500_000,
        ],
        [
            'id' => 'product-c',
            'name' => 'Product C',
            'lending_institution' => 'hdmf',
            'price' => 2_200_000,
        ],
        [
            'id' => 'product-d',
            'name' => 'Product D',
            'lending_institution' => 'rcbc',
            'price' => 1_850_000,
        ],
        [
            'id' => 'product-e',
            'name' => 'Product E',
            'lending_institution' => 'rcbc',
            'price' => 2_300_000,
        ],
        [
            'id' => 'product-f',
            'name' => 'Product F',
            'lending_institution' => 'rcbc',
            'price' => 2_800_000,
        ],
        [
            'id' => 'product-g',
            'name' => 'Product G',
            'lending_institution' => 'cbc',
            'price' => 2_300_000,
        ],
        [
            'id' => 'product-h',
            'name' => 'Product H',
            'lending_institution' => 'cbc',
            'price' => 2_800_000,
        ],
        [
            'id' => 'product-i',
            'name' => 'Product I',
            'lending_institution' => 'cbc',
            'price' => 3_100_000,
        ],
    ],
    'default_lending_institution' => env('DEFAULT_LENDING_INSTITUTION', 'hdmf'),
    'default_seller_code' => env('DEFAULT_SELLER_CODE', 'AA537'),
    'default_disposable_income_multiplier' => env('DEFAULT_DISPOSABLE_INCOME_MULTIPLIER', 0.35),
    'default_loanable_value_multiplier' => env('DEFAULT_LOANABLE_VALUE_MULTIPLIER', 1.0),
    'default_interest_rate' => env('DEFAULT_INTEREST_RATE', 0.0625),
    'default_income_requirement_multiplier' => env('DEFAULT_INCOME_REQUIREMENT_MULTIPLIER', 0.35),
    'default_buffer_margin' => env('DEFAULT_BUFFER_MARGIN', 0.1),
    'default_percent_down_payment' => env('DEFAULT_PERCENT_DOWN_PAYMENT', 0.0),
    'rounding_mode' => env('MONEY_ROUNDING_MODE', RoundingMode::CEILING),
    'default_currency' => env('DEFAULT_CURRENCY', 'PHP'),
    'property' => [
        'market' => [
            'segment' => [
                'open' => env('MARKET_SEGMENT_OPEN', 'Open Market'),
                'economic' => env('MARKET_SEGMENT_ECONOMIC', 'Economic'),
                'socialized' => env('MARKET_SEGMENT_SOCIALIZED', 'Socialized'),
            ],
            'ceiling' => [
                'bp_957' => [
                    'horizontal' => [
                        'socialized' => env('HORIZONTAL_SOCIALIZED_MARKET_CEILING', 850_000),
                        'economic'   => env('HORIZONTAL_ECONOMIC_MARKET_CEILING', 2_500_000),
                        'open'       => env('HORIZONTAL_OPEN_MARKET_CEILING', 10_000_000),
                    ],
                    'vertical' => [
                        'socialized' => env('VERTICAL_SOCIALIZED_MARKET_CEILING_BP957', 1_500_000),
                        'economic'   => env('VERTICAL_ECONOMIC_MARKET_CEILING_BP957', 3_000_000),
                        'open'       => env('VERTICAL_OPEN_MARKET_CEILING_BP957', 10_000_000),
                    ],
                ],
                'bp_220' => [
                    'horizontal' => [
                        'socialized' => env('HORIZONTAL_SOCIALIZED_MARKET_CEILING_BP220', 850_000),
                        'economic'   => env('HORIZONTAL_ECONOMIC_MARKET_CEILING_BP220', 2_500_000),
                        'open'       => env('HORIZONTAL_OPEN_MARKET_CEILING_BP220', 10_000_000),
                    ],
                    'vertical' => [
                        'socialized' => env('VERTICAL_SOCIALIZED_MARKET_CEILING', 1_800_000),
                        'economic'   => env('VERTICAL_ECONOMIC_MARKET_CEILING', 2_500_000),
                        'open'       => env('VERTICAL_OPEN_MARKET_CEILING', 10_000_000),
                    ],
                ],
            ],

            'percent_disposable_income' => [
                'socialized' => env('SOCIALIZED_MARKET_DISPOSABLE_MULTIPLIER', 0.35),
                'economic' => env('ECONOMIC_MARKET_DISPOSABLE_MULTIPLIER', 0.35),
                'open' => env('OPEN_MARKET_DISPOSABLE_MULTIPLIER', 0.30),
            ],

            'percent_loanable_value' => [
                'socialized' => env('SOCIALIZED_MARKET_LOANABLE_MULTIPLIER', 1.00),
                'economic' => env('ECONOMIC_MARKET_LOANABLE_MULTIPLIER', 0.95),
                'open' => env('OPEN_MARKET_LOANABLE_MULTIPLIER', 0.90),
            ],
        ],
        'default' => [
            'development_type' => env('PROPERTY_DEFAULT_DEVELOPMENT_TYPE', DevelopmentType::BP_957->value),
            'development_form' => env('PROPERTY_DEFAULT_DEVELOPMENT_FORM', DevelopmentForm::HORIZONTAL->value),
            'housing_type' => env('PROPERTY_DEFAULT_HOUSING_TYPE', HousingType::SINGLE_DETACHED->value),
            'processing_fee' => env('PROPERTY_DEFAULT_PROCESSING_FEE', 0.0),
            'percent_dp' => env('PROPERTY_DEFAULT_PERCENT_DP', 0.0), // 10%
            'dp_term' => env('PROPERTY_DEFAULT_DP_TERM', 12), // in months
            'percent_mf' => env('PROPERTY_DEFAULT_PERCENT_MISC_FEES', 0.0), // 8.5%
        ],
    ],
    'order' => [
        'default' => [
            'monthly_fees' => [
                'mri' => 800,
                'fire_insurance' => 300,
                'other' => 0,
            ],
        ]
    ],
    'onboarding' => [
        'field_name' => env('ONBOARDING_FIELD_NAME', 'code'),
        'url' => env('ONBOARDING_URL', 'https://seqrcode.net/campaign-checkin/9ef24572-2908-4835-9e76-efdeae7aa797'),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Configuration
    |--------------------------------------------------------------------------
    */
    'ai' => [
        'default_provider' => env('AI_DEFAULT_PROVIDER', 'openai'),
        'fallback_provider' => env('AI_FALLBACK_PROVIDER', 'claude'),
        'max_tokens' => env('AI_MAX_TOKENS', 2000),
        'temperature' => env('AI_TEMPERATURE', 0.7),
        
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'organization' => env('OPENAI_ORGANIZATION'),
        ],
        
        'claude' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022'),
        ],
        
        'gemini' => [
            'api_key' => env('GOOGLE_AI_API_KEY'),
            'model' => env('GOOGLE_AI_MODEL', 'gemini-pro'),
        ],
    ],

];
