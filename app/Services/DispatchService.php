<?php

namespace App\Services;

use App\Models\Epargne;
use App\Models\CompteTransitoire;
use App\Models\Compte;
use App\Models\Mouvement;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class DispatchService
{
    /**
     * Récupérer les épargnes EN ATTENTE de dispatch (celles qui n'ont pas encore été créditées au compte transitoire)
     */
    public function getEpargnesEnAttenteDispatch(): array
    {
        $userId = Auth::id();
        
        // Chercher les épargnes avec statut "en_attente_dispatch" (pas encore créditées au compte transitoire)
        $epargnes = Epargne::where('type_epargne', 'groupe_solidaire')
            ->where('statut', 'en_attente_dispatch')
            ->where('user_id', $userId)
            ->with(['groupeSolidaire', 'groupeSolidaire.membres'])
            ->get();

        Log::info('Epargnes trouvées pour dispatch:', [
            'count' => $epargnes->count(),
            'user_id' => $userId,
            'statut_recherche' => 'en_attente_dispatch',
            'epargnes_ids' => $epargnes->pluck('id')->toArray()
        ]);

        $options = [];
        foreach ($epargnes as $epargne) {
            // Utiliser Carbon pour formater la date
            $dateApport = Carbon::parse($epargne->date_apport);
            $reference = "Groupe: {$epargne->groupeSolidaire->nom_groupe} - {$epargne->montant} {$epargne->devise} - " . $dateApport->format('d/m/Y H:i');
            $options[$epargne->id] = $reference;
        }

        return $options;
    }

    /**
     * CRÉDITER AUTOMATIQUEMENT le compte transitoire quand une épargne groupe est créée
     * (Pour l'ancien code qui appelle encore cette méthode)
     */
    public function crediterCompteTransitoireAuto(Epargne $epargne): bool
    {
        return $this->crediterCompteTransitoire($epargne->id);
    }

    /**
     * CRÉDITER le compte transitoire et changer le statut (appelé depuis la page de dispatch)
     */
    public function crediterCompteTransitoire(int $epargneId): bool
    {
        return DB::transaction(function () use ($epargneId) {
            $epargne = Epargne::findOrFail($epargneId);
            $userId = Auth::id();
            
            // Vérifications
            if ($epargne->type_epargne !== 'groupe_solidaire') {
                throw new \Exception('Seules les épargnes de groupe peuvent être créditées au compte transitoire');
            }
            
            if ($epargne->statut !== 'en_attente_dispatch') {
                throw new \Exception('Cette épargne a déjà été créditée au compte transitoire');
            }
            
            if ($epargne->user_id !== $userId) {
                throw new \Exception('Vous ne pouvez créditer que vos propres épargnes');
            }

            $montant = $epargne->montant;
            $devise = $epargne->devise;

            // Trouver ou créer le compte transitoire
            $compteTransitoire = CompteTransitoire::firstOrCreate(
                [
                    'user_id' => $userId,
                    'devise' => $devise,
                ],
                [
                    'solde' => 0,
                    'statut' => 'actif'
                ]
            );
            
            // Créditer le compte transitoire
            $compteTransitoire->solde += $montant;
            $compteTransitoire->save();
            
            // Changer le statut
            $epargne->statut = 'en_attente_validation';
            $epargne->save();
            
            Log::info('Compte transitoire crédité', [
                'epargne_id' => $epargne->id,
                'user_id' => $userId,
                'montant' => $montant,
                'devise' => $devise,
                'nouveau_statut' => 'en_attente_validation'
            ]);
            
            return true;
        });
    }

    /**
     * DISPATCHER MANUELLEMENT vers les comptes membres
     */
    public function dispatcherEpargneGroupe(int $epargneId, array $repartitions): bool
    {
        return DB::transaction(function () use ($epargneId, $repartitions) {
            $epargne = Epargne::findOrFail($epargneId);
            $userId = Auth::id();
            
            // Vérifications de sécurité
            if ($epargne->type_epargne !== 'groupe_solidaire') {
                throw new \Exception('Seules les épargnes de groupe peuvent être dispatchées');
            }
            
            if ($epargne->statut !== 'en_attente_validation') {
                throw new \Exception('Cette épargne n\'est pas prête pour le dispatch');
            }
            
            if ($epargne->user_id !== $userId) {
                throw new \Exception('Vous ne pouvez dispatcher que vos propres épargnes');
            }
            
            $montantTotal = $epargne->montant;
            $totalReparti = 0;
            
            // Calculer le total réparti avec conversion en float
            foreach ($repartitions as $repartition) {
                $montant = $repartition['montant'] === '' ? 0 : (float) $repartition['montant'];
                $totalReparti += $montant;
            }
            
            // Vérifier que le total réparti = montant total (avec tolérance)
            if (abs($totalReparti - $montantTotal) > 0.01) {
                throw new \Exception("Le total réparti (" . number_format($totalReparti, 2) . ") ne correspond pas au montant collecté (" . number_format($montantTotal, 2) . ")");
            }
            
            // DÉBITER le compte transitoire de l'utilisateur
            $compteTransitoire = CompteTransitoire::where('user_id', $userId)
                ->where('devise', $epargne->devise)
                ->firstOrFail();
                
            if ($compteTransitoire->solde < $montantTotal) {
                throw new \Exception('Solde transitoire insuffisant');
            }
            
            $compteTransitoire->solde -= $montantTotal;
            $compteTransitoire->save();
            
            // CRÉDITER chaque membre avec montant > 0
            foreach ($repartitions as $repartition) {
                $montant = $repartition['montant'] === '' ? 0 : (float) $repartition['montant'];
                if ($montant > 0) {
                    $this->crediterCompteMembre(
                        $repartition['membre_id'],
                        $montant,
                        $epargne->devise,
                        Auth::user()->name,
                        $epargne->groupeSolidaire->nom_groupe
                    );
                }
            }
            
            // MARQUER comme terminé (sans date_dispatch si la colonne n'existe pas)
            $epargne->statut = 'valide';
            
            // Ajouter date_dispatch seulement si la colonne existe
            if (Schema::hasColumn('epargnes', 'date_dispatch')) {
                $epargne->date_dispatch = now();
            }
            
            $epargne->save();
            
            Log::info('Épargne dispatchée avec succès', [
                'epargne_id' => $epargne->id,
                'montant_total' => $montantTotal,
                'membres_credites' => count(array_filter($repartitions, fn($r) => ($r['montant'] === '' ? 0 : (float) $r['montant']) > 0))
            ]);
            
            return true;
        });
    }
    
    /**
     * Créditer le compte d'un membre individuel
     */
    private function crediterCompteMembre(int $membreId, float $montant, string $devise, string $agentNom, string $nomGroupe): void
    {
        $client = Client::findOrFail($membreId);
        
        // Trouver ou créer le compte du membre
        $compte = Compte::firstOrCreate(
            [
                'client_id' => $membreId,
                'devise' => $devise,
            ],
            [
                'solde' => 0,
                'numero_compte' => 'C' . str_pad($membreId, 6, '0', STR_PAD_LEFT),
                'nom' => $client->nom,
                'postnom' => $client->postnom,
                'prenom' => $client->prenom,
                'type_compte' => 'individuel',
                'statut' => 'actif'
            ]
        );
        
        // Créditer le compte
        $compte->solde += $montant;
        $compte->save();
        
        // Données de base du mouvement
        $mouvementData = [
            'compte_id' => $compte->id,
            'numero_compte' => $compte->numero_compte,
            'client_nom' => trim($client->nom . ' ' . $client->postnom . ' ' . $client->prenom),
            'nom_deposant' => $agentNom,
            'type' => 'depot',
            'montant' => $montant,
            'solde_apres' => $compte->solde,
            'description' => "Épargne groupe: {$nomGroupe}",
        ];
        
        // Ajouter operateur_id seulement si la colonne existe
        if (Schema::hasColumn('mouvements', 'operateur_id')) {
            $mouvementData['operateur_id'] = Auth::id();
        }
        
        // Ajouter date_mouvement seulement si la colonne existe
        if (Schema::hasColumn('mouvements', 'date_mouvement')) {
            $mouvementData['date_mouvement'] = now();
        }
        
        Mouvement::create($mouvementData);
    }

    /**
     * Récupérer le solde transitoire de l'utilisateur connecté
     */
    public function getSoldeTransitoireUtilisateur(): array
    {
        $userId = Auth::id();
        
        $soldes = CompteTransitoire::where('user_id', $userId)
            ->get()
            ->keyBy('devise');
            
        return [
            'USD' => $soldes->get('USD')->solde ?? 0,
            'CDF' => $soldes->get('CDF')->solde ?? 0,
        ];
    }

    /**
     * Vérifier si une épargne peut être dispatchée
     */
    public function peutEtreDispatchee(int $epargneId): bool
    {
        $epargne = Epargne::find($epargneId);
        
        if (!$epargne) {
            return false;
        }
        
        return $epargne->type_epargne === 'groupe_solidaire' 
            && $epargne->statut === 'en_attente_validation'
            && $epargne->user_id === Auth::id();
    }

    /**
     * Récupérer les détails d'une épargne pour le dispatch
     */
    public function getDetailsEpargne(int $epargneId): ?array
    {
        $epargne = Epargne::with(['groupeSolidaire.membres'])->find($epargneId);
        
        if (!$epargne || $epargne->type_epargne !== 'groupe_solidaire') {
            return null;
        }
        
        return [
            'id' => $epargne->id,
            'montant' => (float) $epargne->montant,
            'devise' => $epargne->devise,
            'groupe_nom' => $epargne->groupeSolidaire->nom_groupe,
            'statut' => $epargne->statut,
            'membres' => $epargne->groupeSolidaire->membres->map(function ($membre) {
                return [
                    'id' => $membre->id,
                    'nom_complet' => trim($membre->nom . ' ' . $membre->postnom . ' ' . $membre->prenom),
                ];
            })->toArray()
        ];
    }
}