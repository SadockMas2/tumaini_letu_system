<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Échéancier Groupe - {{ $credit->compte->nom }}</title>
    <style>
        @page { size: A4; margin: 0.5cm; }
        body {
            font-family: 'Arial Narrow', Arial, sans-serif;
            margin: 0; padding: 0;
            color: #000;
            font-size: 12px;
            line-height: 1.1;
        }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 5px; }
        .header img { height: 55px; max-width: 100px; object-fit: contain; }
        .header-info { text-align: right; font-size: 12px; flex: 1; margin-left: 8px; }
        .separator { border-top: 1px solid #000; margin: 5px 0; }
        .ref-date { display: flex; justify-content: space-between; margin-bottom: 8px; font-weight: bold; font-size: 12px; }
        .client-info { margin-bottom: 8px; padding: 6px; background-color: #f8f9fa; border-radius: 3px; }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 4px; margin-bottom: 8px; }
        .info-item { display: flex; justify-content: space-between; padding: 1px 0; }
        .info-label { font-weight: bold; min-width: 85px; }
        .echeancier-table {
            width: 100%; border-collapse: collapse;
            margin: 8px 0; font-size: 12px;
        }
        .echeancier-table th, .echeancier-table td {
            border: 1px solid #000; padding: 3px 2px; text-align: center;
        }
        .echeancier-table th { background-color: #f0f0f0; font-weight: bold; }
        .total-section { margin-top: 10px; padding: 6px; background-color: #f8f9fa; border: 1px solid #000; }
        .total-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; text-align: center; }
        .total-item { padding: 3px; }
        .total-value { font-weight: bold; font-size: 10px; margin-top: 2px; }
        .signature-section { margin-top: 15px; display: flex; justify-content: space-between; }
        .signature {
            text-align: center; border-top: 1px solid #000;
            padding-top: 6px; width: 150px; font-size: 12px;
        }
        .footer { margin-top: 10px; text-align: center; font-size: 7px; color: #666; }
        @media print { .no-print { display: none; } body { margin: 0; } }
        .notes {
            margin-top: 8px; padding: 5px;
            background-color: #fff3cd; border: 1px solid #ffeaa7;
            border-radius: 3px; font-size: 12px;
        }
        .title {
            text-align: center; font-weight: bold;
            font-size: 12px; margin: 5px 0 8px 0;
        }
        .table-container { max-height: 400px; overflow: hidden; }
        .montant-cell { font-family: 'Courier New', monospace; letter-spacing: 0.5px; }
        .totals-row { background-color: #e0e0e0 !important; font-weight: bold; }
    </style>
</head>
<body>

    <div class="no-print" style="text-align:center; margin-bottom:15px; padding:15px; background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:white; border-radius:8px;">
        <h2 style="margin:0; font-size:20px;">Échéancier Crédit Groupe</h2>
        <p style="margin:5px 0; font-size:12px; opacity:0.9;">
            {{ $credit->compte->nom }} - {{ $credit->compte->numero_compte }}
        </p>
        <div style="margin-top:10px;">
            <button onclick="window.print()" style="padding:6px 12px; background:white; color:#007bff; border:none; border-radius:3px; cursor:pointer; margin:3px; font-size:10px;">
                📄 Imprimer
            </button>
            <button onclick="window.close()" style="padding:6px 12px; background:#dc3545; color:white; border:none; border-radius:3px; cursor:pointer; margin:3px; font-size:10px;">
                ❌ Fermer
            </button>
        </div>
    </div>

    <div>
        @php
            // Vérification et débogage des données
            $dateDebut = $credit->date_octroi ? $credit->date_octroi->copy()->addWeeks(2) : now()->addWeeks(2);
            
            // Récupération des montants depuis la base de données
            $montantAccorde = floatval($credit->montant_accorde);
            
            // CORRECTION : CALCUL CORRECT POUR CRÉDIT GROUPE
            // 122.5% = coefficient 1.225
            // Donc : montant_total = montant_accorde × 1.225
            $montantTotal = round($montantAccorde * 1.225, 2);
            
            // Pour 2400$ : 2400 × 1.225 = 2940
            // Remboursement hebdo : 2940 ÷ 16 = 183.75
            
            // Calcul du remboursement hebdomadaire pour groupe
            $montantHebdo = round($montantTotal / 16, 2);
            
            // Pourcentages pour les intérêts hebdomadaires SEULEMENT
            $pourcentageInterets = [
                14.4154589019, 12.5668588387, 11.5077233696, 10.4164781435,
                9.2926366489, 9.1352258629, 8.9432727627, 6.7153178136,
                4.4503879929, 3.1475102776, 2.8057116447, 1.8057116447,
                1.8057116447, 1.4057116447, 1.3057116447, 0.2805711645
            ];
            
            // Calcul du total des intérêts
            $totalInterets = round($montantTotal - $montantAccorde, 2);
            
            // Initialisation des variables
            $capitalRestant = $montantTotal;
            $capitalPrincipalRestant = $montantAccorde;
            $echeances = [];
            $totalCapitalPaye = 0;
            $totalInteretsPayes = 0;
            
            // Calcul des 16 échéances
            for ($semaine = 1; $semaine <= 16; $semaine++) {
                $dateEcheance = $dateDebut->copy()->addWeeks($semaine - 1);
                
                // Calcul de l'intérêt hebdomadaire basé sur le pourcentage du TOTAL des intérêts
                $interetHebdomadaire = round(($totalInterets * $pourcentageInterets[$semaine - 1]) / 100, 2);
                
                // Calcul du capital hebdomadaire (remboursement hebdo - intérêt)
                $capitalHebdomadaire = round($montantHebdo - $interetHebdomadaire, 2);
                
                // Pour la dernière échéance, ajuster pour équilibrer
                if ($semaine == 16) {
                    $capitalHebdomadaire = round($capitalPrincipalRestant, 2);
                    $interetHebdomadaire = round($montantHebdo - $capitalHebdomadaire, 2);
                }
                
                // Limiter le capital au capital principal restant
                if ($capitalHebdomadaire > $capitalPrincipalRestant) {
                    $capitalHebdomadaire = round($capitalPrincipalRestant, 2);
                    $interetHebdomadaire = round($montantHebdo - $capitalHebdomadaire, 2);
                }
                
                $totalCapitalPaye += $capitalHebdomadaire;
                $totalInteretsPayes += $interetHebdomadaire;
                
                $capitalPrincipalRestant -= $capitalHebdomadaire;
                $capitalRestant -= $montantHebdo;
                
                if ($capitalPrincipalRestant < 0) $capitalPrincipalRestant = 0;
                if ($capitalRestant < 0) $capitalRestant = 0;
                
                $echeances[] = [
                    'semaine' => $semaine,
                    'date' => $dateEcheance,
                    'capital_hebdo' => $capitalHebdomadaire,
                    'interet_hebdo' => $interetHebdomadaire,
                    'montant_total' => $montantHebdo,
                    'capital_restant' => round($capitalRestant, 2),
                ];
            }
            
            // Ajustement final pour équilibrer les totaux
            $ajustementCapital = round($montantAccorde - $totalCapitalPaye, 2);
            if (abs($ajustementCapital) > 0.01) {
                $echeances[15]['capital_hebdo'] = round($echeances[15]['capital_hebdo'] + $ajustementCapital, 2);
                $echeances[15]['interet_hebdo'] = round($montantHebdo - $echeances[15]['capital_hebdo'], 2);
                $echeances[15]['capital_restant'] = 0;
                
                // Recalculer les totaux après ajustement
                $totalCapitalPaye = $montantAccorde;
                $totalInteretsPayes = $totalInterets;
            }
            
            // Calcul des totaux finaux
            $totalCapitalFinal = round(array_sum(array_column($echeances, 'capital_hebdo')), 2);
            $totalInteretsFinal = round(array_sum(array_column($echeances, 'interet_hebdo')), 2);
            $totalGeneralFinal = round(array_sum(array_column($echeances, 'montant_total')), 2);
            
            // Afficher les calculs pour débogage
            $debugInfo = [
                'montant_accorde' => $montantAccorde,
                'montant_total_calcule' => $montantTotal,
                'total_interets' => $totalInterets,
                'montant_hebdo' => $montantHebdo,
            ];
        @endphp

        <!-- Section de débogage -->
        {{-- @if(env('APP_DEBUG'))
        <div style="background:#ffcccc; padding:10px; margin-bottom:10px; border:1px solid #ff6666; font-size:10px;">
            <strong>DEBUG INFO:</strong><br>
            Montant Accordé: {{ $montantAccorde }} $<br>
            Montant Total: {{ $montantAccorde }} × 1.225 = {{ $montantTotal }} $<br>
            Total Intérêts: {{ $totalInterets }} $<br>
            Montant Hebdo: {{ $montantTotal }} ÷ 16 = {{ $montantHebdo }} $<br>
            Vérification: ({{ $montantHebdo }} × 16) = {{ round($montantHebdo * 16, 2) }} $
        </div>
        @endif --}}

        <div class="header">
            <div class="logo">
                @if(file_exists(public_path('images/logo-tumaini1.png')))
                    <img src="{{ asset('images/logo-tumaini1.png') }}" alt="TUMAINI LETU asbl">
                @else
                    <div style="height:55px;width:100px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;border:1px dashed #ccc;font-size:8px;">
                        TUMAINI LETU
                    </div>
                @endif
            </div>
            <div class="header-info">
                <div><strong>Tumaini Letu asbl</strong></div>
                <div>Siège social 005, avenue du port, quartier les volcans - Goma</div>
                <div>NUM BED : 14453756111</div>
                <div>Tel : +243982618321</div>
                <div>Email : tumainiletu@gmail.com</div>
            </div>
        </div>

        <div class="separator"></div>

        <div class="ref-date">
            <div>RÉF : ÉCH-G{{ $credit->id }}-{{ date('Ymd') }}</div>
            <div>DATE : {{ now()->format('d/m/Y') }}</div>
            <div>PÉRIODE : 16 SEMAINES</div>
        </div>

        <div class="separator"></div>

        <div class="title">ÉCHÉANCIER REMBOURSEMENT - CRÉDIT GROUPE</div>

        <div class="client-info">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Compte :</span>
                    <span>{{ $credit->compte->numero_compte }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Groupe :</span>
                    <span>{{ $credit->compte->nom }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Type :</span>
                    <span>Groupe Solidaire</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Date Octroi :</span>
                    <span>{{ $credit->date_octroi ? $credit->date_octroi->format('d/m/Y') : 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Montant Accordé :</span>
                    <span class="montant-cell">{{ number_format($montantAccorde, 2, ',', ' ') }} $</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Montant Total :</span>
                    <span class="montant-cell">{{ number_format($montantTotal, 2, ',', ' ') }} $</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Début Remb. :</span>
                    <span>{{ $dateDebut->format('d/m/Y') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Remb. Hebdo :</span>
                    <span class="montant-cell">{{ number_format($montantHebdo, 2, ',', ' ') }} $</span>
                </div>
            </div>
        </div>

        <div class="table-container">
            <table class="echeancier-table">
                <thead>
                    <tr>
                        <th>Sem</th>
                        <th>Date</th>
                        <th>Intérêt</th>
                        <th>Capital</th>
                        <th>À Payer</th>
                        <th>Reste</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($echeances as $echeance)
                    <tr>
                        <td>{{ $echeance['semaine'] }}</td>
                        <td>{{ $echeance['date']->format('d/m/Y') }}</td>
                        <td class="montant-cell">{{ number_format($echeance['interet_hebdo'], 2, ',', ' ') }} $</td>
                        <td class="montant-cell">{{ number_format($echeance['capital_hebdo'], 2, ',', ' ') }} $</td>
                        <td class="montant-cell">{{ number_format($echeance['montant_total'], 2, ',', ' ') }} $</td>
                        <td class="montant-cell">{{ number_format($echeance['capital_restant'], 2, ',', ' ') }} $</td>
                    </tr>
                    @endforeach
                    <tr class="totals-row">
                        <td colspan="2"><strong>TOTAUX</strong></td>
                        <td class="montant-cell">{{ number_format($totalInteretsFinal, 2, ',', ' ') }} $</td>
                        <td class="montant-cell">{{ number_format($totalCapitalFinal, 2, ',', ' ') }} $</td>
                        <td class="montant-cell">{{ number_format($totalGeneralFinal, 2, ',', ' ') }} $</td>
                        <td class="montant-cell">0,00 $</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="total-section">
            <div class="total-grid">
                <div class="total-item">
                    <div>Montant Accordé</div>
                    <div class="total-value montant-cell">{{ number_format($montantAccorde, 2, ',', ' ') }} $</div>
                </div>
                <div class="total-item">
                    <div>Total Intérêts</div>
                    <div class="total-value montant-cell">{{ number_format($totalInterets, 2, ',', ' ') }} $</div>
                </div>
                <div class="total-item">
                    <div>Montant Total</div>
                    <div class="total-value montant-cell">{{ number_format($montantTotal, 2, ',', ' ') }} $</div>
                </div>
                <div class="total-item">
                    <div>Remb. Hebdo</div>
                    <div class="total-value montant-cell">{{ number_format($montantHebdo, 2, ',', ' ') }} $</div>
                </div>
            </div>
        </div>

        <div class="notes">
            <strong>Notes importantes :</strong><br>
            • Remboursement hebdomadaire fixe : <strong>{{ number_format($montantHebdo, 2, ',', ' ') }} USD</strong><br>

            • Jour de paiement : chaque <strong>{{ $dateDebut->locale('fr')->translatedFormat('l') }}</strong><br>
            • Début : 2 semaines après l'octroi (le {{ $dateDebut->format('d/m/Y') }})<br>
            • Pénalité retard : 5% du montant dû<br>
            • Caution groupe : {{ number_format($credit->caution_totale, 2, ',', ' ') }} USD<br>
            • Solidarité groupe obligatoire pour tous les membres
        </div>

        <div class="signature-section">
            <div class="signature">
                Responsable Groupe<br>
                <div style="margin-top:25px;">{{ $credit->compte->nom }}</div>
            </div>
            <div class="signature">
                Responsable Tumaini Letu<br>
                <div style="margin-top:25px;">Tumaini Letu asbl</div>
            </div>
        </div>

        <div class="footer">
            <div class="separator"></div>
            Document généré le {{ now()->format('d/m/Y H:i') }} | Tumaini Letu asbl
        </div>
    </div>

</body>
</html>