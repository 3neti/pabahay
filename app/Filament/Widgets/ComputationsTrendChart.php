<?php

namespace App\Filament\Widgets;

use LBHurtado\Mortgage\Models\LoanProfile;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ComputationsTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Computations Trend';
    
    protected static ?string $pollingInterval = null;
    
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        // Get last 30 days of data
        $data = LoanProfile::query()
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Fill in missing days with 0
        $dates = [];
        $counts = [];
        
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dates[] = now()->subDays($i)->format('M d');
            
            $dayData = $data->firstWhere('date', $date);
            $counts[] = $dayData ? $dayData->count : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Computations',
                    'data' => $counts,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $dates,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
