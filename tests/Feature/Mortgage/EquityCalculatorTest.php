<?php

use function Pest\Laravel\postJson;

it('calculates current equity correctly', function () {
    $response = postJson('/api/v1/mortgage/equity', [
        'original_loan_amount' => 3000000,
        'current_balance' => 2500000,
        'home_value' => 3500000,
        'monthly_payment' => 20000,
        'interest_rate' => 0.065,
        'term_remaining' => 240,
    ]);

    $response->assertSuccessful();
    $data = $response->json('data');
    expect($data['current_equity']['amount'])->toBe(1000000); // 3.5M - 2.5M
    expect($data['current_equity']['percent'])->toBeGreaterThan(0);
});

it('projects equity growth over time', function () {
    $response = postJson('/api/v1/mortgage/equity', [
        'original_loan_amount' => 2000000,
        'current_balance' => 1800000,
        'home_value' => 2500000,
        'monthly_payment' => 15000,
        'interest_rate' => 0.06,
        'term_remaining' => 180,
        'appreciation_rate' => 0.03,
    ]);

    $response->assertSuccessful();
    $data = $response->json('data');
    expect($data['equity_projection'])->toBeArray();
    expect($data['equity_projection'])->not->toBeEmpty();
});

it('calculates months to reach target equity', function () {
    $response = postJson('/api/v1/mortgage/equity', [
        'original_loan_amount' => 2000000,
        'current_balance' => 1900000,
        'home_value' => 2000000,
        'monthly_payment' => 14000,
        'interest_rate' => 0.055,
        'term_remaining' => 200,
        'target_equity_percent' => 20,
    ]);

    $response->assertSuccessful();
    $data = $response->json('data');
    expect($data['target']['months_to_reach'])->toBeGreaterThanOrEqual(0);
});

it('shows impact of extra payments on equity', function () {
    $response = postJson('/api/v1/mortgage/equity', [
        'original_loan_amount' => 3000000,
        'current_balance' => 2800000,
        'home_value' => 3200000,
        'monthly_payment' => 22000,
        'interest_rate' => 0.07,
        'term_remaining' => 240,
        'extra_payment' => 5000,
    ]);

    $response->assertSuccessful();
    $data = $response->json('data');
    expect($data['extra_payment'])->toBe(5000);
});

it('validates required fields', function () {
    $response = postJson('/api/v1/mortgage/equity', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'original_loan_amount',
        'current_balance',
        'home_value',
        'monthly_payment',
        'interest_rate',
        'term_remaining',
    ]);
});
