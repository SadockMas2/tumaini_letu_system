<?php

namespace App\Filament\Traits;

use Illuminate\Support\Facades\Auth;

trait HasRoleBasedAccess
{
    public static function checkAccess(): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
            
        if (!$user) {
            return false;
        }

        // Admin a accès à tout
        if ($user->hasRole('Admin')) {
            return true;
        }

        return static::checkUserRolesAndPermissions($user);
    }

    protected static function checkUserRolesAndPermissions($user): bool
    {
        $className = class_basename(static::class);
        
        $rolePermissionMap = [
            // Dashboard - accessible à tous les utilisateurs authentifiés
            'Dashboard' => true,

            // Pages personnelles
            'EditProfile' => true,

            // Resources - Gestion des Membres
            'MembreResource' => $user->hasRole('MembresODP') || $user->hasPermissionTo('creer_compte_membre'),
            'ClientResource' => $user->hasRole('MembresODP') || $user->hasPermissionTo('creer_compte_membre'),

            // Resources - Gestion Financière
            'CompteResource' => $user->hasRole('Caissiere') || $user->hasAnyPermission(['effectuer_depot', 'effectuer_retrait', 'verifier_solde']),
            'TransactionResource' => $user->hasRole('Caissiere') || $user->hasAnyPermission(['effectuer_depot', 'effectuer_retrait']),
            'DepotResource' => $user->hasRole('Caissiere') || $user->hasPermissionTo('effectuer_depot'),
            'RetraitResource' => $user->hasRole('Caissiere') || $user->hasPermissionTo('effectuer_retrait'),

            // Resources - Comptabilité
            'OperationLogistiqueResource' => $user->hasRole('Comptable') || $user->hasPermissionTo('passer_operations_logistique'),
            'OperationCoffreResource' => $user->hasRole('Comptable') || $user->hasPermissionTo('enregistrer_operation_coffre'),

            // Resources - Crédits
            'CreditResource' => $user->hasRole('ChefBureau') || $user->hasPermissionTo('debourser_credit'),
            'AutorisationResource' => $user->hasRole('ChefBureau') || $user->hasPermissionTo('autoriser_retrait'),

            // Resources - Employés & Salaires
            'EmployeResource' => $user->hasRole('Financier') || $user->hasPermissionTo('paiement_salaire'),
            'SalaireResource' => $user->hasRole('Financier') || $user->hasPermissionTo('paiement_salaire'),

            // Resources - Rapports
            'RapportCaisseResource' => $user->hasRole('Caissiere') || $user->hasPermissionTo('extraire_rapport_caisse'),
            'RapportCoffreResource' => $user->hasRole('Comptable') || $user->hasPermissionTo('extraire_operations_coffre'),
            'RapportCollecteResource' => $user->hasRole('AgentCollecteur') || $user->hasPermissionTo('extraire_rapport_collecte'),
            'RapportMembresResource' => $user->hasRole('ConseillerMembres') || $user->hasPermissionTo('extraire_rapport_membres'),
            'RapportGeneralResource' => $user->hasAnyRole(['ControleurAuditeur', 'ConseilAdministration']) || $user->hasPermissionTo('extraire_tous_rapports'),

            // Resources - Administration
            'UserResource' => $user->hasRole('Admin'), // Seul l'admin peut gérer les users
        ];

        return $rolePermissionMap[$className] ?? false;
    }

    // Méthodes pour les Resources
    public static function canViewAny(): bool
    {
        return static::checkAccess();
    }

    public static function canCreate(): bool
    {
        return static::checkAccess();
    }

    public static function canEdit($record): bool
    {
        return static::checkAccess();
    }

    public static function canDelete($record): bool
    {
        return static::checkAccess();
    }

    public static function canView($record): bool
    {
        return static::checkAccess();
    }

    // Méthode pour les Pages (supprimer la duplication)
    // public static function canAccess(): bool
    // {
    //     return static::checkAccess();
    // }
}