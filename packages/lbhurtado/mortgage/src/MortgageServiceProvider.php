<?php

namespace LBHurtado\Mortgage;

use Illuminate\Support\ServiceProvider;
use LBHurtado\Mortgage\Repositories\Contracts\LoanProfileRepositoryInterface;
use LBHurtado\Mortgage\Repositories\Eloquent\EloquentLoanProfileRepository;
use LBHurtado\Mortgage\Services\AmortizationScheduleService;
use LBHurtado\Mortgage\Services\LendingInstitutionService;
use LBHurtado\Mortgage\Services\LoanProfileService;
use LBHurtado\Mortgage\Services\LoanQualificationService;
use LBHurtado\Mortgage\Services\MortgageComputationService;

class MortgageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/mortgage.php',
            'mortgage'
        );

        // Register repositories
        $this->app->bind(LoanProfileRepositoryInterface::class, EloquentLoanProfileRepository::class);

        // Register services
        $this->app->singleton(AmortizationScheduleService::class);
        $this->app->singleton(LoanQualificationService::class);
        $this->app->singleton(LendingInstitutionService::class);
        $this->app->singleton(LoanProfileService::class);
        $this->app->singleton(MortgageComputationService::class);
    }

    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/mortgage.php' => config_path('mortgage.php'),
        ], 'mortgage-config');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'mortgage-migrations');
    }
}
