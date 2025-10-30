<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeComptesSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['designation' => 'Compte épargne collecteur', 'description' => 'Compte destiné à la collecte d’épargne des membres.'],
            ['designation' => 'Compte solidarité', 'description' => 'Compte destiné aux groupes solidaires.'],
            ['designation' => 'Compte courant', 'description' => 'Compte pour opérations courantes du membre.'],
        ];

        foreach ($types as $type) {
            DB::table('type_comptes')->updateOrInsert(
                ['designation' => $type['designation']],
                ['description' => $type['description']]
            );
        }
    }
}
