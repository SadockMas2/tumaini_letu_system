<?php

namespace App\Filament\Resources\Credits\Credits\Schemas;

use App\Models\Client;
use App\Models\Cycle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CreditForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                
                Select::make('client_id')
                ->label('Membre')
                ->options(Client::all()->mapWithKeys(fn($c) => [$c->id => "{$c->nom} {$c->postnom} {$c->prenom}"]))
                ->searchable()
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, $set) {
                    $cycles = Cycle::where('client_id', $state)->where('statut', 'ouvert')->pluck('numero_cycle', 'id');
                    $set('cycle_id', null);
                    $set('cycles', $cycles);
                }),

            // Select::make('cycle_id')
            //     ->label('Cycle actif')
            //     ->options(fn($get) => Cycle::where('client_id', $get('client_id'))->where('statut', 'ouvert')->pluck('numero_cycle', 'id')),

            TextInput::make('montant_principal')
                ->label('Montant principal')
                ->numeric()
                ->required(),

            TextInput::make('taux_interet')
                ->label('Taux d’intérêt (%)')
                ->numeric()
                ->default(5)
                ->required(),

            DatePicker::make('date_octroi')
                ->label('Date d’octroi')
                ->default(now())
                ->required(),

            DatePicker::make('date_echeance')
                ->label('Date d’échéance')
                ->required(),

            Select::make('statut')
                ->label('Statut')
                ->options([
                    'en_cours' => 'En cours',
                    'remboursé' => 'Remboursé',
                    'retard' => 'En retard',
                ])
                ->default('en_cours')
                ->disabled()
                ->dehydrated(),
            ]);
    }
}
