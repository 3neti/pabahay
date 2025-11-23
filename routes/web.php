<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use LBHurtado\Mortgage\Models\Product;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/mortgage-calculator', function () {
    // Get lending institutions
    $institutions = \App\Models\LendingInstitution::where('is_active', true)
        ->get()
        ->keyBy('code');

    $products = Product::all()->map(function ($product) use ($institutions) {
        $institution = $institutions->get($product->lending_institution);

        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'brand' => $product->brand,
            'category' => $product->category,
            'description' => $product->description,
            'lending_institution' => $product->lending_institution,
            'price' => $product->price ? $product->price->inclusive()->getAmount()->toFloat() : 0,
            'base_priority' => $product->base_priority,
            'commission_rate' => $product->commission_rate,
            'is_featured' => $product->is_featured,
            'boost_multiplier' => $product->boost_multiplier,
            // Include lending institution details
            'institution_details' => $institution ? [
                'interest_rate' => (float) $institution->interest_rate,
                'percent_dp' => (float) $institution->percent_dp,
                'percent_mf' => (float) $institution->percent_mf,
                'processing_fee' => (float) $institution->processing_fee,
                'default_add_mri' => (bool) $institution->default_add_mri,
                'default_add_fi' => (bool) $institution->default_add_fi,
                'name' => $institution->name,
                'alias' => $institution->alias,
            ] : null,
        ];
    });

    return Inertia::render('Mortgage/Calculator', [
        'defaults' => config('mortgage.defaults.calculator', [
            'total_contract_price' => 850000,
            'age' => 30,
            'monthly_gross_income' => 25000,
        ]),
        'products' => $products,
    ]);
})->name('mortgage.calculator');
