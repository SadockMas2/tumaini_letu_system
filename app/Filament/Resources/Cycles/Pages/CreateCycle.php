<?php

namespace App\Filament\Resources\Cycles\Pages;

use App\Filament\Resources\Cycles\CycleResource;
use App\Models\CompteSpecial;
use App\Models\Cycle;
use App\Models\HistoriqueCompteSpecial;
use App\Models\CompteTransitoire;
use App\Models\Mouvement;
use App\Services\CycleService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateCycle extends CreateRecord
{
    protected static string $resource = CycleResource::class;

    protected function handleRecordCreation(array $data): Cycle
    {
        Log::info('🎯 CREATE CYCLE - Début création', ['data' => $data]);
        
        try {
            // Utiliser le service pour créer le cycle avec toute la logique
            $cycleService = app(CycleService::class);
            $cycle = $cycleService->creerCycle($data);
            
            Log::info('✅ CREATE CYCLE - Cycle créé avec succès', ['cycle_id' => $cycle->id]);
            
            Notification::make()
                ->title('Cycle créé avec succès')
                ->body("Le cycle a été ouvert et {$cycle->solde_initial} {$cycle->devise} ont été débités du compte transitoire de l'agent.")
                ->success()
                ->send();
                
            return $cycle;
            
        } catch (\Exception $e) {
            Log::error('❌ CREATE CYCLE - Erreur', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            Notification::make()
                ->title('Erreur')
                ->body($e->getMessage())
                ->danger()
                ->send();
                
            throw $e;
        }
    }

    protected function afterCreate(): void
    {
        // 🔹 Récupère le cycle créé
        $cycle = $this->record;
        $montant = $cycle->solde_initial;

        Log::info('🔹 AFTER CREATE - Crédit compte spécial', [
            'cycle_id' => $cycle->id,
            'montant' => $montant,
            'devise' => $cycle->devise
        ]);

        // 🔹 Mettre à jour ou créer le compte spécial selon la devise
        $compte = CompteSpecial::firstOrCreate(
            ['devise' => $cycle->devise],
            [
                'nom' => 'Compte Spécial ' . $cycle->devise,
                'solde' => 0
            ]
        );

        // 🔹 Ajouter le montant au compte
        $compte->increment('solde', $montant);

        // 🔹 Ajouter un enregistrement dans l'historique
        HistoriqueCompteSpecial::create([
            'cycle_id'   => $cycle->id,
            'client_nom' => $cycle->client_nom,
            'montant'    => $montant,
            'devise'     => $cycle->devise,
            'type_operation' => 'depot_initial_cycle',
            // 'description' => 'Dépôt initial pour ouverture du cycle #' . $cycle->numero_cycle,
        ]);

        Log::info('✅ AFTER CREATE - Compte spécial crédité', [
            'compte_special_id' => $compte->id,
            'nouveau_solde' => $compte->solde
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}