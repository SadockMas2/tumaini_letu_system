<?php

namespace App\Filament\Exports;

use App\Models\HistoriqueCompteSpecial;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class HistoriqueCompteSpecialExporter extends Exporter
{
    protected static ?string $model = HistoriqueCompteSpecial::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('created_at')
                ->label('Date')
                ->formatStateUsing(fn ($state) => $state->format('d/m/Y H:i')),
            
            ExportColumn::make('client_nom')
                ->label('Membre/Client'),
            
         

            ExportColumn::make('montant')
                ->label('Montant')
                ->formatStateUsing(function ($state, HistoriqueCompteSpecial $record) {
                    // Format qui peut être converti en nombre dans Excel FR
                    return number_format($state, 2, ',', ''); // Pas d'espace comme séparateur de milliers
                    // OU
                    // return $state; // Laissez le nombre brut et formatez dans Excel
                }),
            
            ExportColumn::make('devise')
                ->label('Devise'),
            
            ExportColumn::make('description')
                ->label('Description')
                ->formatStateUsing(function ($state) {
                    if (empty($state) || strtolower($state) === 'aucune description') {
                        return 'Première mise';
                    }
                    return $state;
                }),
            
            ExportColumn::make('type_operation')
                ->label('Type d\'opération')
                ->getStateUsing(function (HistoriqueCompteSpecial $record) {
                    return self::detecterTypeOperation($record->description);
                }),
            
            ExportColumn::make('cycle.nom')
                ->label('Cycle')
                ->default('N/A'),
            
            ExportColumn::make('categorie_excel')
                ->label('Catégorie Excel')
                ->getStateUsing(function (HistoriqueCompteSpecial $record) {
                    return self::detecterCategorieExcel($record->description);
                }),
            
            ExportColumn::make('signe_montant')
                ->label('Type de mouvement')
                ->getStateUsing(function (HistoriqueCompteSpecial $record) {
                    return $record->montant >= 0 ? 'ENTREE' : 'SORTIE';
                }),
            
            ExportColumn::make('annee')
                ->label('Année')
                ->getStateUsing(fn (HistoriqueCompteSpecial $record) => $record->created_at->format('Y')),
            
            ExportColumn::make('mois')
                ->label('Mois')
                ->getStateUsing(fn (HistoriqueCompteSpecial $record) => $record->created_at->format('m')),
            
            ExportColumn::make('mois_nom')
                ->label('Mois (nom)')
                ->getStateUsing(fn (HistoriqueCompteSpecial $record) => $record->created_at->translatedFormat('F')),
            
            ExportColumn::make('jour_semaine')
                ->label('Jour de la semaine')
                ->getStateUsing(fn (HistoriqueCompteSpecial $record) => $record->created_at->translatedFormat('l')),
        ];
    }
    
    /**
     * Détecte le type d'opération à partir de la description
     */
    public static function detecterTypeOperation(?string $description): string
    {
        if (empty($description) || strtolower($description) === 'aucune description') {
            return 'Première mise';
        }
        
        $descLower = Str::lower($description);
        
        if (preg_match('/frais\s*(d\s*[\'"]?\s*)?adhes?i?o?n/i', $descLower)) {
            return 'Frais d\'adhésion';
        }
        
        if (preg_match('/frais\s*cr[eé]dit|cr[eé]dit\s*#|paiement\s*cr[eé]dit/i', $descLower)) {
            return 'Frais crédit payés';
        }
        
        if (preg_match('/achat.*carnet|carnet.*achat|achet[ée].*carnet/i', $descLower)) {
            return 'Achat carnet';
        }
        
        if (preg_match('/achat.*livre|livre.*achat|achet[ée].*livre/i', $descLower)) {
            return 'Achat livre';
        }
        
        if (preg_match('/premi[èe]re\s*mise|1[èe]re\s*mise/i', $descLower)) {
            return 'Première mise';
        }
        
        return 'Autre opération';
    }
    
    /**
     * Détecte la catégorie Excel pour les tableaux croisés
     */
    private static function detecterCategorieExcel(?string $description): string
    {
        $type = self::detecterTypeOperation($description);
        
        return match($type) {
            'Première mise' => 'ADHESION_PREMIERE_MISE',
            'Frais d\'adhésion' => 'ADHESION_FRAIS',
            'Achat carnet' => 'ACHAT_CARNET',
            'Achat livre' => 'ACHAT_LIVRE',
            'Frais crédit payés' => 'CREDIT_FRAIS',
            default => 'AUTRE'
        };
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Votre export historique compte spécial a été complété avec succès. ' . 
                Number::format($export->successful_rows) . ' ' . 
                str('ligne')->plural($export->successful_rows) . ' exportée(s).';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . 
                    str('ligne')->plural($failedRowsCount) . ' n\'a/ont pas pu être exportée(s).';
        }

        return $body;
    }
}