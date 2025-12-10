<?php

namespace App\Filament\Resources\HistoriqueCompteSpecials\Tables;

use App\Filament\Exports\HistoriqueCompteSpecialExporter;
use App\Models\HistoriqueCompteSpecial;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\Exporter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class HistoriqueCompteSpecialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->searchable(),
                
                TextColumn::make('client_nom')
                    ->label('Membre/Client')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('montant')
                    ->label('Montant')
                    ->formatStateUsing(function ($state, $record) {
                        $devise = $record->devise ?? 'USD';
                        // Affichage normal dans le tableau
                        $montantFormate = number_format($state, 2);
                        return $montantFormate . ' ' . $devise;
                    })
                    ->sortable()
                    ->color(fn ($record) => $record->montant >= 0 ? 'success' : 'danger'),
                
                TextColumn::make('devise')
                    ->label('Devise')
                    ->sortable(),
                
                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                
              TextColumn::make('type_operation_custom')
    ->label('Catégorie')
    ->getStateUsing(function ($record) {
        $description = $record->description ?? '';
        
        // Utiliser la même logique que l'exporteur
        $type = HistoriqueCompteSpecialExporter::detecterTypeOperation($description);
        
        // Retourner avec icône et couleur
        return match($type) {
            'Première mise' => '🟢 Première mise',
            'Frais d\'adhésion' => '🔵 Frais d\'adhésion',
            'Achat carnet' => '📘 Achat carnet',
            'Achat livre' => '📗 Achat livre',
            'Frais crédit payés' => '💰 Frais crédit payés',
            default => '📝 Autre'
        };
    })
    ->badge()
    ->color(fn ($state) => match(true) {
        str_contains($state, 'Première mise') => 'success',
        str_contains($state, 'Frais d\'adhésion') => 'info',
        str_contains($state, 'Achat carnet') => 'warning',
        str_contains($state, 'Achat livre') => 'warning',
        str_contains($state, 'Frais crédit') => 'primary',
        default => 'gray'
    }),
            ])
            ->filters([
                // Filtre par devise
                SelectFilter::make('devise')
                    ->label('Devise')
                    ->options([
                        'USD' => 'USD ($)',
                        'CDF' => 'CDF (FC)',
                    ])
                    ->placeholder('Toutes les devises'),
                
              SelectFilter::make('categorie')
    ->label('Catégorie d\'opération')
    ->options([
        'premiere_mise' => 'Première mise',
        'frais_adhesion' => 'Frais d\'adhésion',
        'achat_carnet' => 'Achat carnets',
        'achat_livre' => 'Achat livres',
        'frais_credit' => 'Frais crédit payés',
        'autre' => 'Autres opérations',
    ])
    ->query(function (Builder $query, array $data) {
        if (empty($data['value'])) {
            return $query;
        }
        
        $categorie = $data['value'];
        
        return $query->where(function (Builder $query) use ($categorie) {
            switch ($categorie) {
                case 'premiere_mise':
                    $query->where(function ($q) {
                        $q->whereNull('description')
                          ->orWhere('description', '')
                          ->orWhere('description', 'like', '%première mise%')
                          ->orWhere('description', 'like', '%Première mise%')
                          ->orWhere('description', 'like', '%1ère mise%')
                          ->orWhere('description', '=', 'Aucune description');
                    });
                    break;
                    
                case 'frais_adhesion':
                    $query->where(function ($q) {
                        $q->where('description', 'like', '%frais d\'adhésion%')
                          ->orWhere('description', 'like', '%frais adhesion%')
                          ->orWhere('description', 'like', '%FRAIS D\'ADHESION%')
                          ->orWhere('description', 'like', '%FRAIS D ADHESION%')
                          ->orWhere('description', 'like', '%frais adhésion%')
                          ->orWhere('description', 'like', '%frais adhesion%');
                    });
                    break;
                    
                case 'achat_carnet':
                    $query->where(function ($q) {
                        $q->where('description', 'like', '%achat carnet%')
                          ->orWhere('description', 'like', '%ACHAT CARNET%')
                          ->orWhere('description', 'like', '%achat de carnet%')
                          ->orWhere('description', 'like', '%carnet%')
                          ->orWhere('description', 'like', '%CARNET%')
                          ->andWhere('description', 'not like', '%livre%'); // Exclure les livres
                    });
                    break;
                    
                case 'achat_livre':
                    $query->where(function ($q) {
                        $q->where('description', 'like', '%achat livre%')
                          ->orWhere('description', 'like', '%ACHAT LIVRE%')
                          ->orWhere('description', 'like', '%achat de livre%')
                          ->orWhere('description', 'like', '%livre%')
                          ->orWhere('description', 'like', '%LIVRE%');
                    });
                    break;
                    
                case 'frais_credit':
                    $query->where(function ($q) {
                        $q->where('description', 'like', '%frais crédit%')
                          ->orWhere('description', 'like', '%frais credit%')
                          ->orWhere('description', 'like', '%FRAIS CREDIT%')
                          ->orWhere('description', 'like', '%paiement crédit%')
                          ->orWhere('description', 'like', '%crédit #%')
                          ->orWhere('description', 'like', '%credit #%');
                    });
                    break;
                    
                case 'autre':
                    $query->where(function ($q) {
                        $q->whereNotNull('description')
                          ->where('description', '!=', '')
                          ->where('description', '!=', 'Aucune description')
                          ->where('description', 'not like', '%première mise%')
                          ->where('description', 'not like', '%frais d\'adhésion%')
                          ->where('description', 'not like', '%frais adhesion%')
                          ->where('description', 'not like', '%achat carnet%')
                          ->where('description', 'not like', '%achat livre%')
                          ->where('description', 'not like', '%frais crédit%');
                    });
                    break;
            }
        });
    }),
                // Filtre par période personnalisée
                Filter::make('periode_personnalisee')
                    ->label('Période personnalisée')
                    ->schema([
                        DatePicker::make('date_debut')
                            ->label('Date de début'),
                        DatePicker::make('date_fin')
                            ->label('Date de fin'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_debut'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['date_fin'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                
                // Filtre par type de mouvement (entrée/sortie)
                SelectFilter::make('type_mouvement')
                    ->label('Type de mouvement')
                    ->options([
                        'entree' => 'Entrées (montants positifs)',
                        'sortie' => 'Sorties (montants négatifs)',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        
                        $type = $data['value'];
                        
                        if ($type === 'entree') {
                            return $query->where('montant', '>=', 0);
                        } elseif ($type === 'sortie') {
                            return $query->where('montant', '<', 0);
                        }
                        
                        return $query;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                // Action pour générer un rapport HTML
                Action::make('generer_rapport')
                    ->label('Générer Rapport HTML')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->button()
                    ->modalWidth('xl')
                    ->modalHeading('Générer Rapport Historique Compte Spécial (HTML)')
                    ->schema([
                        DatePicker::make('date_debut')
                            ->label('Date de début')
                            ->default(now()->subDays(30))
                            ->required()
                            ->displayFormat('d/m/Y'),
                        
                        DatePicker::make('date_fin')
                            ->label('Date de fin')
                            ->default(now())
                            ->required()
                            ->displayFormat('d/m/Y'),
                        
                        Toggle::make('inclure_details')
                            ->label('Inclure le détail des opérations')
                            ->default(true),
                        
                        Toggle::make('inclure_synthese')
                            ->label('Inclure la synthèse')
                            ->default(true),
                        
                        TextInput::make('titre_rapport')
                            ->label('Titre du Rapport (optionnel)')
                            ->placeholder('Ex: Rapport Historique Compte Spécial - Novembre 2024')
                            ->maxLength(100),
                    ])
                    ->action(function (array $data) {
                        try {
                            // Récupérer l'historique dans la période
                            $historique = HistoriqueCompteSpecial::where('created_at', '>=', $data['date_debut'] . ' 00:00:00')
                                ->where('created_at', '<=', $data['date_fin'] . ' 23:59:59')
                                ->orderBy('created_at', 'asc')
                                ->get();
                            
                            if ($historique->isEmpty()) {
                                throw new \Exception('Aucune opération trouvée pour la période sélectionnée.');
                            }
                            
                            // Calculer les statistiques PAR DEVISE SÉPARÉMENT
                            $stats = self::calculerStatistiquesParDevise($historique);
                            
                            // Préparer les données pour le rapport
                            $rapport = [
                                'date_debut' => $data['date_debut'],
                                'date_fin' => $data['date_fin'],
                                'historique' => $historique,
                                'stats' => $stats,
                                'titre_rapport' => $data['titre_rapport'] ?? "Rapport Historique Compte Spécial TUMAINI",
                                'nombre_operations' => $historique->count(),
                                'date_generation' => now()->format('d/m/Y H:i'),
                                'generateur' => Auth::user()->name,
                                'inclure_details' => $data['inclure_details'],
                                'inclure_synthese' => $data['inclure_synthese'],
                                'logo_base64' => self::getLogoBase64(),
                            ];
                            
                            // Générer le HTML
                            $html = view('pdf.rapport-historique-compte-special', compact('rapport'))->render();
                            
                            $filename = 'rapport-historique-compte-special-' . now()->format('Y-m-d') . '.html';
                            
                            return response()->streamDownload(function () use ($html) {
                                echo $html;
                            }, $filename);
                            
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erreur lors de la génération du rapport')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalSubmitActionLabel('Générer le Rapport HTML')
                    ->modalCancelActionLabel('Annuler')
                    ->visible(fn () => Auth::user()->can('view_comptespecial')),
                
                // Action pour exporter en Excel avec analyse
                \Filament\Actions\ExportAction::make()
                    ->label('Exporter Excel (Analyse)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('warning')
                    ->exporter(\App\Filament\Exports\HistoriqueCompteSpecialExporter::class)
                    ->fileName('historique-compte-special-analyse')
                    ->formats([
                        ExportFormat::Csv,
                        ExportFormat::Xlsx,
                    ])
                    ->modalDescription('Exportez les données pour analyse dans Excel (tableaux croisés, diagrammes)')
                    ->modalHeading('Exporter pour Analyse Excel')
                    ->modalSubmitActionLabel('Exporter')
                    ->modalCancelActionLabel('Annuler')
                    ->visible(fn () => Auth::user()->can('view_comptespecial')),
            ]);
    }
    
    /**
     * Calcule les statistiques PAR DEVISE SÉPARÉMENT
     */
    private static function calculerStatistiquesParDevise($historique)
    {
        $statsParDevise = [];
        $groupedByDevise = $historique->groupBy('devise');
        
        foreach ($groupedByDevise as $devise => $operations) {
            $entreesDevise = 0;
            $sortiesDevise = 0;
            $operationsDevise = [];
            
            foreach ($operations as $operation) {
                // Remplacer "Aucune description" par "Première mise"
                $description = $operation->description;
                if (empty($description) || strtolower($description) === 'aucune description') {
                    $description = 'Première mise';
                }
                
                if ($operation->montant >= 0) {
                    $entreesDevise += $operation->montant;
                } else {
                    $sortiesDevise += abs($operation->montant);
                }
                
                $operationsDevise[] = [
                    'date' => $operation->created_at,
                    'client' => $operation->client_nom,
                    'montant' => $operation->montant,
                    'description' => $description,
                ];
            }
            
            $soldeDevise = $entreesDevise - $sortiesDevise;
            
            $statsParDevise[$devise] = [
                'entrees' => $entreesDevise,
                'sorties' => $sortiesDevise,
                'solde' => $soldeDevise,
                'operations' => $operations->count(),
                'liste_operations' => $operationsDevise,
            ];
        }
        
        return [
            'par_devise' => $statsParDevise,
        ];
    }
    
    /**
     * Récupère le logo en base64
     */
    private static function getLogoBase64()
    {
        // Essayez plusieurs chemins possibles
        $possiblePaths = [
            public_path('storage/images/logo-tumaini1.png'),
            public_path('images/logo-tumaini1.png'),
            public_path('logo-tumaini1.png'),
            storage_path('app/public/images/logo-tumaini1.png'),
            base_path('public/images/logo-tumaini1.png'),
        ];
        
        foreach ($possiblePaths as $logoPath) {
            if (file_exists($logoPath)) {
                return base64_encode(file_get_contents($logoPath));
            }
        }
        
        // Logo SVG par défaut
        $defaultLogo = '<?xml version="1.0" encoding="UTF-8"?>
        <svg width="200" height="100" xmlns="http://www.w3.org/2000/svg">
            <rect width="200" height="100" fill="#0066cc"/>
            <text x="100" y="45" text-anchor="middle" fill="white" font-family="Arial, sans-serif" font-size="20" font-weight="bold">TUMAINI LETU</text>
            <text x="100" y="70" text-anchor="middle" fill="white" font-family="Arial, sans-serif" font-size="14">ASBL</text>
        </svg>';
        
        return base64_encode($defaultLogo);
    }
}