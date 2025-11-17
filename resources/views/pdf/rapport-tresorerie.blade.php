{{-- resources/views/pdf/rapport-tresorerie.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport de Trésorerie</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #333; }
        .subtitle { color: #666; margin: 5px 0; }
        .section { margin-bottom: 25px; }
        .section-title { background-color: #f5f5f5; padding: 8px; font-weight: bold; border-left: 4px solid #333; margin-bottom: 10px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .table th { background-color: #333; color: white; padding: 8px; text-align: left; }
        .table td { padding: 8px; border: 1px solid #ddd; }
        .table tr:nth-child(even) { background-color: #f9f9f9; }
        .total-row { background-color: #e8f4fd !important; font-weight: bold; }
        .devise-section { margin-bottom: 30px; page-break-inside: avoid; }
        .mouvement-depot { color: #28a745; }
        .mouvement-retrait { color: #dc3545; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .solde-positif { color: #28a745; }
        .solde-negatif { color: #dc3545; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; text-align: center; color: #666; font-size: 10px; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>RAPPORT DE TRÉSORERIE</h1>
        <div class="subtitle">Date du rapport: {{ $rapport['date_rapport'] }}</div>
        <div class="subtitle">Généré le: {{ $rapport['date_generation'] }}</div>
    </div>

    @foreach($rapport['devises'] as $deviseData)
    <div class="devise-section">
        <div class="section-title">
            DEVISE : {{ $deviseData['devise'] }}
        </div>

        {{-- Synthèse par devise --}}
        <table class="table">
            <thead>
                <tr>
                    <th>Caisse</th>
                    <th>Solde Initial</th>
                    <th>Solde Final</th>
                    <th>Dépôts</th>
                    <th>Retraits</th>
                    <th>Opérations</th>
                </tr>
            </thead>
            <tbody>
                @foreach($deviseData['caisses'] as $caisse)
                <tr>
                    <td>{{ $caisse['nom'] }}</td>
                    <td class="text-right">{{ number_format($caisse['solde_initial'], 2) }} {{ $deviseData['devise'] }}</td>
                    <td class="text-right">{{ number_format($caisse['solde_final'], 2) }} {{ $deviseData['devise'] }}</td>
                    <td class="text-right mouvement-depot">{{ number_format($caisse['depots'], 2) }} {{ $deviseData['devise'] }}</td>
                    <td class="text-right mouvement-retrait">{{ number_format($caisse['retraits'], 2) }} {{ $deviseData['devise'] }}</td>
                    <td class="text-center">{{ $caisse['nombre_operations'] }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td><strong>TOTAUX {{ $deviseData['devise'] }}</strong></td>
                    <td class="text-right">-</td>
                    <td class="text-right"><strong>{{ number_format($deviseData['solde_total'], 2) }} {{ $deviseData['devise'] }}</strong></td>
                    <td class="text-right mouvement-depot"><strong>{{ number_format($deviseData['total_depots'], 2) }} {{ $deviseData['devise'] }}</strong></td>
                    <td class="text-right mouvement-retrait"><strong>{{ number_format($deviseData['total_retraits'], 2) }} {{ $deviseData['devise'] }}</strong></td>
                    <td class="text-center"><strong>{{ $deviseData['nombre_operations'] }}</strong></td>
                </tr>
            </tbody>
        </table>

        {{-- Détail des mouvements par caisse --}}
        @foreach($deviseData['caisses'] as $caisse)
            @if(count($caisse['mouvements']) > 0)
            <div style="margin-top: 15px;">
                <div style="font-weight: bold; margin-bottom: 8px; color: #555;">
                    Détail des mouvements - {{ $caisse['nom'] }}
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Heure</th>
                            <th>Type</th>
                            <th>Opération</th>
                            <th>Montant</th>
                            <th>Description</th>
                            <th>Client/Déposant</th>
                            <th>Opérateur</th>
                            <th>Solde Avant</th>
                            <th>Solde Après</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($caisse['mouvements'] as $mouvement)
                        <tr>
                            <td>{{ $mouvement['heure'] }}</td>
                            <td>
                                @if($mouvement['type'] === 'depot')
                                    <span class="mouvement-depot">DÉPÔT</span>
                                @else
                                    <span class="mouvement-retrait">RETRAIT</span>
                                @endif
                            </td>
                            <td>{{ $mouvement['type_mouvement'] ?? '-' }}</td>
                            <td class="text-right {{ $mouvement['type'] === 'depot' ? 'mouvement-depot' : 'mouvement-retrait' }}">
                                {{ number_format($mouvement['montant'], 2) }} {{ $deviseData['devise'] }}
                            </td>
                            <td>{{ $mouvement['description'] ?? '-' }}</td>
                            <td>{{ $mouvement['nom_deposant'] ?? $mouvement['client_nom'] ?? '-' }}</td>
                            <td>{{ $mouvement['operateur'] }}</td>
                            <td class="text-right">{{ number_format($mouvement['solde_avant'], 2) }}</td>
                            <td class="text-right">{{ number_format($mouvement['solde_apres'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div style="text-align: center; color: #999; padding: 10px;">
                Aucun mouvement pour cette caisse
            </div>
            @endif
        @endforeach
    </div>

    @if(!$loop->last)
    <div class="page-break"></div>
    @endif
    @endforeach

    <div class="footer">
        Rapport généré automatiquement par le Système de Gestion de Trésorerie
    </div>
</body>
</html>