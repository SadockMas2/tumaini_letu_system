<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Enums\TypePaiement; // AJOUTER CE USE STATEMENT

class CreditGroupe extends Model
{
    use HasFactory;

    protected $fillable = [
        'compte_id', 'montant_demande', 'montant_accorde', 'montant_total',
        'frais_dossier', 'frais_alerte', 'frais_carnet', 'frais_adhesion',
        'caution_totale', 'remboursement_hebdo_total', 'statut_demande',
        'date_demande', 'date_octroi', 'date_echeance', 'agent_id', 
        'superviseur_id', 'repartition_membres', 'montants_membres'
    ];

    protected $casts = [
        'date_demande' => 'datetime', 
        'date_octroi' => 'datetime', 
        'date_echeance' => 'datetime',
        'repartition_membres' => 'array', 
        'montants_membres' => 'array',
        'montant_demande' => 'decimal:2', 
        'montant_accorde' => 'decimal:2', 
        'montant_total' => 'decimal:2',
    ];

    // === CORRECTION : RELATION PAIEMENTS ===
    
    /**
     * Relation avec les paiements du groupe
     */
    public function paiements()
    {
        return $this->hasMany(PaiementCredit::class, 'credit_groupe_id');
    }

    // === CORRECTION : ACCESSORS ===
    
    /**
     * Calcule le montant restant à payer (TOTAL - total payé)
     */
    public function getMontantRestantAttribute()
    {
        $totalPaye = $this->getTotalDejaPayeAttribute();
        return max(0, floatval($this->montant_total) - $totalPaye);
    }

    /**
     * Calcule le montant total déjà payé
     */
    public function getTotalDejaPayeAttribute()
    {
        return PaiementCredit::where('credit_groupe_id', $this->id)
            ->where('type_paiement', TypePaiement::GROUPE->value)
            ->sum('montant_paye');
    }

    /**
     * Calcule le capital total remboursé
     * NOTE : La colonne est 'capital_rembourse' avec un S
     */
    public function getCapitalRembourseTotalAttribute()
    {
        return PaiementCredit::where('credit_groupe_id', $this->id)
            ->where('type_paiement', TypePaiement::GROUPE->value)
            ->sum('capital_rembourse');
    }

    /**
     * Calcule les intérêts total payés
     * NOTE : La colonne est 'interets_payes' avec un S
     */
    public function getInteretsPayesTotalAttribute()
    {
        return PaiementCredit::where('credit_groupe_id', $this->id)
            ->where('type_paiement', TypePaiement::GROUPE->value)
            ->sum('interets_payes');
    }

    /**
     * Calcule le montant dû jusqu'à présent
     */
    public function getMontantDuJusquPresentAttribute()
    {
        if (!$this->date_octroi) {
            return 0;
        }

        $dateDebut = $this->date_octroi->copy()->addWeeks(2);
        
        if (now()->lt($dateDebut)) {
            return 0;
        }
        
        $semainesEcoulees = $dateDebut->diffInWeeks(now());
        $semaineActuelle = min(max($semainesEcoulees + 1, 1), 16);
        
        return floatval($this->remboursement_hebdo_total) * $semaineActuelle;
    }

    /**
     * Calcule la semaine actuelle
     */
    public function getSemaineActuelleAttribute()
    {
        if (!$this->date_octroi) {
            return 1;
        }

        $dateDebut = $this->date_octroi->copy()->addWeeks(2);
        
        if (now()->lt($dateDebut)) {
            return 0;
        }
        
        $semainesEcoulees = $dateDebut->diffInWeeks(now());
        
        return min($semainesEcoulees + 1, 16);
    }

    // === RELATIONS ===
    
    public function compte()
    {
        return $this->belongsTo(Compte::class, 'compte_id');
    }

