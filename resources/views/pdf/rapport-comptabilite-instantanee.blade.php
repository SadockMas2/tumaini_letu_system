<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport Comptabilité - Tumaini Letu</title>
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

        .separator {
            border-top: 2px solid #000;
            margin: 12px 0;
        }

        .ref-date {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-weight: bold;
            font-size: 11px;
        }

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

        .total-row {
            background-color: #e8e8e8 !important;
            font-weight: bold;
            border-top: 2px solid #000;
        }

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

        .footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #000;
            text-align: center;
            color: #000;
            font-size: 9px;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .montant { font-family: 'Courier New', monospace; }

        .entree { color: #059669; font-weight: bold; }
        .sortie { color: #dc2626; font-weight: bold; }
        .depot { color: #059669; font-weight: bold; }
        .retrait { color: #dc2626; font-weight: bold; }

        .progress-bar {
            background-color: #e5e7eb;
            border-radius: 3px;
            height: 8px;
            margin-top: 2px;
        }
        .progress-fill {
            background-color: #3b82f6;
            height: 100%;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <!-- En-tête -->
    <div class="header">
        <div class="logo">
            <img src="{{ $rapport['logo_base64'] }}" alt="TUMAINI LETU asbl">
        </div>
        <div class="header-info">
            <div class="institution-name">Tumaini Letu asbl</div>
            <div>Siège social 005, avenue du port, quartier les volcans - Goma - Rd Congo</div>
            <div>NUM BED : 14453756111</div>
            <div>Tel : +243982618321</div>
            <div>Email : tumailetu@gmail.com</div>
        </div>
    </div>

    <div class="separator"></div>

    <!-- Informations du rapport -->
    <div class="ref-date">
        <div>N/REF : COMPTA-{{ \Carbon\Carbon::now()->format('Ymd-His') }}</div>
        <div>Date : {{ $rapport['date_rapport'] }}</div>
        <div>Généré le : {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</div>
    </div>

    <div class="separator"></div>

    <!-- Titre du rapport -->
    <div class="section">
        <div style="text-align: center; margin-bottom: 15px;">
            <h2 style="font-size: 16px; font-weight: bold; color: #000;">RAPPORT COMPTABILITÉ INSTANTANÉ</h2>
            <p style="font-size: 12px; color: #000;">Gestion des petites caisses et mouvements de transit</p>
        </div>
    </div>

    <!-- Synthèse des soldes -->
    <div class="section">
        <div class="section-title">SYNTHÈSE DES SOLDES</div>
        <div class="totals-grid">
            <div class="total-card">
                <div class="total-label">TRANSIT USD</div>
                <div class="total-value montant">{{ number_format($rapport['soldes_comptes']['transit_usd'], 2) }} $</div>
            </div>
            <div class="total-card">
                <div class="total-label">TRANSIT CDF</div>
                <div class="total-value montant">{{ number_format($rapport['soldes_comptes']['transit_cdf'], 2) }} FC</div>
            </div>
            <div class="total-card">
                <div class="total-label">PETITE CAISSE USD</div>
                <div class="total-value montant">{{ number_format($rapport['soldes_comptes']['petite_caisse_usd'], 2) }} $</div>
            </div>
            <div class="total-card">
                <div class="total-label">PETITE CAISSE CDF</div>
                <div class="total-value montant">{{ number_format($rapport['soldes_comptes']['petite_caisse_cdf'], 2) }} FC</div>
            </div>
        </div>
    </div>

    <div class="separator"></div>

    <!-- Section Petites Caisses USD -->
    <div class="section">
        <div class="section-title">PETITES CAISSES - DOLLARS AMÉRICAINS (USD)</div>
        
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 25%">Caisse</th>
                    <th style="width: 15%" class="text-right">Solde Actuel</th>
                    <th style="width: 15%" class="text-right">Plafond</th>
                    <th style="width: 15%" class="text-right">Dépôts</th>
                    <th style="width: 15%" class="text-right">Retraits</th>
                    <th style="width: 15%" class="text-center">Utilisation</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rapport['petites_caisses']['usd']['caisses'] as $caisse)
                <tr>
                    <td>{{ $caisse['nom'] }}</td>
                    <td class="text-right montant">{{ number_format($caisse['solde_actuel'], 2) }}</td>
                    <td class="text-right montant">{{ number_format($caisse['plafond'], 2) }}</td>
                    <td class="text-right depot montant">{{ number_format($caisse['depots'], 2) }}</td>
                    <td class="text-right retrait montant">{{ number_format($caisse['retraits'], 2) }}</td>
                    <td class="text-center">
                        {{ number_format($caisse['pourcentage_utilisation'], 1) }}%
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ min($caisse['pourcentage_utilisation'], 100) }}%"></div>
                        </div>
                    </td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td><strong>TOTAUX USD</strong></td>
                    <td class="text-right montant"><strong>{{ number_format($rapport['petites_caisses']['usd']['solde_total'], 2) }}</strong></td>
                    <td class="text-right">-</td>
                    <td class="text-right depot montant"><strong>{{ number_format($rapport['petites_caisses']['usd']['depots'], 2) }}</strong></td>
                    <td class="text-right retrait montant"><strong>{{ number_format($rapport['petites_caisses']['usd']['retraits'], 2) }}</strong></td>
                    <td class="text-center"><strong>{{ $rapport['petites_caisses']['usd']['operations'] }} ops</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="separator"></div>

    <!-- Section Petites Caisses CDF -->
    <div class="section">
        <div class="section-title">PETITES CAISSES - FRANCS CONGOLAIS (CDF)</div>
        
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 25%">Caisse</th>
                    <th style="width: 15%" class="text-right">Solde Actuel</th>
                    <th style="width: 15%" class="text-right">Plafond</th>
                    <th style="width: 15%" class="text-right">Dépôts</th>
                    <th style="width: 15%" class="text-right">Retraits</th>
                    <th style="width: 15%" class="text-center">Utilisation</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rapport['petites_caisses']['cdf']['caisses'] as $caisse)
                <tr>
                    <td>{{ $caisse['nom'] }}</td>
                    <td class="text-right montant">{{ number_format($caisse['solde_actuel'], 2) }}</td>
                    <td class="text-right montant">{{ number_format($caisse['plafond'], 2) }}</td>
                    <td class="text-right depot montant">{{ number_format($caisse['depots'], 2) }}</td>
                    <td class="text-right retrait montant">{{ number_format($caisse['retraits'], 2) }}</td>
                    <td class="text-center">
                        {{ number_format($caisse['pourcentage_utilisation'], 1) }}%
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ min($caisse['pourcentage_utilisation'], 100) }}%"></div>
                        </div>
                    </td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td><strong>TOTAUX CDF</strong></td>
                    <td class="text-right montant"><strong>{{ number_format($rapport['petites_caisses']['cdf']['solde_total'], 2) }}</strong></td>
                    <td class="text-right">-</td>
                    <td class="text-right depot montant"><strong>{{ number_format($rapport['petites_caisses']['cdf']['depots'], 2) }}</strong></td>
                    <td class="text-right retrait montant"><strong>{{ number_format($rapport['petites_caisses']['cdf']['retraits'], 2) }}</strong></td>
                    <td class="text-center"><strong>{{ $rapport['petites_caisses']['cdf']['operations'] }} ops</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="separator"></div>

    <!-- Section Mouvements de Transit -->
    <div class="section">
        <div class="section-title">MOUVEMENTS DE TRANSIT (COMPTE 511100)</div>
        
        <!-- Sous-section USD -->
        <div style="margin-bottom: 20px;">
            <h4 style="margin-bottom: 8px; color: #000;">DEVISE USD</h4>
            <div class="totals-grid" style="margin-bottom: 10px;">
                <div class="total-card">
                    <div class="total-label">ENTRÉES USD</div>
                    <div class="total-value entree montant">{{ number_format($rapport['transit']['usd']['entrees'], 2) }} $</div>
                </div>
                <div class="total-card">
                    <div class="total-label">SORTIES USD</div>
                    <div class="total-value sortie montant">{{ number_format($rapport['transit']['usd']['sorties'], 2) }} $</div>
                </div>
                <div class="total-card">
                    <div class="total-label">SOLDE USD</div>
                    <div class="total-value montant">{{ number_format($rapport['transit']['usd']['solde'], 2) }} $</div>
                </div>
                <div class="total-card">
                    <div class="total-label">OPÉRATIONS</div>
                    <div class="total-value">{{ count($rapport['transit']['usd']['mouvements']) }}</div>
                </div>
            </div>

            @if(count($rapport['transit']['usd']['mouvements']) > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 10%">Heure</th>
                        <th style="width: 15%">Type</th>
                        <th style="width: 20%">Opération</th>
                        <th style="width: 30%">Libellé</th>
                        <th style="width: 15%" class="text-right">Montant</th>
                        <th style="width: 10%">Opérateur</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rapport['transit']['usd']['mouvements'] as $mouvement)
                    <tr>
                        <td>{{ $mouvement['date_heure'] }}</td>
                        <td>
                            @if($mouvement['type'] === 'entree')
                                <span class="entree">ENTRÉE</span>
                            @else
                                <span class="sortie">SORTIE</span>
                            @endif
                        </td>
                        <td>{{ $mouvement['type_operation'] }}</td>
                        <td style="font-size: 9px;">{{ \Illuminate\Support\Str::limit($mouvement['libelle'], 50) }}</td>
                        <td class="text-right montant {{ $mouvement['type'] === 'entree' ? 'entree' : 'sortie' }}">
                            {{ number_format($mouvement['montant'], 2) }}
                        </td>
                        <td style="font-size: 9px;">{{ \Illuminate\Support\Str::limit($mouvement['operateur'], 12) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <p style="text-align: center; color: #666; font-style: italic; padding: 10px;">Aucun mouvement USD aujourd'hui</p>
            @endif
        </div>

        <!-- Sous-section CDF -->
        <div>
            <h4 style="margin-bottom: 8px; color: #000;">DEVISE CDF</h4>
            <div class="totals-grid" style="margin-bottom: 10px;">
                <div class="total-card">
                    <div class="total-label">ENTRÉES CDF</div>
                    <div class="total-value entree montant">{{ number_format($rapport['transit']['cdf']['entrees'], 2) }} FC</div>
                </div>
                <div class="total-card">
                    <div class="total-label">SORTIES CDF</div>
                    <div class="total-value sortie montant">{{ number_format($rapport['transit']['cdf']['sorties'], 2) }} FC</div>
                </div>
                <div class="total-card">
                    <div class="total-label">SOLDE CDF</div>
                    <div class="total-value montant">{{ number_format($rapport['transit']['cdf']['solde'], 2) }} FC</div>
                </div>
                <div class="total-card">
                    <div class="total-label">OPÉRATIONS</div>
                    <div class="total-value">{{ count($rapport['transit']['cdf']['mouvements']) }}</div>
                </div>
            </div>

            @if(count($rapport['transit']['cdf']['mouvements']) > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 10%">Heure</th>
                        <th style="width: 15%">Type</th>
                        <th style="width: 20%">Opération</th>
                        <th style="width: 30%">Libellé</th>
                        <th style="width: 15%" class="text-right">Montant</th>
                        <th style="width: 10%">Opérateur</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rapport['transit']['cdf']['mouvements'] as $mouvement)
                    <tr>
                        <td>{{ $mouvement['date_heure'] }}</td>
                        <td>
                            @if($mouvement['type'] === 'entree')
                                <span class="entree">ENTRÉE</span>
                            @else
                                <span class="sortie">SORTIE</span>
                            @endif
                        </td>
                        <td>{{ $mouvement['type_operation'] }}</td>
                        <td style="font-size: 9px;">{{ \Illuminate\Support\Str::limit($mouvement['libelle'], 50) }}</td>
                        <td class="text-right montant {{ $mouvement['type'] === 'entree' ? 'entree' : 'sortie' }}">
                            {{ number_format($mouvement['montant'], 2) }}
                        </td>
                        <td style="font-size: 9px;">{{ \Illuminate\Support\Str::limit($mouvement['operateur'], 12) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <p style="text-align: center; color: #666; font-style: italic; padding: 10px;">Aucun mouvement CDF aujourd'hui</p>
            @endif
        </div>
    </div>

    <div class="separator"></div>

    <!-- Synthèse finale -->
    <div class="section">
        <div class="section-title">SYNTHÈSE GÉNÉRALE</div>
        <div class="totals-grid">
            <div class="total-card">
                <div class="total-label">TOTAL ENTRÉES</div>
                <div class="total-value entree montant">{{ number_format($rapport['synthese']['total_entrees'], 2) }}</div>
            </div>
            <div class="total-card">
                <div class="total-label">TOTAL SORTIES</div>
                <div class="total-value sortie montant">{{ number_format($rapport['synthese']['total_sorties'], 2) }}</div>
            </div>
            <div class="total-card">
                <div class="total-label">SOLDE NET</div>
                <div class="total-value montant">{{ number_format($rapport['synthese']['solde_net'], 2) }}</div>
            </div>
            <div class="total-card">
                <div class="total-label">FLUX NET</div>
                <div class="total-value {{ $rapport['synthese']['solde_net'] >= 0 ? 'entree' : 'sortie' }}">
                    {{ $rapport['synthese']['solde_net'] >= 0 ? 'POSITIF' : 'NÉGATIF' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Signatures -->
    <div class="signature-section">
        <div class="signature" style="text-align: left;">
            Le Comptable
        </div>
        <div class="signature" style="text-align: right;">
            Le Directeur Financier
        </div>
    </div>

    <!-- Pied de page -->
    <div class="footer">
        <div>Rapport généré automatiquement par le Système de Gestion Comptable Tumaini Letu</div>
        <div>Document confidentiel - {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</div>
    </div>
</body>
</html>