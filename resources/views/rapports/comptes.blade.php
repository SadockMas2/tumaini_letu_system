<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport des Comptes - Tumaini Letu</title>
    <style>
        /* Style identique à l'exemple fourni */
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
        .devise-usd { font-weight: bold; }
        .devise-cdf { font-weight: bold; }
        
        /* Couleurs pour les statuts */
        .statut-actif { color: #28a745; font-weight: bold; }
        .statut-inactif { color: #dc3545; }
        .statut-suspendu { color: #ffc107; }
        
        /* Badges pour type de compte */
        .badge-individuel {
            background-color: #e3f2fd;
            color: #1976d2;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
        }
        .badge-groupe {
            background-color: #e8f5e9;
            color: #388e3c;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <!-- En-tête Tumaini Letu -->
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
        <div>N/REF : RAPP-COMPTES-{{ \Carbon\Carbon::now()->format('Ymd-His') }}</div>
        <div>Date : {{ $rapport['date_rapport'] }}</div>
        <div>Heure : {{ $rapport['heure_generation'] }}</div>
    </div>

    <div class="separator"></div>

    <!-- Titre du rapport -->
    <div class="section">
        <div style="text-align: center; margin-bottom: 15px;">
            <h2 style="font-size: 16px; font-weight: bold; color: #000;">RAPPORT DES COMPTES ET SOLDES</h2>
            <p style="font-size: 12px; color: #000;">État instantané de tous les comptes</p>
        </div>
    </div>

    <!-- Synthèse générale -->
    <div class="section">
        <div class="section-title">SYNTHÈSE GÉNÉRALE</div>
        <div class="totals-grid">
            <div class="total-card">
                <div class="total-label">TOTAL COMPTES</div>
                <div class="total-value">{{ $rapport['nombre_total_comptes'] }}</div>
            </div>
            <div class="total-card">
                <div class="total-label">SOLDE TOTAL USD</div>
                <div class="total-value devise-usd montant">{{ number_format($rapport['totaux']['usd']['solde_total'], 2) }} $</div>
            </div>
            <div class="total-card">
                <div class="total-label">SOLDE TOTAL CDF</div>
                <div class="total-value devise-cdf montant">{{ number_format($rapport['totaux']['cdf']['solde_total'], 2) }} FC</div>
            </div>
            <div class="total-card">
                <div class="total-label">COMPTES USD</div>
                <div class="total-value">{{ $rapport['totaux']['usd']['nombre_comptes'] }}</div>
            </div>
            <div class="total-card">
                <div class="total-label">COMPTES CDF</div>
                <div class="total-value">{{ $rapport['totaux']['cdf']['nombre_comptes'] }}</div>
            </div>
            <div class="total-card">
                <div class="total-label">CRÉDITS ACTIFS USD</div>
                <div class="total-value">{{ $rapport['totaux']['usd']['credits_actifs'] }}</div>
            </div>
            <div class="total-card">
                <div class="total-label">CRÉDITS ACTIFS CDF</div>
                <div class="total-value">{{ $rapport['totaux']['cdf']['credits_actifs'] }}</div>
            </div>
            <div class="total-card">
                <div class="total-label">DÉPÔTS JOUR USD</div>
                <div class="total-value devise-usd montant">{{ number_format($rapport['totaux']['usd']['depots_jour'], 2) }} $</div>
            </div>
            <div class="total-card">
                <div class="total-label">RETRAITS JOUR USD</div>
                <div class="total-value devise-usd montant">{{ number_format($rapport['totaux']['usd']['retraits_jour'], 2) }} $</div>
            </div>
        </div>
    </div>

    <div class="separator"></div>

    <!-- Liste détaillée des comptes -->
    <div class="section">
        <div class="section-title">LISTE DES COMPTES ({{ $rapport['nombre_total_comptes'] }})</div>
        
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 8%">N° Compte</th>
                    <th style="width: 20%">Client/Membre</th>
                    <th style="width: 10%">Type</th>
                    <th style="width: 8%">Devise</th>
                    <th style="width: 10%" class="text-right">Solde</th>
                    <th style="width: 8%">Statut</th>
                    <th style="width: 8%" class="text-center">Crédits Actifs</th>
                    <th style="width: 8%" class="text-center">Dépôts J</th>
                    <th style="width: 8%" class="text-center">Retraits J</th>
                    <th style="width: 20%">Informations</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rapport['comptes'] as $compte)
                <tr>
                    <td><strong>{{ $compte->numero_compte }}</strong></td>
                    <td>
                        @if($compte->type_compte === 'individuel')
                            {{ $compte->prenom }} {{ $compte->nom }}   {{ $compte->postnom }}  {{ $compte->prenom }}
                        @else
                            {{ $compte->nom }}
                        @endif
                    </td>
                    <td>
                        @if(str_starts_with($compte->numero_compte, 'GS'))
                            <span class="badge-groupe">GROUPE</span>
                        @else
                            <span class="badge-individuel">INDIVIDUEL</span>
                        @endif
                    </td>
                    <td class="text-center {{ $compte->devise === 'USD' ? 'devise-usd' : 'devise-cdf' }}">
                        {{ $compte->devise }}
                    </td>
                    <td class="text-right montant {{ $compte->devise === 'USD' ? 'devise-usd' : 'devise-cdf' }}">
                        {{ number_format($compte->solde, 2) }}
                    </td>
                    <td class="text-center">
                        @if($compte->statut === 'actif')
                            <span class="statut-actif">ACTIF</span>
                        @elseif($compte->statut === 'inactif')
                            <span class="statut-inactif">INACTIF</span>
                        @else
                            <span class="statut-suspendu">SUSPENDU</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @php
                            $creditsActifs = str_starts_with($compte->numero_compte, 'GS') 
                                ? $compte->creditsGroupe->where('montant_total', '>', 0)->count()
                                : $compte->credits->where('montant_total', '>', 0)->count();
                        @endphp
                        {{ $creditsActifs > 0 ? $creditsActifs : '-' }}
                    </td>
                    <td class="text-center">
                        @php
                            $depotsJour = $compte->mouvements->filter(function($m) {
                                $type = \App\Helpers\MouvementHelper::getTypeAffichage($m->type_mouvement);
                                return $type === 'depot' && $m->created_at->isToday();
                            })->sum('montant');
                        @endphp
                        @if($depotsJour > 0)
                            <span class="montant">{{ number_format($depotsJour, 2) }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center">
                        @php
                            $retraitsJour = $compte->mouvements->filter(function($m) {
                                $type = \App\Helpers\MouvementHelper::getTypeAffichage($m->type_mouvement);
                                return $type === 'retrait' && $m->created_at->isToday();
                            })->sum('montant');
                        @endphp
                        @if($retraitsJour > 0)
                            <span class="montant">{{ number_format($retraitsJour, 2) }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td style="font-size: 9px;">
                        Membre: {{ $compte->numero_membre }}<br>
                        Créé: {{ $compte->created_at->format('d/m/Y') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="separator"></div>

    <!-- Signatures -->
    <div class="signature-section">
        <div class="signature" style="text-align: left;">
            Le Gérant
        </div>
        {{-- <div class="signature" style="text-align: right;">
            Le Directeur Financier
        </div> --}}
    </div>

    <!-- Pied de page -->
    <div class="footer">
        <div>Rapport généré automatiquement par le Système de Gestion de Trésorerie Tumaini Letu</div>
        <div>Document confidentiel - {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</div>
    </div>

    <!-- Script pour impression -->
    <script>
        // Impression automatique ou optionnelle
        window.onload = function() {
            // Pour impression automatique, décommentez la ligne suivante :
            // window.print();
            
            // Pour proposer l'impression
            setTimeout(function() {
                if(confirm("Voulez-vous imprimer ce rapport ?")) {
                    window.print();
                }
            }, 1000);
        }
    </script>
</body>
</html>