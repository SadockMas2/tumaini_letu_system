<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport des Mouvements de Coffres - Tumaini Letu</title>
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 15px;
            color: #000;
            font-size: 12px;
            line-height: 1.3;
            background: white;
        }

        /* En-tête Tumaini Letu */
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            align-items: flex-start;
        }
        .header img {
            height: 70px;
            max-width: 140px;
            object-fit: contain;
        }
        .header-info {
            text-align: right;
            font-size: 11px;
            flex: 1;
            margin-left: 15px;
        }
        .institution-name {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 2px;
        }

        /* Séparateurs style bordereau */
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
            font-size: 11px;
        }

        /* Sections de contenu */
        .section {
            margin-bottom: 15px;
        }
        .section-title {
            font-weight: bold;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #000;
            font-size: 13px;
        }

        /* Tables compactes */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 10px;
        }
        .table th {
            background-color: #f5f5f5;
            padding: 6px 4px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #000;
        }
        .table td {
            padding: 4px 3px;
            border: 1px solid #000;
            vertical-align: top;
        }
        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Lignes de total */
        .total-row {
            background-color: #e8e8e8 !important;
            font-weight: bold;
            border-top: 2px solid #000;
        }

        /* Grille de totaux */
        .totals-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            margin-bottom: 12px;
        }
        .total-card {
            padding: 6px;
            border: 1px solid #000;
            border-radius: 3px;
            text-align: center;
            background-color: #f5f5f5;
        }
        .total-label {
            font-size: 9px;
            color: #000;
            margin-bottom: 2px;
        }
        .total-value {
            font-size: 11px;
            font-weight: bold;
            color: #000;
        }

        /* Signatures */
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .signature {
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 4px;
            width: 180px;
            font-size: 10px;
        }

        /* Pied de page */
        .footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #000;
            text-align: center;
            color: #000;
            font-size: 9px;
        }

        /* Classes utilitaires */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .montant { font-family: 'Courier New', monospace; }
        .compact { margin-bottom: 5px; }

        /* Couleurs minimales pour différenciation */
        .devise-usd { font-weight: bold; color: #1e40af; }
        .devise-cdf { font-weight: bold; color: #dc2626; }
        .entree { color: #059669; font-weight: bold; }
        .sortie { color: #dc2626; font-weight: bold; }
        .depot { color: #059669; font-weight: bold; }
        .retrait { color: #dc2626; font-weight: bold; }

        /* En-tête par devise */
        .devise-header {
            background-color: #e0f2fe;
            padding: 8px;
            margin-bottom: 8px;
            border-left: 4px solid #1e40af;
            font-weight: bold;
        }
        .devise-header.cdf {
            background-color: #fef2f2;
            border-left-color: #dc2626;
        }

        /* Pagination */
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <!-- En-tête Tumaini Letu avec logo -->
    <div class="header">
        <div class="logo">
            <img src="{{ $logo_base64 ?? '' }}" alt="TUMAINI LETU asbl" style="height: 70px; max-width: 140px; object-fit: contain;">
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
        <div>N/REF : MVT-COFFRES-{{ \Carbon\Carbon::now()->format('Ymd-His') }}</div>
        <div>Période : {{ $periode['debut'] }} à {{ $periode['fin'] }}</div>
        <div>Généré le : {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</div>
    </div>

    <div class="separator"></div>

    <!-- Titre du rapport -->
    <div class="section">
        <div style="text-align: center; margin-bottom: 15px;">
            <h2 style="font-size: 16px; font-weight: bold; color: #000;">RAPPORT DES MOUVEMENTS DE COFFRES</h2>
            <p style="font-size: 12px; color: #000;">Détail des opérations sur la période indiquée</p>
        </div>
    </div>

    <!-- Synthèse générale -->
    <div class="section">
        <div class="section-title">SYNTHÈSE GÉNÉRALE</div>
        <div class="totals-grid">
            <div class="total-card">
                <div class="total-label">ENTRÉES USD</div>
                <div class="total-value entree devise-usd montant">{{ number_format($total_usd_entrees, 2) }} $</div>
            </div>
            <div class="total-card">
                <div class="total-label">SORTIES USD</div>
                <div class="total-value sortie devise-usd montant">{{ number_format($total_usd_sorties, 2) }} $</div>
            </div>
            <div class="total-card">
                <div class="total-label">SOLDE NET USD</div>
                <div class="total-value montant {{ ($total_usd_entrees - $total_usd_sorties) >= 0 ? 'entree' : 'sortie' }}">
                    {{ number_format($total_usd_entrees - $total_usd_sorties, 2) }} $
                </div>
            </div>
            <div class="total-card">
                <div class="total-label">OPÉRATIONS USD</div>
                <div class="total-value">{{ $count_usd }}</div>
            </div>
            
            <div class="total-card">
                <div class="total-label">ENTRÉES CDF</div>
                <div class="total-value entree devise-cdf montant">{{ number_format($total_cdf_entrees, 0) }} FC</div>
            </div>
            <div class="total-card">
                <div class="total-label">SORTIES CDF</div>
                <div class="total-value sortie devise-cdf montant">{{ number_format($total_cdf_sorties, 0) }} FC</div>
            </div>
            <div class="total-card">
                <div class="total-label">SOLDE NET CDF</div>
                <div class="total-value montant {{ ($total_cdf_entrees - $total_cdf_sorties) >= 0 ? 'entree' : 'sortie' }}">
                    {{ number_format($total_cdf_entrees - $total_cdf_sorties, 0) }} FC
                </div>
            </div>
            <div class="total-card">
                <div class="total-label">OPÉRATIONS CDF</div>
                <div class="total-value">{{ $count_cdf }}</div>
            </div>
        </div>
    </div>

    <div class="separator"></div>

    <!-- Détail des mouvements -->
    <div class="section">
        <div class="section-title">DÉTAIL DES MOUVEMENTS ({{ $count_total }} opérations)</div>
        
        @php
            $groupedMouvements = $mouvements->groupBy(function($item) {
                return $item->date_mouvement->format('Y-m-d');
            });
        @endphp
        
        @foreach($groupedMouvements as $date => $mouvementsJour)
        <div style="margin-top: 15px;">
            <div style="font-weight: bold; background-color: #f0f0f0; padding: 4px; margin-bottom: 5px; border-left: 3px solid #1e40af;">
                {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 8%">Heure</th>
                        <th style="width: 10%">Devise</th>
                        <th style="width: 10%">Type</th>
                        <th style="width: 12%" class="text-right">Montant</th>
                        <th style="width: 25%">Description</th>
                        <th style="width: 15%">Source/Destination</th>
                        <th style="width: 10%">Référence</th>
                        <th style="width: 10%">Opérateur</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mouvementsJour as $mouvement)
                    <tr>
                        <td>{{ $mouvement->date_mouvement->format('H:i') }}</td>
                        <td class="{{ $mouvement->devise === 'USD' ? 'devise-usd' : 'devise-cdf' }}">
                            {{ $mouvement->devise }}
                        </td>
                        <td class="{{ $mouvement->type_mouvement === 'entree' ? 'entree' : 'sortie' }}">
                            {{ $mouvement->type_mouvement === 'entree' ? 'ENTRÉE' : 'SORTIE' }}
                        </td>
                        <td class="text-right montant {{ $mouvement->type_mouvement === 'entree' ? 'entree' : 'sortie' }}">
                            {{ number_format($mouvement->montant, $mouvement->devise === 'USD' ? 2 : 0) }}
                            {{ $mouvement->devise === 'USD' ? '$' : 'FC' }}
                        </td>
                        <td style="font-size: 9px;">{{ \Illuminate\Support\Str::limit($mouvement->description, 50) }}</td>
                        <td style="font-size: 9px;">
                            @if($mouvement->type_mouvement === 'entree')
                                {{ $mouvement->source_type ?? 'N/A' }}
                            @else
                                {{ $mouvement->destination_type ?? 'N/A' }}
                            @endif
                        </td>
                        <td style="font-size: 9px;">{{ \Illuminate\Support\Str::limit($mouvement->reference, 15) }}</td>
                        <td style="font-size: 9px;">{{ \Illuminate\Support\Str::limit($mouvement->operateur->name ?? 'N/A', 12) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endforeach
    </div>

    <div class="separator"></div>

    <!-- Totaux finaux par devise -->
    <div class="section">
        <div class="section-title">RÉCAPITULATIF PAR DEVISES</div>
        
        <!-- USD -->
        <div class="devise-header">
            DOLLARS AMÉRICAINS (USD)
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 30%">Type</th>
                    <th style="width: 25%" class="text-right">Montant</th>
                    <th style="width: 20%">Statut</th>
                    <th style="width: 25%">Observations</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Entrées USD</td>
                    <td class="text-right entree devise-usd montant">{{ number_format($total_usd_entrees, 2) }} $</td>
                    <td class="entree">POSITIF</td>
                    <td class="text-center">{{ $count_usd }} opération(s)</td>
                </tr>
                <tr>
                    <td>Total Sorties USD</td>
                    <td class="text-right sortie devise-usd montant">{{ number_format($total_usd_sorties, 2) }} $</td>
                    <td class="sortie">NÉGATIF</td>
                    <td class="text-center">{{ $count_usd }} opération(s)</td>
                </tr>
                <tr class="total-row">
                    <td><strong>SOLDE NET USD</strong></td>
                    <td class="text-right montant {{ ($total_usd_entrees - $total_usd_sorties) >= 0 ? 'entree' : 'sortie' }}">
                        <strong>{{ number_format($total_usd_entrees - $total_usd_sorties, 2) }} $</strong>
                    </td>
                    <td class="{{ ($total_usd_entrees - $total_usd_sorties) >= 0 ? 'entree' : 'sortie' }}">
                        <strong>{{ ($total_usd_entrees - $total_usd_sorties) >= 0 ? 'EXCÉDENTAIRE' : 'DÉFICITAIRE' }}</strong>
                    </td>
                    <td class="text-center"><strong>{{ $count_usd }} opération(s)</strong></td>
                </tr>
            </tbody>
        </table>

        <!-- CDF -->
        <div class="devise-header cdf" style="margin-top: 15px;">
            FRANCS CONGOLAIS (CDF)
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 30%">Type</th>
                    <th style="width: 25%" class="text-right">Montant</th>
                    <th style="width: 20%">Statut</th>
                    <th style="width: 25%">Observations</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Entrées CDF</td>
                    <td class="text-right entree devise-cdf montant">{{ number_format($total_cdf_entrees, 0) }} FC</td>
                    <td class="entree">POSITIF</td>
                    <td class="text-center">{{ $count_cdf }} opération(s)</td>
                </tr>
                <tr>
                    <td>Total Sorties CDF</td>
                    <td class="text-right sortie devise-cdf montant">{{ number_format($total_cdf_sorties, 0) }} FC</td>
                    <td class="sortie">NÉGATIF</td>
                    <td class="text-center">{{ $count_cdf }} opération(s)</td>
                </tr>
                <tr class="total-row">
                    <td><strong>SOLDE NET CDF</strong></td>
                    <td class="text-right montant {{ ($total_cdf_entrees - $total_cdf_sorties) >= 0 ? 'entree' : 'sortie' }}">
                        <strong>{{ number_format($total_cdf_entrees - $total_cdf_sorties, 0) }} FC</strong>
                    </td>
                    <td class="{{ ($total_cdf_entrees - $total_cdf_sorties) >= 0 ? 'entree' : 'sortie' }}">
                        <strong>{{ ($total_cdf_entrees - $total_cdf_sorties) >= 0 ? 'EXCÉDENTAIRE' : 'DÉFICITAIRE' }}</strong>
                    </td>
                    <td class="text-center"><strong>{{ $count_cdf }} opération(s)</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Observations -->
    <div class="section" style="margin-top: 20px; padding-top: 10px; border-top: 1px solid #ccc;">
        <div class="section-title">OBSERVATIONS</div>
        <div style="font-size: 11px; padding: 8px;">
            @if(($total_usd_entrees - $total_usd_sorties) > 0)
                <p>✓ Excédent en USD de {{ number_format($total_usd_entrees - $total_usd_sorties, 2) }} $ sur la période.</p>
            @elseif(($total_usd_entrees - $total_usd_sorties) < 0)
                <p>⚠ Déficit en USD de {{ number_format(abs($total_usd_entrees - $total_usd_sorties), 2) }} $ sur la période.</p>
            @else
                <p>↔ Équilibre parfait en USD sur la période.</p>
            @endif
            
            @if(($total_cdf_entrees - $total_cdf_sorties) > 0)
                <p>✓ Excédent en CDF de {{ number_format($total_cdf_entrees - $total_cdf_sorties, 0) }} FC sur la période.</p>
            @elseif(($total_cdf_entrees - $total_cdf_sorties) < 0)
                <p>⚠ Déficit en CDF de {{ number_format(abs($total_cdf_entrees - $total_cdf_sorties), 0) }} FC sur la période.</p>
            @else
                <p>↔ Équilibre parfait en CDF sur la période.</p>
            @endif
            
            <p>📊 Taux d'activité : {{ number_format(($count_total / max($periode_jours, 1)) * 100, 1) }}% ({{ $count_total }} opérations sur {{ $periode_jours ?? 'plusieurs' }} jour(s))</p>
        </div>
    </div>

    <!-- Signatures -->
    <div class="signature-section">
        <div class="signature" style="text-align: left;">
            Le Responsable Coffres
        </div>
        <div class="signature" style="text-align: right;">
            Le Comptable
        </div>
    </div>

    <!-- Pied de page -->
    <div class="footer">
        <div>Rapport généré automatiquement par le Système de Gestion des Coffres Tumaini Letu</div>
        <div>Document confidentiel - {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</div>
    </div>
</body>
</html>