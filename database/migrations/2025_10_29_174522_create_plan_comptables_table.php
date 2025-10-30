<?php
// database/migrations/2024_01_01_000004_create_plan_comptables_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreatePlanComptablesTable extends Migration
{
    public function up()
    {
        Schema::create('plan_comptables', function (Blueprint $table) {
            $table->id();
            $table->string('numero_compte', 10)->unique();
            $table->string('libelle');
            $table->string('classe', 1);
            $table->enum('type_compte', ['actif', 'passif', 'charge', 'produit', 'capitaux']);
            $table->string('sous_type')->nullable();
            $table->boolean('compte_de_tiers')->default(false);
            $table->enum('statut', ['actif', 'inactif'])->default('actif');
            $table->timestamps();
        });

        // Insérer le plan comptable OHADA de base
        $this->insererPlanComptableBase();
    }

    private function insererPlanComptableBase()
    {
        $comptes = [
            // Classe 1: Capitaux
            ['101100', 'Capital social', '1', 'capitaux', 'capital'],
            ['106100', 'Réserves', '1', 'capitaux', 'reserves'],
            ['120000', 'Résultat de l\'exercice', '1', 'capitaux', 'resultat'],
            
            // Classe 5: Financiers
            ['521100', 'Banque', '5', 'actif', 'banque'],
            ['571100', 'Caisse principale', '5', 'actif', 'caisse'],
            ['571200', 'Coffre fort', '5', 'actif', 'coffre'],
            ['571300', 'Petite caisse', '5', 'actif', 'petite_caisse'],
            ['571400', 'Caisse opérations', '5', 'actif', 'caisse_operations'],
            
            // Classe 6: Charges
            ['601100', 'Achats fournitures bureau', '6', 'charge', 'achats'],
            ['611100', 'Salaires et appointements', '6', 'charge', 'personnel'],
            ['621100', 'Loyers et charges locatives', '6', 'charge', 'loyers'],
            ['631100', 'Intérêts et charges assimilées', '6', 'charge', 'interets'],
            ['658100', 'Charges diverses d\'exploitation', '6', 'charge', 'divers'],
            
            // Classe 7: Produits
            ['701100', 'Ventes de services financiers', '7', 'produit', 'ventes_services'],
            ['706100', 'Intérêts et produits assimilés', '7', 'produit', 'interets'],
            ['708100', 'Produits divers d\'exploitation', '7', 'produit', 'divers'],
        ];

        foreach ($comptes as $compte) {
            DB::table('plan_comptables')->insert([
                'numero_compte' => $compte[0],
                'libelle' => $compte[1],
                'classe' => $compte[2],
                'type_compte' => $compte[3],
                'sous_type' => $compte[4],
                'compte_de_tiers' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('plan_comptables');
    }
}