<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditGroupe extends Model
{
    use HasFactory;

    protected $fillable = [
        'compte_id', 'montant_demande', 'montant_accorde', 'montant_total',
        'frais_dossier', 'frais_alerte', 'frais_carnet', 'frais_adhesion',
        'caution_totale', 'remboursement_hebdo_total', 'statut_demande',
        'date_demande', 'date_octroi', 'date_echeance', 'repartition_membres', 'montants_membres'
    ];

    protected $casts = [
        'date_demande' => 'datetime', 'date_octroi' => 'datetime', 'date_echeance' => 'datetime',
        'repartition_membres' => 'array', 'montants_membres' => 'array',
        'montant_demande' => 'decimal:2', 'montant_accorde' => 'decimal:2', 'montant_total' => 'decimal:2',
    ];

    public function compte()
    {
        return $this->belongsTo(Compte::class, 'compte_id');
    }

    public function creditsIndividuels()
    {
        return $this->hasMany(Credit::class, 'credit_groupe_id');
    }

    // Récupérer les membres via groupes_membres
    public function getMembresAttribute()
    {
        if (!$this->compte) return collect();

        return DB::table('groupes_membres')
            ->join('clients', 'groupes_membres.client_id', '=', 'clients.id')
            ->join('comptes', 'clients.id', '=', 'comptes.client_id')
            ->where('groupes_membres.groupe_solidaire_id', $this->compte->groupe_solidaire_id)
            ->select('clients.id', 'clients.nom', 'clients.prenom', 'comptes.numero_compte', 'comptes.solde', 'comptes.devise', 'comptes.id as compte_id')
            ->get();
    }

    // Créer les crédits individuels avec caution bloquée
    public function creerCreditsIndividuelsAvecCaution()
    {
        Log::info('🎯 === CRÉATION CRÉDITS INDIVIDUELS AVEC CAUTION ===');
        
        $repartition = $this->repartition_membres ?? [];
        
        foreach ($repartition as $membreId => $details) {
            $montantMembre = floatval($details['montant_accorde'] ?? 0);
            
            if ($montantMembre > 0) {
                Log::info("👤 Traitement membre ID: {$membreId}, Montant: {$montantMembre}");

                $compteMembre = DB::table('comptes')->where('client_id', $membreId)->first();
                if (!$compteMembre) {
                    Log::error("❌ Compte non trouvé pour client_id: {$membreId}");
                    continue;
                }

                try {
                    // BLOQUER LA CAUTION DANS LE COMPTE DU MEMBRE (20% du montant)
                    $caution = floatval($details['caution'] ?? 0);
                    if ($caution > 0) {
                        $this->bloquerCaution($compteMembre->id, $caution);
                        Log::info("🔒 Caution bloquée: {$caution} USD pour compte {$compteMembre->id}");
                    }

                    // CRÉER LE CRÉDIT INDIVIDUEL
                    $creditId = DB::table('credits')->insertGetId([
                        'compte_id' => $compteMembre->id,
                        'credit_groupe_id' => $this->id,
                        'type_credit' => 'groupe',
                        'montant_demande' => $montantMembre,
                        'montant_accorde' => $montantMembre,
                        'montant_total' => $details['montant_total'],
                        'frais_dossier' => $details['frais_dossier'],
                        'frais_alerte' => $details['frais_alerte'],
                        'frais_carnet' => $details['frais_carnet'],
                        'frais_adhesion' => $details['frais_adhesion'],
                        'caution' => $caution,
                        'caution_bloquee' => $caution,
                        'remboursement_hebdo' => $details['remboursement_hebdo'],
                        'duree_mois' => 4,
                        'statut_demande' => 'approuve',
                        'date_demande' => now(),
                        'date_octroi' => now(),
                        'date_echeance' => now()->addMonths(4),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    Log::info("✅ Crédit créé - ID: {$creditId}");

                } catch (\Exception $e) {
                    Log::error("❌ Erreur création crédit membre {$membreId}: " . $e->getMessage());
                    throw $e;
                }
            }
        }
        
        Log::info('🎉 === CRÉDITS INDIVIDUELS TERMINÉS ===');
    }

    // Bloquer la caution dans le compte du membre// Bloquer la caution dans le compte du membre
private function bloquerCaution($compteId, $montantCaution)
{
    // Récupérer le compte
    $compte = DB::table('comptes')->where('id', $compteId)->first();
    if (!$compte) {
        Log::error("❌ Compte non trouvé pour ID: {$compteId}");
        return;
    }

    // Créer un enregistrement de caution bloquée
    DB::table('cautions')->insert([
        'compte_id' => $compteId,
        'credit_groupe_id' => $this->id,
        'montant' => $montantCaution,
        'statut' => 'bloquee',
        'date_blocage' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Créer un mouvement pour la caution bloquée
    Mouvement::create([
        'compte_id' => $compteId,
        'type_mouvement' => 'caution_bloquee',
        'montant' => -$montantCaution,
        'solde_avant' => $compte->solde,
        'solde_apres' => $compte->solde, // Solde inchangé car caution bloquée
        'description' => "Caution crédit groupe bloquée - Montant: {$montantCaution} USD",
        'reference' => 'CAUTION-GROUPE-' . $this->id,
        'date_mouvement' => now(),
        'nom_deposant' => $compte->nom . ' ' . $compte->prenom ?? 'Membre Groupe', // CORRECTION ICI
    ]);
}
    // Créer les échéanciers pour tous les membres
    public function creerEcheanciersMembres()
    {
        Log::info('📅 === CRÉATION ÉCHÉANCIERS MEMBRES ===');
        
        $creditsIndividuels = DB::table('credits')
            ->where('credit_groupe_id', $this->id)
            ->get();

        foreach ($creditsIndividuels as $credit) {
            $this->creerEcheancierMembre($credit);
        }
        
        Log::info('✅ Échéanciers créés pour tous les membres');
    }

    // Créer échéancier pour un membre
    private function creerEcheancierMembre($credit)
    {
        $montantRestant = floatval($credit->montant_total);
        $dateDebut = now()->addWeeks(2); // Début dans 2 semaines
        
        for ($semaine = 1; $semaine <= 16; $semaine++) {
            $dateEcheance = $dateDebut->copy()->addWeeks($semaine - 1);
            $montantPaye = $semaine === 16 ? $montantRestant : $credit->remboursement_hebdo;
            
            if ($montantRestant <= 0) break;
            
            $montantRestant -= $montantPaye;
            if ($montantRestant < 0) $montantRestant = 0;
            
            DB::table('echeanciers')->insert([
                'credit_id' => $credit->id,
                'credit_groupe_id' => $this->id,
                'compte_id' => $credit->compte_id,
                'semaine' => $semaine,
                'date_echeance' => $dateEcheance,
                'montant_a_payer' => $montantPaye,
                'capital_restant' => $montantRestant,
                'statut' => 'a_venir',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        Log::info("📊 Échéancier créé pour crédit ID: {$credit->id}");
    }

    // Générer état de répartition
    public function genererEtatRepartition()
    {
        $repartition = $this->repartition_membres ?? [];
        $etat = [
            'credit_groupe' => [
                'id' => $this->id,
                'montant_total' => $this->montant_total,
                'remboursement_hebdo_total' => $this->remboursement_hebdo_total,
                'date_octroi' => $this->date_octroi,
                'date_echeance' => $this->date_echeance,
            ],
            'membres' => []
        ];

        foreach ($repartition as $membreId => $details) {
            $compteMembre = DB::table('comptes')->where('client_id', $membreId)->first();
            if ($compteMembre) {
                $etat['membres'][] = [
                    'membre_id' => $membreId,
                    'nom_complet' => $compteMembre->nom . ' ' . $compteMembre->prenom,
                    'numero_compte' => $compteMembre->numero_compte,
                    'montant_accorde' => $details['montant_accorde'],
                    'frais_dossier' => $details['frais_dossier'],
                    'frais_alerte' => $details['frais_alerte'],
                    'frais_carnet' => $details['frais_carnet'],
                    'frais_adhesion' => $details['frais_adhesion'],
                    'caution' => $details['caution'],
                    'montant_total' => $details['montant_total'],
                    'remboursement_hebdo' => $details['remboursement_hebdo'],
                ];
            }
        }

        return $etat;
    }

    // Récupérer l'historique des paiements du groupe
    public function getHistoriquePaiementsAttribute()
    {
        return DB::table('paiement_credits')
            ->join('credits', 'paiement_credits.credit_id', '=', 'credits.id')
            ->join('comptes', 'paiement_credits.compte_id', '=', 'comptes.id')
            ->join('clients', 'comptes.client_id', '=', 'clients.id')
            ->where('credits.credit_groupe_id', $this->id)
            ->select(
                'paiement_credits.*',
                'clients.nom',
                'clients.prenom',
                'comptes.numero_compte',
                'credits.montant_total as credit_total'
            )
            ->orderBy('paiement_credits.date_paiement', 'desc')
            ->get();
    }

    // Calcul des frais pour crédit groupe
    public static function calculerFraisGroupe($montantTotalGroupe)
    {
        $frais = [
            50 => ['dossier' => 2, 'alerte' => 4.5, 'carnet' => 2.5, 'adhesion' => 1, 'caution' => 10],
            100 => ['dossier' => 4, 'alerte' => 4.5, 'carnet' => 2.5, 'adhesion' => 1, 'caution' => 20],
            150 => ['dossier' => 6, 'alerte' => 4.5, 'carnet' => 2.5, 'adhesion' => 1, 'caution' => 30],
            200 => ['dossier' => 8, 'alerte' => 4.5, 'carnet' => 2.5, 'adhesion' => 1, 'caution' => 40],
            250 => ['dossier' => 10, 'alerte' => 4.5, 'carnet' => 2.5, 'adhesion' => 1, 'caution' => 50],
            300 => ['dossier' => 12, 'alerte' => 4.5, 'carnet' => 2.5, 'adhesion' => 1, 'caution' => 60],
            350 => ['dossier' => 14, 'alerte' => 4.5, 'carnet' => 2.5, 'adhesion' => 1, 'caution' => 70],
            400 => ['dossier' => 16, 'alerte' => 4.5, 'carnet' => 2.5, 'adhesion' => 1, 'caution' => 80],
            450 => ['dossier' => 18, 'alerte' => 4.5, 'carnet' => 2.5, 'adhesion' => 1, 'caution' => 90],
            500 => ['dossier' => 20, 'alerte' => 4.5, 'carnet' => 2.5, 'adhesion' => 1, 'caution' => 100],
        ];

        $montantArrondi = floor($montantTotalGroupe / 50) * 50;
        return $frais[$montantArrondi] ?? $frais[500] ?? ['dossier' => 20, 'alerte' => 4.5, 'carnet' => 2.5, 'adhesion' => 1, 'caution' => 100];
    }
}