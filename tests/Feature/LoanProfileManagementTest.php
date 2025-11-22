<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\MortgageComputationMail;

uses(RefreshDatabase::class);

it('can save a mortgage calculation as loan profile', function () {
    $data = [
        'lending_institution' => 'hdmf',
        'total_contract_price' => 1000000,
        'age' => 35,
        'monthly_gross_income' => 50000,
        'co_borrower_age' => null,
        'co_borrower_income' => null,
        'additional_income' => null,
        'balance_payment_interest' => null,
        'percent_down_payment' => null,
        'percent_miscellaneous_fee' => null,
        'processing_fee' => null,
        'add_mri' => false,
        'add_fi' => false,
        'borrower_name' => 'John Doe',
        'borrower_email' => 'john@example.com',
        'send_email' => false,
    ];

    $response = $this->postJson('/api/v1/mortgage/loan-profiles', $data);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'payload' => [
                'reference_code',
                'lending_institution',
                'total_contract_price',
                'monthly_amortization',
                'loanable_amount',
                'borrower_name',
                'borrower_email',
            ],
        ])
        ->assertJson([
            'success' => true,
        ]);

    expect($response->json('payload.reference_code'))->not->toBeEmpty();
    expect($response->json('payload.borrower_name'))->toBe('John Doe');
    expect($response->json('payload.borrower_email'))->toBe('john@example.com');
});

it('can retrieve saved loan profile by reference code', function () {
    // First, save a profile
    $data = [
        'lending_institution' => 'hdmf',
        'total_contract_price' => 1500000,
        'age' => 30,
        'monthly_gross_income' => 60000,
        'co_borrower_age' => null,
        'co_borrower_income' => null,
        'additional_income' => null,
        'balance_payment_interest' => null,
        'percent_down_payment' => null,
        'percent_miscellaneous_fee' => null,
        'processing_fee' => null,
        'add_mri' => false,
        'add_fi' => false,
        'borrower_name' => 'Jane Smith',
        'send_email' => false,
    ];

    $saveResponse = $this->postJson('/api/v1/mortgage/loan-profiles', $data);
    $referenceCode = $saveResponse->json('payload.reference_code');

    // Then, retrieve it
    $response = $this->getJson("/api/v1/mortgage/loan-profiles/{$referenceCode}");

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'payload' => [
                'reference_code' => $referenceCode,
                'lending_institution' => 'hdmf',
                'total_contract_price' => 1500000,
                'borrower_name' => 'Jane Smith',
            ],
        ]);
});

it('returns 404 for non-existent reference code', function () {
    $response = $this->getJson('/api/v1/mortgage/loan-profiles/INVALID123');

    $response->assertNotFound()
        ->assertJson([
            'success' => false,
            'message' => 'Loan profile not found.',
        ]);
});

it('sends email when requested and email provided', function () {
    Mail::fake();

    $data = [
        'lending_institution' => 'hdmf',
        'total_contract_price' => 2000000,
        'age' => 40,
        'monthly_gross_income' => 80000,
        'co_borrower_age' => null,
        'co_borrower_income' => null,
        'additional_income' => null,
        'balance_payment_interest' => null,
        'percent_down_payment' => null,
        'percent_miscellaneous_fee' => null,
        'processing_fee' => null,
        'add_mri' => false,
        'add_fi' => false,
        'borrower_name' => 'Bob Johnson',
        'borrower_email' => 'bob@example.com',
        'send_email' => true,
    ];

    $response = $this->postJson('/api/v1/mortgage/loan-profiles', $data);

    $response->assertSuccessful();

    Mail::assertSent(MortgageComputationMail::class, function ($mail) {
        return $mail->hasTo('bob@example.com');
    });
});

