<?php

use function Pest\Laravel\postJson;

it('can calculate refinance with savings', function () {
    $response = postJson('/api/v1/mortgage/refinance', [
        'current_balance' => 2000000,
        'current_rate' => 0.0725, // 7.25%
        'current_term_remaining' => 240, // 20 years
        'current_monthly_payment' => 16000,
        'new_rate' => 0.06, // 6%
        'new_term' => 240, // 20 years
        'closing_costs' => 50000,
    ]);

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'success',
        'data' => [
            'current_loan',
            'new_loan',
            'analysis' => [
                'monthly_payment_difference',
                'total_interest_savings',
                'lifetime_savings',
                'break_even_months',
                'break_even_years',
                'recommendation',
                'recommendation_text',
            ],
        ],
    ]);
    
    $data = $response->json('data');
    expect($data['analysis']['monthly_payment_difference'])->toBeGreaterThan(0);
    expect($data['analysis']['recommendation'])->toBeIn(['recommended', 'caution', 'not_recommended']);
});

it('calculates break-even point correctly', function () {
    $response = postJson('/api/v1/mortgage/refinance', [
        'current_balance' => 1500000,
        'current_rate' => 0.08,
        'current_term_remaining' => 180,
        'current_monthly_payment' => 14000,
        'new_rate' => 0.065,
        'new_term' => 180,
        'closing_costs' => 60000,
    ]);

    $response->assertSuccessful();
    
    $data = $response->json('data.analysis');
    expect($data['break_even_months'])->toBeGreaterThan(0);
    expect($data['break_even_years'])->toBeGreaterThan(0);
});

it('recommends not refinancing when no savings', function () {
    $response = postJson('/api/v1/mortgage/refinance', [
        'current_balance' => 1000000,
        'current_rate' => 0.05,
        'current_term_remaining' => 120,
        'current_monthly_payment' => 10000,
        'new_rate' => 0.07, // Higher rate
        'new_term' => 120,
        'closing_costs' => 40000,
    ]);

    $response->assertSuccessful();
    
    $data = $response->json('data.analysis');
    expect($data['recommendation'])->toBe('not_recommended');
    expect($data['monthly_payment_difference'])->toBeLessThan(0); // Negative = paying more
});

it('compares current and new loan details', function () {
    $response = postJson('/api/v1/mortgage/refinance', [
        'current_balance' => 3000000,
        'current_rate' => 0.075,
        'current_term_remaining' => 300,
        'current_monthly_payment' => 22000,
        'new_rate' => 0.055,
        'new_term' => 240,
        'closing_costs' => 75000,
    ]);

    $response->assertSuccessful();
    
    $data = $response->json('data');
    
    // Current loan
    expect($data['current_loan']['balance'])->toBe(3000000);
    expect($data['current_loan']['rate'])->toBe(7.5);
    expect($data['current_loan']['term_remaining_months'])->toBe(300);
    
    // New loan
    expect($data['new_loan']['balance'])->toBe(3000000);
    expect($data['new_loan']['rate'])->toBe(5.5);
    expect($data['new_loan']['term_months'])->toBe(240);
    expect($data['new_loan']['closing_costs'])->toBe(75000);
});

it('validates required fields', function () {
    $response = postJson('/api/v1/mortgage/refinance', [
        // Missing required fields
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'current_balance',
        'current_rate',
        'current_term_remaining',
        'current_monthly_payment',
        'new_rate',
        'new_term',
        'closing_costs',
    ]);
});

it('validates interest rate range', function () {
    $response = postJson('/api/v1/mortgage/refinance', [
        'current_balance' => 2000000,
        'current_rate' => 1.5, // Invalid > 1
        'current_term_remaining' => 240,
        'current_monthly_payment' => 16000,
        'new_rate' => 0.06,
        'new_term' => 240,
        'closing_costs' => 50000,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['current_rate']);
});

it('validates term range', function () {
    $response = postJson('/api/v1/mortgage/refinance', [
        'current_balance' => 2000000,
        'current_rate' => 0.0725,
        'current_term_remaining' => 500, // Too long
        'current_monthly_payment' => 16000,
        'new_rate' => 0.06,
        'new_term' => 240,
        'closing_costs' => 50000,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['current_term_remaining']);
});

it('calculates total interest for both loans', function () {
    $response = postJson('/api/v1/mortgage/refinance', [
        'current_balance' => 2500000,
        'current_rate' => 0.07,
        'current_term_remaining' => 180,
        'current_monthly_payment' => 20000,
        'new_rate' => 0.055,
        'new_term' => 180,
        'closing_costs' => 65000,
    ]);

    $response->assertSuccessful();
    
    $data = $response->json('data');
    expect($data['current_loan']['total_interest'])->toBeGreaterThanOrEqual(0);
    expect($data['new_loan']['total_interest'])->toBeGreaterThanOrEqual(0);
    // Savings could be positive or negative depending on the scenario
    expect($data['analysis'])->toHaveKey('total_interest_savings');
});
