<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mouvements', function (Blueprint $table) {
            // Ajouter operateur_id (nullable)
            if (!Schema::hasColumn('mouvements', 'operateur_id')) {
                $table->unsignedBigInteger('operateur_id')->nullable()->after('description');
            }
            
            // Ajouter date_mouvement (nullable)
            if (!Schema::hasColumn('mouvements', 'date_mouvement')) {
                $table->timestamp('date_mouvement')->nullable()->after('operateur_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mouvements', function (Blueprint $table) {
            $table->dropColumn(['operateur_id', 'date_mouvement']);
        });
    }
};