it('does not send email when send_email is false', function () {
    Mail::fake();

    $data = [
        'lending_institution' => 'hdmf',
        'total_contract_price' => 2000000,
        'age' => 40,
        'monthly_gross_income' => 80000,
        'co_borrower_age' => null,
        'co_borrower_income' => null,
        'additional_income' => null,
        'balance_payment_interest' => null,
        'percent_down_payment' => null,
        'percent_miscellaneous_fee' => null,
        'processing_fee' => null,
        'add_mri' => false,
        'add_fi' => false,
        'borrower_name' => 'Alice Williams',
        'borrower_email' => 'alice@example.com',
        'send_email' => false,
    ];

    $response = $this->postJson('/api/v1/mortgage/loan-profiles', $data);

    $response->assertSuccessful();

    Mail::assertNotSent(MortgageComputationMail::class);
});

it('does not send email when no email provided', function () {
    Mail::fake();

    $data = [
        'lending_institution' => 'hdmf',
        'total_contract_price' => 2000000,
        'age' => 40,
        'monthly_gross_income' => 80000,
        'co_borrower_age' => null,
        'co_borrower_income' => null,
        'additional_income' => null,
        'balance_payment_interest' => null,
        'percent_down_payment' => null,
        'percent_miscellaneous_fee' => null,
        'processing_fee' => null,
        'add_mri' => false,
        'add_fi' => false,
        'send_email' => true,
    ];

    $response = $this->postJson('/api/v1/mortgage/loan-profiles', $data);

    $response->assertSuccessful();

    Mail::assertNotSent(MortgageComputationMail::class);
});

it('saves loan profile without borrower information', function () {
    $data = [
        'lending_institution' => 'rcbc',
        'total_contract_price' => 800000,
        'age' => 28,
        'monthly_gross_income' => 35000,
        'co_borrower_age' => null,
        'co_borrower_income' => null,
        'additional_income' => null,
        'balance_payment_interest' => null,
        'percent_down_payment' => null,
        'percent_miscellaneous_fee' => null,
        'processing_fee' => null,
        'add_mri' => false,
        'add_fi' => false,
        'send_email' => false,
    ];

    $response = $this->postJson('/api/v1/mortgage/loan-profiles', $data);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
        ]);

    expect($response->json('payload.reference_code'))->not->toBeEmpty();
    expect($response->json('payload.borrower_name'))->toBeNull();
    expect($response->json('payload.borrower_email'))->toBeNull();
});

it('validates required fields when saving loan profile', function () {
    $response = $this->postJson('/api/v1/mortgage/loan-profiles', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'lending_institution',
            'total_contract_price',
            'age',
            'monthly_gross_income',
        ]);
});

it('validates email format when provided', function () {
    $data = [
        'lending_institution' => 'hdmf',
        'total_contract_price' => 1000000,
        'age' => 35,
        'monthly_gross_income' => 50000,
        'borrower_email' => 'invalid-email',
    ];

    $response = $this->postJson('/api/v1/mortgage/loan-profiles', $data);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['borrower_email']);
});

it('preserves all computation results in saved profile', function () {
    $data = [
        'lending_institution' => 'hdmf',
        'total_contract_price' => 1200000,
        'age' => 32,
        'monthly_gross_income' => 55000,
        'co_borrower_age' => 30,
        'co_borrower_income' => 40000,
        'additional_income' => null,
        'balance_payment_interest' => null,
        'percent_down_payment' => 0.20,
        'percent_miscellaneous_fee' => null,
        'processing_fee' => null,
        'add_mri' => false,
        'add_fi' => false,
    ];

    $response = $this->postJson('/api/v1/mortgage/loan-profiles', $data);

    $response->assertSuccessful();

    $payload = $response->json('payload');
    expect($payload)->toHaveKeys([
        'monthly_amortization',
        'balance_payment_term',
        'loanable_amount',
        'required_equity',
        'interest_rate',
        'miscellaneous_fees',
        'cash_out',
        'qualification',
    ]);

    expect($payload['co_borrower_age'])->toBe(30);
    expect($payload['co_borrower_income'])->toBe(40000);
});
