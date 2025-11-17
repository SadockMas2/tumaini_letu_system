<?php

namespace App\Filament\Resources\RapportTresoreries\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RapportTresorerieForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations du Rapport')
                    ->schema([
                        DatePicker::make('date_rapport')
                            ->label('Date du Rapport')
                            ->required()
                            ->default(now())
                            ->displayFormat('d/m/Y')
                            ->closeOnDateSelection(),

                        TextInput::make('numero_rapport')
                            ->label('Numéro du Rapport')
                            ->disabled()
                            ->default('RAPP-' . now()->format('Ymd-His')),

                        Select::make('statut')
                            ->label('Statut')
                            ->options([
                                'brouillon' => 'Brouillon',
                                'finalise' => 'Finalisé',
                                'valide' => 'Validé',
                                'transfere_comptabilite' => 'Transféré Comptabilité',
                            ])
                            ->default('brouillon')
                            ->required(),

                        Textarea::make('observations')
                            ->label('Observations')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Totaux')
                    ->schema([
                        TextInput::make('total_depots')
                            ->label('Total Dépôts')
                            ->numeric()
                            ->prefix('$')
                            ->disabled(),

                        TextInput::make('total_retraits')
                            ->label('Total Retraits')
                            ->numeric()
                            ->prefix('$')
                            ->disabled(),

                        TextInput::make('solde_total_caisses')
                            ->label('Solde Total Caisses')
                            ->numeric()
                            ->prefix('$')
                            ->disabled(),

                        TextInput::make('nombre_operations')
                            ->label('Nombre d\'Opérations')
                            ->numeric()
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }
}
