<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ManualRolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Désactiver la garde web si nécessaire
        config(['permission.default_guard' => 'filament']);

        // 1. Nettoyer les rôles et permissions existants
        Permission::query()->delete();
        Role::query()->delete();

        // 2. Créer les rôles
        $superAdminRole = Role::create(['name' => 'super_admin', 'guard_name' => 'filament']);
        $roles = [
            'Admin',
            'MembresODP',
            'Caissiere', 
            'Comptable',
            'ChefBureau',
            'Financier',
            'AgentCollecteur',
            'ConseillerMembres',
            'ControleurAuditeur',
            'ConseilAdministration',
        ];

        foreach ($roles as $roleName) {
            Role::create(['name' => $roleName, 'guard_name' => 'filament']);
        }

        // 3. Créer les permissions

        // Ressources
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
            'Permission',
            'Role',
        ];

        $permissions = [
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

        foreach ($resources as $resource) {
            foreach ($permissions as $permission) {
                Permission::create([
                    'name' => $permission . '_' . strtolower($resource),
                    'guard_name' => 'filament'
                ]);
                $permissionsCreated++;
            }
        }

        // Pages
        $pages = [
            'Dashboard',
            'EditProfile',
            'PermissionManager',
            'HeaderThemeSwitcher',
            'StatsOverview',
        ];

        foreach ($pages as $page) {
            Permission::create([
                'name' => 'view_' . strtolower($page),
                'guard_name' => 'filament'
            ]);
            $permissionsCreated++;
        }

        // Widgets (si vous en avez)
        $widgets = [
            // Ajoutez vos widgets ici si nécessaire
        ];

        foreach ($widgets as $widget) {
            Permission::create([
                'name' => 'view_' . strtolower($widget),
                'guard_name' => 'filament'
            ]);
            $permissionsCreated++;
        }

        $this->command->info("Créées {$permissionsCreated} permissions.");

        // 4. Assigner toutes les permissions au super_admin
        $allPermissions = Permission::all();
        $superAdminRole->syncPermissions($allPermissions);
        $this->command->info("Assignées {$allPermissions->count()} permissions au rôle super_admin.");

        // 5. Assigner le rôle super_admin à l'utilisateur principal
        $user = \App\Models\User::where('email', 'admintumainiletu@gmail.com')->first();
        if ($user) {
            $user->assignRole($superAdminRole);
            $this->command->info('Rôle super_admin assigné à l\'utilisateur.');
        } else {
            $this->command->error('Utilisateur non trouvé.');
        }
    }
}