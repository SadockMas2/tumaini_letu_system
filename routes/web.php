<?php

use App\Filament\Resources\Clients\Pages\GalerieClients;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\CreditController;
use App\Http\Controllers\DashboardCreditController;
use App\Http\Controllers\EtatTresorerieController;
use App\Http\Controllers\GalerieClientsController;
use App\Http\Controllers\MouvementController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\PaiementGroupeController;
use App\Http\Controllers\RapportRemboursementController;
use App\Http\Controllers\RapportTresorerieController;
use App\Http\Controllers\CompteEpargneController;
use App\Models\Client;
use App\Models\CompteTransitoire;
use App\Services\CycleService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request\Http;
use Illuminate\Support\Facades\DB;
use App\Services\SmsService;
use App\Models\Mouvement;
use App\Filament\Resources\Tresoreries\Pages\ManageTresorerie;

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

// // Route de test pour crédit groupe
// Route::get('/test-credit-groupe/{id}', function($id) {
//     echo "<h1>Test Crédit Groupe ID: $id</h1>";
    
//     try {
//         $credit = App\Models\CreditGroupe::find($id);
        
//         if (!$credit) {
//             throw new Exception("Crédit groupe non trouvé");
//         }

//         echo "<h2>1. Informations du crédit</h2>";
//         echo "<pre>" . print_r($credit->toArray(), true) . "</pre>";

//         echo "<h2>2. Informations du compte</h2>";
//         $compte = $credit->compte;
//         if ($compte) {
//             echo "<pre>" . print_r($compte->toArray(), true) . "</pre>";
//         } else {
//             echo "❌ Compte non trouvé";
//         }

//         echo "<h2>3. Liste des membres</h2>";
//         $membres = $credit->membres;
//         echo "<pre>" . print_r($membres->toArray(), true) . "</pre>";

//         echo "<h2>4. Test de répartition</h2>";
//         $montantsTest = [];
//         foreach ($membres as $membre) {
//             $montantsTest[$membre->id] = 100;
//         }
        
//         $repartition = App\Models\CreditGroupe::calculerRepartitionAvecMontants($montantsTest, 300);
//         echo "<pre>" . print_r($repartition, true) . "</pre>";

//         echo "<h2>5. Test création crédits</h2>";
//         try {
//             DB::beginTransaction();
            
//             $credit->update(['montants_membres' => $montantsTest]);
//             $credit->creerCreditsIndividuels();
            
//             DB::rollBack();
//             echo "✅ Test réussi - Transaction annulée";
            
//         } catch (Exception $e) {
//             DB::rollBack();
//             echo "❌ Erreur: " . $e->getMessage() . "<br>";
//             echo "Fichier: " . $e->getFile() . " Ligne: " . $e->getLine() . "<br>";
//             echo "<pre>" . $e->getTraceAsString() . "</pre>";
//         }

//     } catch (Exception $e) {
//         echo "❌ Erreur générale: " . $e->getMessage() . "<br>";
//         echo "Fichier: " . $e->getFile() . " Ligne: " . $e->getLine() . "<br>";
//         echo "<pre>" . $e->getTraceAsString() . "</pre>";
//     }
// });

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

Route::get('/test-approval-groupe/{id}', [CreditController::class, 'testApprovalGroupe']);

Route::get('/credits/{credit_id}/echeancier', [CreditController::class, 'showEcheancier'])->name('credits.echeancier');


// // Dans routes/web.php
// Route::get('/test-debit-direct/{userId}/{devise}/{montant}', function ($userId, $devise, $montant) {
//     try {
//         Log::info('=== TEST DÉBIT DIRECT ===');
        
//         // 1. Vérifier le compte transitoire
//         $compte = CompteTransitoire::where('user_id', $userId)
//             ->where('devise', $devise)
//             ->first();
            
//         if (!$compte) {
//             return response()->json(['error' => 'Compte transitoire introuvable'], 404);
//         }
        
//         Log::info('Compte trouvé', [
//             'compte_id' => $compte->id,
//             'solde_avant' => $compte->solde,
//             'user_id' => $compte->user_id,
//             'devise' => $compte->devise
//         ]);
        
//         // 2. Tester le débit directement
//         $ancienSolde = $compte->solde;
//         $montant = (float)$montant;
        
//         Log::info('Tentative de débit', [
//             'ancien_solde' => $ancienSolde,
//             'montant' => $montant
//         ]);
        
//         // Méthode 1: Utiliser la méthode debit()
//         $resultat = $compte->debit($montant);
        
