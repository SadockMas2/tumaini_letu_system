<?php
// app/Filament/Resources/GestionTresorerieResource/Pages/ManageGestionTresorerie.php

namespace App\Filament\Resources\GestionTresorerieResource\Pages;

// use App\Filament\Resources\GestionTresorerieResource;
use App\Filament\Resources\GestionTresoreries\GestionTresorerieResource;
use App\Models\CashRegister;
use App\Models\Caisse;
use App\Services\SupplyChainService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\DB;

class ManageGestionTresorerie extends ManageRecords
{
    protected static string $resource = GestionTresorerieResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('creer_coffre')
                ->label('Nouveau Coffre')
                ->icon('heroicon-o-plus')
                ->schema([
                    TextInput::make('nom')
                        ->required()
                        ->label('Nom du Coffre'),
                    TextInput::make('solde_initial')
                        ->numeric()
                        ->required()
                        ->label('Solde Initial (USD)'),
                    Select::make('responsable_id')
                        ->relationship('responsable', 'name')
                        ->required()
                        ->label('Responsable'),
                    TextInput::make('plafond_journalier')
                        ->numeric()
                        ->label('Plafond Journalier (USD)'),
                ])
                ->action(function (array $data) {
                    DB::transaction(function () use ($data) {
                        CashRegister::create([
                            'nom' => $data['nom'],
                            'solde_actuel' => $data['solde_initial'],
                            'solde_ouverture' => $data['solde_initial'],
                            'responsable_id' => $data['responsable_id'],
                            'plafond_journalier' => $data['plafond_journalier'],
                            'agence_id' => 1,
                            'devise' => 'USD',
                            'statut' => 'actif',
                        ]);

                        Notification::make()
                            ->title('Coffre créé avec succès')
                            ->success()
                            ->send();
                    });
                }),

            Action::make('alimenter_banque')
                ->label('Alimenter depuis Banque')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->schema([
                    Select::make('coffre_id')
                        ->options(CashRegister::where('statut', 'actif')->pluck('nom', 'id'))
                        ->required()
                        ->label('Coffre Destination'),
                    TextInput::make('montant')
                        ->numeric()
                        ->required()
                        ->label('Montant (USD)'),
                    TextInput::make('reference_banque')
                        ->required()
                        ->label('Référence Banque'),
                    Textarea::make('description')
                        ->label('Description')
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    try {
                        app(SupplyChainService::class)->processusApprovisionnementBanque(
                            $data['coffre_id'],
                            $data['montant'],
                            $data['reference_banque']
                        );

                        Notification::make()
                            ->title('Alimentation réussie')
                            ->body("Le coffre a été alimenté de {$data['montant']} USD")
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

            Action::make('transferer_comptable')
                ->label('Transférer vers Comptable')
                ->icon('heroicon-o-arrow-right')
                ->color('warning')
                ->schema([
                    Select::make('coffre_id')
                        ->options(CashRegister::where('statut', 'actif')->pluck('nom', 'id'))
                        ->required()
                        ->label('Coffre Source'),
                    TextInput::make('montant')
                        ->numeric()
                        ->required()
                        ->label('Montant (USD)'),
                    TextInput::make('motif')
                        ->required()
                        ->label('Motif du Transfert'),
                ])
                ->action(function (array $data) {
                    try {
                        app(SupplyChainService::class)->transfererVersComptable(
                            $data['coffre_id'],
                            $data['montant'],
                            $data['motif']
                        );

                        Notification::make()
                            ->title('Transfert réussi')
                            ->body("{$data['montant']} USD transférés vers le comptable")
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

            Action::make('distribuer_caisses')
                ->label('Distribuer aux Caisses')
                ->icon('heroicon-o-share')
                ->color('info')
                ->schema([
                    Select::make('comptable_id')
                        ->relationship('comptable', 'name')
                        ->required()
                        ->label('Comptable Responsable'),
                    TextInput::make('montant_grande_caisse')
                        ->numeric()
                        ->label('Montant Grande Caisse (USD)')
                        ->default(0),
                    TextInput::make('montant_petite_caisse')
                        ->numeric()
                        ->label('Montant Petite Caisse (USD)')
                        ->default(0),
                    TextInput::make('reference')
                        ->required()
                        ->label('Référence'),
                ])
                ->action(function (array $data) {
                    try {
                        $distributions = [
                            'grande_caisse' => $data['montant_grande_caisse'],
                            'petite_caisse' => $data['montant_petite_caisse'],
                        ];

                        app(SupplyChainService::class)->distribuerFondsCaisses(
                            $data['comptable_id'],
                            $distributions
                        );

                        Notification::make()
                            ->title('Distribution réussie')
                            ->body("Fonds distribués aux caisses")
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

            Action::make('cloturer_journee')
                ->label('Clôturer Journée')
                ->icon('heroicon-o-clock')
                ->color('danger')
                ->schema([
                    Select::make('coffre_id')
                        ->options(CashRegister::where('statut', 'actif')->pluck('nom', 'id'))
                        ->required()
                        ->label('Coffre'),
                    TextInput::make('solde_physique')
                        ->numeric()
                        ->required()
                        ->label('Solde Physique Réel (USD)'),
                    Textarea::make('observations')
                        ->label('Observations')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    try {
                        $resultat = app(SupplyChainService::class)->processusFinJournee(
                            $data['coffre_id'],
                            $data['solde_physique'],
                            $data['observations']
                        );

                        Notification::make()
                            ->title('Journée clôturée')
                            ->body("Rapport généré avec succès")
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