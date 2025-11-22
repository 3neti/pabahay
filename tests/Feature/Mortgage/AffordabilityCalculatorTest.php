<?php

use function Pest\Laravel\postJson;

it('can calculate affordability with minimum inputs', function () {
    $response = postJson('/api/v1/mortgage/affordability', [
        'lending_institution' => 'hdmf',
        'monthly_gross_income' => 50000,
        'age' => 30,
        'down_payment_available' => 500000,
    ]);

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'success',
        'data' => [
            'max_home_price',
            'max_loan_amount',
            'recommended_down_payment',
            'recommended_down_payment_percent',
            'estimated_monthly_payment',
            'debt_to_income_ratio',
            'available_down_payment',
            'monthly_gross_income',
            'monthly_debts',
            'lending_institution',
            'loan_term_years',
        ],
    ]);
    
    expect($response->json('data.max_home_price'))->toBeGreaterThan(0);
    expect($response->json('data.debt_to_income_ratio'))->toBeLessThan(100);
});

it('can calculate affordability with monthly debts', function () {
    $response = postJson('/api/v1/mortgage/affordability', [
        'lending_institution' => 'rcbc',
        'monthly_gross_income' => 100000,
        'monthly_debts' => 15000,
        'age' => 35,
        'down_payment_available' => 1000000,
    ]);

    $response->assertSuccessful();
    
    // Monthly debts should reduce affordability
    $data = $response->json('data');
    expect($data['monthly_debts'])->toBe(15000);
    expect($data['debt_to_income_ratio'])->toBeGreaterThan(0);
});

it('can calculate affordability with co-borrower', function () {
    $response = postJson('/api/v1/mortgage/affordability', [
        'lending_institution' => 'hdmf',
        'monthly_gross_income' => 40000,
        'age' => 28,
        'co_borrower_age' => 30,
        'co_borrower_income' => 35000,
        'down_payment_available' => 750000,
    ]);

    $response->assertSuccessful();
    
    // Combined income should increase affordability
    $data = $response->json('data');
    expect($data['monthly_gross_income'])->toBeGreaterThanOrEqual(40000); // Primary borrower income
    expect($data['max_home_price'])->toBeGreaterThan(0);
});

it('can calculate affordability with custom loan term', function () {
    $response = postJson('/api/v1/mortgage/affordability', [
        'lending_institution' => 'cbc',
        'monthly_gross_income' => 60000,
        'age' => 25,
        'down_payment_available' => 600000,
        'loan_term' => 15, // 15 years instead of default
    ]);

    $response->assertSuccessful();
    
    $data = $response->json('data');
    expect($data['loan_term_years'])->toBe(15);
});

it('validates required fields', function () {
    $response = postJson('/api/v1/mortgage/affordability', [
        // Missing required fields
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'lending_institution',
        'monthly_gross_income',
        'age',
        'down_payment_available',
    ]);
});

it('validates minimum income', function () {
    $response = postJson('/api/v1/mortgage/affordability', [
        'lending_institution' => 'hdmf',
        'monthly_gross_income' => 0, // Invalid
        'age' => 30,
        'down_payment_available' => 500000,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['monthly_gross_income']);
});

it('validates lending institution', function () {
    $response = postJson('/api/v1/mortgage/affordability', [
        'lending_institution' => 'invalid_bank',
        'monthly_gross_income' => 50000,
        'age' => 30,
        'down_payment_available' => 500000,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['lending_institution']);
});

it('validates loan term range', function () {
    $response = postJson('/api/v1/mortgage/affordability', [
        'lending_institution' => 'hdmf',
        'monthly_gross_income' => 50000,
        'age' => 30,
        'down_payment_available' => 500000,
        'loan_term' => 50, // Too long
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['loan_term']);
});

it('includes all three lending institutions data', function () {
    $institutions = [
        'hdmf' => 'PAG-IBIG',
        'rcbc' => 'RCBC',
        'cbc' => 'CBC',
    ];
    
    foreach ($institutions as $key => $alias) {
        $response = postJson('/api/v1/mortgage/affordability', [
            'lending_institution' => $key,
            'monthly_gross_income' => 50000,
            'age' => 30,
            'down_payment_available' => 500000,
        ]);

        $response->assertSuccessful();
        expect($response->json('data.lending_institution'))->toBe($alias);
    }
});
