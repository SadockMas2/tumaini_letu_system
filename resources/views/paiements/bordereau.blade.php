<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bordereau de Paiement - Tumaini Letu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">
    <div class="max-w-4xl mx-auto bg-white p-8 my-8 rounded-lg shadow-lg">
        <!-- En-tête -->
        <div class="text-center border-b-2 border-gray-300 pb-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <div class="text-left">
                    <h1 class="text-2xl font-bold text-gray-800">Tumaini Letu</h1>
                    <p class="text-gray-600">Microfinance Solidaire</p>
                </div>
                <div class="text-right">
                    <h2 class="text-xl font-bold text-blue-600">BORDEREAU DE PAIEMENT</h2>
                    <p class="text-gray-500 text-sm">Référence: {{ $paiement->reference ?? 'PAY-' . now()->format('Ymd-His') }}</p>
                </div>
            </div>
        </div>

        <!-- Informations du Paiement -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg">
                <h3 class="font-semibold text-blue-800 mb-2">Informations Client</h3>
                <p class="text-sm"><strong>Nom:</strong> {{ $paiement->credit->compte->nom }} {{ $paiement->credit->compte->prenom }}</p>
                <p class="text-sm"><strong>Compte:</strong> {{ $paiement->credit->compte->numero_compte }}</p>
                <p class="text-sm"><strong>Membre:</strong> {{ $paiement->credit->compte->numero_membre }}</p>
            </div>
            
            <div class="bg-green-50 p-4 rounded-lg">
                <h3 class="font-semibold text-green-800 mb-2">Détails Paiement</h3>
                <p class="text-sm"><strong>Date:</strong> {{ $paiement->date_paiement->format('d/m/Y H:i') }}</p>
                <p class="text-sm"><strong>Méthode:</strong> {{ ucfirst($paiement->methode_paiement) }}</p>
                <p class="text-sm"><strong>Statut:</strong> <span class="font-bold text-green-600">{{ ucfirst($paiement->statut) }}</span></p>
            </div>
        </div>

        <!-- Détails du Crédit -->
        <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <h3 class="font-semibold text-gray-800 mb-3">Informations Crédit</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <p class="text-gray-600">Type Crédit</p>
                    <p class="font-semibold capitalize">{{ $paiement->credit->type_credit }}</p>
                </div>
                <div>
                    <p class="text-gray-600">Montant Initial</p>
                    <p class="font-semibold">{{ number_format($paiement->credit->montant_accorde, 2, ',', ' ') }} {{ $paiement->credit->compte->devise }}</p>
                </div>
                <div>
                    <p class="text-gray-600">Reste Avant Paiement</p>
                    <p class="font-semibold">{{ number_format($paiement->credit->montant_total + $paiement->montant_paye, 2, ',', ' ') }} {{ $paiement->credit->compte->devise }}</p>
                </div>
                <div>
                    <p class="text-gray-600">Reste Après Paiement</p>
                    <p class="font-semibold">{{ number_format($paiement->credit->montant_total, 2, ',', ' ') }} {{ $paiement->credit->compte->devise }}</p>
                </div>
            </div>
        </div>

        <!-- Montant du Paiement -->
        <div class="text-center py-6 border-2 border-green-200 bg-green-50 rounded-lg mb-6">
            <p class="text-lg text-gray-600 mb-2">Montant Payé</p>
            <p class="text-4xl font-bold text-green-600">
                {{ number_format($paiement->montant_paye, 2, ',', ' ') }} {{ $paiement->credit->compte->devise }}
            </p>
        </div>

        <!-- Notes -->
        @if($paiement->notes)
        <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200 mb-6">
            <h4 class="font-semibold text-yellow-800 mb-2">Notes</h4>
            <p class="text-sm text-yellow-700">{{ $paiement->notes }}</p>
        </div>
        @endif

        <!-- Signatures -->
        <div class="grid grid-cols-2 gap-6 mt-8 pt-6 border-t border-gray-300">
            <div class="text-center">
                <p class="font-semibold text-gray-700 mb-4">Signature du Client</p>
                <div class="border-b border-gray-400 pb-8"></div>
            </div>
            <div class="text-center">
                <p class="font-semibold text-gray-700 mb-4">Signature du Caissier</p>
                <div class="border-b border-gray-400 pb-8"></div>
            </div>
        </div>

        <!-- Pied de page -->
        <div class="text-center mt-8 text-xs text-gray-500">
            <p>Ce bordereau est une preuve de paiement. Conservez-le précieusement.</p>
            <p>Généré le {{ now()->format('d/m/Y à H:i') }} - Tumaini Letu System</p>
        </div>

        <!-- Bouton d'impression -->
        <div class="text-center mt-6">
            <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-6 rounded-lg">
                <i class="fas fa-print mr-2"></i>Imprimer le Bordereau
            </button>
            
            <a href="{{ route('paiement.bordereau.pdf', $paiement->id) }}" 
               class="ml-4 bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-6 rounded-lg">
                <i class="fas fa-download mr-2"></i>Télécharger PDF
            </a>
        </div>
    </div>
</body>
</html>