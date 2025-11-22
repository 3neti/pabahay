<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('displays the mortgage calculator page', function () {
    $page = visit('/mortgage-calculator');

    $page->assertSee('Mortgage Calculator')
        ->assertSee('Calculate your mortgage based on various lending institutions')
        ->assertSee('Property Details')
        ->assertSee('Buyer Details')
        ->assertSee('Loan Parameters')
        ->assertNoJavascriptErrors();
});

it('can compute mortgage with valid inputs', function () {
    $page = visit('/mortgage-calculator');

    $page->select('lending_institution', 'hdmf')
        ->fill('total_contract_price', '1000000')
        ->fill('age', '35')
        ->fill('monthly_gross_income', '50000')
        ->click('Compute Mortgage')
        ->waitFor('Computation Results', timeout: 5)
        ->assertSee('Computation Results')
        ->assertSee('Monthly Amortization')
        ->assertNoJavascriptErrors();
});

it('shows qualification status when loan qualifies', function () {
    $page = visit('/mortgage-calculator');

    // Enter values that should qualify
    $page->select('lending_institution', 'hdmf')
        ->fill('total_contract_price', '1000000')
        ->fill('age', '30')
        ->fill('monthly_gross_income', '80000')
        ->fill('percent_down_payment', '0.20')
        ->click('Compute Mortgage')
        ->waitFor('Loan Qualified', timeout: 5)
        ->assertSee('Loan Qualified')
        ->assertNoJavascriptErrors();
});

it('shows not qualified status with remedies when income is insufficient', function () {
    $page = visit('/mortgage-calculator');

    // Enter values that should not qualify
    $page->select('lending_institution', 'hdmf')
        ->fill('total_contract_price', '5000000')
        ->fill('age', '35')
        ->fill('monthly_gross_income', '20000')
        ->click('Compute Mortgage')
        ->waitFor('Not Qualified', timeout: 5)
        ->assertSee('Not Qualified')
        ->assertSee('Income Gap')
        ->assertSee('Suggested Down Payment')
        ->assertNoJavascriptErrors();
});

it('can reset the form', function () {
    $page = visit('/mortgage-calculator');

    $page->fill('total_contract_price', '1000000')
        ->fill('age', '35')
        ->fill('monthly_gross_income', '50000')
        ->click('Reset')
        ->assertInputValue('total_contract_price', '')
        ->assertInputValue('age', '')
        ->assertInputValue('monthly_gross_income', '');
});

it('supports co-borrower information', function () {
    $page = visit('/mortgage-calculator');

    $page->select('lending_institution', 'hdmf')
        ->fill('total_contract_price', '2000000')
        ->fill('age', '35')
        ->fill('monthly_gross_income', '30000')
        ->fill('co_borrower_age', '32')
        ->fill('co_borrower_income', '25000')
        ->click('Compute Mortgage')
        ->waitFor('Computation Results', timeout: 5)
        ->assertSee('Monthly Amortization')
        ->assertNoJavascriptErrors();
});

it('displays error message on API failure', function () {
    $page = visit('/mortgage-calculator');

    // Submit without required fields
    $page->click('Compute Mortgage')
        ->wait(1)
        ->assertSee('error', timeout: 3);
});

it('shows additional details when expanded', function () {
    $page = visit('/mortgage-calculator');

    $page->select('lending_institution', 'hdmf')
        ->fill('total_contract_price', '1000000')
        ->fill('age', '30')
        ->fill('monthly_gross_income', '50000')
        ->click('Compute Mortgage')
        ->waitFor('Computation Results', timeout: 5)
        ->click('View All Details')
        ->assertSee('Interest Rate')
        ->assertSee('Down Payment %')
        ->assertSee('Cash Out')
        ->assertNoJavascriptErrors();
});
