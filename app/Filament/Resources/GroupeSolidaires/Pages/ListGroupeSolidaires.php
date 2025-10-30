<?php

namespace App\Filament\Resources\GroupeSolidaires\Pages;

use App\Filament\Resources\GroupeSolidaires\GroupeSolidaireResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGroupeSolidaires extends ListRecords
{
    protected static string $resource = GroupeSolidaireResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function removeMember($membreId, $groupeId)
    {
        $groupe = \App\Models\GroupeSolidaire::find($groupeId);
        $groupe->membres()->detach($membreId);
        $this->dispatchBrowserEvent('notify', ['message' => 'Membre supprimé']);
    }

}
