<?php
// app/Filament/Resources/RapportTresorerieResource/Pages/ViewRapportTresorerie.php

namespace App\Filament\Resources\RapportTresorerieResource\Pages;

// use App\Filament\Resources\RapportTresorerieResource;
use App\Filament\Resources\RapportTresoreries\RapportTresorerieResource;
use App\Models\RapportTresorerie;
use App\Services\TresorerieService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class ViewRapportTresorerie extends ViewRecord
{
    protected static string $resource = RapportTresorerieResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Rapport de Trésorerie - ' . $this->record->numero_rapport;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Date du rapport: ' . $this->record->date_rapport->format('d/m/Y');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generer_pdf')
                ->label('Télécharger PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    try {
                        $tresorerieService = app(TresorerieService::class);
                        $rapport = $tresorerieService->genererRapportDetaillePDF($this->record->date_rapport);
                        
                        return response()->streamDownload(function () use ($rapport) {
                            echo Pdf::loadView('pdf.rapport-tresorerie', compact('rapport'))
                                ->setPaper('A4', 'portrait')
                                ->output();
                        }, 'rapport-tresorerie-' . $this->record->date_rapport->format('Y-m-d') . '.pdf');
                        
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erreur')
                            ->body('Impossible de générer le PDF: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('retour')
                ->label('Retour à la liste')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(RapportTresorerieResource::getUrl('index')),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        $synthese = app(TresorerieService::class)->rapportSynthese($this->record->date_rapport);

        return $schema
            ->schema([
                Section::make('Informations du Rapport')
                    ->schema([
                        Components\TextEntry::make('numero_rapport')
                            ->label('Numéro du Rapport'),
                            
                        Components\TextEntry::make('date_rapport')
                            ->label('Date du Rapport')
                            ->date('d/m/Y'),
                            
                        Components\TextEntry::make('statut')
                            ->label('Statut')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'brouillon' => 'warning',
                                'finalise' => 'success',
                                'valide' => 'primary',
                                'transfere_comptabilite' => 'info',
                            }),
                            
                        Components\TextEntry::make('createdBy.name')
                            ->label('Créé par'),
                            
                        Components\TextEntry::make('created_at')
                            ->label('Créé le')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(3),

                Section::make('Synthèse Générale')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                Components\TextEntry::make('total_depots')
                                    ->label('Total Dépôts')
                                    ->money('USD')
                                    ->color('success'),
                                    
                                Components\TextEntry::make('total_retraits')
                                    ->label('Total Retraits')
                                    ->money('USD')
                                    ->color('danger'),
                                    
                                Components\TextEntry::make('solde_total_caisses')
                                    ->label('Solde Total')
                                    ->money('USD')
                                    ->color('primary'),
                                    
                                Components\TextEntry::make('nombre_operations')
                                    ->label('Nombre d\'Opérations')
                                    ->numeric(),
                            ]),
                    ]),

                Section::make('Synthèse par Devise')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                KeyValueEntry::make('synthese_usd')
                                    ->label('USD - Dollars Américains')
                                    ->state([
                                        'Solde Total' => '$' . number_format($synthese['usd']['solde_total'], 2),
                                        'Dépôts' => '$' . number_format($synthese['usd']['depots'], 2),
                                        'Retraits' => '$' . number_format($synthese['usd']['retraits'], 2),
                                        'Opérations' => $synthese['usd']['operations'],
                                    ])
                                    ->columnSpan(1),

                                KeyValueEntry::make('synthese_cdf')
                                    ->label('CDF - Francs Congolais')
                                    ->state([
                                        'Solde Total' => number_format($synthese['cdf']['solde_total'], 2) . ' CDF',
                                        'Dépôts' => number_format($synthese['cdf']['depots'], 2) . ' CDF',
                                        'Retraits' => number_format($synthese['cdf']['retraits'], 2) . ' CDF',
                                        'Opérations' => $synthese['cdf']['operations'],
                                    ])
                                    ->columnSpan(1),
                            ]),
                    ]),

                Section::make('Détails par Caisse')
                    ->schema([
                        RepeatableEntry::make('detailsCaisses')
                            ->schema([
                                Grid::make(6)
                                    ->schema([
                                        TextEntry::make('caisse.nom')
                                            ->label('Caisse'),
                                            
                                        TextEntry::make('type_caisse')
                                            ->label('Type'),
                                            
                                        TextEntry::make('solde_initial')
                                            ->label('Solde Initial')
                                            ->money('USD'),
                                            
                                        TextEntry::make('solde_final')
                                            ->label('Solde Final')
                                            ->money('USD'),
                                            
                                        TextEntry::make('nombre_operations')
                                            ->label('Opérations')
                                            ->numeric(),
                                            
                                        TextEntry::make('total_mouvements')
                                            ->label('Total Mouvements')
                                            ->money('USD'),
                                    ]),
                            ])
                            ->grid(1),
                    ]),

                Section::make('Observations')
                    ->schema([
                        Components\TextEntry::make('observations')
                            ->label('')
                            ->prose()
                            ->hidden(fn ($state) => empty($state)),
                    ])
                    ->hidden(fn ($record) => empty($record->observations)),
            ]);
    }
}