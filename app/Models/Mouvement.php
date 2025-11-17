<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Mouvement extends Model
{
    protected $fillable = [
        'caisse_id',
        'compte_id',
        'compte_epargne_id',
        'numero_compte',
        'client_nom',
        'nom_deposant',
        'type',
        'montant',
        'solde_avant',
        'solde_apres',
        'description',
        'operateur_id',
        'type_mouvement',
        'reference',
        'compte_number',
        'date_mouvement',
        'devise',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'solde_avant' => 'decimal:2',
        'solde_apres' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }

    public function operateur()
    {
        return $this->belongsTo(User::class, 'operateur_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($mouvement) {
            // CORRECTION : Déterminer la devise automatiquement
            self::determinerDevise($mouvement);
            
            // SI c'est un mouvement de CAISSE (avec caisse_id), ignorer la logique des comptes
            if ($mouvement->caisse_id) {
                $mouvement->operateur_id = Auth::id();
                return;
            }

            // SINON, c'est un mouvement de COMPTE (logique existante)
            $compte = $mouvement->compte;

            if (!$compte) {
                throw new \Exception('Compte introuvable pour ce mouvement');
            }

            // Convertir le montant en float pour les calculs
            $montant = (float) $mouvement->montant;

            // VALIDATION STRICTE DES RETRAITS
            if ($mouvement->type === 'retrait') {
                self::validerRetraitStrict($compte, $montant);
            }

            // Remplir automatiquement les informations
            self::remplirInfosAutomatiques($mouvement, $compte);

            // CORRECTION : NE PAS mettre à jour le solde ici pour les types spécifiques
            // Le solde est déjà mis à jour dans les contrôleurs
            self::enregistrerMouvementSansDoubleComptage($mouvement, $compte, $montant);
        });
    }

    /**
     * NOUVELLE MÉTHODE : Enregistrer le mouvement sans double comptage
     */
    private static function enregistrerMouvementSansDoubleComptage($mouvement, $compte, $montant)
    {
        $soldeActuel = (float) $compte->solde;
        
        // Types de mouvements où le solde a DÉJÀ été mis à jour
        $typesSoldeDejaMisAJour = [
            'credit_octroye',
            'credit_octroye_groupe', 
            'paiement_salaire',
            'paiement_salaire_charge',
            'depense_comptabilite',
            'depense_diverse_comptabilite'
        ];

        if (in_array($mouvement->type_mouvement, $typesSoldeDejaMisAJour)) {
            // Pour ces types, utiliser simplement le solde actuel
            $mouvement->solde_avant = $soldeActuel - ($mouvement->type === 'depot' ? $montant : -$montant);
            $mouvement->solde_apres = $soldeActuel;
        } else {
            // Pour les autres types, appliquer la logique normale
            self::appliquerMouvementSolde($mouvement, $compte, $montant, $soldeActuel);
        }
    }

    /**
     * Appliquer le mouvement au solde (pour les types normaux)
     */
    private static function appliquerMouvementSolde($mouvement, $compte, $montant, $soldeActuel)
    {
        $nouveauSolde = $soldeActuel;

        if ($mouvement->type === 'depot') {
            $nouveauSolde = $soldeActuel + $montant;
        } elseif ($mouvement->type === 'retrait') {
            $nouveauSolde = $soldeActuel - $montant;
        }

        // Mettre à jour le compte seulement pour les types normaux
        $compte->solde = $nouveauSolde;
        $compte->save();

        $mouvement->solde_avant = $soldeActuel;
        $mouvement->solde_apres = $nouveauSolde;
    }

    private static function determinerDevise($mouvement)
    {
        // Si la devise est déjà définie, la conserver
        if (!empty($mouvement->devise)) {
            return;
        }

        // Déterminer la devise selon le contexte
        if ($mouvement->caisse_id) {
            // Mouvement de caisse : utiliser la devise de la caisse
            $caisse = Caisse::find($mouvement->caisse_id);
            if ($caisse) {
                $mouvement->devise = $caisse->devise;
            }
        } elseif ($mouvement->compte_id) {
            // Mouvement de compte : utiliser la devise du compte
            $compte = Compte::find($mouvement->compte_id);
            if ($compte) {
                $mouvement->devise = $compte->devise;
            }
        } elseif (!empty($mouvement->compte_transitoire_id)) {
            // Mouvement de compte transitoire : utiliser la devise du compte transitoire
            $compteTransitoire = CompteTransitoire::find($mouvement->compte_transitoire_id);
            if ($compteTransitoire) {
                $mouvement->devise = $compteTransitoire->devise;
            }
        }

        // Devise par défaut si aucune trouvée
        if (empty($mouvement->devise)) {
            $mouvement->devise = 'USD';
        }
    }

    /**
     * VALIDATION STRICTE - EMPÊCHER TOUT RETRAIT QUI TOUCHE À LA CAUTION
     */
    private static function aCreditsActifs($compteId)
    {
        $creditsIndividuelsActifs = Credit::where('compte_id', $compteId)
            ->where('statut_demande', 'approuve')
            ->where('montant_total', '>', 0)
            ->exists();

        $creditsGroupeActifs = CreditGroupe::where('compte_id', $compteId)
            ->where('statut_demande', 'approuve')
            ->where('montant_total', '>', 0)
            ->exists();

        return $creditsIndividuelsActifs || $creditsGroupeActifs;
    }

    private static function validerRetraitStrict(Compte $compte, $montantRetrait)
    {
        // Convertir en float
        $soldeActuel = (float) $compte->solde;
        $montantRetrait = (float) $montantRetrait;

        // Vérification basique du solde
        if ($soldeActuel < $montantRetrait) {
            throw new \Exception('Solde insuffisant pour ce retrait. Solde actuel: ' . number_format($soldeActuel, 2) . ' USD');
        }

        // Vérifier si le compte a des crédits actifs
        $creditsActifs = self::aCreditsActifs($compte->id);

        if ($creditsActifs) {
            // Calculer la caution totale bloquée
            $cautionTotale = self::getCautionBloquee($compte->id);

            // Calculer le solde disponible (solde total - caution bloquée)
            $soldeDisponible = self::getSoldeDisponible($compte->id);

            // VALIDATION 1: Empêcher tout retrait qui dépasse le solde disponible
            if ($montantRetrait > $soldeDisponible) {
                throw new \Exception(
                    "RETRAIT REFUSÉ ❌\n\n" .
                    "Vous avez des crédits actifs. La caution est bloquée.\n" .
                    "Solde total: " . number_format($soldeActuel, 2) . " USD\n" .
                    "Caution bloquée: " . number_format($cautionTotale, 2) . " USD\n" .
                    "Solde disponible: " . number_format($soldeDisponible, 2) . " USD\n\n" .
                    "Montant maximum autorisé: " . number_format($soldeDisponible, 2) . " USD"
                );
            }

            // VALIDATION 2: Empêcher le retrait si le solde après serait inférieur à la caution
            $soldeApresRetrait = $soldeActuel - $montantRetrait;
            if ($soldeApresRetrait < $cautionTotale) {
                $maxRetrait = $soldeActuel - $cautionTotale;
                throw new \Exception(
                    "RETRAIT REFUSÉ ❌\n\n" .
                    "Ce retrait toucherait à la caution bloquée.\n" .
                    "Solde après retrait: " . number_format($soldeApresRetrait, 2) . " USD\n" .
                    "Caution à maintenir: " . number_format($cautionTotale, 2) . " USD\n\n" .
                    "Montant maximum autorisé: " . number_format($maxRetrait, 2) . " USD"
                );
            }

            // VALIDATION 3: Empêcher le retrait total (laisser au moins 1 USD)
            if ($soldeApresRetrait <= 1) {
                throw new \Exception(
                    "RETRAIT REFUSÉ ❌\n\n" .
                    "Vous ne pouvez pas vider complètement le compte.\n" .
                    "Un solde minimum doit être maintenu.\n" .
                    "Montant maximum: " . number_format($soldeActuel - 1, 2) . " USD"
                );
            }
        }
    }

    /**
     * Remplir automatiquement les informations du mouvement
     */
    private static function remplirInfosAutomatiques($mouvement, $compte)
    {
        // Numéro de compte
        $mouvement->numero_compte = $compte->numero_compte;

        // Nom du client selon le type de compte
        if ($compte->type_compte === 'groupe_solidaire') {
            $mouvement->client_nom = $compte->nom . ' (Groupe)';
        } else {
            $mouvement->client_nom = trim($compte->nom . ' ' . ($compte->postnom ?? '') . ' ' . ($compte->prenom ?? ''));
        }

        // Nom du déposant/retirant
        if ($mouvement->type === 'retrait') {
            $mouvement->nom_deposant = $mouvement->nom_deposant ?? 'Retrait';
        }

        // Opérateur connecté
        $mouvement->operateur_id = Auth::id();
    }

    /**
     * Méthode pour obtenir le solde disponible (hors caution bloquée)
     */
    public static function getSoldeDisponible($compteId)
    {
        $compte = Compte::find($compteId);
        if (!$compte) return 0;

        $cautionBloquee = self::getCautionBloquee($compteId);
        $soldeTotal = (float) $compte->solde;

        return max(0, $soldeTotal - $cautionBloquee);
    }

    /**
     * Méthode pour obtenir le montant de la caution bloquée
     */
    public static function getCautionBloquee($compteId)
    {
        // Vérifier d'abord si la table cautions existe
        if (!\Illuminate\Support\Facades\Schema::hasTable('cautions')) {
            return 0;
        }

        $caution = DB::table('cautions')
            ->where('compte_id', $compteId)
            ->where('statut', 'bloquee')
            ->sum('montant');

        return (float) $caution;
    }

    /**
     * Débloquer automatiquement les cautions quand le crédit est remboursé
     */
    public static function debloquerCautionAutomatique($compteId)
    {
        try {
            DB::transaction(function () use ($compteId) {
                $compte = Compte::find($compteId);
                if (!$compte) return;

                // Vérifier si tous les crédits sont remboursés
                $creditsNonRembourses = Credit::where('compte_id', $compteId)
                    ->where('statut_demande', 'approuve')
                    ->where('montant_total', '>', 0)
                    ->exists();

                // Si aucun crédit n'est en cours, débloquer les cautions
                if (!$creditsNonRembourses) {
                    $cautionsBloquees = DB::table('cautions')
                        ->where('compte_id', $compteId)
                        ->where('statut', 'bloquee')
                        ->get();

                    $totalDebloque = 0;

                    foreach ($cautionsBloquees as $caution) {
                        // Convertir le montant
                        $montantCaution = (float) $caution->montant;

                        // Débloquer la caution
                        DB::table('cautions')
                            ->where('id', $caution->id)
                            ->update([
                                'statut' => 'debloquee',
                                'date_deblocage' => now(),
                                'updated_at' => now()
                            ]);

                        $totalDebloque += $montantCaution;

                        // Créer un mouvement pour le déblocage
                        Mouvement::create([
                            'compte_id' => $compteId,
                            'type_mouvement' => 'deblocage_caution_auto',
                            'montant' => $montantCaution,
                            'solde_avant' => (float) $compte->solde,
                            'solde_apres' => (float) $compte->solde + $montantCaution,
                            'description' => "Déblocage automatique caution - Montant: " . number_format($montantCaution, 2) . " USD",
                            'reference' => 'AUTO-CAUTION-' . $caution->id,
                            'date_mouvement' => now(),
                            'nom_deposant' => 'Système',
                            'operateur_id' => 1 // ID système
                        ]);
                    }

                    if ($totalDebloque > 0) {
                        // Mettre à jour le solde du compte
                        $compte->solde = (float) $compte->solde + $totalDebloque;
                        $compte->save();

                        Log::info("✅ Cautions débloquées automatiquement pour le compte {$compteId}: " . number_format($totalDebloque, 2) . " USD");
                    }
                }
            });
        } catch (\Exception $e) {
            Log::error("❌ Erreur déblocage automatique caution: " . $e->getMessage());
        }
    }

    // Méthode pour générer le numéro de référence
    public function getNumeroReferenceAttribute()
    {
        return str_pad($this->id, 7, '0', STR_PAD_LEFT);
    }

    // Méthode pour obtenir le nom abrégé de l'opérateur
    public function getOperateurAbregeAttribute()
    {
        if (!$this->operateur) return 'N/A';
        
        $nom = substr($this->operateur->name, 0, 1) ?? '';
        $postnom = substr($this->operateur->postnom ?? '', 0, 1) ?? '';
        
        return $nom . $postnom . '-' . $this->operateur_id;
    }
}