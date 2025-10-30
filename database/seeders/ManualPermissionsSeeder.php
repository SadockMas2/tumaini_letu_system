<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ManualPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Nettoyer les anciennes permissions
        Permission::query()->delete();

        // Liste de toutes vos ressources Filament (basées sur vos noms de classes)
        $resources = [
            'User',
            'Client',
            'Membre',
            'Compte',
            'Transaction',
            'Depot',
            'Retrait',
            'Credit',
            'Employe',
            'Salaire',
            'Coffre',
            'OperationLogistique',
            'OperationCoffre',
            'Autorisation',
            'RapportCaisse',
            'RapportCoffre',
            'RapportCollecte',
            'RapportMembres',
            'RapportGeneral',
            'CompteBancaire',
            'CompteSpecial',
            'CompteTransitoire',
            'Cycle',
            'Dispatch',
            'Epargne',
            'GroupeSolidaire',
            'HistoriqueCompteSpecial',
            'MouvementCredit',
            'Mouvement',
            'TypeCompte',
            'CaisseComptable',
            'CaisseJournaliere',
        ];

        // Pages
        $pages = [
            'Dashboard',
            'EditProfile',
            'PermissionManager',
            'HeaderThemeSwitcher',
            'StatsOverview',
        ];

        // Types de permissions pour les ressources
        $resourcePermissions = [
            'view_any',
            'view',
            'create',
            'update',
            'delete',
            'delete_any',
            'force_delete',
            'force_delete_any',
            'restore',
            'restore_any',
            'replicate',
            'reorder',
        ];

        $permissionsCreated = 0;

        // Créer les permissions pour les ressources
        foreach ($resources as $resource) {
            foreach ($resourcePermissions as $permission) {
                Permission::create([
                    'name' => $permission . '_' . strtolower($resource),
                    'guard_name' => 'filament'
                ]);
                $permissionsCreated++;
            }
        }

        // Créer les permissions pour les pages
        foreach ($pages as $page) {
            Permission::create([
                'name' => 'view_' . strtolower($page),
                'guard_name' => 'filament'
            ]);
            $permissionsCreated++;
        }

        $this->command->info("✅ Créées {$permissionsCreated} permissions manuellement.");

        // Assigner toutes les permissions au super_admin
        $superAdminRole = Role::where('name', 'super_admin')->where('guard_name', 'filament')->first();
        
        if ($superAdminRole) {
            $allPermissions = Permission::where('guard_name', 'filament')->get();
            $superAdminRole->syncPermissions($allPermissions);
            $this->command->info("✅ Assignées {$allPermissions->count()} permissions au rôle super_admin.");
        } else {
            $this->command->error("❌ Rôle super_admin non trouvé!");
        }

        // Vérifier l'utilisateur super admin
        $user = \App\Models\User::where('email', 'admintumainiletu@gmail.com')->first();
        if ($user) {
            $user->syncRoles([$superAdminRole]);
            $this->command->info("✅ Rôle super_admin réassigné à l'utilisateur.");
        }
    }
}