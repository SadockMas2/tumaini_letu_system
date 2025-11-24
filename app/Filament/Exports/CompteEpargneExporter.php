<?php

namespace App\Filament\Exports;

use App\Models\CompteEpargne;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class CompteEpargneExporter extends Exporter
{
    protected static ?string $model = CompteEpargne::class;

    public static function getColumns(): array
    {
        return [
                ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('numero_compte')
                ->label('Numéro de compte'),
            ExportColumn::make('nom_complet')
                    ->label('Titulaire')
                    ->getStateUsing(function (CompteEpargne $record) {
                        if ($record->type_compte === 'individuel' && $record->client) {
                            return $record->client->nom_complet;
                        } elseif ($record->type_compte === 'groupe_solidaire' && $record->groupeSolidaire) {
                            return $record->groupeSolidaire->nom_groupe . ' (Groupe)';
                        }
                        return 'N/A';
                    }),
                  
            ExportColumn::make('type_compte')
                ->label('Type de compte'),
            ExportColumn::make('solde')
                ->label('Solde du compte'),
            ExportColumn::make('devise')
                ->label('Devise'),
             
            ExportColumn::make('created_at')
                ->label('Créé le')
                ->formatStateUsing(fn ($state) => $state->format('d/m/Y H:i')),
            ExportColumn::make('updated_at')
                ->label('Modifié le')
                ->formatStateUsing(fn ($state) => $state->format('d/m/Y H:i')),
        ];
       
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your compte epargne export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
