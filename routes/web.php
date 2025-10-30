<?php

use App\Http\Controllers\CompteController;
use App\Http\Controllers\CreditController;
use App\Http\Controllers\MouvementController;
use App\Http\Controllers\PaiementController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Models\Mouvement;

Route::get('/', function () {
    return view('welcome');
});

// Routes pour les crédits
Route::prefix('credits')->group(function () {
    // Crédits individuels
    Route::get('/create/{compte_id}', [CreditController::class, 'create'])->name('credits.create');
    Route::post('/store', [CreditController::class, 'store'])->name('credits.store');
    Route::get('/approval/{credit_id}', [CreditController::class, 'showApproval'])->name('credits.approval');
    Route::post('/approval/{credit_id}', [CreditController::class, 'processApproval'])->name('credits.process-approval');
    Route::get('/payment/{compte_id}', [CreditController::class, 'showPayment'])->name('credits.payment');
    Route::post('/payment/{credit_id}', [CreditController::class, 'processPayment'])->name('credits.process-payment');
    
    // Crédits groupe
    Route::get('/approval-groupe/{credit_groupe_id}', [CreditController::class, 'showApprovalGroupe'])->name('credits.approval-groupe');
    Route::post('/approval-groupe/{credit_groupe_id}', [CreditController::class, 'processApprovalGroupe'])->name('credits.process-approval-groupe');
});

// Routes pour les comptes
Route::get('comptes/{compte_id}/details', [CompteController::class, 'details'])->name('comptes.details');
Route::get('comptes', [CompteController::class, 'index'])->name('comptes.index');

// Routes pour les mouvements
Route::get('/mouvement/{mouvement}/bordereau', function (Mouvement $mouvement) {
    return view('bordereau-mouvement', compact('mouvement'));
})->name('mouvement.bordereau');

// Routes pour les paiements
Route::prefix('paiements')->group(function () {
    // Bordereau de paiement
    Route::get('/{paiement}/bordereau', [PaiementController::class, 'bordereau'])->name('paiement.bordereau');
    
    // Historique des paiements pour un crédit
    Route::get('/credit/{credit}/historique', [PaiementController::class, 'historiqueCredit'])->name('paiements.credit.historique');
    
    // Génération PDF
    Route::get('/{paiement}/bordereau/pdf', [PaiementController::class, 'generateBordereauPDF'])->name('paiement.bordereau.pdf');
});

// Route de debug
Route::get('/debug-credit-groupe/{id}', function($id) {
    $credit = App\Models\CreditGroupe::with(['compte', 'groupeCompte.membres.compte'])->find($id);
    
    if (!$credit) {
        return "Credit groupe non trouvé";
    }
    
    dd([
        'credit' => $credit->toArray(),
        'compte' => $credit->compte ? $credit->compte->toArray() : null,
        'groupe_compte' => $credit->groupeCompte ? $credit->groupeCompte->toArray() : null,
        'membres' => $credit->groupeCompte ? $credit->groupeCompte->membres->toArray() : null
    ]);
});

// Route alternative pour crédit groupe
Route::get('/credits/approval-groupe-final/{credit_groupe_id}', function($credit_groupe_id) {
    try {
        $credit = App\Models\CreditGroupe::with('compte')->findOrFail($credit_groupe_id);
        $compte = $credit->compte;
        
        // Récupérer les membres via la table groupes_membres
        $membres = DB::table('groupes_membres')
            ->join('clients', 'groupes_membres.client_id', '=', 'clients.id')
            ->join('comptes', 'clients.id', '=', 'comptes.client_id')
            ->where('groupes_membres.groupe_solidaire_id', $compte->groupe_solidaire_id)
            ->select(
                'clients.id',
                'clients.nom',
                'clients.prenom',
                'comptes.numero_compte',
                'comptes.solde',
                'comptes.devise'
            )
            ->get();
        
        return view('credits.approval-groupe-final', [
            'credit' => $credit,
            'membres' => $membres,
            'compte' => $compte
        ]);
        
    } catch (\Exception $e) {
        return "Erreur: " . $e->getMessage() . 
               "<br><br>Détails: " . json_encode([
                   'credit_id' => $credit_groupe_id,
                   'compte' => isset($compte) ? $compte->toArray() : null,
                   'error_trace' => $e->getTraceAsString()
               ]);
    }
})->name('credits.approval-groupe-final');

