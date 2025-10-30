<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement de Crédit - Tumaini Letu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .payment-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .amount-display {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .progress-bar {
            background: linear-gradient(90deg, #10b981, #059669);
            height: 8px;
            border-radius: 4px;
            transition: width 1s ease-in-out;
        }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="w-full max-w-2xl mx-4">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-full shadow-lg mb-4">
                <i class="fas fa-credit-card text-2xl text-purple-600"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Paiement de Crédit</h1>
            <p class="text-white/80 text-lg">Système de Gestion Tumaini Letu</p>
        </div>

        <!-- Payment Card -->
        <div class="payment-card rounded-2xl shadow-2xl p-8">
            <!-- Account Info -->
            <div class="text-center mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-2">
                    Compte {{ $compte->numero_compte }}
                </h2>
                <div class="flex items-center justify-center space-x-4 text-sm text-gray-600">
                    <div class="flex items-center">
                        <i class="fas fa-user mr-2 text-blue-500"></i>
                        <span>{{ $compte->nom }} {{ $compte->prenom }}</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-id-card mr-2 text-green-500"></i>
                        <span>{{ $compte->numero_membre }}</span>
                    </div>
                </div>
            </div>

            <!-- Credit Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-100">
                    <div class="flex items-center mb-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-money-bill-wave text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Montant Accordé</p>
                            <p class="text-xl font-bold text-gray-800">
                               {{ number_format(floatval($variable ?? 0), 2, ',', ' ') }} {{ $compte->devise }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 border border-green-100">
                    <div class="flex items-center mb-3">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-file-invoice-dollar text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Montant Total Dû</p>
                            <p class="text-xl font-bold text-gray-800">
                            {{ number_format(floatval($variable ?? 0), 2, ',', ' ') }} {{ $compte->devise }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Barre de progression -->
                <div class="md:col-span-2 bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-6 border border-purple-100">
                    <div class="mb-4">
                        <div class="flex justify-between text-sm text-gray-600 mb-2">
                            <span>Progression du remboursement</span>
                            <span class="font-semibold">
                                @php
                                    $pourcentage = (($credit->montant_accorde - $credit->montant_total) / $credit->montant_accorde) * 100;
                                    $pourcentage = max(0, min(100, $pourcentage));
                                @endphp
                                {{ number_format($pourcentage, 1) }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="progress-bar rounded-full h-3" style="width: {{ $pourcentage }}%"></div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div class="bg-white rounded-lg p-3 border border-gray-200">
                            <p class="text-sm text-gray-600">Déjà Remboursé</p>
                            <p class="text-lg font-bold text-green-600">
                                {{ number_format($credit->montant_accorde - $credit->montant_total, 2, ',', ' ') }} {{ $compte->devise }}
                            </p>
                        </div>
                        <div class="bg-white rounded-lg p-3 border border-gray-200">
                            <p class="text-sm text-gray-600">Reste à Payer</p>
                            <p class="text-lg font-bold text-orange-600">
                               {{ number_format(floatval($variable ?? 0), 2, ',', ' ') }} {{ $compte->devise }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informations sur le crédit -->
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-6 border border-gray-200 mb-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-info-circle mr-2 text-indigo-500"></i>
                    Détails du Crédit
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <p class="text-gray-600">Type</p>
                        <p class="font-semibold capitalize">{{ $credit->type_credit }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Remb. Hebdo</p>
                        <p class="font-semibold">
                            {{ number_format($credit->remboursement_hebdo, 2, ',', ' ') }} {{ $compte->devise }}
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-600">Date Début</p>
                        <p class="font-semibold">
                            {{ $credit->date_octroi->format('d/m/Y') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-600">Date Fin</p>
                        <p class="font-semibold">
                            {{ $credit->date_echeance->format('d/m/Y') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <form action="{{ route('credits.process-payment', $credit->id) }}" method="POST" class="space-y-6">
                @csrf

                <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-6 border border-gray-200">
                    <label class="block text-lg font-semibold text-gray-700 mb-4">
                        <i class="fas fa-credit-card mr-2 text-indigo-500"></i>
                        Montant à Payer
                    </label>
                    
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 font-medium">{{ $compte->devise }}</span>
                        </div>
                        <input 
                            type="number" 
                            name="montant_paye" 
                            step="0.01"
                            min="0.01"
                            max="{{ $credit->montant_total }}"
                            class="block w-full pl-16 pr-4 py-4 text-lg border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                            placeholder="0.00"
                            required
                            value="{{ min($credit->remboursement_hebdo, $credit->montant_total) }}"
                        >
                    </div>
                    
                    <div class="mt-3 flex justify-between text-sm text-gray-500">
                        <span>
                            Remb. hebdo suggéré: 
                            <strong>{{ number_format($credit->remboursement_hebdo, 2, ',', ' ') }} {{ $compte->devise }}</strong>
                        </span>
                        <span class="text-indigo-600 font-medium">
                            Max: {{ number_format(floatval($variable ?? 0), 2, ',', ' ') }} {{ $compte->devise }}
                        </span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 pt-4">
                    <button 
                        type="submit" 
                        class="flex-1 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl"
                    >
                        <i class="fas fa-check-circle mr-2"></i>
                        Confirmer le Paiement
                    </button>
                    
                    <a 
                        href="{{ url('/admin/comptes') }}" 
                        class="flex-1 bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl text-center"
                    >
                        <i class="fas fa-arrow-left mr-2"></i>
                        Retour aux Comptes
                    </a>
                </div>
            </form>

            <!-- Security Notice -->
            <div class="mt-6 text-center">
                <div class="inline-flex items-center text-xs text-gray-500 bg-gray-100 rounded-full px-4 py-2">
                    <i class="fas fa-shield-alt mr-2 text-green-500"></i>
                    Transaction sécurisée et cryptée
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8">
            <p class="text-white/60 text-sm">
                &copy; 2024 Tumaini Letu System. Tous droits réservés.
            </p>
        </div>
    </div>

    <script>
        // Animation pour le champ de saisie
        document.querySelector('input[name="montant_paye"]').addEventListener('focus', function() {
            this.parentElement.classList.add('ring-2', 'ring-indigo-200');
        });
        
        document.querySelector('input[name="montant_paye"]').addEventListener('blur', function() {
            this.parentElement.classList.remove('ring-2', 'ring-indigo-200');
        });

        // Validation en temps réel
        document.querySelector('input[name="montant_paye"]').addEventListener('input', function() {
            const maxAmount = parseFloat('{{ $credit->montant_total }}');
            const currentAmount = parseFloat(this.value) || 0;
            
            if (currentAmount > maxAmount) {
                this.value = maxAmount;
            }
            
            if (currentAmount < 0) {
                this.value = 0;
            }
        });

        // Animation de la barre de progression
        document.addEventListener('DOMContentLoaded', function() {
            const progressBar = document.querySelector('.progress-bar');
            if (progressBar) {
                const computedStyle = getComputedStyle(progressBar);
                const finalWidth = computedStyle.width;
                
                progressBar.style.width = '0';
                setTimeout(() => {
                    progressBar.style.width = finalWidth;
                }, 500);
            }
        });
    </script>
</body>
</html>