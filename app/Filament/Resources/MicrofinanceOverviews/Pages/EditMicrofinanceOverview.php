<?php

namespace App\Filament\Resources\MicrofinanceOverviews\Pages;

use App\Filament\Resources\MicrofinanceOverviews\MicrofinanceOverviewResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMicrofinanceOverview extends EditRecord
{
    protected static string $resource = MicrofinanceOverviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
