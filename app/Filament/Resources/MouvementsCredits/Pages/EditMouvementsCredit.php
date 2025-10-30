<?php

namespace App\Filament\Resources\MouvementsCredits\Pages;

use App\Filament\Resources\MouvementsCredits\MouvementsCreditResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMouvementsCredit extends EditRecord
{
    protected static string $resource = MouvementsCreditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
