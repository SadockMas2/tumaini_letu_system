<?php

namespace App\Filament\Resources\Comptabilites\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ComptabilitesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date_ecriture')
                    ->date()
                    ->sortable(),
                TextColumn::make('journal.libelle_journal')
                    ->label('Journal'),
                TextColumn::make('compte_number')
                    ->label('Compte'),
                TextColumn::make('libelle')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('montant_debit')
                    ->money(function ($record) {
                        // Détermine la devise basée sur le compte ou autre logique métier
                        return self::getDeviseFromCompte($record->compte_number);
                    })
                    ->label('Débit'),
                TextColumn::make('montant_credit')
                    ->money(function ($record) {
                        return self::getDeviseFromCompte($record->compte_number);
                    })
                    ->label('Crédit'),
                TextColumn::make('devise')
                    ->label('Devise')
                    ->badge()
                    ->color(fn ($state) => $state === 'USD' ? 'success' : 'warning'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Détermine la devise basée sur le numéro de compte
     */
    private static function getDeviseFromCompte(string $compteNumber): string
    {
        // Logique pour déterminer la devise selon le plan comptable
        // Par exemple, les comptes 5111xx et 5712xx pourraient être en USD
        // et les comptes 5112xx et 5713xx en CDF
        
        $prefix = substr($compteNumber, 0, 4);
        
        $comptesUSD = ['5111', '5712', '5211']; // Exemple
        $comptesCDF = ['5112', '5713', '5212']; // Exemple
        
        if (in_array($prefix, $comptesUSD)) {
            return 'USD';
        } elseif (in_array($prefix, $comptesCDF)) {
            return 'CDF';
        }
        
        return 'USD'; // Devise par défaut
    }
}