//         Log::info('Résultat méthode debit()', ['resultat' => $resultat]);
        
//         // Recharger le compte
//         $compte->refresh();
        
//         Log::info('Après débit', [
//             'nouveau_solde' => $compte->solde,
//             'difference' => $ancienSolde - $compte->solde
//         ]);
        
//         // Méthode 2: Débit manuel
//         $compte2 = CompteTransitoire::find($compte->id);
//         $compte2->solde = $compte2->solde - $montant;
//         $resultat2 = $compte2->save();
        
//         Log::info('Résultat débit manuel', [
//             'resultat_save' => $resultat2,
//             'solde_apres_manuel' => $compte2->solde
//         ]);
        
//         return response()->json([
//             'success' => true,
//             'compte_id' => $compte->id,
//             'debit_method_result' => $resultat,
//             'manual_debit_result' => $resultat2,
//             'solde_avant' => $ancienSolde,
//             'solde_apres' => $compte->solde,
//             'solde_apres_manuel' => $compte2->solde,
//             'devise' => $devise
//         ]);
        
//     } catch (\Exception $e) {
//         Log::error('Erreur test débit direct', [
//             'error' => $e->getMessage(),
//             'trace' => $e->getTraceAsString()
//         ]);
        
//         return response()->json([
//             'error' => $e->getMessage(),
//             'trace' => $e->getTraceAsString()
//         ], 500);
//     }
// });

Route::get('/comptes/{compte_id}/export-releve', [CompteController::class, 'exportReleve'])
    ->name('comptes.export-releve');

Route::prefix('etats-tresorerie')->group(function () {
    Route::get('/temps-reel', [EtatTresorerieController::class, 'etatTresorerieTempsReel']);
    Route::get('/etat-sortie', [EtatTresorerieController::class, 'etatSortieTempsReel']);
    Route::get('/grandes-caisses-comptabilite', [EtatTresorerieController::class, 'etatGrandesCaissesComptabilite']);
    Route::get('/export-pdf', [EtatTresorerieController::class, 'exportPdfEtatSortie']);
});



Route::prefix('rapports-tresorerie')->group(function () {
    Route::get('/pdf', [RapportTresorerieController::class, 'genererRapportPDF']);
    Route::get('/apercu-pdf', [RapportTresorerieController::class, 'apercuRapportPDF']);
    Route::get('/synthese', [RapportTresorerieController::class, 'rapportSynthese']);
    Route::get('/donnees', [RapportTresorerieController::class, 'donneesRapport']);
});


// Routes pour les comptes épargne
Route::get('comptes-epargne/{compte_epargne_id}/details', [CompteEpargneController::class, 'details'])->name('comptes-epargne.details');
Route::get('comptes-epargne/{compte_epargne_id}/mouvements', [CompteEpargneController::class, 'mouvements'])->name('comptes-epargne.mouvements');
Route::get('comptes-epargne/{compte_epargne_id}/export-releve', [CompteEpargneController::class, 'exportReleve'])->name('comptes-epargne.export-releve');


Route::prefix('credits')->group(function () {
    Route::get('/tableau-de-bord', [DashboardCreditController::class, 'tableauDeBordComplet'])
        ->name('credits.dashboard');
    
    Route::get('/details/{id}/{type}', [DashboardCreditController::class, 'detailsCredit'])
        ->name('credits.details');
});


Route::get('/galerie-clients', [GalerieClientsController::class, 'index'])
    ->name('galerie.clients')
    ->middleware(['auth']);

Route::get('/galerie-clients/{id}', [GalerieClientsController::class, 'show'])
    ->name('galerie.clients.show')
    ->middleware(['auth']);

    // Route pour servir les images des clients
Route::get('/client-image/{filename}', function ($filename) {
    $path = storage_path('app/public/clients/' . $filename);
    
    if (!file_exists($path)) {
        abort(404);
    }

    $file = file_get_contents($path);
    $type = mime_content_type($path);

    return response($file, 200)->header('Content-Type', $type);
})->name('client.image')->middleware('auth');

Route::get('/paiement-credits-groupe', [PaiementGroupeController::class, 'index'])->name('paiement.credits.groupe');
Route::post('/paiement-credits-groupe/processer', [PaiementGroupeController::class, 'processerPaiements'])->name('paiement.credits.groupe.processer');





