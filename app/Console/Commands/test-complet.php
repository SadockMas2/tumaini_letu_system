<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "<h1>Test Complet Crédit Groupe</h1>";

use App\Models\CreditGroupe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

try {
    $creditGroupeId = 1;
    $credit = CreditGroupe::find($creditGroupeId);
    
    if (!$credit) {
        throw new Exception("Crédit groupe non trouvé");
    }

    echo "<h2>1. Simulation d'approbation</h2>";
    
    // Données de test
    $montantsMembres = [
        2 => 200, // Louise Martin
        3 => 300  // KWABO Alain
    ];
    
    $montantTotalGroupe = 500;
    
    echo "<p>Montant total groupe: {$montantTotalGroupe}</p>";
    echo "<p>Répartition membres: " . json_encode($montantsMembres) . "</p>";
    
    // Début transaction
    DB::beginTransaction();
    
    try {
        // Mise à jour du crédit groupe
        $credit->update([
            'montant_accorde' => $montantTotalGroupe,
            'montant_total' => $montantTotalGroupe * 1.225,
            'frais_dossier' => 20,
            'frais_alerte' => 4.5,
            'frais_carnet' => 2.5,
            'frais_adhesion' => 1,
            'caution_totale' => 100,
            'remboursement_hebdo_total' => ($montantTotalGroupe * 1.225) / 16,
            'repartition_membres' => $montantsMembres,
            'montants_membres' => $montantsMembres,
            'statut_demande' => 'approuve',
            'date_octroi' => now(),
            'date_echeance' => now()->addMonths(4),
        ]);
        
        echo "<p style='color: green;'>✅ Crédit groupe mis à jour</p>";
        
        // Création des crédits individuels
        $credit->creerCreditsIndividuels();
        echo "<p style='color: green;'>✅ Crédits individuels créés</p>";
        
        // Annulation pour test
        DB::rollBack();
        echo "<p style='color: orange;'>🔄 Transaction annulée (test seulement)</p>";
        
        echo "<h2 style='color: green;'>🎉 TEST RÉUSSI !</h2>";
        
    } catch (Exception $e) {
        DB::rollBack();
        echo "<p style='color: red;'>❌ Erreur: " . $e->getMessage() . "</p>";
        echo "<pre>Trace: " . $e->getTraceAsString() . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur générale: " . $e->getMessage() . "</p>";
}

// Vérification des logs
echo "<h2>Derniers logs</h2>";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logs = file($logFile);
    $recentLogs = array_slice($logs, -20); // 20 dernières lignes
    echo "<pre>" . implode("", $recentLogs) . "</pre>";
}
?>