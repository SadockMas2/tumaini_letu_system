<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\CreditGroupe;
use App\Models\PaiementCredit;
use App\Models\User;
use App\Services\RemboursementDirectService;
use App\Services\RemboursementPeriodeService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class RapportRemboursementController extends Controller
{
    /**
     * Affiche le formulaire de sélection
     */
    public function showForm()
    {
        $agents = User::whereHas('roles', function ($query) {
            $query->where('name', 'ConseillerMembres');
        })->get();
        
        $superviseurs = User::whereHas('roles', function ($query) {
            $query->where('name', 'ChefBureau');
        })->get();
        
        return view('filament.pages.selection-remboursement-periode', [
            'agents' => $agents,
            'superviseurs' => $superviseurs
        ]);
    }
    
    /**
     * Génère le rapport
     */
// Dans app/Http/Controllers/RapportRemboursementController.php
public function generateReport(Request $request)
{
    $request->validate([
        'periode' => 'required|in:jour,semaine,mois',
        'date_debut' => 'required|date',
        'date_fin' => 'required|date|after_or_equal:date_debut',
        'type_credit' => 'nullable|in:all,individuel,groupe',
        'agent_id' => 'nullable', // Modifier de 'exists:users,id' à nullable
        'superviseur_id' => 'nullable', // Modifier de 'exists:users,id' à nullable
        'format_export' => 'required|in:html,pdf,excel',
    ]);
    
    // Récupérer les paramètres
    $periode = $request->periode;
    $dateDebut = Carbon::parse($request->date_debut);
    $dateFin = Carbon::parse($request->date_fin);
    $typeCredit = $request->type_credit;
    $agentId = $request->agent_id;
    $superviseurId = $request->superviseur_id;
    $format = $request->format_export;
    
    Log::info('🎯 GÉNÉRATION RAPPORT', [
        'periode' => $periode,
        'dateDebut' => $dateDebut,
        'dateFin' => $dateFin,
        'typeCredit' => $typeCredit,
        'agentId' => $agentId,
        'superviseurId' => $superviseurId,
        'format' => $format
    ]);
    
    // Utiliser le service direct avec les nouveaux paramètres
    $service = new RemboursementDirectService();
    
    try {
        $remboursements = $service->getRemboursementsDirects(
            $periode,
            $dateDebut,
            $dateFin,
            $typeCredit,
            $agentId,      // Nouveau paramètre
            $superviseurId // Nouveau paramètre
        );
        
        Log::info('📊 REMBOURSEMENTS RÉCUPÉRÉS', [
            'count' => $remboursements->count(),
            'first_five' => $remboursements->take(5)->toArray()
        ]);
        
        if ($remboursements->isEmpty()) {
            // Tester avec des dates plus larges
            Log::warning('⚠️ AUCUN REMBOURSEMENT TROUVÉ - Test avec dates élargies');
            
            $dateDebutTest = Carbon::parse('2024-01-01');
            $dateFinTest = Carbon::parse('2026-12-31');
            
            $remboursements = $service->getRemboursementsDirects(
                $periode,
                $dateDebutTest,
                $dateFinTest,
                $typeCredit,
                $agentId,
                $superviseurId
            );
            
            Log::info('📊 REMBOURSEMENTS AVEC DATES ÉLARGIES', [
                'count' => $remboursements->count()
            ]);
        }
        
        // Calculer les totaux
        $totaux = $service->calculerTotaux($remboursements);
        
        // Récupérer les noms pour l'affichage
        $agentNom = 'Tous les agents';
        $superviseurNom = 'Tous les superviseurs';
        
        if ($agentId && $agentId !== 'all') {
            $agent = User::find($agentId);
            $agentNom = $agent ? $agent->name : 'Agent #' . $agentId;
        }
        
        if ($superviseurId && $superviseurId !== 'all') {
            $superviseur = User::find($superviseurId);
            $superviseurNom = $superviseur ? $superviseur->name : 'Superviseur #' . $superviseurId;
        }
        
        // Préparer les données pour la vue
        $data = [
            'remboursements' => $remboursements,
            'totaux' => $totaux,
            'periode' => $periode,
            'date_debut' => $dateDebut->format('d/m/Y'),
            'date_fin' => $dateFin->format('d/m/Y'),
            'type_credit' => $typeCredit === 'all' ? 'Tous les crédits' : 
                            ($typeCredit === 'individuel' ? 'Crédits Individuels' : 'Crédits Groupe'),
            'agent_nom' => $agentNom,
            'superviseur_nom' => $superviseurNom,
            'date_rapport' => now()->format('d/m/Y H:i'),
            'logo_base64' => $this->getLogoBase64(),
        ];
        
        Log::info('✅ DONNÉES PRÉPARÉES POUR EXPORT', [
            'format' => $format,
            'nb_remboursements' => count($remboursements)
        ]);
        
        // Retourner selon le format demandé
        switch ($format) {
            case 'pdf':
                return $this->generatePdf($data, $periode);
            case 'excel':
                return $this->generateExcel($data, $periode);
            case 'html':
            default:
                return $this->generateHtml($data, $periode);
        }
        
    } catch (\Exception $e) {
        Log::error('❌ ERREUR LORS DE LA GÉNÉRATION DU RAPPORT', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return back()->withInput()->with('error', 'Erreur lors de la génération du rapport: ' . $e->getMessage());
    }
}
    /**
     * Génère le rapport HTML
     */
    private function generateHtml($data, $periode)
    {
        $titres = [
            'jour' => 'Journalier',
            'semaine' => 'Hebdomadaire',
            'mois' => 'Mensuel'
        ];
        
        $data['titre_periode'] = $titres[$periode];
        
        return view('filament.exports.rapport-remboursement-periode', $data);
    }
    
    /**
     * Génère le rapport PDF
     */
    private function generatePdf($data, $periode)
    {
        $titres = [
            'jour' => 'Journalier',
            'semaine' => 'Hebdomadaire',
            'mois' => 'Mensuel'
        ];
        
        $data['titre_periode'] = $titres[$periode];
        
        $pdf = Pdf::loadView('filament.exports.rapport-remboursement-periode-pdf', $data);
        
        $fileName = 'remboursement_' . $periode . '_' . now()->format('Ymd_His') . '.pdf';
        
        return $pdf->download($fileName);
    }
    
    /**
     * Génère le rapport Excel
     */
    private function generateExcel($data, $periode)
    {
        // Implémenter l'export Excel selon vos besoins
        // Vous pouvez utiliser Laravel Excel ou générer un CSV
        
        $fileName = 'remboursement_' . $periode . '_' . now()->format('Ymd_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];
        
        return response()->stream(function() use ($data) {
            $handle = fopen('php://output', 'w');
            
            // En-têtes
            fputcsv($handle, [
                'Période',
                'Date',
                'Numéro Compte',
                'Type Crédit',
                'Client/Groupe',
                'Montant Total',
                'Capital',
                'Intérêts',
                '% Capital',
                '% Intérêts',
                'Statut'
            ], ';');
            
            // Données
            foreach ($data['remboursements'] as $item) {
                fputcsv($handle, [
                    $item['periode'],
                    $item['date_periode']->format('d/m/Y'),
                    $item['numero_compte'],
                    $item['type_credit'],
                    $item['nom_complet'],
                    number_format($item['montant_total'], 2),
                    number_format($item['capital'], 2),
                    number_format($item['interets'], 2),
                    number_format($item['pourcentage_capital'], 2),
                    number_format($item['pourcentage_interets'], 2),
                    $item['statut']
                ], ';');
            }
            
            fclose($handle);
        }, 200, $headers);
    }
    
    /**
     * Récupère le logo en base64
     */
    private function getLogoBase64(): string
    {
        $logoPath = public_path('images/logo-tumaini1.png');
        
        if (file_exists($logoPath)) {
            $imageData = file_get_contents($logoPath);
            return 'data:image/png;base64,' . base64_encode($imageData);
        }
        
        // Logo par défaut
        return 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">' .
            '<rect width="100" height="100" fill="#2c5282"/>' .
            '<text x="50" y="50" font-family="Arial" font-size="30" fill="white" text-anchor="middle" dy=".3em">TL</text>' .
            '</svg>'
        );
    }


    public function debugRemboursements()
{
    $dateDebut = Carbon::parse('2024-01-01');
    $dateFin = Carbon::parse('2026-12-31');
    
    $service = new RemboursementDirectService();
    
    // Test avec un seul crédit
    $credit = Credit::where('statut_demande', 'approuve')->first();
    
    if ($credit) {
        Log::info('DÉBUG CRÉDIT', [
            'id' => $credit->id,
            'montant_accorde' => $credit->montant_accorde,
            'montant_total' => $credit->montant_total,
            'remboursement_hebdo' => $credit->remboursement_hebdo,
            'date_octroi' => $credit->date_octroi,
            'date_echeance' => $credit->date_echeance,
        ]);
        
        // Calculer manuellement les échéances
        $echeances = $this->calculerEcheancesManuel($credit);
        
        return response()->json([
            'credit' => $credit,
            'echeances' => $echeances,
            'total_remboursement' => array_sum(array_column($echeances, 'montant_total'))
        ]);
    }
    
    return response()->json(['error' => 'Aucun crédit trouvé']);
}

private function calculerEcheancesManuel($credit)
{
    $dateDebut = $credit->date_octroi->copy()->addWeeks(2);
    $montantHebdo = $credit->remboursement_hebdo;
    $echeances = [];
    
    // Pourcentages d'intérêts
    $pourcentageInterets = [
        14.4154589019438, 12.5668588386971, 11.5077233695784, 10.4164781434722,
        9.292636648909, 9.13522586294972, 8.94327276265538, 6.71531781361745,
        4.45038799289693, 3.14751027755479, 2.80571164465202, 1.80571164465202,
        1.80571164465202, 1.40571164465202, 1.30571164465202, 0.280571164465202
    ];
    
    $totalInterets = $credit->montant_total - $credit->montant_accorde;
    $capitalRestant = $credit->montant_total;
    $capitalPrincipalRestant = $credit->montant_accorde;
    
    for ($semaine = 1; $semaine <= 16; $semaine++) {
        $dateEcheance = $dateDebut->copy()->addWeeks($semaine - 1);
        
        // Calcul des intérêts hebdomadaires
        $interetHebdomadaire = ($totalInterets * $pourcentageInterets[$semaine - 1]) / 100;
        
        // Pour la dernière échéance, ajuster
        if ($semaine == 16) {
            $capitalHebdomadaire = $capitalPrincipalRestant;
            $interetHebdomadaire = $montantHebdo - $capitalHebdomadaire;
        } else {
            $capitalHebdomadaire = $montantHebdo - $interetHebdomadaire;
        }
        
        // Limiter le capital
        if ($capitalHebdomadaire > $capitalPrincipalRestant) {
            $capitalHebdomadaire = $capitalPrincipalRestant;
            $interetHebdomadaire = $montantHebdo - $capitalHebdomadaire;
        }
        
        $capitalPrincipalRestant -= $capitalHebdomadaire;
        $capitalRestant -= $montantHebdo;
        
        $echeances[] = [
            'semaine' => $semaine,
            'date' => $dateEcheance->format('Y-m-d'),
            'montant_total' => $montantHebdo,
            'capital' => $capitalHebdomadaire,
            'interets' => $interetHebdomadaire,
            'pourcentage_capital' => ($capitalHebdomadaire / $montantHebdo) * 100,
            'pourcentage_interets' => ($interetHebdomadaire / $montantHebdo) * 100,
        ];
    }
    
    return $echeances;
}
}