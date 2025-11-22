<?php

use App\Http\Controllers\Mortgage\{
    MortgageComputationController,
    LendingInstitutionController,
    LoanProfileController,
    AmortizationScheduleController,
    ComparisonController,
    AffordabilityController,
    RefinanceController,
    EquityController,
    EarlyPaymentController
};
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API v1 routes
Route::prefix('v1')->name('api.v1.')->group(function () {
    // Mortgage API endpoints - direct routes without prefix for backward compatibility
    Route::post('/mortgage-compute', [MortgageComputationController::class, 'compute'])
        ->name('mortgage-compute');

    // Mortgage API endpoints under /mortgage prefix
    Route::prefix('mortgage')->name('mortgage.')->group(function () {
        // Compute mortgage (alternative route)
        Route::post('/compute', [MortgageComputationController::class, 'compute'])
            ->name('compute');

        // Lending institutions
        Route::get('/lending-institutions', [LendingInstitutionController::class, 'index'])
            ->name('institutions.index');
        Route::get('/lending-institutions/{key}', [LendingInstitutionController::class, 'show'])
            ->name('institutions.show');

        // Loan profiles
        Route::post('/loan-profiles', [LoanProfileController::class, 'store'])
            ->name('profiles.store');
        Route::get('/loan-profiles/{referenceCode}', [LoanProfileController::class, 'show'])
            ->name('profiles.show');

        // Amortization schedule
        Route::post('/amortization-schedule', [AmortizationScheduleController::class, 'generate'])
            ->name('amortization.generate');

        // Comparison
        Route::post('/compare', [ComparisonController::class, 'compare'])
            ->name('compare');

        // Affordability calculator
        Route::post('/affordability', [AffordabilityController::class, 'calculate'])
            ->name('affordability.calculate');

        // Refinance calculator
        Route::post('/refinance', [RefinanceController::class, 'calculate'])
            ->name('refinance.calculate');

        // Equity calculator
        Route::post('/equity', [EquityController::class, 'calculate'])
            ->name('equity.calculate');

        // Early payment calculator
        Route::post('/early-payment', [EarlyPaymentController::class, 'calculate'])
            ->name('early-payment.calculate');
    });
});
