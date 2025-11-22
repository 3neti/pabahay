<?php

namespace App\Filament\Resources\LoanProfileResource\Pages;

use App\Filament\Resources\LoanProfileResource;
use App\Filament\Widgets\MortgageStatsOverview;
use App\Filament\Widgets\ComputationsTrendChart;
use App\Filament\Widgets\InstitutionComparisonChart;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLoanProfiles extends ListRecords
{
    protected static string $resource = LoanProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Removed create action as we don't want manual creation
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            MortgageStatsOverview::class,
        ];
    }
    
    protected function getFooterWidgets(): array
    {
        return [
            ComputationsTrendChart::class,
            InstitutionComparisonChart::class,
        ];
    }
}
