<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('credits', function (Blueprint $table) {
            if (!Schema::hasColumn('credits', 'statut_demande')) {
                $table->enum('statut_demande', ['en_attente', 'approuve', 'rejete', 'annule'])->default('en_attente')->after('statut');
            }
            if (!Schema::hasColumn('credits', 'date_demande')) {
                $table->timestamp('date_demande')->nullable()->after('date_echeance');
            }
            if (!Schema::hasColumn('credits', 'date_approbation')) {
                $table->timestamp('date_approbation')->nullable()->after('date_demande');
            }
            if (!Schema::hasColumn('credits', 'motif_rejet')) {
                $table->text('motif_rejet')->nullable()->after('date_approbation');
            }
        });
    }

    public function down()
    {
        Schema::table('credits', function (Blueprint $table) {
            $table->dropColumn(['statut_demande', 'date_demande', 'date_approbation', 'motif_rejet']);
        });
    }
};