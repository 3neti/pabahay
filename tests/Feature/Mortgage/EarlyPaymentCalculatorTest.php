<?php

use function Pest\Laravel\postJson;

it('calculates savings from extra payments', function () {
    $response = postJson('/api/v1/mortgage/early-payment', [
        'current_balance' => 2000000,
        'interest_rate' => 0.065,
        'term_remaining' => 240,
        'monthly_payment' => 15000,
        'extra_payment' => 3000,
    ]);

    $response->assertSuccessful();
    $data = $response->json('data');
    expect($data['savings']['interest_savings'])->toBeGreaterThan(0);
    expect($data['savings']['time_saved_months'])->toBeGreaterThan(0);
});

it('shows time saved with extra payments', function () {
    $response = postJson('/api/v1/mortgage/early-payment', [
        'current_balance' => 1500000,
        'interest_rate' => 0.06,
        'term_remaining' => 180,
        'monthly_payment' => 12000,
        'extra_payment' => 2000,
    ]);

    $response->assertSuccessful();
    $data = $response->json('data');
    expect($data['standard_scenario']['months_to_payoff'])->toBeGreaterThan(
        $data['accelerated_scenario']['months_to_payoff']
    );
});

it('compares standard vs accelerated scenarios', function () {
    $response = postJson('/api/v1/mortgage/early-payment', [
        'current_balance' => 2500000,
        'interest_rate' => 0.07,
        'term_remaining' => 300,
        'monthly_payment' => 18000,
        'extra_payment' => 5000,
    ]);

    $response->assertSuccessful();
    $data = $response->json('data');
    expect($data)->toHaveKeys(['standard_scenario', 'accelerated_scenario', 'savings']);
    expect($data['accelerated_scenario']['total_paid'])->toBeLessThan(
        $data['standard_scenario']['total_paid']
    );
});

it('handles zero extra payment', function () {
    $response = postJson('/api/v1/mortgage/early-payment', [
        'current_balance' => 1000000,
        'interest_rate' => 0.055,
        'term_remaining' => 120,
        'monthly_payment' => 10000,
        'extra_payment' => 0,
    ]);

    $response->assertSuccessful();
    $data = $response->json('data');
    expect($data['savings']['time_saved_months'])->toBe(0);
});

it('validates required fields', function () {
    $response = postJson('/api/v1/mortgage/early-payment', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'current_balance',
        'interest_rate',
        'term_remaining',
        'monthly_payment',
        'extra_payment',
    ]);
});
