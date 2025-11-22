<?php

namespace App\Filament\Widgets;

use LBHurtado\Mortgage\Models\LoanProfile;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class InstitutionComparisonChart extends ChartWidget
{
    protected static ?string $heading = 'Lending Institutions Comparison';
    
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $data = LoanProfile::query()
            ->select('lending_institution', DB::raw('count(*) as total'))
            ->groupBy('lending_institution')
            ->orderBy('total', 'desc')
            ->get();
        
        $labels = [];
        $counts = [];
        $colors = [];
        
        foreach ($data as $item) {
            $labels[] = match($item->lending_institution) {
                'hdmf' => 'HDMF',
                'rcbc' => 'RCBC',
                'cbc' => 'CBC',
                default => strtoupper($item->lending_institution),
            };
            $counts[] = $item->total;
            $colors[] = match($item->lending_institution) {
                'hdmf' => 'rgba(34, 197, 94, 0.8)',
                'rcbc' => 'rgba(59, 130, 246, 0.8)',
                'cbc' => 'rgba(251, 191, 36, 0.8)',
                default => 'rgba(156, 163, 175, 0.8)',
            };
        }

        return [
            'datasets' => [
                [
                    'label' => 'Computations',
                    'data' => $counts,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
