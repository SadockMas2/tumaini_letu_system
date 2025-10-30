<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Paiements - Tumaini Letu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        .history-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }
        .payment-item {
            border-left: 4px solid;
            transition: all 0.3s ease;
        }
        .payment-item:hover {
            transform: translateX(5px);
        }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen py-8">
        <div class="w-full max-w-6xl mx-4 my-8">
            <!-- Header Section -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-2xl shadow-lg mb-4">
                    <i class="fas fa-history text-3xl text-purple-600"></i>
                </div>
                <h1 class="text-4xl font-bold text-white mb-3">Historique des Paiements</h1>
                <p class="text-white/80 text-lg">Historique complet des remboursements du crédit</p>
            </div>

            <!-- Main Card -->
            <div class="history-card rounded-2xl p-8">
                <!-- Credit Information -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200 mb-8">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">
                        Crédit #{{ $credit->id }} - {{ $credit->compte->nom }} {{ $credit->compte->prenom }}
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="text-gray-600">Montant Accordé</p>
                            <p class="font-semibold text-gray-800">
                               {{ number_format(floatval($variable ?? 0), 2, ',', ' ') }} {{ $credit->compte->devise }}
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-600">Total Remboursé</p>
                            <p class="font-semibold text-green-600">
                                {{ number_format($paiements->sum('montant_paye'), 2, ',', ' ') }} {{ $credit->compte->devise }}
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-600">Reste à Payer</p>
                            <p class="font-semibold text-orange-600">
                             {{ number_format(floatval($variable ?? 0), 2, ',', ' ') }}{{ $credit->compte->devise }}
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-600">Nombre de Paiements</p>
                            <p class="font-semibold text-purple-600">{{ $paiements->count() }}</p>
                        </div>
                    </div>
                </div>

                <!-- Payment History -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800 flex items-center justify-between">
                            <span>
                                <i class="fas fa-receipt mr-2 text-green-500"></i>
                                Historique des Paiements
                            </span>
                            <span class="text-sm font-normal text-gray-600">
                                {{ $paiements->count() }} paiement(s) enregistré(s)
                            </span>
                        </h3>
                    </div>
                    
                    @if($paiements->count() > 0)
                        <div class="divide-y divide-gray-200">
                            @foreach($paiements as $paiement)
                            <div class="payment-item border-l-green-400 p-6 hover:bg-green-50">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h4 class="font-semibold text-gray-800 flex items-center">
                                            <i class="fas fa-money-bill-wave text-green-500 mr-2"></i>
                                            Paiement #{{ $paiement->id }}
                                        </h4>
                                        <p class="text-sm text-gray-600 mt-1">
                                            Référence: <span class="font-mono">{{ $paiement->reference ?? 'N/A' }}</span>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-bold text-green-600">
                                            {{ number_format($paiement->montant_paye, 2, ',', ' ') }} {{ $credit->compte->devise }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            {{ $paiement->date_paiement->format('d/m/Y H:i') }}
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-600">Méthode:</span>
                                        <span class="font-medium ml-2 capitalize">{{ $paiement->methode_paiement ?? 'Non spécifiée' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Statut:</span>
                                        <span class="font-medium ml-2 px-2 py-1 rounded-full text-xs 
                                            {{ $paiement->statut === 'complet' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ $paiement->statut ?? 'En attente' }}
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <a href="{{ route('paiement.bordereau', $paiement->id) }}" 
                                           class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium">
                                            <i class="fas fa-print mr-1"></i>
                                            Voir Bordereau
                                        </a>
                                    </div>
                                </div>
                                
                                @if($paiement->notes)
                                <div class="mt-3 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                                    <p class="text-sm text-yellow-800">
                                        <i class="fas fa-sticky-note mr-1"></i>
                                        {{ $paiement->notes }}
                                    </p>
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <i class="fas fa-receipt text-gray-300 text-5xl mb-4"></i>
                            <p class="text-gray-500 text-lg mb-4">Aucun paiement enregistré</p>
                            <p class="text-gray-400 text-sm">Les paiements apparaîtront ici une fois effectués</p>
                        </div>
                    @endif
                    
                    <!-- Résumé des Paiements -->
                    @if($paiements->count() > 0)
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-t border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                            <div class="text-center">
                                <p class="text-gray-600">Total Payé</p>
                                <p class="text-xl font-bold text-green-600">
                                    {{ number_format($paiements->sum('montant_paye'), 2, ',', ' ') }} {{ $credit->compte->devise }}
                                </p>
                            </div>
                            <div class="text-center">
                                <p class="text-gray-600">Premier Paiement</p>
                                <p class="text-lg font-semibold text-gray-800">
                                    {{ $paiements->min('date_paiement')->format('d/m/Y') }}
                                </p>
                            </div>
                            <div class="text-center">
                                <p class="text-gray-600">Dernier Paiement</p>
                                <p class="text-lg font-semibold text-gray-800">
                                    {{ $paiements->max('date_paiement')->format('d/m/Y') }}
                                </p>
                            </div>
                            <div class="text-center">
                                <p class="text-gray-600">Moyenne</p>
                                <p class="text-lg font-semibold text-blue-600">
                                    {{ number_format($paiements->avg('montant_paye'), 2, ',', ' ') }} {{ $credit->compte->devise }}
                                </p>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 mt-8 pt-6 border-t border-gray-200">
                    <a 
                        href="{{ route('comptes.details', $credit->compte_id) }}" 
                        class="flex-1 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg flex items-center justify-center"
                    >
                        <i class="fas fa-arrow-left mr-3"></i>
                        Retour au Compte
                    </a>
                    
                    <a 
                        href="{{ route('credits.payment', $credit->compte_id) }}" 
                        class="flex-1 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg flex items-center justify-center"
                    >
                        <i class="fas fa-credit-card mr-3"></i>
                        Nouveau Paiement
                    </a>
                </div>

                <!-- Security Notice -->
                <div class="mt-6 text-center">
                    <div class="inline-flex items-center text-xs text-gray-500 bg-gray-100 rounded-full px-4 py-2">
                        <i class="fas fa-shield-alt mr-2 text-green-500"></i>
                        Historique sécurisé et tracé
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Animation pour les éléments d'historique des paiements
        document.querySelectorAll('.payment-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(5px)';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
            });
        });
    </script>
</body>
</html>