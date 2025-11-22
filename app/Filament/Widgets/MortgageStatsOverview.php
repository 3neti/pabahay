<?php

namespace App\Filament\Widgets;

use LBHurtado\Mortgage\Models\LoanProfile;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class MortgageStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalCount = LoanProfile::count();
        $qualifiedCount = LoanProfile::where('qualified', true)->count();
        $qualificationRate = $totalCount > 0 ? round(($qualifiedCount / $totalCount) * 100, 1) : 0;
        
        $popularInstitution = LoanProfile::select('lending_institution')
            ->groupBy('lending_institution')
            ->orderByRaw('COUNT(*) DESC')
            ->first();
            
        $institutionName = $popularInstitution ? match($popularInstitution->lending_institution) {
            'hdmf' => 'HDMF',
            'rcbc' => 'RCBC',
            'cbc' => 'CBC',
            default => strtoupper($popularInstitution->lending_institution),
        } : 'N/A';
        
        $avgLoanAmount = LoanProfile::whereNotNull('computation')
            ->get()
            ->avg(fn ($profile) => $profile->computation['loanable_amount'] ?? 0);
            
        $recentActivity = LoanProfile::where('created_at', '>=', now()->subDays(7))->count();

        return [
            Stat::make('Total Computations', number_format($totalCount))
                ->description('All loan profiles')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('primary')
                ->chart([7, 12, 10, 14, 18, 16, 20]),
            
            Stat::make('Qualification Rate', $qualificationRate . '%')
                ->description("{$qualifiedCount} of {$totalCount} qualified")
                ->descriptionIcon('heroicon-o-check-circle')
                ->color($qualificationRate >= 70 ? 'success' : ($qualificationRate >= 50 ? 'warning' : 'danger')),
            
            Stat::make('Popular Institution', $institutionName)
                ->description('Most used lender')
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('info'),
            
            Stat::make('Avg Loan Amount', '₱' . number_format($avgLoanAmount, 2))
                ->description('Average loanable amount')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success'),
            
            Stat::make('Recent Activity', number_format($recentActivity))
                ->description('Last 7 days')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),
        ];
    }
}
