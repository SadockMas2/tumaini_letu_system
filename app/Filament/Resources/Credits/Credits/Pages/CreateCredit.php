<?php

namespace App\Filament\Resources\Credits\Credits\Pages;

use App\Filament\Resources\Credits\Credits\CreditResource;
use App\Models\MouvementCredit;
use Filament\Resources\Pages\CreateRecord;

class CreateCredit extends CreateRecord
{
    protected static string $resource = CreditResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['montant_total'] = $data['montant_principal'] * (1 + $data['taux_interet'] / 100);
        $data['statut'] = 'en_cours';
        return $data;
    }

    protected function afterCreate(): void
    {
        // Créer le premier mouvement : Coffre → Comptable
        MouvementCredit::create([
            'credit_id' => $this->record->id,
            'role_source' => 'coffre',
            'role_dest' => 'comptable',
            'montant' => $this->record->montant_total,
            'statut' => 'en_attente',
        ]);
    }

    protected function getRedirectUrl(): string
{
    return $this->getResource()::getUrl('index');
}
}
