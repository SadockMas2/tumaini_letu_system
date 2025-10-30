<?php

namespace App\Filament\Resources\GestionComptables\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GestionComptableForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
            Section::make('Écriture Comptable')
                    ->schema([
                        Select::make('journal_id')
                            ->relationship('journal', 'libelle_journal')
                            ->required()
                            ->label('Journal'),
                        TextInput::make('reference_operation')
                            ->required()
                            ->label('Référence Opération'),
                        Select::make('type_operation')
                            ->options([
                                'banque_vers_coffre' => 'Banque vers Coffre',
                                'coffre_vers_comptable' => 'Coffre vers Comptable',
                                'depense' => 'Dépense',
                                'produit' => 'Produit',
                                'pret_membre' => 'Prêt Membre',
                                'remboursement' => 'Remboursement',
                                'transfert_interne' => 'Transfert Interne',
                            ])
                            ->required(),
                        Select::make('compte_number')
                            ->relationship('planComptable', 'libelle')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Compte'),
                        TextInput::make('libelle')
                            ->required()
                            ->label('Libellé'),
                        TextInput::make('montant_debit')
                            ->numeric()
                            ->default(0)
                            ->label('Débit (USD)'),
                        TextInput::make('montant_credit')
                            ->numeric()
                            ->default(0)
                            ->label('Crédit (USD)'),
                        DatePicker::make('date_ecriture')
                            ->required()
                            ->label('Date Écriture'),
                        DatePicker::make('date_valeur')
                            ->required()
                            ->label('Date Valeur'),
                        Select::make('statut')
                            ->options([
                                'brouillon' => 'Brouillon',
                                'comptabilise' => 'Comptabilisé',
                                'annule' => 'Annulé',
                            ])
                            ->required()
                            ->default('brouillon'),
                    ])->columns(2),
            ]);
    }
}
