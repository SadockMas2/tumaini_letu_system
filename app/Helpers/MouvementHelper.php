<?php

namespace App\Helpers;

class MouvementHelper
{
    /**
     * Détermine si un type de mouvement est un retrait ou un dépôt
     */
   public static function getTypeAffichage($typeMouvement)
    {
        // Types qui sont des RETRAITS (affichage négatif)
        $typesRetrait = [
            'paiement_credit',
            'paiement_credit_groupe',
            'frais_payes_credit',
            'frais_payes_credit_groupe',
            'retrait_compte',
            'frais_service',
            'commission',
            'frais_ouverture_compte',
            'frais_gestion',
            'debit_automatique',
            'frais_adhesion',
            'paiement_credit_automatique',
            'complement_paiement_groupe',
            'achat_carnet_livre',
            'delaisage_comptabilite',
        ];
        
        // Types qui sont des DÉPÔTS (affichage positif)
        $typesDepot = [
            'credit_octroye',
            'credit_groupe_recu',
            'depot_compte',
            'excedent_groupe',
            'excedent_groupe_exact',
            'remboursement',
            'interet',
            'revenus_interets',
            'commission_recue',
            'bonus',
            'distribution_comptabilite',
            'paiement_salaire_charge',
            'versement_agent',
        ];
        
        // Types NEUTRES (affichage sans signe)
        $typesNeutres = [
            'caution_bloquee',
            'caution_bloquee_groupe',
            'transfert_sortant',
            'transfert_entrant',
            'conversion_devise_sortant',
            'conversion_devise_entrant',
        ];
        
        if (in_array($typeMouvement, $typesRetrait)) {
            return 'retrait';
        }
        
        if (in_array($typeMouvement, $typesDepot)) {
            return 'depot';
        }
        
        if (in_array($typeMouvement, $typesNeutres)) {
            return 'neutre';
        }
        
        return 'autre';
    }
    
    /**
     * Retourne le signe (+ ou -) pour l'affichage
     * Version simplifiée pour montants toujours positifs
     */
    public static function getSigne($typeMouvement)
    {
        $typeAffichage = self::getTypeAffichage($typeMouvement);
        
        if ($typeAffichage === 'depot') {
            return '+';
        } elseif ($typeAffichage === 'retrait') {
            return '-';
        } elseif ($typeAffichage === 'neutre') {
            return ''; // Pas de signe
        }
        
        return '+'; // Par défaut
    }
    
    /**
     * Formate le montant pour l'affichage avec le bon signe
     */
    public static function formatMontant($typeMouvement, $montant, $devise = 'USD')
    {
        $signe = self::getSigne($typeMouvement, $montant);
        
        // Pour les mouvements neutres avec montant 0, afficher sans signe
        if ($montant == 0 && $typeMouvement == 'caution_bloquee') {
            return number_format($montant, 2, ',', ' ') . ' ' . $devise;
        }
        
        $montantAbsolu = abs($montant);
        $signeAffichage = $signe ? $signe . ' ' : '';
        
        return $signeAffichage . number_format($montantAbsolu, 2, ',', ' ') . ' ' . $devise;
    }
    
    /**
     * Retourne la classe CSS pour la couleur
     */
    public static function getCouleurClasse($typeMouvement)
    {
        $typeAffichage = self::getTypeAffichage($typeMouvement);
        
        if ($typeAffichage === 'depot') {
            return 'text-green-600';
        } elseif ($typeAffichage === 'retrait') {
            return 'text-red-600';
        } elseif ($typeAffichage === 'neutre') {
            return 'text-gray-600';
        }
        
        return 'text-gray-600';
    }
    
    /**
     * Retourne la classe CSS pour le badge de type
     */
    public static function getBadgeClasse($typeMouvement)
    {
        $typeAffichage = self::getTypeAffichage($typeMouvement);
        
        if ($typeAffichage === 'depot') {
            return 'bg-green-100 text-green-800';
        } elseif ($typeAffichage === 'retrait') {
            return 'bg-red-100 text-red-800';
        } elseif ($typeAffichage === 'neutre') {
            return 'bg-gray-100 text-gray-800';
        }
        
        return 'bg-gray-100 text-gray-800';
    }
    
    /**
     * Retourne l'icône appropriée pour le type de mouvement
     */
    public static function getIcone($typeMouvement)
    {
        $typeAffichage = self::getTypeAffichage($typeMouvement);
        
        if ($typeAffichage === 'depot') {
            return 'fa-arrow-down';
        } elseif ($typeAffichage === 'retrait') {
            return 'fa-arrow-up';
        } elseif ($typeAffichage === 'neutre') {
            return 'fa-lock';
        }
        
        return 'fa-exchange-alt';
    }
    
    /**
     * Traduit le type de mouvement en français pour l'affichage
     */
    public static function traduireType($typeMouvement)
    {
        $traductions = [
            // Crédits
            'paiement_credit' => 'Remboursement crédit', // ← Remboursement
            'paiement_credit_groupe' => 'Paiement crédit groupe',
            'credit_octroye' => 'Octroi de crédit', // ← Octroi
            'credit_groupe_recu' => 'Crédit groupe reçu',
            'frais_payes_credit' => 'Frais crédit payés',
            'frais_payes_credit_groupe' => 'Frais groupe payés',
            
            // Dépôts/Retraits
            'depot_compte' => 'Dépôt',
            'retrait_compte' => 'Retrait',
            
            // Frais et cautions
            'caution_bloquee' => 'Caution bloquée',
            'caution_bloquee_groupe' => 'Caution groupe bloquée',
            
            // Autres
            'excedent_groupe' => 'Excédent groupe',
            'excedent_groupe_exact' => 'Excédent groupe exact',
            'frais_service' => 'Frais de service',
            'commission' => 'Commission',
            'remboursement' => 'Remboursement',
            'interet' => 'Intérêts',
            'revenus_interets' => 'Revenus intérêts',
            'frais_ouverture_compte' => 'Frais ouverture compte',
            'frais_gestion' => 'Frais de gestion',
            'frais_adhesion' => 'Frais d\'adhésion',
            'bonus' => 'Bonus',
            'debit_automatique' => 'Débit automatique',
            'transfert' => 'Transfert',
            'achat_carnet_livre' => 'Achat carnet/livre',
            'versement_agent' => 'Versement agent',
            'distribution_comptabilite' => 'Distribution comptabilité',
            'conversion_devise_sortant' => 'Conversion devise sortante',
            'conversion_devise_entrant' => 'Conversion devise entrante',
            'delaisage_comptabilite' => 'Délaistage comptabilité',
            'paiement_credit_automatique' => 'Paiement crédit automatique',
        ];
        
        return $traductions[$typeMouvement] ?? ucfirst(str_replace('_', ' ', $typeMouvement));
    }
}