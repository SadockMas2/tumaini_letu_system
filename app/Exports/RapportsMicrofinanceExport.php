<?php

namespace App\Exports;

use App\Models\Credit;
use App\Models\CreditGroupe;
use App\Helpers\CurrencyHelper;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RapportsMicrofinanceExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function collection()
    {
        $creditsIndividuels = Credit::where('statut_demande', 'approuve')
            ->with(['compte', 'agent', 'superviseur', 'paiements'])
            ->get();

        $creditsGroupe = CreditGroupe::where('statut_demande', 'approuve')
            ->with(['compte', 'agent', 'superviseur'])
            ->get();

        // Combiner et formater les données
        $data = collect();
        
        // Crédits individuels
        foreach ($creditsIndividuels as $credit) {
            $data->push([
                'type' => 'individuel',
                'credit' => $credit
            ]);
        }
        
        // Crédits groupe
        foreach ($creditsGroupe as $credit) {
            $data->push([
                'type' => 'groupe',
                'credit' => $credit
            ]);
        }
        
        return $data;
    }

    public function headings(): array
    {
        return [
            'Numéro Compte',
            'Client/Groupe',
            'Type Crédit',
            'Agent',
            'Superviseur',
            'Montant Accordé',
            'Montant Total',
            'Intérêts Attendus',
            'Montant Payé',
            'Date Octroi',
            'Date Échéance',
            'Statut'
        ];
    }

    public function map($row): array
    {
        $credit = $row['credit'];
        $type = $row['type'];
        
        // Calculer les valeurs communes
        $montantAccorde = $credit->montant_accorde;
        $montantTotal = $credit->montant_total;
        $interetsAttendus = $montantTotal - $montantAccorde;
        
        // Calculer le montant payé
        if ($type === 'individuel') {
            $totalPaiements = $credit->paiements->sum('montant_paye');
        } else {
            // Pour les crédits groupe, vous devrez adapter selon votre structure
            $totalPaiements = 0; // À adapter selon votre logique
        }
        
        // Déterminer le statut
        $statut = $credit->date_echeance < now() ? 'En retard' : 'En cours';
        
        return [
            $type === 'groupe' ? 
                ($credit->compte->numero_compte ?? 'GS' . str_pad($credit->id, 5, '0', STR_PAD_LEFT)) : 
                ($credit->compte->numero_compte ?? 'N/A'),
            
            $type === 'groupe' ? 
                ($credit->compte->nom ?? 'Groupe ' . ($credit->compte->numero_compte ?? 'N/A')) : 
                ($credit->compte->nom ?? '') . ' ' . ($credit->compte->prenom ?? ''),
            
            $type === 'groupe' ? 'Groupe' : 'Individuel',
            
            $credit->agent->name ?? 'N/A',
            $credit->superviseur->name ?? 'N/A',
            CurrencyHelper::format($montantAccorde, false),
            CurrencyHelper::format($montantTotal, false),
            CurrencyHelper::format($interetsAttendus, false),
            CurrencyHelper::format($totalPaiements, false),
            $credit->date_octroi?->format('d/m/Y') ?? 'N/A',
            $credit->date_echeance?->format('d/m/Y') ?? 'N/A',
            $statut
        ];
    }

    public function styles(\OpenSpout\Writer\Common\Entity\Worksheet $sheet)
    {
        return [
            // Style pour l'en-tête
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '2E86AB']]
            ],
            
            // Style alterné pour les lignes
            'A2:Z1000' => [
                'font' => ['size' => 11]
            ],
        ];
    }

    /**
     */
    public function __construct() {
    }
}