<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport Classe 6 - Charges - Tumaini Letu</title>
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

        .devise-usd { color: #1e40af; }
        .devise-cdf { color: #dc2626; }
        
        .type-charge {
            padding: 4px 8px;
            margin: 4px 0;
            background-color: #f0f9ff;
            border-left: 4px solid #3b82f6;
            font-weight: bold;
        }
        
        .compte-section {
            margin: 8px 0 12px 0;
            padding: 6px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        
        .progress-bar {
            height: 8px;
            background-color: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin: 4px 0;
        }
        
        .progress-fill {
            height: 100%;
            background-color: #3b82f6;
        }
        
        .charge-danger { border-left-color: #ef4444; background-color: #fef2f2; }
        .charge-warning { border-left-color: #f59e0b; background-color: #fffbeb; }
        .charge-success { border-left-color: #10b981; background-color: #f0fdf4; }
        .charge-info { border-left-color: #0ea5e9; background-color: #f0f9ff; }
        
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            margin-left: 4px;
        }
        
        .badge-danger { background-color: #ef4444; color: white; }
        .badge-warning { background-color: #f59e0b; color: white; }
        .badge-success { background-color: #10b981; color: white; }
        .badge-info { background-color: #0ea5e9; color: white; }
        
        .chart-container {
            height: 200px;
            margin: 10px 0;
            position: relative;
        }
        
        .distribution-day {
            display: flex;
            align-items: center;
            margin: 4px 0;
            padding: 4px;
            background-color: #f8fafc;
            border-radius: 4px;
        }
        
        .day-bar {
            flex-grow: 1;
            height: 20px;
            background-color: #3b82f6;
            margin: 0 8px;
            border-radius: 3px;
            position: relative;
        }
        
        .day-value {
            font-weight: bold;
            min-width: 80px;
            text-align: right;
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
            <div>Email : tumainiletu@gmail.com</div>
        </div>
    </div>

    <div class="separator"></div>

    <!-- Informations du rapport -->
    <div class="ref-date">
        <div>N/REF : CLASSE6-{{ \Carbon\Carbon::now()->format('Ymd-His') }}</div>
        <div>Période : {{ $rapport['periode']['debut'] }} au {{ $rapport['periode']['fin'] }}</div>
        <div>Généré le : {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</div>
    </div>

    <div class="separator"></div>

    <!-- Titre du rapport -->
    <div class="section">
        <div style="text-align: center; margin-bottom: 15px;">
            <h2 style="font-size: 16px; font-weight: bold; color: #000;">RAPPORT DÉTAILLÉ DES CHARGES</h2>
            <p style="font-size: 12px; color: #000;">Classe 6 - Période: {{ $rapport['periode']['jours'] }} jours</p>
        </div>
    </div>

    <!-- Synthèse des totaux -->
    <div class="section">
        <div class="section-title">SYNTHÈSE DES CHARGES</div>
        <div class="totals-grid">
            <div class="total-card">
                <div class="total-label">TOTAL CHARGES USD</div>
                <div class="total-value devise-usd montant">{{ number_format($rapport['totaux_generaux']['total_usd'], 2) }} $</div>
            </div>
            <div class="total-card">
                <div class="total-label">TOTAL CHARGES CDF</div>
                <div class="total-value devise-cdf montant">{{ number_format($rapport['totaux_generaux']['total_cdf'], 2) }} FC</div>
            </div>
            <div class="total-card">
                <div class="total-label">OPÉRATIONS</div>
                <div class="total-value">{{ number_format($rapport['totaux_generaux']['total_operations'], 0) }}</div>
            </div>
            <div class="total-card">
                <div class="total-label">CATÉGORIES</div>
                <div class="total-value">{{ $rapport['totaux_generaux']['nombre_types_charges'] }}</div>
            </div>
        </div>
    </div>

    <div class="separator"></div>

    <!-- Distribution des charges par type -->
    <div class="section">
        <div class="section-title">DISTRIBUTION PAR TYPE DE CHARGE</div>
        
        @foreach($rapport['charges_par_type'] as $type)
        <div class="type-charge {{ $type['total_usd'] + $type['total_cdf'] > 10000 ? 'charge-danger' : ($type['total_usd'] + $type['total_cdf'] > 5000 ? 'charge-warning' : 'charge-info') }}">
            {{ $type['type'] }}
            <span class="badge {{ $type['total_usd'] + $type['total_cdf'] > 10000 ? 'badge-danger' : ($type['total_usd'] + $type['total_cdf'] > 5000 ? 'badge-warning' : 'badge-info') }}">
                {{ number_format($type['total_usd'] + $type['total_cdf'], 2) }}
            </span>
        </div>
        
        <div style="margin-left: 20px;">
            <!-- Totaux par devise -->
            <div style="display: flex; justify-content: space-between; margin: 4px 0;">
                <span>USD: <strong class="devise-usd">{{ number_format($type['total_usd'], 2) }} $</strong></span>
                <span>{{ number_format($type['pourcentage_usd'], 1) }}%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: {{ $type['pourcentage_usd'] }}%"></div>
            </div>
            
            <div style="display: flex; justify-content: space-between; margin: 4px 0;">
                <span>CDF: <strong class="devise-cdf">{{ number_format($type['total_cdf'], 2) }} FC</strong></span>
                <span>{{ number_format($type['pourcentage_cdf'], 1) }}%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: {{ $type['pourcentage_cdf'] }}%; background-color: #dc2626;"></div>
            </div>
            
            <!-- Détail par compte -->
            @if($detail_niveau !== 'synthese')
                @foreach($type['comptes'] as $compte)
                <div class="compte-section">
                    <div style="font-weight: bold; margin-bottom: 4px;">
                        {{ $compte['compte_number'] }} - {{ $compte['libelle'] }}
                        <span class="badge badge-info">{{ $compte['nombre_operations'] }} op.</span>
                    </div>
                    
                    <div style="font-size: 9px; color: #666;">
                        USD: {{ number_format($compte['total_usd'], 2) }} $ | 
                        CDF: {{ number_format($compte['total_cdf'], 2) }} FC
                    </div>
                    
                    <!-- Opérations détaillées -->
                    @if($detail_niveau === 'complet' && !empty($compte['operations']))
                    <div style="margin-top: 8px; font-size: 9px;">
                        <table class="table" style="font-size: 8px;">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Référence</th>
                                    <th>Libellé</th>
                                    <th class="text-right">Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($compte['operations'] as $operation)
                                <tr>
                                    <td>{{ $operation['date'] }}</td>
                                    <td>{{ $operation['reference'] }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($operation['libelle'], 40) }}</td>
                                    <td class="text-right {{ $operation['devise'] === 'USD' ? 'devise-usd' : 'devise-cdf' }}">
                                        {{ number_format($operation['montant'], 2) }} {{ $operation['devise'] }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
                @endforeach
            @endif
        </div>
        @endforeach
        
        <!-- Total général -->
        <div class="total-row" style="margin-top: 15px;">
            <table class="table">
                <tr>
                    <td><strong>TOTAL GÉNÉRAL CHARGES</strong></td>
                    <td class="text-right devise-usd montant"><strong>{{ number_format($rapport['totaux_generaux']['total_usd'], 2) }} $</strong></td>
                    <td class="text-right devise-cdf montant"><strong>{{ number_format($rapport['totaux_generaux']['total_cdf'], 2) }} FC</strong></td>
                    <td class="text-center"><strong>{{ $rapport['totaux_generaux']['total_operations'] }} opérations</strong></td>
                </tr>
            </table>
        </div>
    </div>

    {{-- <div class="separator"></div> --}}

    {{-- <!-- Distribution périodique -->
    @if(!empty($rapport['distribution_periodique']))
    <div class="section">
        <div class="section-title">ÉVOLUTION QUOTIDIENNE</div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Jour</th>
                    <th class="text-right">USD</th>
                    <th class="text-right">CDF</th>
                    <th class="text-center">Opérations</th>
                    <th class="text-center">Moyenne</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rapport['distribution_periodique'] as $jour)
                <tr>
                    <td>{{ $jour['date'] }}</td>
                    <td>{{ $jour['jour_semaine'] }}</td>
                    <td class="text-right devise-usd montant">{{ number_format($jour['total_usd'], 2) }}</td>
                    <td class="text-right devise-cdf montant">{{ number_format($jour['total_cdf'], 2) }}</td>
                    <td class="text-center">{{ $jour['operations'] }}</td>
                    <td class="text-center">
                        @if($jour['operations'] > 0)
                        {{ number_format($jour['moyenne_usd'], 2) }}$ / {{ number_format($jour['moyenne_cdf'], 2) }}FC
                        @else
                        -
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif --}}

    <!-- Top 10 des opérations -->
    {{-- @if(!empty($rapport['top_operations']))
    <div class="section">
        <div class="section-title">TOP 10 DES OPÉRATIONS</div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Compte</th>
                    <th>Libellé</th>
                    <th class="text-right">Montant</th>
                    <th>Type</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rapport['top_operations'] as $operation)
                <tr>
                    <td>{{ $operation['date'] }}</td>
                    <td>{{ $operation['compte'] }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($operation['libelle'], 30) }}</td>
                    <td class="text-right {{ $operation['devise'] === 'USD' ? 'devise-usd' : 'devise-cdf' }}">
                        {{ number_format($operation['montant'], 2) }} {{ $operation['devise'] }}
                    </td>
                    <td>{{ $operation['type'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif --}}

    {{-- <div class="separator"></div> --}}

    <!-- Signatures -->
    <div class="signature-section">
        <div class="signature" style="text-align: left;">
            Le Comptable
        </div>
        <div class="signature" style="text-align: right;">
            Le Gérant
        </div>
    </div>

    <!-- Pied de page -->
    <div class="footer">
        <div>Rapport généré automatiquement par le Système de Gestion Comptable Tumaini Letu</div>
        <div>Document confidentiel - Classe 6 Charges - {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</div>
    </div>
</body>
</html>