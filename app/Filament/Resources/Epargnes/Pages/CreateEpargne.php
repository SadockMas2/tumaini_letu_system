<?php

namespace App\Filament\Resources\Epargnes\Pages;
use App\Services\DispatchService;
use App\Filament\Resources\Epargnes\EpargneResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateEpargne extends CreateRecord
{
    protected static string $resource = EpargneResource::class;

        protected function getRedirectUrl(): string
            {
                return $this->getResource()::getUrl('index');
            }

        // protected function afterCreate(): void
        //     {
        //         // Après la création de l'épargne, créditer le compte transitoire si c'est un groupe
        //         if ($this->record->type_epargne === 'groupe_solidaire') {
        //             $dispatchService = new DispatchService();
        //             $dispatchService->crediterCompteTransitoireAuto($this->record);
        //         }
        //     }

}
