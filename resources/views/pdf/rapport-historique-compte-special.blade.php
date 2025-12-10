<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport Historique Compte Spécial - Tumaini Letu</title>
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
        .mouvement-depot { font-weight: bold; }
        .mouvement-retrait { font-weight: bold; }
        
        /* Nouvelles classes pour les montants */
        .entree { color: #28a745; font-weight: bold; }
        .sortie { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <!-- En-tête Tumaini Letu avec logo -->
    <div class="header">
        <div class="logo">
            @if(isset($rapport['logo_base64']) && !empty($rapport['logo_base64']))
                <img src="data:image/png;base64,{{ $rapport['logo_base64'] }}" alt="TUMAINI LETU asbl" style="height: 70px; max-width: 140px; object-fit: contain;">
            @else
                <div style="height: 70px; width: 140px; background-color: #0066cc; color: white; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 5px;">
                    <div style="font-weight: bold; font-size: 14px; text-align: center;">TUMAINI LETU</div>
                    <div style="font-size: 10px; text-align: center;">ASBL</div>
                </div>
            @endif
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
        <div>N/REF : RAPP-HIST-{{ \Carbon\Carbon::now()->format('Ymd-His') }}</div>
        <div>Période : {{ \Carbon\Carbon::parse($rapport['date_debut'])->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($rapport['date_fin'])->format('d/m/Y') }}</div>
        <div>Généré le : {{ $rapport['date_generation'] }}</div>
    </div>

    <div class="separator"></div>

    <!-- Titre du rapport -->
    <div class="section">
        <div style="text-align: center; margin-bottom: 15px;">
            <h2 style="font-size: 16px; font-weight: bold; color: #000;">RAPPORT HISTORIQUE COMPTE SPÉCIAL TUMAINI</h2>
            <p style="font-size: 12px; color: #000;">Synthèse des opérations du compte spécial</p>
        </div>
    </div>

    <!-- Synthèse générale -->
    @if($rapport['inclure_synthese'])
    <div class="section">
        <div class="section-title">SYNTHÈSE GÉNÉRALE</div>
        
        <!-- Afficher les statistiques PAR DEVISE SÉPARÉMENT -->
        @foreach($rapport['stats']['par_devise'] as $devise => $stat)
        <div style="margin-bottom: 15px;">
            <h3 style="font-size: 13px; margin-bottom: 8px; color: #333;">DEVISE : {{ $devise }}</h3>
            
            <div class="totals-grid">
                <div class="total-card">
                    <div class="total-label">TOTAL ENTREES {{ $devise }}</div>
                    <div class="total-value entree montant">{{ number_format($stat['entrees'], 2) }} {{ $devise == 'USD' ? '$' : 'FC' }}</div>
                </div>
                <div class="total-card">
                    <div class="total-label">TOTAL SORTIES {{ $devise }}</div>
                    <div class="total-value sortie montant">{{ number_format($stat['sorties'], 2) }} {{ $devise == 'USD' ? '$' : 'FC' }}</div>
                </div>
                <div class="total-card">
                    <div class="total-label">SOLDE FINAL {{ $devise }}</div>
                    <div class="total-value montant {{ $stat['solde'] >= 0 ? 'entree' : 'sortie' }}">
                        {{ number_format($stat['solde'], 2) }} {{ $devise == 'USD' ? '$' : 'FC' }}
                    </div>
                </div>
                <div class="total-card">
                    <div class="total-label">OPÉRATIONS {{ $devise }}</div>
                    <div class="total-value">{{ $stat['operations'] }}</div>
                </div>
            </div>
            
            <!-- Tableau détaillé par devise -->
            <table class="table" style="margin-top: 10px;">
                <thead>
                    <tr>
                        <th style="width: 30%">Statistique</th>
                        <th style="width: 35%" class="text-right">Valeur</th>
                        <th style="width: 35%">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Total des Entrées</td>
                        <td class="text-right entree montant">{{ number_format($stat['entrees'], 2) }} {{ $devise == 'USD' ? '$' : 'FC' }}</td>
                        <td>Somme de tous les montants positifs en {{ $devise }}</td>
                    </tr>
                    <tr>
                        <td>Total des Sorties</td>
                        <td class="text-right sortie montant">{{ number_format($stat['sorties'], 2) }} {{ $devise == 'USD' ? '$' : 'FC' }}</td>
                        <td>Somme de tous les montants négatifs en {{ $devise }}</td>
                    </tr>
                    <tr class="total-row">
                        <td><strong>Solde Final en {{ $devise }}</strong></td>
                        <td class="text-right montant {{ $stat['solde'] >= 0 ? 'entree' : 'sortie' }}">
                            <strong>{{ number_format($stat['solde'], 2) }} {{ $devise == 'USD' ? '$' : 'FC' }}</strong>
                        </td>
                        <td>Entrées - Sorties en {{ $devise }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="separator"></div>
        @endforeach
    </div>
    @endif

    <!-- Détail des opérations -->
    @if($rapport['inclure_details'])
    <div class="section">
        <div class="section-title">DÉTAIL DES OPÉRATIONS ({{ $rapport['nombre_operations'] }} opérations)</div>
        
        <!-- Afficher les opérations regroupées par devise -->
        @foreach($rapport['stats']['par_devise'] as $devise => $stat)
        <div style="margin-bottom: 20px;">
            <h3 style="font-size: 13px; margin-bottom: 8px; color: #333; background-color: #f5f5f5; padding: 5px;">
                DEVISE : {{ $devise }} ({{ $stat['operations'] }} opérations)
            </h3>
            
            @if(!empty($stat['liste_operations']))
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 15%">Date</th>
                        <th style="width: 20%">Membre/Client</th>
                        <th style="width: 10%">Type</th>
                        <th style="width: 15%" class="text-right">Montant</th>
                        <th style="width: 40%">Description</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stat['liste_operations'] as $operation)
                    @php
                        $type = $operation['montant'] >= 0 ? 'ENTRÉE' : 'SORTIE';
                        $typeClass = $operation['montant'] >= 0 ? 'entree' : 'sortie';
                        $montantFormate = number_format(abs($operation['montant']), 2);
                        $description = !empty($operation['description']) ? $operation['description'] : 'Première mise';
                    @endphp
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($operation['date'])->format('d/m/Y H:i') }}</td>
                        <td>{{ $operation['client'] ?? 'Non spécifié' }}</td>
                        <td class="{{ $typeClass }}"><strong>{{ $type }}</strong></td>
                        <td class="text-right montant {{ $typeClass }}">{{ $montantFormate }} {{ $devise == 'USD' ? '$' : 'FC' }}</td>
                        <td>{{ $description }}</td>
                    </tr>
                    @endforeach
                    
                    <!-- Total par devise -->
                    <tr class="total-row">
                        <td colspan="3"><strong>TOTAL {{ $devise }}</strong></td>
                        <td class="text-right montant {{ $stat['solde'] >= 0 ? 'entree' : 'sortie' }}">
                            <strong>{{ number_format($stat['solde'], 2) }} {{ $devise == 'USD' ? '$' : 'FC' }}</strong>
                        </td>
                        <td><strong>{{ $stat['operations'] }} opérations</strong></td>
                    </tr>
                </tbody>
            </table>
            @else
            <p style="text-align: center; padding: 10px; background-color: #f9f9f9; border: 1px solid #ddd;">
                Aucune opération en {{ $devise }} pour cette période.
            </p>
            @endif
        </div>
        @endforeach
        
        <!-- Total général (informations seulement, pas de somme) -->
        <div style="margin-top: 15px; padding: 10px; background-color: #f0f0f0; border: 1px solid #000;">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 60%;"><strong>TOTAL GÉNÉRAL</strong></td>
                    {{-- <td style="width: 40%; text-align: right;">
                        <strong>⚠️ Les montants en devises différentes ne sont pas additionnés</strong>
                    </td> --}}
                </tr>
                @foreach($rapport['stats']['par_devise'] as $devise => $stat)
                <tr>
                    <td>Total en {{ $devise }} :</td>
                    <td class="text-right montant {{ $stat['solde'] >= 0 ? 'entree' : 'sortie' }}">
                        {{ number_format($stat['solde'], 2) }} {{ $devise == 'USD' ? '$' : 'FC' }}
                    </td>
                </tr>
                @endforeach
                <tr>
                    <td><strong>Nombre total d'opérations :</strong></td>
                    <td class="text-right"><strong>{{ $rapport['nombre_operations'] }}</strong></td>
                </tr>
            </table>
        </div>
    </div>
    @endif

    <div class="separator"></div>

    <!-- Informations de génération -->
    <div style="margin-bottom: 20px; font-size: 10px; color: #666;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%;">
                    <strong>Informations de génération :</strong><br>
                    Généré par : {{ $rapport['generateur'] }}<br>
                    Date de génération : {{ $rapport['date_generation'] }}<br>
                    Période couverte : {{ \Carbon\Carbon::parse($rapport['date_debut'])->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($rapport['date_fin'])->format('d/m/Y') }}
                </td>
                <td style="width: 50%; text-align: right;">
                    <strong>Référence :</strong> RAPP-HIST-{{ \Carbon\Carbon::now()->format('Ymd-His') }}<br>
                    <strong>Type :</strong> Rapport Historique Compte Spécial<br>
                    <strong>Statut :</strong> Document officiel
                </td>
            </tr>
        </table>
    </div>

    <!-- Signatures -->
    <div class="signature-section">
        <div class="signature" style="text-align: left;">
           le  Gérant<br>
            TUMAINI LETU asbl
        </div>
        {{-- <div class="signature" style="text-align: right;">
            Le Responsable Administratif<br>
            TUMAINI LETU asbl
        </div> --}}
    </div>

    <!-- Pied de page -->
    <div class="footer">
        <div>Rapport généré automatiquement par le Système de Gestion TUMAINI LETU</div>
        <div>Document confidentiel - {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</div>
    </div>
</body>
</html>