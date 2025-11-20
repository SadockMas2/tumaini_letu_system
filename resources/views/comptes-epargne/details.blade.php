<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Compte Épargne - Tumaini Letu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
             background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }
        .details-card {
            margin: 0 10px;
            backdrop-filter: blur(10px);
            background: rgba(233, 225, 225, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }
        .stat-card {
            transition: all 0.3s ease;
            border-left: 4px solid;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .filter-btn {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .filter-btn.active {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }
        .mouvement-row {
            transition: all 0.3s ease;
        }
        .mouvement-row.hidden {
            display: none;
        }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="w-full max-w-6xl mx-4 my-8">
        <!-- Header Section -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-2xl shadow-lg mb-4">
                <i class="fas fa-piggy-bank text-3xl text-green-600"></i>
            </div>
            <h1 class="text-4xl font-bold text-white mb-3">Détails du Compte Épargne</h1>
            <p class="text-white/80 text-lg">Vue d'ensemble complète de votre compte épargne</p>
        </div>

        <!-- Main Details Card -->
        <div class="details-card rounded-2xl p-8">
            <!-- Account Header -->
            <div class="text-center mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-2">
                    Compte Épargne <span class="text-green-600">{{ $compte->numero_compte }}</span>
                </h2>
                <div class="flex flex-wrap justify-center gap-4 text-sm text-gray-600 mb-4">
                    <div class="flex items-center bg-blue-50 rounded-full px-4 py-2">
                        <i class="fas fa-user mr-2 text-blue-500"></i>
                        <span class="font-medium">{{ $compte->nom_complet }}</span>
                    </div>
                    <div class="flex items-center bg-green-50 rounded-full px-4 py-2">
                        <i class="fas fa-wallet mr-2 text-green-500"></i>
                        <span class="font-medium">{{ $compte->devise }}</span>
                    </div>
                    <div class="flex items-center bg-purple-50 rounded-full px-4 py-2">
                        <i class="fas fa-tag mr-2 text-purple-500"></i>
                        <span class="font-medium">{{ ucfirst($compte->type_compte) }}</span>
                    </div>
                    @if($compte->type_compte === 'groupe_solidaire')
                    <div class="flex items-center bg-orange-50 rounded-full px-4 py-2">
                        <i class="fas fa-users mr-2 text-orange-500"></i>
                        <span class="font-medium">Compte Groupe</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Account Information Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Solde Actuel -->
                <div class="stat-card bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 border-l-green-400">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-wallet text-green-600 text-xl"></i>
                        </div>
                        <span class="text-2xl font-bold text-green-600">
                            {{ number_format($compte->solde, 2, ',', ' ') }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 font-medium">Solde Actuel</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $compte->devise }}</p>
                </div>

                {{-- <!-- Taux d'Intérêt -->
                <div class="stat-card bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border-l-blue-400">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-percentage text-blue-600 text-xl"></i>
                        </div>
                        <span class="text-2xl font-bold text-blue-600">
                            {{ number_format($compte->taux_interet, 2) }}%
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 font-medium">Taux d'Intérêt</p>
                    <p class="text-xs text-gray-500 mt-1">Annuel</p>
                </div> --}}

                <!-- Solde Minimum -->
                <div class="stat-card bg-gradient-to-br from-orange-50 to-red-50 rounded-xl p-6 border-l-orange-400">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-lock text-orange-600 text-xl"></i>
                        </div>
                        <span class="text-2xl font-bold text-orange-600">
                            {{ number_format($compte->solde_minimum, 2, ',', ' ') }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 font-medium">Solde Minimum</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $compte->devise }}</p>
                </div>

                <!-- Statut du Compte -->
                <div class="stat-card bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-6 border-l-purple-400">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check-circle text-purple-600 text-xl"></i>
                        </div>
                        @php
                            $statutColor = $compte->statut === 'actif' ? 'text-green-600' : 
                                         ($compte->statut === 'inactif' ? 'text-orange-600' : 'text-red-600');
                            $statutIcon = $compte->statut === 'actif' ? 'fa-check' : 
                                        ($compte->statut === 'inactif' ? 'fa-pause' : 'fa-ban');
                        @endphp
                        <span class="text-xl font-bold {{ $statutColor }}">
                            <i class="fas {{ $statutIcon }} mr-1"></i>
                            {{ ucfirst($compte->statut) }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 font-medium">Statut du Compte</p>
                    <p class="text-xs text-gray-500 mt-1">État actuel</p>
                </div>
            </div>

            <!-- Section Relevé des Mouvements -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-8">
                <div class="bg-gradient-to-r from-green-50 to-emerald-100 px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                                <i class="fas fa-exchange-alt mr-2 text-green-500"></i>
                                Relevé des Mouvements - Épargnes & Retraits
                            </h3>
                            <p class="text-sm text-gray-600 mt-1">
                                Historique complet des transactions du compte épargne
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <button 
                                onclick="toggleReleve()"
                                class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg transition-all duration-200 flex items-center"
                            >
                                <i class="fas fa-eye mr-2"></i>
                                Voir le Relevé
                            </button>
                            <a 
                                href="{{ route('comptes-epargne.export-releve', $compte->id) }}"
                                class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg transition-all duration-200 flex items-center"
                            >
                                <i class="fas fa-file-export mr-2"></i>
                                Exporter Relevé
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Contenu du relevé (caché par défaut) -->
                <div id="releveContent" class="hidden">
                    <!-- Statistiques Rapides -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-6 bg-gray-50 border-b border-gray-200">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">
                                {{ number_format($statsMouvements['total_depots'], 2, ',', ' ') }} {{ $compte->devise }}
                            </div>
                            <p class="text-sm text-gray-600">Total Épargnes</p>
                            <p class="text-xs text-gray-500">{{ $statsMouvements['nombre_depots'] }} opérations</p>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-red-600">
                                {{ number_format($statsMouvements['total_retraits'], 2, ',', ' ') }} {{ $compte->devise }}
                            </div>
                            <p class="text-sm text-gray-600">Total Retraits</p>
                            <p class="text-xs text-gray-500">{{ $statsMouvements['nombre_retraits'] }} opérations</p>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600">
                                {{ number_format($statsMouvements['total_depots'] - $statsMouvements['total_retraits'], 2, ',', ' ') }} {{ $compte->devise }}
                            </div>
                            <p class="text-sm text-gray-600">Solde Net</p>
                            <p class="text-xs text-gray-500">Épargnes - Retraits</p>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-600">
                                {{ $mouvements->total() }}
                            </div>
                            <p class="text-sm text-gray-600">Total Opérations</p>
                            <p class="text-xs text-gray-500">Toutes transactions</p>
                        </div>
                    </div>

                    <!-- Filtres -->
                    <div class="px-6 py-4 bg-white border-b border-gray-200">
                        <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                            <div class="flex gap-2">
                                <button class="filter-btn active bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-medium transition-all duration-200">
                                    Tous
                                </button>
                                <button class="filter-btn bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-medium transition-all duration-200" data-type="depot">
                                    Épargnes
                                </button>
                                <button class="filter-btn bg-red-100 text-red-700 px-3 py-1 rounded-full text-sm font-medium transition-all duration-200" data-type="retrait">
                                    Retraits
                                </button>
                            </div>
                            <div class="text-sm text-gray-500">
                                Affichage des {{ $mouvements->count() }} dernières opérations
                            </div>
                        </div>
                    </div>

                    <!-- Tableau des Mouvements -->
<div class="overflow-x-auto">
    <table class="w-full">
        <thead>
            <tr class="bg-gray-50 border-b border-gray-200">
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Date & Heure
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Type
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Référence
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Description
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Montant
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Agent/Opérateur
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($mouvements as $mouvement)
            <tr class="mouvement-row hover:bg-gray-50 transition-colors duration-150" data-type="{{ $mouvement['type'] }}">
               <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">
                        {{ isset($mouvement['date_operation']) ? \Carbon\Carbon::parse($mouvement['date_operation'])->format('d/m/Y') : \Carbon\Carbon::parse($mouvement['created_at'])->format('d/m/Y') }}
                    </div>
                    <div class="text-xs text-gray-500">
                        {{ isset($mouvement['date_operation']) ? \Carbon\Carbon::parse($mouvement['date_operation'])->format('H:i') : \Carbon\Carbon::parse($mouvement['created_at'])->format('H:i') }}
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($mouvement['type'] === 'depot')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-piggy-bank mr-1"></i>
                            Épargne
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <i class="fas fa-money-bill-wave mr-1"></i>
                            Retrait
                        </span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-mono text-gray-900">
                        {{ $mouvement['reference'] ?? 'N/A' }}
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-gray-900 max-w-xs">
                        {{ $mouvement['description'] ?? 'Transaction' }}
                    </div>
                    @if($mouvement['nom_deposant'])
                    <div class="text-xs text-gray-500">
                        Par: {{ $mouvement['nom_deposant'] }}
                    </div>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium {{ $mouvement['type'] === 'depot' ? 'text-green-600' : 'text-red-600' }}">
                        {{ $mouvement['type'] === 'depot' ? '+' : '-' }}
                        {{ number_format($mouvement['montant'], 2, ',', ' ') }} {{ $mouvement['devise'] ?? $compte->devise }}
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-600">
                        {{ $mouvement['operateur']['name'] ?? ($mouvement['nom_deposant'] ?? 'Système') }}
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-6 py-12 text-center">
                    <div class="text-gray-400 mb-4">
                        <i class="fas fa-piggy-bank text-4xl"></i>
                    </div>
                    <p class="text-gray-500 text-lg">Aucune transaction enregistrée</p>
                    <p class="text-gray-400 text-sm mt-2">Les épargnes et retraits apparaîtront ici</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

                    <!-- Pagination -->
                    @if($mouvements->hasPages())
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Affichage de {{ $mouvements->firstItem() }} à {{ $mouvements->lastItem() }} sur {{ $mouvements->total() }} résultats
                            </div>
                            <div class="flex space-x-2">
                                @if($mouvements->onFirstPage())
                                    <span class="px-3 py-1 bg-gray-200 text-gray-500 rounded-md text-sm">Précédent</span>
                                @else
                                    <a href="{{ $mouvements->previousPageUrl() }}" class="px-3 py-1 bg-green-500 text-white rounded-md text-sm hover:bg-green-600 transition-colors">
                                        Précédent
                                    </a>
                                @endif

                                @if($mouvements->hasMorePages())
                                    <a href="{{ $mouvements->nextPageUrl() }}" class="px-3 py-1 bg-green-500 text-white rounded-md text-sm hover:bg-green-600 transition-colors">
                                        Suivant
                                    </a>
                                @else
                                    <span class="px-3 py-1 bg-gray-200 text-gray-500 rounded-md text-sm">Suivant</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 mt-8 pt-6 border-t border-gray-200">
                <a 
                    href="{{ url('/admin/compte-epargnes') }}" 
                    class="flex-1 bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center"
                >
                    <i class="fas fa-arrow-left mr-3"></i>
                    Retour aux Comptes Épargne
                </a>
{{--                 
                <a 
                    href="{{ route('comptes-epargne.mouvements', $compte->id) }}" 
                    class="flex-1 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center"
                >
                    <i class="fas fa-list mr-3"></i>
                    Voir Tous les Mouvements
                </a> --}}
            </div>

            <!-- Security Notice -->
            <div class="mt-6 text-center">
                <div class="inline-flex items-center text-xs text-gray-500 bg-gray-100 rounded-full px-4 py-2">
                    <i class="fas fa-shield-alt mr-2 text-green-500"></i>
                    Informations sécurisées et confidentielles
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8">
            <p class="text-white/60 text-sm">
                &copy; 2025 Tumaini Letu System. Tous droits réservés.
            </p>
        </div>
    </div>

    <script>
        // Fonction pour afficher/masquer le relevé
        function toggleReleve() {
            const releveContent = document.getElementById('releveContent');
            const toggleBtn = document.querySelector('[onclick="toggleReleve()"]');
            
            if (releveContent.classList.contains('hidden')) {
                // Afficher le relevé
                releveContent.classList.remove('hidden');
                releveContent.style.opacity = '0';
                releveContent.style.transform = 'translateY(-10px)';
                
                setTimeout(() => {
                    releveContent.style.transition = 'all 0.5s ease';
                    releveContent.style.opacity = '1';
                    releveContent.style.transform = 'translateY(0)';
                }, 50);
                
                toggleBtn.innerHTML = '<i class="fas fa-eye-slash mr-2"></i>Masquer le Relevé';
                toggleBtn.classList.remove('bg-green-500', 'hover:bg-green-600');
                toggleBtn.classList.add('bg-gray-500', 'hover:bg-gray-600');
            } else {
                // Masquer le relevé
                releveContent.style.opacity = '0';
                releveContent.style.transform = 'translateY(-10px)';
                
                setTimeout(() => {
                    releveContent.classList.add('hidden');
                }, 500);
                
                toggleBtn.innerHTML = '<i class="fas fa-eye mr-2"></i>Voir le Relevé';
                toggleBtn.classList.remove('bg-gray-500', 'hover:bg-gray-600');
                toggleBtn.classList.add('bg-green-500', 'hover:bg-green-600');
            }
        }

        // Filtrage des mouvements
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const mouvementRows = document.querySelectorAll('.mouvement-row');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Retirer la classe active de tous les boutons
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    // Ajouter la classe active au bouton cliqué
                    this.classList.add('active');
                    
                    const filterType = this.dataset.type;
                    
                    // Filtrer les lignes
                    mouvementRows.forEach(row => {
                        if (!filterType || row.dataset.type === filterType) {
                            row.classList.remove('hidden');
                        } else {
                            row.classList.add('hidden');
                        }
                    });
                });
            });
        });

        // Animation pour les cartes de statistiques
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>