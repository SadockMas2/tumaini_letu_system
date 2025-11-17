<?php

namespace App\Services;

use App\Models\Cycle;
use App\Models\Epargne;
use App\Models\CompteSpecial;
use App\Models\CompteTransitoire;
use App\Models\Mouvement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CycleService
{
    /**
     * Créer un nouveau cycle d'épargne
     */
   public function creerCycle(array $data): Cycle
{
    return DB::transaction(function () use ($data) {
        Log::info('Début création cycle', ['data' => $data]);
        
        // Vérifier d'abord si le compte transitoire a suffisamment de fonds
        if (isset($data['solde_initial']) && $data['solde_initial'] > 0) {
            $this->validerSoldeTransitoire($data);
        }
        
        $cycle = Cycle::create($data);
        Log::info('Cycle créé', ['cycle_id' => $cycle->id]);
        
        // Débiter le compte transitoire APRÈS la création du cycle
        if ($cycle->solde_initial > 0) {
            Log::info('Début débit compte transitoire', [
                'agent_id' => $cycle->user_id,
                'montant' => $cycle->solde_initial,
                'devise' => $cycle->devise
            ]);
            $this->debiterCompteTransitoire($cycle);
            Log::info('Débit compte transitoire terminé');
        }
        
        // Créditer le compte spécial UNIQUEMENT ici
        // if ($cycle->solde_initial > 0) {
        //     Log::info('Début crédit compte spécial', ['montant' => $cycle->solde_initial]);
        //     $cycle->crediterCompteSpecial();
        //     Log::info('Crédit compte spécial terminé');
        // }
        
        // ✅ NOUVEAU : Enregistrer l'écriture comptable
        $comptabilityService = app(ComptabilityService::class);
        $comptabilityService->enregistrerOuvertureCycle($cycle);
        Log::info('Écriture comptable créée pour le cycle');
        
        return $cycle;
    });
}

    // Ajoutez cette méthode dans CycleService
    public function diagnostiquerCompteTransitoire(int $userId, string $devise)
{
    $compte = CompteTransitoire::where('user_id', $userId)
        ->where('devise', $devise)
        ->first();
    
    Log::info('Diagnostic Compte Transitoire', [
        'user_id' => $userId,
        'devise' => $devise,
        'compte_existe' => !is_null($compte),
        'compte_id' => $compte ? $compte->id : null,
        'solde_actuel' => $compte ? $compte->solde : null
    ]);
    
    return $compte;
}

    /**
     * Valider le solde du compte transitoire avant création du cycle
     */
private function validerSoldeTransitoire(array $data): void
{
    Log::info('=== VALIDATION SOLDE TRANSITOIRE ===');
    
    if (!isset($data['user_id']) || !isset($data['devise'])) {
        throw new \Exception("L'agent et la devise sont requis pour ouvrir un cycle avec solde initial.");
    }

    $compteTransitoire = CompteTransitoire::where('user_id', $data['user_id'])
        ->where('devise', $data['devise'])
        ->first();

    Log::info('Résultat recherche compte', [
        'user_id' => $data['user_id'],
        'devise' => $data['devise'],
        'compte_trouve' => !is_null($compteTransitoire),
        'compte_id' => $compteTransitoire ? $compteTransitoire->id : null,
        'solde_compte' => $compteTransitoire ? $compteTransitoire->solde : null
    ]);

    if (!$compteTransitoire) {
        throw new \Exception("L'agent ne dispose pas d'un compte transitoire en {$data['devise']}.");
    }

    Log::info('Comparaison soldes', [
        'solde_disponible' => $compteTransitoire->solde,
        'solde_requis' => $data['solde_initial'],
        'suffisant' => $compteTransitoire->solde >= $data['solde_initial']
    ]);

    if ($compteTransitoire->solde < $data['solde_initial']) {
        throw new \Exception(
            "Solde insuffisant dans le compte transitoire de l'agent. " .
            "Solde disponible: {$compteTransitoire->solde} {$data['devise']}, " .
            "Montant requis: {$data['solde_initial']} {$data['devise']}"
        );
    }
    
    Log::info('=== FIN VALIDATION - SOLDE SUFFISANT ===');
}

    /**
     * Débiter le compte transitoire après création du cycle
     */
private function debiterCompteTransitoire(Cycle $cycle): void
{
    Log::info('💰 DÉBUT DÉBIT COMPTE TRANSITOIRE', [
        'cycle_id' => $cycle->id,
        'user_id' => $cycle->user_id,
        'devise' => $cycle->devise,
        'montant' => $cycle->solde_initial
    ]);

    // 1. Recherche du compte
    $compteTransitoire = CompteTransitoire::where('user_id', $cycle->user_id)
        ->where('devise', $cycle->devise)
        ->first();

    if (!$compteTransitoire) {
        Log::error('❌ COMPTE TRANSITOIRE INTROUVABLE', [
            'user_id' => $cycle->user_id,
            'devise' => $cycle->devise
        ]);
        throw new \Exception("Compte transitoire introuvable pour l'agent ID: {$cycle->user_id} en devise: {$cycle->devise}");
    }

    Log::info('✅ COMPTE TROUVÉ', [
        'compte_id' => $compteTransitoire->id,
        'solde_avant' => $compteTransitoire->solde
    ]);

    // 2. Vérification solde
    if ($compteTransitoire->solde < $cycle->solde_initial) {
        Log::error('❌ SOLDE INSUFFISANT', [
            'solde_disponible' => $compteTransitoire->solde,
            'solde_requis' => $cycle->solde_initial
        ]);
        throw new \Exception("Solde insuffisant. Disponible: {$compteTransitoire->solde}, Requis: {$cycle->solde_initial}");
    }

    // 3. DÉBIT
    $ancienSolde = $compteTransitoire->solde;
    
    // Méthode directe
    $compteTransitoire->solde = $ancienSolde - $cycle->solde_initial;
    $resultat = $compteTransitoire->save();
    
    if (!$resultat) {
        Log::error('❌ ÉCHEC SAUVEGARDE COMPTE');
        throw new \Exception("Échec de la sauvegarde du compte transitoire");
    }

    // Recharger pour confirmation
    $compteTransitoire->refresh();

    Log::info('✅ DÉBIT RÉUSSI', [
        'ancien_solde' => $ancienSolde,
        'nouveau_solde' => $compteTransitoire->solde,
        'montant_débité' => $cycle->solde_initial
    ]);

    // 4. Enregistrement du mouvement
    try {
        Mouvement::create([
            'compte_transitoire_id' => $compteTransitoire->id,
            'type' => 'retrait',
            'type_mouvement' => 'ouverture_cycle',
            'montant' => $cycle->solde_initial,
            'solde_avant' => $ancienSolde,
            'solde_apres' => $compteTransitoire->solde,
            'description' => "Ouverture cycle {$cycle->numero_cycle} - {$cycle->client_nom}",
            'nom_deposant' => $cycle->agent_nom ?? 'Système',
            'operateur_id' => Auth::id() ?? 1,
            'devise' => $cycle->devise,
            'numero_compte' => 'CYCLE-' . $cycle->id,
            'client_nom' => $cycle->client_nom,
            'date_mouvement' => now()
        ]);

        Log::info('📝 MOUVEMENT ENREGISTRÉ');
        
    } catch (\Exception $e) {
        Log::error('⚠️ ERREUR MOUVEMENT', ['error' => $e->getMessage()]);
    }

    Log::info('🏁 FIN DÉBIT COMPTE TRANSITOIRE');
}

    /**
     * Ajouter une épargne à un cycle
     */
   public function ajouterEpargne(array $data): Epargne
{
    return DB::transaction(function () use ($data) {
        $epargne = Epargne::create($data);
        
        // ✅ NOUVEAU : Enregistrer l'écriture comptable
        if ($epargne->statut === 'valide') {
            $comptabilityService = app(ComptabilityService::class);
            $comptabilityService->enregistrerEpargne($epargne);
            Log::info('Écriture comptable créée pour l\'épargne');
        }
        
        return $epargne;
    });
}


    /**
     * Clôturer un cycle et traiter les soldes
     */public function cloturerCycle(int $cycleId): Cycle
{
    return DB::transaction(function () use ($cycleId) {
        $cycle = Cycle::findOrFail($cycleId);
        
        // Vérifier que toutes les épargnes sont validées
        $epargnesEnAttente = Epargne::where('cycle_id', $cycleId)
            ->whereIn('statut', ['en_attente_dispatch', 'en_attente_validation'])
            ->exists();
        
        if ($epargnesEnAttente) {
            throw new \Exception('Impossible de clôturer le cycle : des épargnes sont en attente');
        }

        $cycle->fermer();
        
        // ✅ NOUVEAU : Enregistrer l'écriture comptable de clôture
        $comptabilityService = app(ComptabilityService::class);
        $comptabilityService->enregistrerClotureCycle($cycle);
        Log::info('Écriture comptable créée pour la clôture du cycle');
        
        return $cycle;
    });
}

    /**
     * Récupérer le solde total d'un cycle
     */
    public function getSoldeCycle(int $cycleId): array
    {
        $cycle = Cycle::findOrFail($cycleId);
        
        $soldeInitial = $cycle->solde_initial;
        $totalEpargnes = Epargne::where('cycle_id', $cycleId)
            ->where('statut', 'valide')
            ->sum('montant');
        
        $soldeCompteSpecial = $soldeInitial;
        $soldeMembres = $totalEpargnes;
        
        return [
            'solde_initial' => $soldeInitial,
            'total_epargnes' => $totalEpargnes,
            'solde_compte_special' => $soldeCompteSpecial,
            'solde_membres' => $soldeMembres,
            'solde_total' => $soldeInitial + $totalEpargnes,
        ];
    }

    /**
     * Obtenir les comptes transitoires disponibles pour un agent
     */
    public function getComptesTransitoiresAgent(int $userId): array
    {
        $comptes = CompteTransitoire::where('user_id', $userId)->get();
        
        return $comptes->map(function ($compte) {
            return [
                'devise' => $compte->devise,
                'solde' => $compte->solde,
                'solde_formate' => number_format($compte->solde, 2) . ' ' . $compte->devise
            ];
        })->toArray();
    }
}