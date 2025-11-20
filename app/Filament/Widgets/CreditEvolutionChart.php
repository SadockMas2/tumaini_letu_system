<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Credit;

class CreditEvolutionChart extends ChartWidget
{
    protected ?string $heading = 'Évolution des Crédits (4 mois)';

    protected function getData(): array
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $total = Credit::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('montant_total');
            
            $data[$month->format('M Y')] = $total;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Crédits Octroyés (USD)',
                    'data' => array_values($data),
                    'backgroundColor' => '#f59e0b',
                    'borderColor' => '#f59e0b',
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}