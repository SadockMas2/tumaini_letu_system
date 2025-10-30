<?php

namespace App\Filament\Resources\GroupeSolidaires\Pages;

use App\Filament\Resources\GroupeSolidaires\GroupeSolidaireResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGroupeSolidaire extends CreateRecord
{
    protected static string $resource = GroupeSolidaireResource::class;

    // ✅ Déclaration de la propriété manquante
    protected array $membres = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->membres = $data['membres'] ?? [];
        unset($data['membres']);
        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->membres()->sync($this->membres);
    }

    protected function getRedirectUrl(): string
{
    return $this->getResource()::getUrl('index');
}
}
