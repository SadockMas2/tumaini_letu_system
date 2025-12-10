<?php

namespace App\Filament\Resources\Clients\Widgets;

use App\Models\Client;
use Filament\Tables;
use Filament\Widgets\TableWidget;

class GalerieClientsTable extends TableWidget
{
    protected static ?string $heading = 'Galerie professionnelle des membres';

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(Client::query())
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Photo')
                    ->square()
                    ->extraImgAttributes([
                        'class' => 'w-32 h-32 object-cover rounded-lg cursor-pointer hover:scale-105 transition',
                    ])
                    ->url(fn ($record) => asset('storage/' . $record->image), true),

                Tables\Columns\TextColumn::make('nom')
                    ->label('Nom complet')
                    ->formatStateUsing(fn ($record) => $record->nom.' '.$record->postnom.' '.$record->prenom),

                Tables\Columns\TextColumn::make('numero_membre')
                    ->label('N° membre')
                    ->sortable(),

                Tables\Columns\ImageColumn::make('signature')
                    ->label('Signature')
                    ->extraImgAttributes([
                        'class' => 'w-32 h-20 object-contain bg-white border rounded-lg p-2 cursor-pointer hover:scale-105 transition',
                    ])
                    ->url(fn ($record) => asset('storage/' . $record->signature), true),
            ])
            ->paginationPageOptions([12, 24, 48]);
    }
}
