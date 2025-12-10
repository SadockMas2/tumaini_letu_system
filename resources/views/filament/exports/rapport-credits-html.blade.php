<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport des Crédits Microfinance - Tumaini Letu</title>
    <style>
        /* Reset et base */
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #000;
            font-size: 11px;
            line-height: 1.3;
            background: white;
        }

        /* En-tête Tumaini Letu */
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            align-items: flex-start;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header-info {
            text-align: right;
            font-size: 10px;
            flex: 1;
        }
        .institution-name {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 3px;
        }

        /* Séparateurs */
        .separator {
            border-top: 2px solid #000;
            margin: 12px 0;
        }

        /* Informations référence et date */
        .ref-date {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-weight: bold;
            font-size: 10px;
        }

        /* Titre du rapport */
        .report-title {
            text-align: center;
            margin-bottom: 20px;
        }
        .report-title h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .report-title .subtitle {
            font-size: 13px;
            color: #666;
        }

        /* Synthèse générale */
        .summary-section {
            margin-bottom: 20px;
        }
        .section-title {
            font-weight: bold;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #000;
            font-size: 14px;
        }

        /* Grille de totaux */
        .totals-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 15px;
        }
        .total-card {
            padding: 8px;
            border: 1px solid #000;
            border-radius: 4px;
            text-align: center;
            background-color: #f5f5f5;
        }
        .total-label {
            font-size: 9px;
            color: #000;
            margin-bottom: 3px;
        }
        .total-value {
            font-size: 11px;
            font-weight: bold;
            color: #000;
        }

        /* Table principale */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9px;
            page-break-inside: avoid;
        }
        .main-table th {
            background-color: #e8e8e8;
            padding: 6px 4px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #000;
            vertical-align: top;
        }
        .main-table td {
            padding: 4px 3px;
            border: 1px solid #000;
            vertical-align: top;
        }
        .main-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Lignes de total */
        .total-row {
            background-color: #e8e8e8 !important;
            font-weight: bold;
            border-top: 2px solid #000;
        }

        /* Classes utilitaires */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .montant { font-family: 'Courier New', monospace; }
        .nowrap { white-space: nowrap; }

        /* Couleurs pour statuts */
        .statut-termine { color: #28a745; font-weight: bold; }
        .statut-en-cours { color: #007bff; font-weight: bold; }
        .statut-en-retard { color: #dc3545; font-weight: bold; }

        /* Pied de page */
        .footer {
            margin-top: 25px;
            padding-top: 10px;
            border-top: 1px solid #000;
            text-align: center;
            color: #000;
            font-size: 9px;
        }

        /* Signatures */
        .signatures {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .signature {
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 5px;
            width: 180px;
            font-size: 10px;
        }
        
        /* Impressions */
        @media print {
            body {
                padding: 10px;
                font-size: 10px;
            }
            
            .main-table {
                font-size: 8px;
            }
            
            .totals-grid {
                gap: 6px;
            }
            
            .total-card {
                padding: 6px;
            }
        }
    </style>
</head>
<body>
    <!-- En-tête -->
    <div class="header">
        <div class="logo">
            <!-- Simple logo texte -->
            <div style="font-weight: bold; font-size: 16px; color: #2c5282;">TUMAINI LETU asbl</div>
        </div>
        <div class="header-info">
            <div class="institution-name">Tumaini Letu asbl</div>
            <div>Siège social 005, avenue du port, quartier les volcans - Goma - Rd Congo</div>
            <div>NUM BED : 14453756111</div>
            <div>Tel : +243982618321</div>
            <div>Email : tumainiletu@gmail.com</div>
        </div>
    </div>

    <div class="separator"></div>

    <!-- Informations du rapport -->
    <div class="ref-date">
        <div>N/REF : CREDIT-RAPP-{{ now()->format('Ymd-His') }}</div>
        <div>Période : {{ $periode }}</div>
        <div>Généré le : {{ $date_generation }}</div>
    </div>

    <div class="separator"></div>

    <!-- Titre du rapport -->
    <div class="report-title">
        <h1>RAPPORT DES CRÉDITS MICROFINANCE</h1>
        <div class="subtitle">Synthèse détaillée des crédits actifs - Portefeuille crédits</div>
    </div>

    <!-- Synthèse générale -->
    <div class="summary-section">
        <div class="section-title">SYNTHÈSE GÉNÉRALE</div>
        <div class="totals-grid">
            <div class="total-card">
                <div class="total-label">TOTAL CRÉDITS</div>
                <div class="total-value">{{ number_format($totaux['total_credits'], 0) }}</div>
            </div>
            <div class="total-card">
                <div class="total-label">CAPITAL ACCORDÉ</div>
                <div class="total-value montant">{{ number_format($totaux['total_montant_accorde'], 2) }} $</div>
            </div>
            <div class="total-card">
                <div class="total-label">MONTANT TOTAL</div>
                <div class="total-value montant">{{ number_format($totaux['total_montant_total'], 2) }} $</div>
            </div>
            <div class="total-card">
                <div class="total-label">INTÉRÊTS ATTENDUS</div>
                <div class="total-value montant">{{ number_format($totaux['total_interets_attendus'], 2) }} $</div>
            </div>
            <div class="total-card">
                <div class="total-label">TOTAL PAYÉ</div>
                <div class="total-value montant">{{ number_format($totaux['total_paiements'], 2) }} $</div>
            </div>
            <div class="total-card">
                <div class="total-label">MONTANT RESTANT</div>
                <div class="total-value montant">{{ number_format($totaux['total_montant_restant'], 2) }} $</div>
            </div>
            <div class="total-card">
                <div class="total-label">CAPITAL REMBOURSÉ</div>
                <div class="total-value montant">{{ number_format($totaux['total_capital_rembourse'], 2) }} $</div>
            </div>
            <div class="total-card">
                <div class="total-label">INTÉRÊTS PAYÉS</div>
                <div class="total-value montant">{{ number_format($totaux['total_interets_payes'], 2) }} $</div>
            </div>
            <div class="total-card">
                <div class="total-label">TAUX REMBOURSEMENT</div>
                <div class="total-value">{{ number_format($totaux['taux_remboursement_global'], 2) }} %</div>
            </div>
            <div class="total-card">
                <div class="total-label">CRÉDITS INDIVIDUELS</div>
                <div class="total-value">{{ number_format($totaux['credits_individuels'], 0) }}</div>
            </div>
            <div class="total-card">
                <div class="total-label">CRÉDITS GROUPE</div>
                <div class="total-value">{{ number_format($totaux['credits_groupe'], 0) }}</div>
            </div>
            <div class="total-card">
                <div class="total-label">PORTEFEUILLE CAPITAL</div>
                <div class="total-value montant">{{ number_format($totaux['total_montant_accorde'] - $totaux['total_capital_rembourse'], 2) }} $</div>
            </div>
            <div class="total-card">
                <div class="total-label">PORTEFEUILLE INTÉRÊTS</div>
                <div class="total-value montant">{{ number_format($totaux['total_interets_attendus'] - $totaux['total_interets_payes'], 2) }} $</div>
            </div>
            <div class="total-card">
                <div class="total-label">EN COURS</div>
                <div class="total-value">{{ number_format($totaux['credits_en_cours'], 0) }}</div>
            </div>
            <div class="total-card">
                <div class="total-label">TERMINÉS</div>
                <div class="total-value">{{ number_format($totaux['credits_termines'], 0) }}</div>
            </div>
            <div class="total-card">
                <div class="total-label">EN RETARD</div>
                <div class="total-value statut-en-retard">{{ number_format($totaux['credits_en_retard'], 0) }}</div>
            </div>
        </div>
    </div>

    <div class="separator"></div>

    <!-- Table détaillée des crédits -->
    <div class="section-title">DÉTAIL DES CRÉDITS ({{ count($credits) }} crédits)</div>
    
    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 4%">N°</th>
                <th style="width: 8%">Compte</th>
                <th style="width: 12%">Client/Groupe</th>
                <th style="width: 5%">Type</th>
                <th style="width: 8%">Agent</th>
                <th style="width: 8%">Superviseur</th>
                <th style="width: 8%">Date Octroi</th>
                <th style="width: 8%">Date Échéance</th>
                <th style="width: 7%" class="text-right">Capital Accordé</th>
                <th style="width: 7%" class="text-right">Montant Total</th>
                <th style="width: 7%" class="text-right">Intérêts Attendus</th>
                <th style="width: 7%" class="text-right">Capital Remboursé</th>
                <th style="width: 7%" class="text-right">Intérêts Payés</th>
                <th style="width: 7%" class="text-right">Total Payé</th>
                <th style="width: 7%" class="text-right">Montant Restant</th>
                <th style="width: 5%" class="text-center">Statut</th>
                <th style="width: 5%" class="text-center">Taux %</th>
            </tr>
        </thead>
        <tbody>
            @php
                $counter = 1;
            @endphp
            
            @foreach($credits as $credit)
            <tr>
                <td class="text-center">{{ $counter++ }}</td>
                <td>{{ $credit['numero_compte'] }}</td>
                <td>{{ $credit['nom_complet'] }}</td>
                <td class="text-center">{{ $credit['type_credit'] }}</td>
                <td>{{ \Illuminate\Support\Str::limit($credit['agent'], 12) }}</td>
                 <td>{{ \Illuminate\Support\Str::limit($credit['superviseur'], 12) }}</td>
                <td class="text-center">{{ $credit['date_octroi'] }}</td>
                <td class="text-center">{{ $credit['date_echeance'] }}</td>
                <td class="text-right montant">{{ number_format($credit['montant_accorde'], 2) }}</td>
                <td class="text-right montant">{{ number_format($credit['montant_total'], 2) }}</td>
                <td class="text-right montant">{{ number_format($credit['interets_attendus'], 2) }}</td>
                <td class="text-right montant">{{ number_format($credit['capital_deja_rembourse'], 2) }}</td>
                <td class="text-right montant">{{ number_format($credit['interets_deja_payes'], 2) }}</td>
                <td class="text-right montant">{{ number_format($credit['total_paiements'], 2) }}</td>
                <td class="text-right montant">{{ number_format($credit['montant_restant'], 2) }}</td>
                <td class="text-center 
                    @if($credit['statut'] === 'Terminé') statut-termine
                    @elseif($credit['statut'] === 'En retard') statut-en-retard
                    @else statut-en-cours
                    @endif">
                    {{ $credit['statut'] }}
                </td>
                <td class="text-center">{{ number_format($credit['taux_remboursement'], 2) }}%</td>
            </tr>
            @endforeach
            
            <!-- Ligne de totaux -->
            <tr class="total-row">
                <td colspan="7"><strong>TOTAUX GÉNÉRAUX</strong></td>
                <td class="text-right montant"><strong>{{ number_format($totaux['total_montant_accorde'], 2) }}</strong></td>
                <td class="text-right montant"><strong>{{ number_format($totaux['total_montant_total'], 2) }}</strong></td>
                <td class="text-right montant"><strong>{{ number_format($totaux['total_interets_attendus'], 2) }}</strong></td>
                <td class="text-right montant"><strong>{{ number_format($totaux['total_capital_rembourse'], 2) }}</strong></td>
                <td class="text-right montant"><strong>{{ number_format($totaux['total_interets_payes'], 2) }}</strong></td>
                <td class="text-right montant"><strong>{{ number_format($totaux['total_paiements'], 2) }}</strong></td>
                <td class="text-right montant"><strong>{{ number_format($totaux['total_montant_restant'], 2) }}</strong></td>
                <td class="text-center">
                    <strong>{{ $totaux['credits_termines'] }} / {{ $totaux['total_credits'] }}</strong>
                </td>
                <td class="text-center">
                    <strong>{{ number_format($totaux['taux_remboursement_global'], 2) }}%</strong>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="separator"></div>

    <!-- Résumé du portefeuille -->
    <div style="margin-top: 15px; font-size: 10px;">
        <div class="section-title">RÉSUMÉ DU PORTEFEUILLE CRÉDITS</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px;">
            <div style="border: 1px solid #000; padding: 10px; border-radius: 4px;">
                <div style="font-weight: bold; margin-bottom: 8px; color: #2c5282;">PORTEFEUILLE CAPITAL</div>
                <div>Capital accordé initial: <strong class="montant">{{ number_format($totaux['total_montant_accorde'], 2) }} $</strong></div>
                <div>Capital déjà remboursé: <strong class="montant">{{ number_format($totaux['total_capital_rembourse'], 2) }} $</strong></div>
                <div style="border-top: 1px solid #ccc; margin-top: 5px; padding-top: 5px;">
                    Capital restant à recouvrer: <strong class="montant" style="color: #dc3545;">{{ number_format($totaux['total_montant_accorde'] - $totaux['total_capital_rembourse'], 2) }} $</strong>
                </div>
            </div>
            
            <div style="border: 1px solid #000; padding: 10px; border-radius: 4px;">
                <div style="font-weight: bold; margin-bottom: 8px; color: #2c5282;">PORTEFEUILLE INTÉRÊTS</div>
                <div>Intérêts attendus totaux: <strong class="montant">{{ number_format($totaux['total_interets_attendus'], 2) }} $</strong></div>
                <div>Intérêts déjà payés: <strong class="montant">{{ number_format($totaux['total_interets_payes'], 2) }} $</strong></div>
                <div style="border-top: 1px solid #ccc; margin-top: 5px; padding-top: 5px;">
                    Intérêts restants à percevoir: <strong class="montant" style="color: #28a745;">{{ number_format($totaux['total_interets_attendus'] - $totaux['total_interets_payes'], 2) }} $</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="separator"></div>

    <!-- Notes et informations complémentaires -->
    <div style="margin-top: 15px; font-size: 10px;">
        <div><strong>Notes :</strong></div>
        <div>1. Tous les montants sont en dollars américains (USD)</div>
        <div>2. La durée standard des crédits est de 16 semaines (4 mois)</div>
        <div>3. Le remboursement est hebdomadaire (16 paiements égaux)</div>
        <div>5. Le portefeuille capital diminue au fur et à mesure que le capital est remboursé</div>
        <div>6. Les intérêts attendus diminuent au fur et à mesure que les intérêts sont payés</div>
    </div>

    <!-- Signatures -->
    <div class="signatures">
        <div class="signature" style="text-align: left;">
            Le  Gérant<br>
            _________________________
        </div>
        
    </div>

    <!-- Pied de page -->
    <div class="footer">
        <div>Rapport généré automatiquement par le Système de Gestion Microfinance Tumaini Letu</div>
        <div>Document confidentiel - Page 1/1 - {{ now()->format('d/m/Y H:i:s') }}</div>
    </div>

    <!-- Script d'impression -->
    <script>
        // Auto-impression optionnelle
        document.addEventListener('DOMContentLoaded', function() {
            // Après 1 seconde, proposer l'impression
            setTimeout(function() {
                if (confirm('Voulez-vous imprimer ce rapport ?')) {
                    window.print();
                }
            }, 1000);
            
            // Ajouter des boutons d'action
            const actionsDiv = document.createElement('div');
            actionsDiv.style.position = 'fixed';
            actionsDiv.style.top = '10px';
            actionsDiv.style.right = '10px';
            actionsDiv.style.zIndex = '1000';
            
            actionsDiv.innerHTML = `
                <button onclick="window.print()" style="padding: 5px 10px; margin: 2px; background: #2c5282; color: white; border: none; border-radius: 3px; cursor: pointer;">
                    📄 Imprimer
                </button>
                <button onclick="window.close()" style="padding: 5px 10px; margin: 2px; background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer;">
                    ❌ Fermer
                </button>
            `;
            
            document.body.appendChild(actionsDiv);
        });
    </script>
</body>
</html>