<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Relevé Compte Épargne - {{ $compte->numero_compte }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .info-table td { padding: 5px; border: 1px solid #ddd; }
        .transaction-table { width: 100%; border-collapse: collapse; }
        .transaction-table th, .transaction-table td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        .transaction-table th { background-color: #f5f5f5; }
        .positive { color: #059669; }
        .negative { color: #dc2626; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>RELEVÉ DE COMPTE ÉPARGNE</h1>
        <h2>Tumaini Letu System</h2>
        <p>Date d'édition : {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td><strong>Numéro de compte :</strong></td>
            <td>{{ $compte->numero_compte }}</td>
            <td><strong>Titulaire :</strong></td>
            <td>{{ $compte->nom_complet }}</td>
        </tr>
        <tr>
            <td><strong>Type de compte :</strong></td>
            <td>{{ ucfirst($compte->type_compte) }}</td>
            <td><strong>Devise :</strong></td>
            <td>{{ $compte->devise }}</td>
        </tr>
        <tr>
            <td><strong>Solde actuel :</strong></td>
            <td colspan="3" style="font-weight: bold; font-size: 14px;">
                {{ number_format($compte->solde, 2, ',', ' ') }} {{ $compte->devise }}
            </td>
        </tr>
    </table>

    <h3>Historique des transactions</h3>
    <table class="transaction-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Description</th>
                <th>Montant</th>
                <th>Agent</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $transaction)
            <tr>
                <td>{{ $transaction['date'] }}</td>
                <td>{{ $transaction['type'] }}</td>
                <td>{{ $transaction['description'] }}</td>
                <td class="{{ $transaction['montant'] >= 0 ? 'positive' : 'negative' }}">
                    {{ $transaction['montant'] >= 0 ? '+' : '' }}{{ number_format($transaction['montant'], 2, ',', ' ') }} {{ $transaction['devise'] }}
                </td>
                <td>{{ $transaction['agent'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Document généré automatiquement par Tumaini Letu System</p>
        <p>Page 1/1</p>
    </div>
</body>
</html>