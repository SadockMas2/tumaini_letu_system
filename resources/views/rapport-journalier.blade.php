<!DOCTYPE html>
<html>
<head>
    <title>Rapport Journalier - {{ $date }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .totals { background: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rapport Journalier des Mouvements</h1>
        <h2>Date: {{ $date }}</h2>
    </div>

    <div class="totals">
        <h3>Totaux de la journée</h3>
        <p><strong>Total Dépôts:</strong> {{ number_format($totalDepots, 2, ',', ' ') }} USD</p>
        <p><strong>Total Retraits:</strong> {{ number_format($totalRetraits, 2, ',', ' ') }} USD</p>
        <p><strong>Solde:</strong> {{ number_format($totalDepots - $totalRetraits, 2, ',', ' ') }} USD</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Compte</th>
                <th>Client</th>
                <th>Type</th>
                <th>Montant</th>
                <th>Opérateur</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mouvements as $mouvement)
            <tr>
                <td>{{ $mouvement->numero_compte }}</td>
                <td>{{ $mouvement->client_nom }}</td>
                <td>{{ $mouvement->type }}</td>
                <td>{{ number_format($mouvement->montant, 2, ',', ' ') }} USD</td>
                <td>{{ $mouvement->operateur->name ?? 'N/A' }}</td>
                <td>{{ $mouvement->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>