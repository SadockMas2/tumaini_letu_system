<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
          
            
            // Ajouter le champ notifications SMS s'il n'existe pas
            if (!Schema::hasColumn('clients', 'sms_notifications')) {
                $table->boolean('sms_notifications')->default(true)->after('telephone');
            }
            
          
        });
    }

    public function down(): void
    {
        // Ne pas supprimer les colonnes pour éviter la perte de données
    }
};