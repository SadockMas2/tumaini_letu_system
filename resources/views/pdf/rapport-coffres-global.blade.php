<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport Global des Coffres - Tumaini Letu</title>
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
        .devise-usd { font-weight: bold; }
        .devise-cdf { font-weight: bold; }
        .entree { color: #28a745; font-weight: bold; }
        .sortie { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <!-- En-tête Tumaini Letu avec logo en base64 -->
    <div class="header">
        <div class="logo">
            <img src="{{ $rapport['logo_base64'] ?? '' }}" alt="TUMAINI LETU asbl" style="height: 70px; max-width: 140px; object-fit: contain;">
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
        <div>N/REF : RAPP-COFFRES-{{ \Carbon\Carbon::now()->format('Ymd-His') }}</div>
        <div>Date : {{ $rapport['date_rapport'] }}</div>
        <div>Généré le : {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</div>
    </div>

    <div class="separator"></div>

    <!-- Titre du rapport -->
    <div class="section">
        <div style="text-align: center; margin-bottom: 15px;">
            <h2 style="font-size: 16px; font-weight: bold; color: #000;">RAPPORT GLOBAL DES COFFRES</h2>
            <p style="font-size: 12px; color: #000;">Synthèse de tous les coffres - {{ $rapport['total_coffres'] }} coffre(s)</p>
        </div>
    </div>

    <!-- Synthèse générale -->
    <div class="section">
        <div class="section-title">SYNTHÈSE GÉNÉRALE</div>
        <div class="totals-grid">
            <div class="total-card">
                <div class="total-label">TOTAL COFFRES USD</div>
                <div class="total-value devise-usd montant">{{ number_format($rapport['usd']['solde_total'], 2) }} $</div>
            </div>
            <div class="total-card">
                <div class="total-label">TOTAL COFFRES CDF</div>
                <div class="total-value devise-cdf montant">{{ number_format($rapport['cdf']['solde_total'], 2) }} FC</div>
            </div>
            <div class="total-card">
                <div class="total-label">ENTRÉES USD</div>
                <div class="total-value entree montant devise-usd">
                    {{ number_format($rapport['usd']['total_entrees'], 2) }} $
                </div>
            </div>
            <div class="total-card">
                <div class="total-label">SORTIES USD</div>
                <div class="total-value sortie montant devise-usd">
                    {{ number_format($rapport['usd']['total_sorties'], 2) }} $
                </div>
            </div>
            <div class="total-card">
                <div class="total-label">ENTRÉES CDF</div>
                <div class="total-value entree montant devise-cdf">
                    {{ number_format($rapport['cdf']['total_entrees'], 2) }} FC
                </div>
            </div>
            <div class="total-card">
                <div class="total-label">SORTIES CDF</div>
                <div class="total-value sortie montant devise-cdf">
                    {{ number_format($rapport['cdf']['total_sorties'], 2) }} FC
                </div>
            </div>
            <div class="total-card">
                <div class="total-label">COFFRES USD</div>
                <div class="total-value">{{ count($rapport['usd']['coffres']) }}</div>
            </div>
            <div class="total-card">
                <div class="total-label">COFFRES CDF</div>
                <div class="total-value">{{ count($rapport['cdf']['coffres']) }}</div>
            </div>
        </div>
    </div>

    <div class="separator"></div>

    <!-- Section USD -->
    @if(count($rapport['usd']['coffres']) > 0)
    <div class="section">
        <div class="section-title">COFFRES EN DOLLARS AMÉRICAINS (USD)</div>
        
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 25%">Coffre</th>
                    <th style="width: 15%">Responsable</th>
                    <th style="width: 12%" class="text-right">Solde Initial</th>
                    <th style="width: 12%" class="text-right">Solde Final</th>
                    <th style="width: 12%" class="text-right">Entrées</th>
                    <th style="width: 12%" class="text-right">Sorties</th>
                    <th style="width: 12%" class="text-center">Opérations</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rapport['usd']['coffres'] as $coffre)
                <tr>
                    <td>{{ $coffre['nom'] }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($coffre['responsable'], 15) }}</td>
                    <td class="text-right montant">{{ number_format($coffre['solde_initial'], 2) }}</td>
                    <td class="text-right montant">{{ number_format($coffre['solde_final'], 2) }}</td>
                    <td class="text-right entree montant">{{ number_format($coffre['entrees'], 2) }}</td>
                    <td class="text-right sortie montant">{{ number_format($coffre['sorties'], 2) }}</td>
                    <td class="text-center">{{ $coffre['operations'] }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2"><strong>TOTAUX USD</strong></td>
                    <td class="text-right">-</td>
                    <td class="text-right devise-usd montant"><strong>{{ number_format($rapport['usd']['solde_total'], 2) }}</strong></td>
                    <td class="text-right entree montant"><strong>{{ number_format($rapport['usd']['total_entrees'], 2) }}</strong></td>
                    <td class="text-right sortie montant"><strong>{{ number_format($rapport['usd']['total_sorties'], 2) }}</strong></td>
                    <td class="text-center"><strong>{{ array_sum(array_column($rapport['usd']['coffres'], 'operations')) }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="separator"></div>
    @endif

    <!-- Section CDF -->
    @if(count($rapport['cdf']['coffres']) > 0)
    <div class="section">
        <div class="section-title">COFFRES EN FRANCS CONGOLAIS (CDF)</div>
        
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 25%">Coffre</th>
                    <th style="width: 15%">Responsable</th>
                    <th style="width: 12%" class="text-right">Solde Initial</th>
                    <th style="width: 12%" class="text-right">Solde Final</th>
                    <th style="width: 12%" class="text-right">Entrées</th>
                    <th style="width: 12%" class="text-right">Sorties</th>
                    <th style="width: 12%" class="text-center">Opérations</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rapport['cdf']['coffres'] as $coffre)
                <tr>
                    <td>{{ $coffre['nom'] }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($coffre['responsable'], 15) }}</td>
                    <td class="text-right montant">{{ number_format($coffre['solde_initial'], 2) }}</td>
                    <td class="text-right montant">{{ number_format($coffre['solde_final'], 2) }}</td>
                    <td class="text-right entree montant">{{ number_format($coffre['entrees'], 2) }}</td>
                    <td class="text-right sortie montant">{{ number_format($coffre['sorties'], 2) }}</td>
                    <td class="text-center">{{ $coffre['operations'] }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2"><strong>TOTAUX CDF</strong></td>
                    <td class="text-right">-</td>
                    <td class="text-right devise-cdf montant"><strong>{{ number_format($rapport['cdf']['solde_total'], 2) }}</strong></td>
                    <td class="text-right entree montant"><strong>{{ number_format($rapport['cdf']['total_entrees'], 2) }}</strong></td>
                    <td class="text-right sortie montant"><strong>{{ number_format($rapport['cdf']['total_sorties'], 2) }}</strong></td>
                    <td class="text-center"><strong>{{ array_sum(array_column($rapport['cdf']['coffres'], 'operations')) }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="separator"></div>
    @endif

    <!-- Détail des mouvements (optionnel) -->
    @if($inclure_mouvements && count($rapport['mouvements_detail']) > 0)
    <div class="section">
        <div class="section-title">DÉTAIL DES MOUVEMENTS ({{ count($rapport['mouvements_detail']) }} opérations)</div>
        
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 8%">Heure</th>
                    <th style="width: 15%">Coffre</th>
                    <th style="width: 8%">Type</th>
                    <th style="width: 10%" class="text-right">Montant</th>
                    <th style="width: 25%">Description</th>
                    <th style="width: 15%">Source/Destination</th>
                    <th style="width: 10%">Référence</th>
                    <th style="width: 9%">Opérateur</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rapport['mouvements_detail'] as $mouvement)
                <tr>
                    <td>{{ $mouvement['heure'] }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($mouvement['coffre'], 15) }}</td>
                    <td>
                        @if($mouvement['type'] === 'depot')
                            <span class="entree">ENTRÉE</span>
                        @else
                            <span class="sortie">SORTIE</span>
                        @endif
                    </td>
                    <td class="text-right montant {{ $mouvement['type'] === 'depot' ? 'entree' : 'sortie' }}">
                        {{ number_format($mouvement['montant'], 2) }} {{ $mouvement['devise'] === 'USD' ? '$' : 'FC' }}
                    </td>
                    <td style="font-size: 9px;">{{ \Illuminate\Support\Str::limit($mouvement['description'], 35) }}</td>
                    <td style="font-size: 9px;">{{ \Illuminate\Support\Str::limit($mouvement['source_destination'], 15) }}</td>
                    <td style="font-size: 9px;">{{ \Illuminate\Support\Str::limit($mouvement['reference'], 12) }}</td>
                    <td style="font-size: 9px;">{{ \Illuminate\Support\Str::limit($mouvement['operateur'], 10) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="separator"></div>

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