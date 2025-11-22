<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/mortgage-calculator', function () {
    return Inertia::render('Mortgage/Calculator', [
        'defaults' => config('mortgage.defaults.calculator', [
            'total_contract_price' => 850000,
            'age' => 30,
            'monthly_gross_income' => 25000,
        ]),
    ]);
})->name('mortgage.calculator');
