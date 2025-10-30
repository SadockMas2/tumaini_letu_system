<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionsToSuperAdminSeeder extends Seeder
{
    public function run()
    {
        $superAdminRole = Role::where('name', 'super_admin')->where('guard_name', 'filament')->first();

        if (!$superAdminRole) {
            $this->command->error('Rôle super_admin non trouvé!');
            return;
        }

        $permissions = Permission::where('guard_name', 'filament')->get();

        $superAdminRole->syncPermissions($permissions);

        $this->command->info("Assignées {$permissions->count()} permissions au rôle super_admin.");
    }
}