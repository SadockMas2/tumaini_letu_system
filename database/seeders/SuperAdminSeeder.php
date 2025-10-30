<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Configuration du Super Admin...');

        // Vérifie si un utilisateur Admin existe déjà
        $user = User::where('email', 'admintumainiletu@gmail.com')->first();
        
        if (!$user) {
            $user = User::create([
                'name' => 'Super Admin',
                'email' => 'admintumainiletu@gmail.com',
                'password' => bcrypt('Admin123!'),
            ]);
            $this->command->info('✅ Super Admin créé.');
        } else {
            $this->command->info('✅ Super Admin existe déjà.');
        }

        // Assigner le rôle super_admin
        $superAdminRole = Role::where('name', 'super_admin')->where('guard_name', 'filament')->first();
        
        if ($superAdminRole) {
            $user->syncRoles([$superAdminRole]);
            
            // Vérifier et assigner les permissions
            $allPermissions = \Spatie\Permission\Models\Permission::where('guard_name', 'filament')->get();
            
            if ($allPermissions->count() > 0) {
                $superAdminRole->syncPermissions($allPermissions);
                $this->command->info("✅ Rôle super_admin assigné avec " . $allPermissions->count() . " permissions.");
            } else {
                $this->command->warn('⚠️  Aucune permission trouvée. Exécutez d\'abord: php artisan shield:generate --all');
                $this->command->info('✅ Rôle super_admin assigné (sans permissions pour le moment).');
            }
        } else {
            $this->command->error('❌ Le rôle super_admin n\'existe pas! Exécutez d\'abord: php artisan db:seed --class=RolesAndPermissionsSeeder');
        }

        $this->command->info('');
        $this->command->info('🎉 Testez maintenant l\'accès à: http://localhost/admin/shield/roles');
    }
}