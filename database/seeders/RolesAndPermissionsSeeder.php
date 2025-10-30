<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('🚀 Démarrage de la configuration des rôles et permissions...');

        // =====================
        // 1. NETTOYAGE COMPLET
        // =====================
        $this->command->info('🔧 Nettoyage de la base...');
        
        DB::table('model_has_roles')->delete();
        DB::table('model_has_permissions')->delete();
        DB::table('role_has_permissions')->delete();
        
        Permission::query()->delete();
        Role::query()->delete();

        // =====================
        // 2. CRÉATION DES PERMISSIONS (EN MINUSCULE)
        // =====================
        $this->command->info('🔑 Création des permissions...');

        // Liste de toutes vos ressources EN MINUSCULE
        $resources = [
            'user', 'client', 'membre', 'compte', 'transaction', 'depot', 'retrait',
            'credit', 'employe', 'salaire', 'coffre', 'operationlogistique', 'operationcoffre',
            'autorisation', 'rapportcaisse', 'rapportcoffre', 'rapportcollecte', 'rapportmembres',
            'rapportgeneral', 'comptebancaire', 'comptespecial', 'comptetransitoire', 'cycle',
            'dispatch', 'epargne', 'groupesolidaire', 'historiquecomptespecial', 'mouvementcredit',
            'mouvement', 'typecompte', 'caissecomptable', 'caissejournaliere'
        ];

        // Actions de base pour chaque ressource
        $actions = ['view', 'create', 'edit', 'delete'];

        $permissionsCount = 0;

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                Permission::create([
                    'name' => "{$action}_{$resource}",
                    'guard_name' => 'filament'
                ]);
                $permissionsCount++;
            }
        }

        // Permissions spéciales pour les pages
        $pagePermissions = ['view_dashboard', 'view_profile', 'manage_roles'];
        foreach ($pagePermissions as $permission) {
            Permission::create([
                'name' => $permission,
                'guard_name' => 'filament'
            ]);
            $permissionsCount++;
        }

        $this->command->info("✅ {$permissionsCount} permissions créées");

        // =====================
        // 3. CRÉATION DES RÔLES
        // =====================
        $this->command->info('👥 Création des rôles...');

        // Rôle Super Admin (a toutes les permissions)
        $superAdminRole = Role::create([
            'name' => 'super_admin',
            'guard_name' => 'filament'
        ]);

        // Rôles métier
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
            Role::create([
                'name' => $roleName,
                'guard_name' => 'filament'
            ]);
            $this->command->info("✅ Rôle {$roleName} créé");
        }

        // =====================
        // 4. ASSIGNATION DES PERMISSIONS AU SUPER ADMIN
        // =====================
        $this->command->info('👑 Configuration du Super Admin...');

        $allPermissions = Permission::all();
        $superAdminRole->syncPermissions($allPermissions);
        $this->command->info("✅ {$allPermissions->count()} permissions assignées au Super Admin");

        // =====================
        // 5. CRÉATION/ASSIGNATION DE L'UTILISATEUR
        // =====================
        $this->command->info('👤 Configuration de l\'utilisateur Super Admin...');

        $user = User::where('email', 'admintumainiletu@gmail.com')->first();
        
        if (!$user) {
            $user = User::create([
                'name' => 'Super Admin',
                'email' => 'admintumainiletu@gmail.com',
                'password' => bcrypt('Admin123!'),
            ]);
            $this->command->info('✅ Utilisateur Super Admin créé');
        } else {
            $this->command->info('✅ Utilisateur Super Admin existe déjà');
        }

        $user->syncRoles([$superAdminRole]);
        $this->command->info('✅ Rôle super_admin assigné à l\'utilisateur');

        // =====================
        // 6. VÉRIFICATIONS FINALES
        // =====================
        $this->command->info("\n🎯 VÉRIFICATIONS FINALES:");
        $this->command->info("=========================");
        
        $user->refresh();
        $user->load('roles.permissions');

        $this->command->info("Rôles totaux: " . Role::count());
        $this->command->info("Permissions totales: " . Permission::count());
        $this->command->info("Utilisateur: " . $user->email);
        $this->command->info("Rôle utilisateur: " . $user->getRoleNames()->implode(', '));
        $this->command->info("Permissions utilisateur: " . $user->getAllPermissions()->count());

        // Tests de permissions critiques (EN MINUSCULE)
        $this->command->info("\n🔍 TESTS DE PERMISSIONS:");
        $tests = [
            'view_user' => 'Voir les utilisateurs',
            'create_user' => 'Créer des utilisateurs',
            'edit_user' => 'Modifier des utilisateurs',
            'delete_user' => 'Supprimer des utilisateurs',
            'view_dashboard' => 'Accéder au dashboard',
        ];

        foreach ($tests as $permission => $description) {
            $result = $user->can($permission) ? '✅' : '❌';
            $status = $user->can($permission) ? 'AUTORISÉ' : 'REFUSÉ';
            $this->command->info("   {$result} {$description}: {$status}");
        }

        $this->command->info("\n🎉 CONFIGURATION TERMINÉE!");
    }
}