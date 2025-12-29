<?php


namespace App\Filament\Resources\SmsCampaigns\Pages;

use App\Filament\Resources\SmsCampaigns\SmsCampaignResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Services\SmsCampaignService;
use App\Services\SmsService;
use Illuminate\Support\Facades\Auth;

class CreateSmsCampaign extends CreateRecord
{
    protected static string $resource = SmsCampaignResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Récupérer l'utilisateur connecté
        $data['user_id'] = auth::id();
        
        return $data;
    }
    
    // Cette méthode est appelée quand on clique sur "Créer"
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Créer le service
        $service = new SmsCampaignService(new SmsService());
        
        // Envoyer les SMS
        $result = $service->sendToAllClients(
            $data['message'],
            $data['include_name'] ?? true,
            Auth::id()
        );
        
        // Sauvegarder le résultat dans les données
        $data['recipients_count'] = $result['total_clients'] ?? 0;
        $data['success_count'] = $result['success'] ?? 0;
        $data['failed_count'] = $result['failed'] ?? 0;
        $data['sent_at'] = now();
        $data['status'] = 'completed';
        
        // Créer l'enregistrement dans la base
        $record = static::getModel()::create($data);
        
        // Afficher la notification
        Notification::make()
            ->title('Campagne SMS lancée')
            ->body($result['success'] . ' SMS envoyés avec succès sur ' . $result['total_clients'] . ' clients')
            ->success()
            ->send();
        
        return $record;
    }
    
    // Optionnel : Personnaliser le message de succès
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Campagne SMS créée et envoyée';
    }
}