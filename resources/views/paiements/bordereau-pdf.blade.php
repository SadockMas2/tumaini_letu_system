<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bordereau de Paiement - Tumaini Letu</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .info-section { margin-bottom: 20px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .info-label { font-weight: bold; width: 200px; }
        .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f5f5f5; }
        .total-row { font-weight: bold; background-color: #f9f9f9; }
        .signature-section { margin-top: 50px; display: flex; justify-content: space-between; }
        .signature-box { width: 200px; border-top: 1px solid #333; padding-top: 5px; text-align: center; }
        .watermark { opacity: 0.1; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 80px; color: #ccc; }
    </style>
</head>
<body>
    <div class="watermark">TUMAINI LETU</div>
    
    <div class="header">
        <h1>BORDEREAU DE PAIEMENT CRÉDIT</h1>
        <h2>TUMAINI LETU</h2>
        <p>Coopérative d'Épargne et de Crédit</p>
    </div>

    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Référence Paiement:</span>
            <span>{{ $paiement->reference }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date Paiement:</span>
            <span>{{ $paiement->date_paiement->format('d/m/Y H:i') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Type Paiement:</span>
            <span>{{ strtoupper($paiement->type_paiement) }}</span>
        </div>
    </div>

    <div class="info-section">
        <h3>INFORMATIONS DU COMPTE</h3>
        <div class="info-row">
            <span class="info-label">Nom du Membre:</span>
            <span>{{ $paiement->compte->nom }} {{ $paiement->compte->prenom }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Numéro de Compte:</span>
            <span>{{ $paiement->compte->numero_compte }}</span>
        </div>
    </div>

    <div class="info-section">
        <h3>DÉTAILS DU CRÉDIT</h3>
        <div class="info-row">
            <span class="info-label">Type de Crédit:</span>
            <span>{{ $paiement->credit->type_credit === 'groupe' ? 'Crédit Groupe' : 'Crédit Individuel' }}</span>
        </div>
        @if($paiement->credit->credit_groupe_id)
        <div class="info-row">
            <span class="info-label">Groupe:</span>
            <span>{{ $paiement->credit->creditGroupe->compte->nom ?? 'Groupe Solidaire' }}</span>
        </div>
        @endif
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Description</th>
                <th>Montant (USD)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Montant Payé</td>
                <td>{{ number_format($paiement->montant_paye, 2, ',', ' ') }}</td>
            </tr>
            <tr>
                <td>Capital Restant Avant</td>
                <td>{{ number_format($paiement->credit->montant_total + $paiement->montant_paye, 2, ',', ' ') }}</td>
            </tr>
            <tr>
                <td>Capital Restant Après</td>
                <td>{{ number_format($paiement->credit->montant_total, 2, ',', ' ') }}</td>
            </tr>
            <tr class="total-row">
                <td><strong>SOLDE ACTUEL DU CRÉDIT</strong></td>
                <td><strong>{{ number_format($paiement->credit->montant_total, 2, ',', ' ') }} USD</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Prochain Échéance:</span>
            <span>
                @if($paiement->credit->montant_total > 0)
                {{ $paiement->credit->date_echeance->format('d/m/Y') }}
                @else
                <strong style="color: green;">CRÉDIT ENTIÈREMENT REMBOURSÉ</strong>
                @endif
            </span>
        </div>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <p>Signature du Membre</p>
        </div>
        <div class="signature-box">
            <p>Signature du Caissier</p>
        </div>
    </div>

    <div style="margin-top: 30px; text-align: center; font-size: 12px; color: #666;">
        <p>Ce bordereau est généré automatiquement le {{ now()->format('d/m/Y à H:i') }}</p>
        <p>TUMAINI LETU - Tous droits réservés</p>
    </div>
</body>
</html>