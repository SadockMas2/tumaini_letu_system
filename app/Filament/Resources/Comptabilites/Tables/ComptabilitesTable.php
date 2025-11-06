<?php

namespace App\Filament\Resources\Comptabilites\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TextInput\Actions\CopyAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ComptabilitesTable
{
    public static function configure(Table $table): Table
    {
        return $table
                ->defaultSort('created_at', 'desc')
                

            ->columns([
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('date_ecriture')
                    ->date()
                    ->sortable(),
                TextColumn::make('reference_operation')
                    ->label('Reference'),
                TextColumn::make('journal.libelle_journal')
                    ->label('Journal'),
                TextColumn::make('compte_number')
                    ->label('Compte'),
                TextColumn::make('libelle')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('montant_debit')
                        ->numeric(
                            decimalPlaces: 2,
                            decimalSeparator: ',',
                            thousandsSeparator: ' '
                        )
                        ->label('Débit'),

                TextColumn::make('montant_credit')
                        ->numeric(
                            decimalPlaces: 2,
                            decimalSeparator: ',',
                            thousandsSeparator: ' '
                        )
                        ->label('Crédit'),
                TextColumn::make('devise')
                    ->label('Devise')
                    ->badge()
                    ->color(fn ($state) => $state === 'USD' ? 'success' : 'warning'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                    Filter::make('created_at')

            ->schema([
                        DatePicker::make('created_from')
                            ->label('Du'),
                        DatePicker::make('created_until')
                            ->label('Au'),
                        TextInput::make('compte_number')
                    ->label('Compte'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                // EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // DeleteBulkAction::make(),
                    ExportBulkAction::make(),
                    
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