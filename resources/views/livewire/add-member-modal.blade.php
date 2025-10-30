<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\GroupeSolidaire;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;

class AddMemberModal extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];
    public $groupeId;

    public function mount($groupeId): void
    {
        $this->groupeId = $groupeId;
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('membre_id')
                    ->label('Sélectionner un membre')
                    ->options(Client::whereNotIn('id', function($query) {
                        $query->select('client_id')
                            ->from('groupe_solidaire_client')
                            ->where('groupe_solidaire_id', $this->groupeId);
                    })->get()->mapWithKeys(function ($client) {
                        return [$client->id => $client->nom . ' ' . $client->postnom . ' ' . $client->prenom];
                    })->toArray())
                    ->searchable()
                    ->preload()
                    ->required(),
            ])
            ->statePath('data');
    }

    public function addMember(): void
    {
        $data = $this->form->getState();
        
        $groupe = GroupeSolidaire::find($this->groupeId);
        $groupe->membres()->attach($data['membre_id']);

        $this->dispatch('memberAdded');
        $this->dispatch('close-modal', id: 'add-member-modal');
    }

    public function render()
    {
        return view('livewire.add-member-modal');
    }
}