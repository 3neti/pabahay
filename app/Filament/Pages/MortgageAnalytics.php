<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ComputationsTrendChart;
use App\Filament\Widgets\InstitutionComparisonChart;
use App\Filament\Widgets\MortgageStatsOverview;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;

class MortgageAnalytics extends Page
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Mortgage';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Analytics Dashboard';

    protected static string $view = 'filament.pages.mortgage-analytics';

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
