<?php
// database/migrations/2024_01_01_000005_create_journal_comptables_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;  

class CreateJournalComptablesTable extends Migration
{
    public function up()
    {
        Schema::create('journal_comptables', function (Blueprint $table) {
            $table->id();
            $table->string('code_journal')->unique();
            $table->string('libelle_journal');
            $table->enum('type_journal', ['banque', 'caisse', 'achats', 'ventes', 'general', 'operations']);
            $table->foreignId('agence_id')->constrained()->onDelete('cascade');
            $table->foreignId('responsable_id')->constrained('users')->onDelete('cascade');
            $table->date('date_ouverture')->nullable();
            $table->date('date_fermeture')->nullable();
            $table->decimal('solde_initial', 15, 2)->default(0);
            $table->decimal('solde_final', 15, 2)->default(0);
            $table->enum('statut', ['ouvert', 'ferme'])->default('ouvert');
            $table->timestamps();
        });

        // Insérer les journaux par défaut
        $agenceId = DB::table('agences')->where('code_agence', 'AG001')->value('id');
        $userId = DB::table('users')->first()->id;

        $journaux = [
            ['BQ', 'Journal de Banque', 'banque'],
            ['CA', 'Journal de Caisse', 'caisse'],
            ['AC', 'Journal d\'Achats', 'achats'],
            ['VT', 'Journal de Ventes', 'ventes'],
            ['OD', 'Journal des Opérations Diverses', 'general'],
            ['OP', 'Journal des Opérations', 'operations'],
        ];

        foreach ($journaux as $journal) {
            DB::table('journal_comptables')->insert([
                'code_journal' => $journal[0],
                'libelle_journal' => $journal[1],
                'type_journal' => $journal[2],
                'agence_id' => $agenceId,
                'responsable_id' => $userId,
                'date_ouverture' => now(),
                'statut' => 'ouvert',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('journal_comptables');
    }
}