@extends('layouts.app')

@section('title', 'Tableau de Bord Crédits')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Tableau de Bord Crédits</h1>
            <p class="text-gray-600">Vue d'ensemble de votre portefeuille de crédits</p>
        </div>
        <div class="flex space-x-3">
            <button class="bg-white border border-gray-300 rounded-lg px-4 py-2 text-gray-700 hover:bg-gray-50">
                <i class="fas fa-download mr-2"></i>Exporter
            </button>
            <button class="bg-blue-600 text-white rounded-lg px-4 py-2 hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Nouveau Crédit
            </button>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Crédits Actifs -->
        <div class="stat-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Crédits Actifs</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">
                        {{ number_format($portefeuilleTotal['montant_total_encours'], 0) }} USD
                    </p>
                </div>
                <div class="p-3 bg-blue-50 rounded-lg">
                    <i class="fas fa-wallet text-blue-500 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm text-green-600">
                <i class="fas fa-arrow-up mr-1"></i>
                <span>12% vs mois dernier</span>
            </div>
        </div>

        <!-- Remboursé ce mois -->
        <div class="stat-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Remboursé ce mois</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">
                        {{ number_format($portefeuilleTotal['total_rembourse_ce_mois'], 0) }} USD
                    </p>
                </div>
                <div class="p-3 bg-green-50 rounded-lg">
                    <i class="fas fa-money-bill-wave text-green-500 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm text-green-600">
                <i class="fas fa-arrow-up mr-1"></i>
                <span>8% vs mois dernier</span>
            </div>
        </div>

        <!-- En attente -->
        <div class="stat-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">En attente</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">
                        {{ $portefeuilleTotal['credits_en_attente'] }}
                    </p>
                </div>
                <div class="p-3 bg-yellow-50 rounded-lg">
                    <i class="fas fa-clock text-yellow-500 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm text-red-600">
                <i class="fas fa-arrow-down mr-1"></i>
                <span>3% vs mois dernier</span>
            </div>
        </div>

        <!-- Approuvés ce mois -->
        <div class="stat-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Approuvés ce mois</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">
                        {{ $portefeuilleTotal['credits_approuves_ce_mois'] }}
                    </p>
                </div>
                <div class="p-3 bg-purple-50 rounded-lg">
                    <i class="fas fa-check-circle text-purple-500 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm text-green-600">
                <i class="fas fa-arrow-up mr-1"></i>
                <span>15% vs mois dernier</span>
            </div>
        </div>
    </div>

    <!-- Charts and Performance -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Graphique de performance -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Performance des Remboursements</h3>
                <select class="border border-gray-300 rounded-lg px-3 py-1 text-sm">
                    <option>30 derniers jours</option>
                    <option>3 derniers mois</option>
                    <option>Cette année</option>
                </select>
            </div>
            <div class="h-80">
                <canvas id="performanceChart"></canvas>
            </div>
        </div>

        <!-- Performance des agents -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Performance des Agents</h3>
            <div class="space-y-4">
                @foreach($performanceAgents as $agent)
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold">
                            {{ substr($agent->name, 0, 1) }}
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">{{ $agent->name }}</p>
                            <p class="text-sm text-gray-500">{{ $agent->role }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-gray-900">{{ $agent->total_credits_geres }} crédits</p>
                        <p class="text-sm text-gray-500">{{ number_format($agent->montant_total_geres, 0) }} USD</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Liste des Crédits -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="border-b border-gray-200">
            <div class="flex justify-between items-center p-6">
                <h3 class="text-lg font-semibold text-gray-900">Tous les Crédits</h3>
                <div class="flex space-x-3">
                    <input type="text" placeholder="Rechercher..." class="border border-gray-300 rounded-lg px-4 py-2 text-sm">
                    <select class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option>Tous les statuts</option>
                        <option>Approuvé</option>
                        <option>En attente</option>
                        <option>Rejeté</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Navigation des tabs -->
        <div class="border-b border-gray-200">
            <nav class="flex space-x-8 px-6">
                <button class="py-4 px-1 border-b-2 border-blue-500 text-blue-600 font-medium">
                    Crédits Individuels
                </button>
                <button class="py-4 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium">
                    Crédits de Groupe
                </button>
            </nav>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Compte & Client
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Montant
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Agent
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Statut
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Date
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($creditsIndividuels as $credit)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-user text-blue-600"></i>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $credit->compte->numero_compte }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $credit->compte->client->nom_complet ?? 'N/A' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-gray-900">
                                {{ number_format($credit->montant_total, 0) }} USD
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $credit->duree_mois }} mois
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $credit->agent->name ?? 'N/A' }}</div>
                            <div class="text-sm text-gray-500">Agent</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusColors = [
                                    'approuve' => 'bg-green-100 text-green-800',
                                    'en_attente' => 'bg-yellow-100 text-yellow-800',
                                    'rejete' => 'bg-red-100 text-red-800'
                                ];
                            @endphp
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$credit->statut_demande] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $credit->statut_demande }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $credit->date_octroi?->format('d/m/Y') ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="{{ route('credits.details', [$credit->id, 'individuel']) }}" 
                                   class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="#" class="text-green-600 hover:text-green-900">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="#" class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="bg-white px-6 py-4 border-t border-gray-200">
            {{ $creditsIndividuels->links() }}
        </div>
    </div>

    <!-- Situation des Comptes -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Situation des Comptes</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Compte
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Type
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Total Crédits
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Remboursé
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Reste
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Progression
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($situationComptes as $compte)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $compte['numero_compte'] }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $compte['nom'] }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $compte['type_compte'] == 'individuel' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                {{ $compte['type_compte'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                            {{ number_format($compte['total_credits'], 0) }} USD
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                            {{ number_format($compte['total_rembourse'], 0) }} USD
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold 
                            {{ $compte['reste_a_rembourser'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ number_format($compte['reste_a_rembourser'], 0) }} USD
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $percentage = $compte['total_credits'] > 0 ? 
                                    ($compte['total_rembourse'] / $compte['total_credits']) * 100 : 0;
                            @endphp
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" 
                                     style="width: {{ $percentage }}%"></div>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">{{ round($percentage, 1) }}%</div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Graphique de performance
    const ctx = document.getElementById('performanceChart').getContext('2d');
    const performanceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
            datasets: [{
                label: 'Remboursements (USD)',
                data: [12000, 19000, 15000, 25000, 22000, 30000, 28000, 32000, 35000, 40000, 38000, 42000],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // Animation des cartes statistiques
    document.addEventListener('DOMContentLoaded', function() {
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
            card.classList.add('animate-fade-in-up');
        });
    });
</script>

<style>
    .animate-fade-in-up {
        animation: fadeInUp 0.6s ease-out;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
@endsection