<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionsSetupSeeder extends Seeder
{
    public function run()
    {
        // Créer quelques permissions de base
        $permissions = [
            'view_users',
            'create_users', 
            'edit_users',
            'delete_users',
            'view_roles',
            'create_roles',
            'edit_roles',
            'delete_roles',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'filament'
            ]);
        }

        // Créer le rôle Super Admin
        $superAdminRole = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'filament'
        ]);

        // Donner toutes les permissions au Super Admin
        $superAdminRole->syncPermissions(Permission::all());

        // Créer/Chercher l'utilisateur Super Admin
        $user = User::firstOrCreate(
            ['email' => 'admintumainiletu@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('Admin123!'),
            ]
        );

        // Assigner le rôle
        $user->assignRole($superAdminRole);

        $this->command->info('✅ Configuration des permissions terminée !');
        $this->command->info('👤 Super Admin: admintumainiletu@gmail.com / Admin123!');
    }
}