<?php

namespace App\Filament\Resources\SmsCampaigns\Pages;

use App\Filament\Resources\SmsCampaigns\SmsCampaignResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSmsCampaign extends EditRecord
{
    protected static string $resource = SmsCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