Route::prefix('sms')->group(function () {
    
    // Envoyer à tous les clients
    Route::get('/send-to-all', function () {
        try {
            $clients = Client::whereNotNull('telephone')
                ->where('sms_notifications', true)
                ->get();
            
            $smsService = app(SmsService::class);
            $results = [];
            
            foreach ($clients as $client) {
                $message = "Cher(e) {$client->nom_complet},\nNouvelles de TUMAINI LETU!";
                
                $result = $smsService->sendTransactionSMS(
                    $client->telephone,
                    $message,
                    'broadcast_' . time() . '_' . $client->id
                );
                
                $results[] = [
                    'client' => $client->nom_complet,
                    'phone' => $client->telephone,
                    'status' => $result['status'],
                    'message_id' => $result['message_id'] ?? null,
                ];
                
                // Pause pour éviter le spam
                sleep(1);
            }
            
            return response()->json([
                'total_sent' => count($results),
                'successful' => count(array_filter($results, fn($r) => $r['status'] === 'S')),
                'results' => $results,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur envoi masse SMS', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Envoyer à des numéros spécifiques
    Route::post('/send-custom', function () {
        request()->validate([
            'numbers' => 'required|string',
            'message' => 'required|string|max:160',
        ]);
        
        $numbers = array_filter(
            explode("\n", request('numbers')),
            fn($n) => !empty(trim($n))
        );
        
        $smsService = app(SmsService::class);
        $results = [];
        
        foreach ($numbers as $number) {
            $cleanNumber = preg_replace('/[^0-9]/', '', trim($number));
            
            if (!str_starts_with($cleanNumber, '243')) {
                $cleanNumber = '243' . ltrim($cleanNumber, '0');
            }
            
            $result = $smsService->sendTransactionSMS(
                $cleanNumber,
                request('message'),
                'custom_' . time() . '_' . substr(md5($cleanNumber), 0, 6)
            );
            
            $results[] = [
                'number' => $cleanNumber,
                'status' => $result['status'],
                'message' => $result['remarks'] ?? 'N/A',
            ];
            
            sleep(1);
        }
        
        return response()->json([
            'sent_to' => count($results),
            'results' => $results,
        ]);
    });
    
    // Vérifier/activer les SMS pour un client
    Route::post('/client/{client}/toggle-sms', function (Client $client) {
        $enabled = request('enabled', true);
        $client->update(['sms_notifications' => $enabled]);
        
        return response()->json([
            'success' => true,
            'message' => 'SMS ' . ($enabled ? 'activés' : 'désactivés') . ' pour ' . $client->nom_complet,
            'client' => $client->only(['id', 'nom_complet', 'telephone', 'sms_notifications']),
        ]);
    });
});




Route::get('/test-soldes', function() {
    $comptes = App\Models\CompteEpargne::take(95)->get();
    
    $resultats = [];
    
    foreach ($comptes as $compte) {
        // Calculer les épargnes
        $totalEpargnes = 0;
        if ($compte->type_compte === 'individuel' && $compte->client_id) {
            $totalEpargnes = App\Models\Epargne::where('client_id', $compte->client_id)
                ->where('statut', 'valide')
                ->where('devise', $compte->devise)
                ->sum('montant');
        }
        
        // Calculer les retraits
        $totalRetraits = App\Models\Mouvement::where('compte_epargne_id', $compte->id)
            ->where('type', 'retrait')
            ->sum('montant');
        
        $soldeCalcule = $totalEpargnes - $totalRetraits;
        
        $resultats[] = [
            'numero_compte' => $compte->numero_compte,
            'client' => $compte->nom_complet,
            'solde_actuel' => $compte->solde,
            'total_epargnes' => $totalEpargnes,
            'total_retraits' => $totalRetraits,
            'solde_calcule' => $soldeCalcule,
            'ecart' => $compte->solde - $soldeCalcule,
        ];
    }
    
    return response()->json($resultats);
});




Route::get('/debug-compte/{numero}', function($numero) {
    $compte = App\Models\CompteEpargne::where('numero_compte', $numero)->first();
    
    if (!$compte) {
        return "Compte non trouvé";
    }
    
    // 1. Les épargnes originales
    $epargnes = App\Models\Epargne::where('client_id', $compte->client_id)
        ->where('statut', 'valide')
        ->where('devise', $compte->devise)
        ->get(['id', 'montant', 'created_at', 'reference']);
    
    // 2. Les mouvements d'épargne (potentiels doublons)
    $mouvementsEpargne = App\Models\MouvementEpargne::where('compte_epargne_id', $compte->id)
        ->where('type', 'depot')
        ->get(['id', 'montant', 'epargne_id', 'created_at', 'reference']);
    
    // 3. Les retraits
    $retraits = App\Models\Mouvement::where('compte_epargne_id', $compte->id)
        ->where('type', 'retrait')
        ->get(['id', 'montant', 'created_at', 'reference']);
    
    $resultat = [
        'compte' => [
            'numero' => $compte->numero_compte,
            'client' => $compte->nom_complet,
            'solde_actuel' => $compte->solde,
        ],
        'epargnes' => [
            'total' => $epargnes->sum('montant'),
            'nombre' => $epargnes->count(),
            'liste' => $epargnes
        ],
        'mouvements_epargne' => [
            'total' => $mouvementsEpargne->sum('montant'),
            'nombre' => $mouvementsEpargne->count(),
            'liste' => $mouvementsEpargne
        ],
        'retraits' => [
            'total' => $retraits->sum('montant'),
            'nombre' => $retraits->count(),
            'liste' => $retraits
        ],
        'calculs' => [
            'total_epargnes_seulement' => $epargnes->sum('montant'),
            'total_mouvements_epargne_seulement' => $mouvementsEpargne->sum('montant'),
            'solde_theorique_epargnes_seulement' => $epargnes->sum('montant') - $retraits->sum('montant'),
            'solde_theorique_mouvements_seulement' => $mouvementsEpargne->sum('montant') - $retraits->sum('montant'),
        ]
    ];
    
    return response()->json($resultat, 200, [], JSON_PRETTY_PRINT);
});



Route::get('/verifier-doublons', function() {
    $resultats = [];
    
    // Vérifions les 5 premiers comptes
    $comptes = App\Models\CompteEpargne::take(95)->get();
    
    foreach ($comptes as $compte) {
        // 1. Épargnes originales
        $epargnes = App\Models\Epargne::where('client_id', $compte->client_id)
            ->where('statut', 'valide')
            ->where('devise', $compte->devise)
            ->sum('montant');
            
        // 2. Mouvements d'épargne
        $mouvementsEpargne = App\Models\MouvementEpargne::where('compte_epargne_id', $compte->id)
            ->where('type', 'depot')
            ->sum('montant');
            
        // 3. Retraits
        $retraits = App\Models\Mouvement::where('compte_epargne_id', $compte->id)
            ->where('type', 'retrait')
            ->sum('montant');
            
        $resultats[] = [
            'compte' => $compte->numero_compte,
            'client' => $compte->nom_complet,
            'solde_actuel' => $compte->solde,
            'epargnes_originales' => $epargnes,
            'mouvements_epargne' => $mouvementsEpargne,
            'retraits' => $retraits,
            'solde_epargnes_seulement' => $epargnes - $retraits,
            'solde_mouvements_seulement' => $mouvementsEpargne - $retraits,
            'doublon_detecte' => ($epargnes > 0 && $mouvementsEpargne > 0) ? 'OUI' : 'NON',
            'difference_epargnes_vs_mouvements' => $epargnes - $mouvementsEpargne,
        ];
    }
    
    return response()->json($resultats, 200, [], JSON_PRETTY_PRINT);
});



Route::get('/verifier-soldes-finaux', function() {
    $resultats = [];
    
    $comptes = App\Models\CompteEpargne::take(95)->get();
    
    foreach ($comptes as $compte) {
        // Solde calculé depuis les épargnes VALIDES seulement
        $totalEpargnes = 0;
        if ($compte->type_compte === 'individuel' && $compte->client_id) {
            $totalEpargnes = App\Models\Epargne::where('client_id', $compte->client_id)
                ->where('statut', 'valide')
                ->where('devise', $compte->devise)
                ->sum('montant');
        }
        
        $totalRetraits = App\Models\Mouvement::where('compte_epargne_id', $compte->id)
            ->where('type', 'retrait')
            ->sum('montant');
            
        $soldeCalcule = $totalEpargnes - $totalRetraits;
        $ecart = abs($compte->solde - $soldeCalcule);
        
        $resultats[] = [
            'compte' => $compte->numero_compte,
            'client' => $compte->nom_complet,
            'solde_actuel' => $compte->solde,
            'epargnes_valides' => $totalEpargnes,
            'retraits' => $totalRetraits,
            'solde_calcule' => $soldeCalcule,
            'ecart' => $ecart,
            'statut' => $ecart < 0.01 ? '✅ OK' : '❌ INCOHERENT'
        ];
    }
    
    return response()->json($resultats, 200, [], JSON_PRETTY_PRINT);
});


Route::middleware(['auth'])->group(function () {
    // Formulaire de sélection
    Route::get('/rapport/remboursement/period/form', 
        [App\Http\Controllers\RapportRemboursementController::class, 'showForm'])
        ->name('rapport.remboursement.periode.form');
    
    // Génération du rapport
    Route::post('/rapport/remboursement/period/generate', 
        [App\Http\Controllers\RapportRemboursementController::class, 'generateReport'])
        ->name('rapport.remboursement.periode.generate');
});




Route::get('/debug/remboursements', [RapportRemboursementController::class, 'debugRemboursements']);
Route::get('/test/remboursements', function () {
    $service = new \App\Services\RemboursementDirectService();
    $dateDebut = \Carbon\Carbon::parse('2024-01-01');
    $dateFin = \Carbon\Carbon::parse('2026-12-31');
    
    $result = $service->getRemboursementsDirects('mois', $dateDebut, $dateFin, 'all');
    
    return response()->json([
        'count' => $result->count(),
        'data' => $result->take(5)
    ]);
});




Route::get('/test/remboursements', function () {
    try {
        $service = new \App\Services\RemboursementDirectService();
        $dateDebut = \Carbon\Carbon::parse('2024-01-01');
        $dateFin = \Carbon\Carbon::parse('2026-12-31');
        
        $result = $service->getRemboursementsDirects('mois', $dateDebut, $dateFin, 'all');
        
        return response()->json([
            'count' => $result->count(),
            'data' => $result->take(5),
            'total_remboursement' => $result->sum('montant_total'),
            'total_capital' => $result->sum('capital'),
            'total_interets' => $result->sum('interets')
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});


Route::get('/rapport/comptes', [CompteController::class, 'rapportComptes'])
    ->name('rapport.comptes')
    ->middleware(['auth']);

Route::get('/rapport/epargne', [CompteEpargneController::class, 'rapportEpargne'])
    ->name('rapport.epargne')
    ->middleware(['auth']);

// Optionnel pour PDF
Route::get('/rapport/epargne/pdf', [CompteEpargneController::class, 'rapportEpargnePDF'])
    ->name('rapport.epargne.pdf')
    ->middleware(['auth']);

Route::post('/admin/tresorerie/confirm-operation', function (\Illuminate\Http\Request $request) {
    $operationId = $request->input('operation_id');
    $action = $request->input('action');
    
    if (!$operationId) {
        \Filament\Notifications\Notification::make()
            ->title('Erreur')
            ->body('ID d\'opération manquant')
            ->danger()
            ->send();
        return back();
    }
    
    // Récupérer les données de la session
    $data = session()->get("operation_{$operationId}");
    
    if (!$data) {
        \Filament\Notifications\Notification::make()
            ->title('Session expirée')
            ->body('La session de confirmation a expiré. Veuillez recommencer.')
            ->warning()
            ->send();
        return back();
    }
    
    if ($action === 'confirm') {
        try {
            // Nettoyer la session
            session()->forget("operation_{$operationId}");
            
            // Exécuter l'opération
            \App\Filament\Resources\TresorerieResource\Pages\ManageTresorerie::executerOperationFinale($data);
            
            \Filament\Notifications\Notification::make()
                ->title('✅ Opération réussie')
                ->body('L\'opération a été exécutée avec succès')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('❌ Erreur lors de l\'exécution')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    } else {
        // Annulation
        session()->forget("operation_{$operationId}");
        
        \Filament\Notifications\Notification::make()
            ->title('Opération annulée')
            ->body('L\'opération a été annulée par l\'utilisateur')
            ->success()
            ->send();
    }
    
    return back();
})->name('filament.admin.resources.tresorerie.confirm-operation');



Route::get('/admin/tresorerie/final-confirmation', function () {
    // Récupérer les données encodées
    $encodedData = request()->input('data');
    
    if (!$encodedData) {
        abort(404, 'Données non fournies');
    }
    
    $data = json_decode(base64_decode($encodedData), true);
    
    // Retourner vers la page de gestion avec les données
    return redirect()->to(Filament::getUrl())
        ->with('final_confirmation_data', $data);
})->name('filament.admin.resources.tresorerie.final-confirmation');


// Routes pour les rapports d'épargne
Route::prefix('rapports')->group(function () {
    Route::get('/epargne/filtre', [CompteEpargneController::class, 'filtreRapportEpargne'])->name('rapport.epargne.filtre');
    Route::get('/epargne', [CompteEpargneController::class, 'rapportEpargne'])->name('rapport.epargne');
    Route::get('/epargne/export', [CompteEpargneController::class, 'exportRapportEpargne'])->name('rapport.epargne.export');
});