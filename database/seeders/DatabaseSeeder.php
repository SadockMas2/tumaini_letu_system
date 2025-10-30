<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            TypeComptesSeeder::class,
            // RolesAndPermissionsSeeder::class,
              
            PermissionsSetupSeeder::class, 
            RolesAndPermissionsSeeder::class, 
            SuperAdminSeeder::class,// D'abord créer les rôles
            // NOTE: NE PAS appeler SuperAdminSeeder ici
            // Il doit être appelé APRÈS shield:generate --all
        ]);

        $this->command->info('');
        $this->command->info('🚀 ÉTAPES SUIVANTES:');
        $this->command->info('1. Exécutez: php artisan shield:generate --all');
        $this->command->info('2. Exécutez: php artisan db:seed --class=SuperAdminSeeder');
        $this->command->info('3. Testez: http://localhost/admin/shield/roles');
        $this->command->info('');
    }
}