// Route de test pour crédit groupe
Route::get('/test-credit-groupe/{id}', function($id) {
    echo "<h1>Test Crédit Groupe ID: $id</h1>";
    
    try {
        $credit = App\Models\CreditGroupe::find($id);
        
        if (!$credit) {
            throw new Exception("Crédit groupe non trouvé");
        }

        echo "<h2>1. Informations du crédit</h2>";
        echo "<pre>" . print_r($credit->toArray(), true) . "</pre>";

        echo "<h2>2. Informations du compte</h2>";
        $compte = $credit->compte;
        if ($compte) {
            echo "<pre>" . print_r($compte->toArray(), true) . "</pre>";
        } else {
            echo "❌ Compte non trouvé";
        }

        echo "<h2>3. Liste des membres</h2>";
        $membres = $credit->membres;
        echo "<pre>" . print_r($membres->toArray(), true) . "</pre>";

        echo "<h2>4. Test de répartition</h2>";
        $montantsTest = [];
        foreach ($membres as $membre) {
            $montantsTest[$membre->id] = 100;
        }
        
        $repartition = App\Models\CreditGroupe::calculerRepartitionAvecMontants($montantsTest, 300);
        echo "<pre>" . print_r($repartition, true) . "</pre>";

        echo "<h2>5. Test création crédits</h2>";
        try {
            DB::beginTransaction();
            
            $credit->update(['montants_membres' => $montantsTest]);
            $credit->creerCreditsIndividuels();
            
            DB::rollBack();
            echo "✅ Test réussi - Transaction annulée";
            
        } catch (Exception $e) {
            DB::rollBack();
            echo "❌ Erreur: " . $e->getMessage() . "<br>";
            echo "Fichier: " . $e->getFile() . " Ligne: " . $e->getLine() . "<br>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }

    } catch (Exception $e) {
        echo "❌ Erreur générale: " . $e->getMessage() . "<br>";
        echo "Fichier: " . $e->getFile() . " Ligne: " . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
});

// Dans web.php
Route::get('/credits/groupe/{id}/details', [CreditController::class, 'showDetailsGroupe'])->name('credits.details-groupe');
Route::get('/credits/groupe/{id}/echeanciers', [CreditController::class, 'showEcheanciersGroupe'])->name('credits.echeanciers-groupe');
Route::get('/credits/groupe/{id}/echeancier-membre/{membre_id}', [CreditController::class, 'showEcheancierMembre'])->name('credits.echeancier-membre');

// Routes pour les détails du crédit groupe
Route::get('/credits/groupe/{id}/details', [CreditController::class, 'showDetailsGroupe'])->name('credits.details-groupe');
Route::get('/credits/groupe/{id}/echeanciers', [CreditController::class, 'showEcheanciersGroupe'])->name('credits.echeanciers-groupe');
Route::get('/credits/groupe/{id}/echeancier-membre/{membre_id}', [CreditController::class, 'showEcheancierMembre'])->name('credits.echeancier-membre');

// Routes pour les paiements
Route::get('/paiement/{paiement}/bordereau', [CreditController::class, 'generateBordereauPDF'])->name('paiement.bordereau');

// Routes pour l'historique des paiements
Route::get('/credits/{credit}/historique-paiements', [CreditController::class, 'showHistoriquePaiements'])->name('credits.historique-paiements');

Route::get('/test-approval-groupe/{id}', [App\Http\Controllers\CreditController::class, 'testApprovalGroupe']);

Route::get('/credits/{credit_id}/echeancier', [CreditController::class, 'showEcheancier'])->name('credits.echeancier');