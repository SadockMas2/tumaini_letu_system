<?php

namespace App\Filament\Resources\Comptabilites\Schemas;

use App\Models\PlanComptable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ComptabiliteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
            Select::make('journal_id')
                    ->relationship('journal', 'libelle_journal')
                    ->required(),
                TextInput::make('reference_operation')
                    ->required(),
                Select::make('compte_number')
                    ->options(PlanComptable::pluck('libelle', 'numero_compte'))
                    ->required(),
                TextInput::make('libelle')
                    ->required(),
                TextInput::make('montant_debit')
                    ->numeric()
                    ->default(0),
                TextInput::make('montant_credit')
                    ->numeric()
                    ->default(0),
                DatePicker::make('date_ecriture')
                    ->required(),
            ]);
    }
}
