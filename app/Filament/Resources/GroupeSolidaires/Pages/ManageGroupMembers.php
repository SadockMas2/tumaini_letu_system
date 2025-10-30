<?php

namespace App\Filament\Resources\GroupeSolidaires\Pages;

use App\Filament\Resources\GroupeSolidaires\GroupeSolidaireResource;
use App\Models\Client;
use App\Models\GroupeSolidaire;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action as TableAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class ManageGroupMembers extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = GroupeSolidaireResource::class;
    protected  string $view = 'filament.resources.groupe-solidaires.pages.manage-group-members';

    public GroupeSolidaire $record;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add_member')
                ->label('Ajouter un membre')
                ->icon('heroicon-o-user-plus')
                ->schema([
                    Select::make('client_id')
                        ->label('Sélectionner un membre')
                        ->options(Client::whereNotIn('id', $this->record->membres->pluck('id'))
                            ->get()
                            ->mapWithKeys(function ($client) {
                                return [$client->id => "{$client->nom} {$client->postnom} {$client->prenom} - {$client->telephone}"];
                            })
                            ->toArray())
                        ->searchable()
                        ->preload()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->record->membres()->attach($data['client_id']);
                    
                    Notification::make()
                        ->title('Membre ajouté avec succès')
                        ->success()
                        ->send();
                    
                    $this->refreshTableData();
                }),
                
            Action::make('back')
                ->label('Retour à la liste')
                ->url(fn () => GroupeSolidaireResource::getUrl('index'))
                ->color('gray'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Client::query()->whereHas('groupesSolidaires', function (Builder $query) {
                $query->where('groupes_solidaires.id', $this->record->id);
            }))
            ->columns([
                TextColumn::make('nom')
                    ->label('Nom')
                    ->sortable()
                    ->searchable(),
                    
                TextColumn::make('postnom')
                    ->label('Post-nom')
                    ->sortable()
                    ->searchable(),
                    
                TextColumn::make('prenom')
                    ->label('Prénom')
                    ->sortable()
                    ->searchable(),
                    
                TextColumn::make('adresse')
                    ->label('Adresse')
                    ->searchable(),
                    
                TextColumn::make('telephone')
                    ->label('Téléphone')
                    ->searchable(),
            ])
            ->RecordActions([
                TableAction::make('remove')
                    ->label('Supprimer')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Client $client): void {
                        $this->record->membres()->detach($client->id);
                        
                        Notification::make()
                            ->title('Membre supprimé du groupe')
                            ->success()
                            ->send();
                            
                        $this->refreshTableData();
                    }),
            ])
            ->emptyStateHeading('Aucun membre dans ce groupe')
            ->emptyStateDescription('Ajoutez des membres pour commencer.');
    }

    protected function refreshTableData(): void
    {
        $this->resetTable();
    }
}