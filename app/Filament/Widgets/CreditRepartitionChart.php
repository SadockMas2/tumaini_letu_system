<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Credit;
use App\Models\CreditGroupe;

class CreditRepartitionChart extends ChartWidget
{
    protected  ?string $heading = 'Répartition par Type de Crédit';

    protected function getData(): array
    {
        $individuels = Credit::where('statut_demande', 'approuve')->count();
        $groupes = CreditGroupe::where('statut_demande', 'approuve')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Types de Crédits',
                    'data' => [$individuels, $groupes],
                    'backgroundColor' => ['#10b981', '#6366f1'],
                ],
            ],
            'labels' => ['Individuels', 'Groupes'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}