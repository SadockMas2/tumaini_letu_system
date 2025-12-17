<?php

namespace App\Filament\Resources\SmsCampaigns\Schemas;

use App\Models\Client;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SmsCampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Destinataires')
                    ->schema([
                        Select::make('recipient_type')
                            ->label('Type de destinataires')
                            ->options([
                                'all_clients' => 'Tous les clients',
                                'selected_clients' => 'Clients sélectionnés',
                                'selected_comptes' => 'Comptes sélectionnés',
                                'selected_epargnes' => 'Comptes épargne sélectionnés',
                                'custom_numbers' => 'Numéros personnalisés',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state === 'all_clients') {
                                    $set('recipients_count', Client::whereNotNull('telephone')->count());
                                } elseif ($state === 'selected_clients') {
                                    $set('recipients_count', 0);
                                }
                            }),
                            
                        TextInput::make('recipients_count')
                            ->label('Nombre de destinataires')
                            ->disabled()
                            ->default(0),
                            
                        Select::make('client_ids')
                            ->label('Sélectionner des clients')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->relationship('clients', 'nom_complet')
                            ->visible(fn (callable $get) => $get('recipient_type') === 'selected_clients'),
                            
                        Textarea::make('custom_numbers')
                            ->label('Numéros personnalisés')
                            ->placeholder('Entrez un numéro par ligne\nEx: 243812345678\n243907654321')
                            ->helperText('Un numéro par ligne, format: 243XXXXXXXXX')
                            ->rows(3)
                            ->visible(fn (callable $get) => $get('recipient_type') === 'custom_numbers'),
                    ]),
                    
                Section::make('Message')
                    ->schema([
                        Textarea::make('message')
                            ->label('Message')
                            ->required()
                            ->rows(5)
                            ->maxLength(160)
                            ->helperText('Maximum 160 caractères')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('message_length', strlen($state));
                                $set('message_parts', ceil(strlen($state) / 160));
                            }),
                            
                        TextInput::make('message_length')
                            ->label('Longueur')
                            ->disabled()
                            ->suffix('caractères'),
                            
                        TextInput::make('message_parts')
                            ->label('Nombre de SMS')
                            ->disabled()
                            ->suffix('SMS'),
                            
                        Toggle::make('include_name')
                            ->label('Inclure le nom du client')
                            ->default(true)
                            ->helperText('Ajouter "Cher [Nom]" au début'),
                    ]),
                    
                Section::make('Paramètres')
                    ->schema([
                        Select::make('sms_type')
                            ->label('Type de SMS')
                            ->options([
                                'T' => 'Transactionnel',
                                'P' => 'Promotionnel',
                            ])
                            ->default('P')
                            ->required(),
                            
                        DateTimePicker::make('schedule_time')
                            ->label('Planifier l\'envoi')
                            ->helperText('Laissez vide pour envoyer immédiatement')
                            ->minDate(now()),
                            
                        Toggle::make('test_mode')
                            ->label('Mode test')
                            ->default(false)
                            ->helperText('Envoyer seulement au premier destinataire pour test'),
                    ]),
            ]);
    }
}
