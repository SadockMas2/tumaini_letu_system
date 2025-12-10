<?php

namespace App\Filament\Resources\CompteEpargnes\Pages;

use App\Filament\Resources\CompteEpargneResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListCompteEpargnes extends ListRecords
{
    protected static string $resource = \App\Filament\Resources\CompteEpargnes\CompteEpargneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function applySearchToTableQuery(Builder $query): Builder
    {
        $search = $this->getTableSearch();
        
        if (blank($search)) {
            return $query;
        }

        // Recherche par numéro de compte ET par nom complet
        return $query->where(function ($q) use ($search) {
            $q->where('numero_compte', 'like', "%{$search}%")
              ->orWhereHas('client', function ($clientQuery) use ($search) {
                  $clientQuery->where('nom_complet', 'like', "%{$search}%");
              })
              ->orWhereHas('groupeSolidaire', function ($groupeQuery) use ($search) {
                  $groupeQuery->where('nom_groupe', 'like', "%{$search}%");
              });
        });
    }
}