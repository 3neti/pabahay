<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/mortgage-calculator', function () {
    return Inertia::render('Mortgage/Calculator');
})->name('mortgage.calculator');
