<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            // Ajouter toutes les colonnes manquantes
            if (!Schema::hasColumn('clients', 'image')) {
                $table->string('image')->nullable()->after('email');
            }
            
            if (!Schema::hasColumn('clients', 'activites')) {
                $table->string('activites')->nullable()->after('telephone');
            }
            
            if (!Schema::hasColumn('clients', 'signature')) {
                $table->string('signature')->nullable()->after('type_client');
            }
            
            if (!Schema::hasColumn('clients', 'etat_civil')) {
                $table->string('etat_civil')->nullable()->after('activites');
            }
            
            if (!Schema::hasColumn('clients', 'adresse')) {
                $table->text('adresse')->nullable()->after('etat_civil');
            }
            
            if (!Schema::hasColumn('clients', 'ville')) {
                $table->string('ville')->nullable()->after('adresse');
            }
            
            if (!Schema::hasColumn('clients', 'pays')) {
                $table->string('pays')->nullable()->after('ville');
            }
            
            if (!Schema::hasColumn('clients', 'code_postal')) {
                $table->string('code_postal')->nullable()->after('pays');
            }
            
            if (!Schema::hasColumn('clients', 'type_compte')) {
                $table->string('type_compte')->nullable()->after('code_postal');
            }
            
            if (!Schema::hasColumn('clients', 'type_client')) {
                $table->string('type_client')->nullable()->after('type_compte');
            }
            
            if (!Schema::hasColumn('clients', 'identifiant_national')) {
                $table->string('identifiant_national')->nullable()->after('signature');
            }
            
            if (!Schema::hasColumn('clients', 'id_createur')) {
                $table->foreignId('id_createur')->nullable()->after('identifiant_national');
            }
            
            if (!Schema::hasColumn('clients', 'status')) {
                $table->string('status')->default('actif')->after('id_createur');
            }
        });
    }

    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'image',
                'activites', 
                'signature',
                'etat_civil',
                'adresse',
                'ville', 
                'pays',
                'code_postal',
                'type_compte',
                'type_client',
                'identifiant_national',
                'id_createur',
                'status'
            ]);
        });
    }
};