    public function creditsIndividuels()
    {
        return $this->hasMany(Credit::class, 'credit_groupe_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function superviseur()
    {
        return $this->belongsTo(User::class, 'superviseur_id');
    }

    /**
     * Récupérer les membres via groupes_membres
     */
    public function getMembresAttribute()
    {
        if (!$this->compte) return collect();

        return DB::table('groupes_membres')
            ->join('clients', 'groupes_membres.client_id', '=', 'clients.id')
            ->join('comptes', 'clients.id', '=', 'comptes.client_id')
            ->where('groupes_membres.groupe_solidaire_id', $this->compte->groupe_solidaire_id)
            ->select(
                'clients.id', 
                'clients.nom', 
                'clients.prenom', 
                'comptes.numero_compte', 
                'comptes.solde', 
                'comptes.devise', 
                'comptes.id as compte_id'
            )
            ->get();
    }

    /**
     * Récupère les membres avec leurs soldes et montants dus
     */
    public function getMembresAvecSoldesAttribute()
    {
        $repartition = $this->repartition_membres ?? [];
        $membresAvecSoldes = [];

        foreach ($repartition as $membreId => $details) {
            $compteMembre = Compte::where('client_id', $membreId)->first();
            if ($compteMembre) {
                // Calculer le solde disponible (hors caution)
                $caution = DB::table('cautions')
                    ->where('compte_id', $compteMembre->id)
                    ->where('statut', 'bloquee')
                    ->sum('montant');
                
                $soldeDisponible = max(0, $compteMembre->solde - $caution);
                
                $montantAccorde = floatval($details['montant_accorde'] ?? 0);
                $montantTotal = floatval($details['montant_total'] ?? 0);
                $capitalHebdomadaire = $montantAccorde / 16;
                $interetHebdomadaire = ($montantTotal - $montantAccorde) / 16;
                $remboursementHebdo = $capitalHebdomadaire + $interetHebdomadaire;
                
                $membresAvecSoldes[] = [
                    'membre_id' => $membreId,
                    'nom_complet' => $details['nom_complet'] ?? $compteMembre->nom . ' ' . $compteMembre->prenom,
                    'numero_compte' => $compteMembre->numero_compte,
                    'solde_total' => $compteMembre->solde,
                    'solde_disponible' => $soldeDisponible,
                    'montant_du' => $remboursementHebdo,
                    'montant_accorde' => $montantAccorde,
                    'montant_total' => $montantTotal,
                    'capital_hebdomadaire' => $capitalHebdomadaire,
                    'interet_hebdomadaire' => $interetHebdomadaire,
                    'remboursement_hebdo' => $remboursementHebdo,
                ];
            }
        }

        return $membresAvecSoldes;
    }

    /**
     * Récupère les membres crédités uniquement
     */
    public function getMembresCreditesAttribute()
    {
        $repartition = $this->repartition_membres ?? [];
        $membresCredites = [];

        foreach ($repartition as $membreId => $details) {
            if (isset($details['credite']) && $details['credite']) {
                $compteMembre = Compte::where('client_id', $membreId)->first();
                
                $membresCredites[] = [
                    'membre_id' => $membreId,
                    'nom_complet' => $compteMembre ? 
                        ($compteMembre->nom . ' ' . $compteMembre->prenom) : 
                        ($details['nom_complet'] ?? 'Membre ' . $membreId),
                    'numero_compte' => $compteMembre ? 
                        $compteMembre->numero_compte : 
                        ($details['numero_compte'] ?? 'N/A'),
                    'montant_accorde' => $details['montant_accorde'] ?? 0,
                    'montant_credite' => $details['montant_accorde'] ?? 0,
                    'montant_total' => $details['montant_total'] ?? 0,
                ];
            }
        }

        return $membresCredites;
    }

    /**
     * Créer les échéanciers pour le groupe
     */
    public function creerEcheanciersMembres()
    {
        Log::info('📅 === CRÉATION ÉCHÉANCIERS POUR GROUPE ===');
        
        $montantRestant = floatval($this->montant_total);
        $dateDebut = $this->date_octroi->copy()->addWeeks(2);
        
        for ($semaine = 1; $semaine <= 16; $semaine++) {
            $dateEcheance = $dateDebut->copy()->addWeeks($semaine - 1);
            
            // Montant à payer cette semaine
            $montantAPayer = ($semaine === 16) ? 
                $montantRestant : 
                $this->remboursement_hebdo_total;
            
            if ($montantRestant <= 0) break;
            
            // Calculer le capital restant
            $capitalHebdomadaire = $this->montant_accorde / 16;
            $capitalRestant = max(0, $this->montant_accorde - ($capitalHebdomadaire * $semaine));
            
            DB::table('echeanciers')->insert([
                'credit_groupe_id' => $this->id,
                'compte_id' => $this->compte_id,
                'semaine' => $semaine,
                'date_echeance' => $dateEcheance,
                'montant_a_payer' => $montantAPayer,
                'capital_restant' => $capitalRestant,
                'statut' => 'a_venir',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $montantRestant -= $montantAPayer;
        }
        
        Log::info("📊 Échéancier créé pour crédit groupe ID: {$this->id}");
    }

    /**
     * Calcul des frais pour crédit groupe
     */
    public static function calculerFraisGroupe($montantTotalGroupe)
    {
        $frais = [
            50 => ['dossier' => 2, 'alerte' => 4.5, 'carnet' => 2.5, 'caution' => 10],
            100 => ['dossier' => 4, 'alerte' => 4.5, 'carnet' => 2.5, 'caution' => 20],
            150 => ['dossier' => 6, 'alerte' => 4.5, 'carnet' => 2.5, 'caution' => 30],
            200 => ['dossier' => 8, 'alerte' => 4.5, 'carnet' => 2.5, 'caution' => 40],
            250 => ['dossier' => 10, 'alerte' => 4.5, 'carnet' => 2.5, 'caution' => 50],
            300 => ['dossier' => 12, 'alerte' => 4.5, 'carnet' => 2.5, 'caution' => 60],
            350 => ['dossier' => 14, 'alerte' => 4.5, 'carnet' => 2.5, 'caution' => 70],
            400 => ['dossier' => 16, 'alerte' => 4.5, 'carnet' => 2.5, 'caution' => 80],
            450 => ['dossier' => 18, 'alerte' => 4.5, 'carnet' => 2.5, 'caution' => 90],
            500 => ['dossier' => 20, 'alerte' => 4.5, 'carnet' => 2.5, 'caution' => 100],
        ];

        $montantArrondi = floor($montantTotalGroupe / 50) * 50;
        return $frais[$montantArrondi] ?? $frais[500] ?? [
            'dossier' => 20, 
            'alerte' => 4.5, 
            'carnet' => 2.5, 
            'caution' => 100
        ];
    }

    /**
     * Générer état de répartition
     */
    public function genererEtatRepartition()
    {
        $repartition = $this->repartition_membres ?? [];
        $etat = [
            'credit_groupe' => [
                'id' => $this->id,
                'montant_total' => $this->montant_total,
                'montant_accorde' => $this->montant_accorde,
                'remboursement_hebdo_total' => $this->remboursement_hebdo_total,
                'date_octroi' => $this->date_octroi,
                'date_echeance' => $this->date_echeance,
                'semaine_actuelle' => $this->semaine_actuelle,
                'montant_restant' => $this->montant_restant,
                'total_deja_paye' => $this->total_deja_paye,
                'capital_rembourse_total' => $this->capital_rembourse_total,
                'montant_du_jusqu_present' => $this->montant_du_jusqu_present,
            ],
            'membres' => []
        ];

        foreach ($repartition as $membreId => $details) {
            $compteMembre = Compte::where('client_id', $membreId)->first();
            if ($compteMembre) {
                $etat['membres'][] = [
                    'membre_id' => $membreId,
                    'nom_complet' => $compteMembre->nom . ' ' . $compteMembre->prenom,
                    'numero_compte' => $compteMembre->numero_compte,
                    'montant_accorde' => $details['montant_accorde'] ?? 0,
                    'frais_dossier' => $details['frais_dossier'] ?? 0,
                    'frais_alerte' => $details['frais_alerte'] ?? 0,
                    'frais_carnet' => $details['frais_carnet'] ?? 0,
                    'frais_adhesion' => $details['frais_adhesion'] ?? 0,
                    'caution' => $details['caution'] ?? 0,
                    'montant_total' => $details['montant_total'] ?? 0,
                    'remboursement_hebdo' => $details['remboursement_hebdo'] ?? 0,
                ];
            }
        }

        return $etat;
    }
}