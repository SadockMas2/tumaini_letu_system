<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement Crédits Groupe - Tumaini Letu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        .payment-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }
        .info-card {
            transition: all 0.3s ease;
            border-left: 4px solid;
        }
        .member-card {
            transition: all 0.3s ease;
            border-left: 4px solid;
        }
        .member-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        .submit-btn:disabled:hover {
            transform: none !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
        }
        .montant-total {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="w-full max-w-7xl mx-auto py-8 px-4">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-2xl shadow-lg mb-4">
                <i class="fas fa-money-bill-wave text-3xl text-green-600"></i>
            </div>
            <h1 class="text-4xl font-bold text-white mb-3">Paiement Crédits Groupe</h1>
            <p class="text-white/80 text-lg">Gestion des remboursements hebdomadaires des groupes</p>
        </div>

        <!-- Main Card -->
        <div class="payment-card rounded-2xl p-8">
            <!-- Sélection du groupe -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200 mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-users mr-2 text-blue-500"></i>
                    Sélection du Groupe
                </h3>
                
                <select 
                    wire:model.live="selectedGroupeId"
                    class="w-full border border-gray-300 rounded-xl p-4 text-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                >
                    <option value="">Choisir un groupe...</option>
                    @foreach($groupesActifs as $groupe)
                        <option value="{{ $groupe->id }}">
                            {{ $groupe->compte->nom ?? 'Groupe '.$groupe->id }} - 
                            Montant restant: {{ number_format($groupe->montant_restant, 2) }} USD
                        </option>
                    @endforeach
                </select>
            </div>

            @if($selectedGroupeId)
                <!-- Informations du groupe sélectionné -->
                @php
                    $groupeSelectionne = $groupesActifs->firstWhere('id', $selectedGroupeId);
                    $membresAvecSoldes = $groupeSelectionne->membres_avec_soldes ?? [];
                @endphp
                
                <!-- Statistiques du groupe -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="info-card bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border-l-blue-400">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-chart-line text-blue-600 text-xl"></i>
                            </div>
                            <span class="text-2xl font-bold text-blue-600">
                                {{ number_format($groupeSelectionne->montant_total, 2) }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 font-medium">Montant Total</p>
                        <p class="text-xs text-gray-500 mt-1">USD</p>
                    </div>

                    <div class="info-card bg-gradient-to-br from-orange-50 to-red-50 rounded-xl p-6 border-l-orange-400">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-balance-scale text-orange-600 text-xl"></i>
                            </div>
                            <span class="text-2xl font-bold text-orange-600">
                                {{ number_format($groupeSelectionne->montant_restant, 2) }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 font-medium">Montant Restant</p>
                        <p class="text-xs text-gray-500 mt-1">USD</p>
                    </div>

                    <div class="info-card bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 border-l-green-400">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-calendar-week text-green-600 text-xl"></i>
                            </div>
                            <span class="text-2xl font-bold text-green-600">
                                {{ number_format($groupeSelectionne->remboursement_hebdo_total, 2) }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 font-medium">Remb. Hebdo</p>
                        <p class="text-xs text-gray-500 mt-1">USD</p>
                    </div>
                </div>

                <!-- Informations détaillées du groupe -->
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-6 border border-gray-200 mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-info-circle mr-2 text-indigo-500"></i>
                        Informations du Groupe
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Nom du Groupe</p>
                            <p class="font-semibold text-gray-800">{{ $groupeSelectionne->compte->nom ?? 'Groupe Solidaire' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Numéro de Compte</p>
                            <p class="font-semibold text-gray-800">{{ $groupeSelectionne->compte->numero_compte }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Semaine Actuelle</p>
                            <p class="font-semibold text-gray-800">{{ $groupeSelectionne->semaine_actuelle ?? 1 }}/16</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Date Échéance</p>
                            <p class="font-semibold text-gray-800">
                                {{ $groupeSelectionne->date_echeance->format('d/m/Y') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Formulaire de paiement des membres -->
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-6 border border-green-200 mb-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
                        <i class="fas fa-money-bill-wave mr-2 text-green-500"></i>
                        Paiements des Membres - Semaine {{ $groupeSelectionne->semaine_actuelle ?? 1 }}
                    </h4>

                    <form wire:submit="processerPaiementsGroupe">
                        <div class="space-y-4">
                            @foreach($membresAvecSoldes as $membre)
                            <div class="member-card bg-white rounded-xl p-6 border-l-green-400 shadow-sm">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center">
                                        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mr-4">
                                            <i class="fas fa-user text-green-600 text-lg"></i>
                                        </div>
                                        <div>
                                            <p class="text-lg font-semibold text-gray-800">{{ $membre['nom_complet'] }}</p>
                                            <p class="text-sm text-gray-600">{{ $membre['numero_compte'] }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-gray-600">
                                            Solde: <span class="font-semibold">{{ number_format($membre['solde_disponible'], 2) }} USD</span>
                                        </p>
                                        <p class="text-sm font-semibold text-green-600">
                                            Dû: {{ number_format($membre['montant_du'], 2) }} USD
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-3">
                                            Montant payé par le membre:
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <span class="text-gray-500 font-medium">USD</span>
                                            </div>
                                            <input 
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                max="{{ min($membre['solde_disponible'], $membre['montant_du']) }}"
                                                wire:model="paiementsMembres.{{ $membre['membre_id'] }}"
                                                class="block w-full pl-20 pr-4 py-3 text-lg border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200"
                                                placeholder="0.00"
                                            >
                                        </div>
                                        <div class="mt-2 text-sm text-gray-500">
                                            <i class="fas fa-info-circle mr-1 text-blue-500"></i>
                                            Maximum: {{ number_format(min($membre['solde_disponible'], $membre['montant_du']), 2) }} USD
                                        </div>
                                    </div>
                                    
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <div class="text-sm text-gray-600 mb-3 font-medium">Détails du crédit:</div>
                                        <div class="space-y-2 text-sm">
                                            <div class="flex justify-between items-center">
                                                <span class="text-gray-600">Montant accordé:</span>
                                                <span class="font-semibold text-blue-600">{{ number_format($membre['montant_accorde'], 2) }} USD</span>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <span class="text-gray-600">Montant total:</span>
                                                <span class="font-semibold text-purple-600">{{ number_format($membre['montant_total'], 2) }} USD</span>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <span class="text-gray-600">Remboursement hebdo:</span>
                                                <span class="font-semibold text-green-600">{{ number_format($membre['montant_du'], 2) }} USD</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <!-- Total des paiements -->
                        <div class="mt-8 p-6 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-xl font-semibold text-blue-800">
                                    Total des paiements saisis:
                                </span>
                                <span class="text-2xl font-bold text-blue-600 montant-total">
                                    {{ number_format($this->totalPaiementsSaisis, 2) }} USD
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-lg text-blue-700">
                                    Remboursement hebdomadaire attendu:
                                </span>
                                <span class="text-lg font-semibold text-blue-700">
                                    {{ number_format($groupeSelectionne->remboursement_hebdo_total, 2) }} USD
                                </span>
                            </div>
                        </div>

                        <!-- Bouton de soumission -->
                        <div class="mt-8 flex justify-end">
                            <button 
                                type="submit"
                                class="submit-btn bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold py-4 px-12 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                                wire:loading.attr="disabled"
                                @if($this->totalPaiementsSaisis <= 0) disabled @endif
                            >
                                <i class="fas fa-check-circle mr-3 text-xl"></i>
                                <span wire:loading.remove>Exécuter les Paiements</span>
                                <span wire:loading>Traitement en cours...</span>
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <!-- Message quand aucun groupe n'est sélectionné -->
                <div class="text-center py-12">
                    <div class="w-24 h-24 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-users text-blue-600 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Aucun groupe sélectionné</h3>
                    <p class="text-gray-600">Veuillez sélectionner un groupe dans la liste déroulante pour commencer les paiements.</p>
                </div>
            @endif

            <!-- Boutons d'action -->
            <div class="flex flex-col sm:flex-row gap-4 mt-8 pt-8 border-t border-gray-200">
                <a 
                    href="{{ url('/admin/microfinance-overviews') }}" 
                    class="flex-1 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg flex items-center justify-center"
                >
                    <i class="fas fa-arrow-left mr-3"></i>
                    Retour aux Rapports
                </a>
                
                <button 
                    type="button"
                    onclick="window.print()"
                    class="flex-1 bg-gradient-to-r from-purple-500 to-pink-600 hover:from-purple-600 hover:to-pink-700 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg flex items-center justify-center"
                >
                    <i class="fas fa-print mr-3"></i>
                    Imprimer le Reçu
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            // Animation pour les cartes membres
            Livewire.hook('element.initialized', (el) => {
                if (el.component.name.includes('paiement-groupes')) {
                    const memberCards = document.querySelectorAll('.member-card');
                    memberCards.forEach((card, index) => {
                        card.style.opacity = '0';
                        card.style.transform = 'translateY(20px)';
                        setTimeout(() => {
                            card.style.transition = 'all 0.5s ease';
                            card.style.opacity = '1';
                            card.style.transform = 'translateY(0)';
                        }, index * 100);
                    });
                }
            });

            // Mise à jour en temps réel du total
            Livewire.on('paiementsMembresUpdated', (event) => {
                const totalElement = document.querySelector('.montant-total');
                if (totalElement) {
                    totalElement.style.transform = 'scale(1.1)';
                    setTimeout(() => {
                        totalElement.style.transform = 'scale(1)';
                    }, 300);
                }
            });
        });

        // Confirmation avant soumission
        function confirmPayment() {
            const totalPaiements = {{ $this->totalPaiementsSaisis ?? 0 }};
            const remboursementAttendu = {{ $groupeSelectionne->remboursement_hebdo_total ?? 0 }};
            
            if (totalPaiements <= 0) {
                return false;
            }

            let message = `Êtes-vous sûr de vouloir exécuter les paiements ?\n\n`;
            message += `📊 Total à collecter: ${totalPaiements.toFixed(2)} USD\n`;
            message += `💰 Remboursement attendu: ${remboursementAttendu.toFixed(2)} USD\n\n`;
            
            if (totalPaiements < remboursementAttendu) {
                message += `⚠️ Attention: Le total collecté est inférieur au remboursement attendu.\n`;
                message += `Certains membres n'ont pas payé leur part complète.`;
            } else {
                message += `✅ Tous les membres ont payé leur part.`;
            }

            return confirm(message);
        }

        // Ajouter la confirmation au formulaire
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form[wire\\:submit="processerPaiementsGroupe"]');
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (!confirmPayment()) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>