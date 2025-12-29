<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport de Remboursement par Période</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        .header { text-align: center; margin-bottom: 30px; }
        .title { color: #2d3748; font-size: 24px; }
        .subtitle { color: #718096; font-size: 14px; }
        .totals { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .total-box { background: #f8fafc; padding: 15px; border-radius: 8px; width: 23%; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #edf2f7; padding: 12px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
        .progress-bar { height: 8px; background: #e2e8f0; border-radius: 4px; }
        .progress-fill { height: 100%; border-radius: 4px; }
        .capital-fill { background: #4c51bf; }
        .interet-fill { background: #ed8936; }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">Rapport de Remboursement par Période</h1>
        <p class="subtitle">Généré le {{ date('d/m/Y H:i') }}</p>
    </div>
    
    <div class="totals">
        <div class="total-box">
            <strong>Total Remboursement:</strong><br>
            {{ number_format($totaux['total_remboursement'], 2) }} USD
        </div>
        <div class="total-box">
            <strong>Total Capital:</strong><br>
            {{ number_format($totaux['total_capital'], 2) }} USD
        </div>
        <div class="total-box">
            <strong>Total Intérêts:</strong><br>
            {{ number_format($totaux['total_interets'], 2) }} USD
        </div>
        <div class="total-box">
            <strong>Nombre de Périodes:</strong><br>
            {{ $totaux['nombre_periodes'] }}
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Période</th>
                <th>Date</th>
                <th>Crédit</th>
                <th>Type</th>
                <th>Client/Groupe</th>
                <th>Montant Total</th>
                <th>Capital</th>
                <th>Intérêts</th>
                <th>Répartition</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($remboursements as $item)
            <tr>
                <td>{{ $item['periode'] }}</td>
                <td>{{ \Carbon\Carbon::parse($item['date_periode'])->format('d/m/Y') }}</td>
                <td>{{ $item['numero_compte'] }}</td>
                <td>{{ $item['type_credit'] }}</td>
                <td>{{ $item['nom_complet'] }}</td>
                <td><strong>{{ number_format($item['montant_total'], 2) }} USD</strong></td>
                <td>{{ number_format($item['capital'], 2) }} USD</td>
                <td>{{ number_format($item['interets'], 2) }} USD</td>
                <td>
                    <div class="progress-bar">
                        <div class="progress-fill capital-fill" style="width: {{ $item['pourcentage_capital'] }}%"></div>
                        <div class="progress-fill interet-fill" style="width: {{ $item['pourcentage_interets'] }}%"></div>
                    </div>
                    <small>{{ round($item['pourcentage_capital'], 1) }}% Capital / {{ round($item['pourcentage_interets'], 1) }}% Intérêts</small>
                </td>
                <td>{{ $item['statut'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>