<?php

namespace App\Filament\Resources\SmsLogs\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class SmsLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
           Section::make('Informations SMS')
                    ->schema([
                        TextInput::make('phone_number')
                            ->label('Numéro de téléphone')
                            ->required()
                            ->maxLength(20)
                            ,
                            
                        Textarea::make('message')
                            ->label('Message')
                            ->required()
                            ->rows(3)
                           
                            ->columnSpanFull(),
                            
                        Select::make('type')
                            ->label('Type de SMS')
                            ->options([
                                'transaction' => 'Transaction',
                                'alert' => 'Alerte',
                                'marketing' => 'Marketing',
                                'otp' => 'OTP',
                                'reminder' => 'Rappel',
                            ])
                            ->default('transaction')
                            ->disabled()
                            ->required(),
                            
                        TextInput::make('uid')
                            ->label('Référence unique (UID)')
                            ->disabled()
                            ->maxLength(100),
                    ])
                    ->columns(2),
                    
                Section::make('Statut et Suivi')
                    ->schema([
                        Select::make('status')
                            ->label('Statut')
                            ->options([
                                'sent' => 'Envoyé',
                                'delivered' => 'Livré',
                                'failed' => 'Échoué',
                                'pending' => 'En attente',
                                'undeliverable' => 'Non livrable',
                            ])
                            ->required(),
                            
                        Select::make('delivery_status')
                            ->label('Statut de livraison')
                            ->options([
                                'Delivered' => 'Livré',
                                'Pending' => 'En attente',
                                'Undeliverable' => 'Non livrable',
                                'Acknowledged' => 'Accusé',
                                'Expired' => 'Expiré',
                                'Accepted' => 'Accepté',
                                'Rejected' => 'Rejeté',
                                'Unknown' => 'Inconnu',
                                'Failed' => 'Échoué',
                                'DND' => 'DND',
                            ])
                            ->nullable(),
                            
                        TextInput::make('message_id')
                            ->disabled()
                            ->label('ID Message'),
                            
                        TextInput::make('cost')
                            ->label('Coût')
                            ->numeric()
                            ->disabled()
                            ->prefix('USD'),
                    ])
                    ->columns(2),
                    
                Section::make('Informations de suivi')
                    ->schema([
                        Textarea::make('remarks')
                            ->label('Remarques')
                            ->rows(2),
                            
                        TextInput::make('sent_at')
                            ->label('Date d\'envoi')
                            ->disabled(),
                            
                        TextInput::make('created_at')
                            ->label('Date de création')
                            ->disabled(),
                    ])
                    ->columns(2),
                    
                    
               
            ]);
    }
}
