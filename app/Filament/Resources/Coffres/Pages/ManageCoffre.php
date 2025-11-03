<?php
// app/Filament/Resources/CoffreResource/Pages/ManageCoffre.php

namespace App\Filament\Resources\CoffreResource\Pages;

use App\Filament\Resources\Coffres\CoffreResource;
use App\Models\CashRegister;
use App\Models\MouvementCoffre;
use App\Services\CoffreService;
use App\Services\ComptabilityService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\DB;

class ManageCoffre extends ManageRecords
{
    protected static string $resource = CoffreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // 1. Alimentation depuis Banque/Partenaire
            Action::make('alimenter_banque')
                ->label('Alimentation Banque/Partenaire')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->schema([
                    Select::make('coffre_id')
                        ->options(CashRegister::pluck('nom', 'id'))
                        ->required()
                        ->label('Coffre'),
                    TextInput::make('montant')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->label('Montant'),
                    Select::make('devise')
                        ->options(['USD' => 'USD', 'CDF' => 'CDF'])
                        ->required()
                        ->default('USD'),
                    Select::make('source')
                        ->options([
                            'banque' => 'Banque',
                            'partenaire' => 'Partenaire',
                            'autre' => 'Autre'
                        ])
                        ->required()
                        ->label('Source'),
                    TextInput::make('reference')
                        ->required()
                        ->label('Référence'),
                    Textarea::make('description')
                        ->label('Description'),
                ])
                ->action(function (array $data) {
                    try {
                        DB::transaction(function () use ($data) {
                            $coffre = CashRegister::find($data['coffre_id']);
                            
                            // Créer mouvement physique
                            $mouvement = $coffre->alimenter(
                                $data['montant'],
                                $data['source'],
                                $data['reference'],
                                $data['description']
                            );

                            // Enregistrement comptable OBLIGATOIRE
                            app(ComptabilityService::class)->enregistrerAlimentationCoffre(
                                $mouvement->id, 
                                $data['reference']
                            );
                        });

                        Notification::make()
                            ->title('Alimentation réussie')
                            ->body("Coffre alimenté de {$data['montant']} {$data['devise']}")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erreur')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            // 2. Transfert vers Comptabilité
            Action::make('transferer_comptabilite')
                ->label('Transfert vers Comptabilité')
                ->icon('heroicon-o-arrow-right')
                ->color('warning')
                ->schema([
                    Select::make('coffre_id')
                        ->options(CashRegister::pluck('nom', 'id'))
                        ->required()
                        ->label('Coffre Source'),
                    TextInput::make('montant')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->label('Montant'),
                    Select::make('devise')
                        ->options(['USD' => 'USD', 'CDF' => 'CDF'])
                        ->required()
                        ->default('USD'),
                    TextInput::make('motif')
                        ->required()
                        ->label('Motif du transfert'),
                ])
                ->action(function (array $data) {
                    try {
                        DB::transaction(function () use ($data) {
                            $coffre = CashRegister::find($data['coffre_id']);
                            
                            // Créer mouvement physique
                            $mouvement = $coffre->transfererVersComptabilite(
                                $data['montant'],
                                $data['motif']
                            );

                            // Enregistrement comptable OBLIGATOIRE
                            app(ComptabilityService::class)->enregistrerTransfertCoffreVersComptable($mouvement->id);
                        });

                        Notification::make()
                            ->title('Transfert réussi')
                            ->body("{$data['montant']} {$data['devise']} transférés vers la comptabilité")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erreur')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}