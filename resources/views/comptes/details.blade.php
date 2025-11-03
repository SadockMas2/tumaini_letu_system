<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du Compte - Tumaini Letu</title>
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
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
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
        .progress-bar {
            background: linear-gradient(90deg, #10b981, #059669);
            height: 8px;
            border-radius: 4px;
            transition: width 1s ease-in-out;
        }
        .timeline-item {
            position: relative;
            padding-left: 2rem;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0.5rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #667eea;
        }
        .empty-state {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
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
    <div class="w-full max-w-6xl mx-4 my-8">
        <!-- Header Section -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-2xl shadow-lg mb-4">
                <i class="fas fa-file-invoice-dollar text-3xl text-purple-600"></i>
            </div>
            <h1 class="text-4xl font-bold text-white mb-3">Détails du Compte</h1>
            <p class="text-white/80 text-lg">Vue d'ensemble complète de votre compte</p>
        </div>

        <!-- Main Details Card -->
        <div class="details-card rounded-2xl p-8">
            <!-- Account Header -->
            <div class="text-center mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-2">
                    Compte <span class="text-purple-600">{{ $compte->numero_compte }}</span>
                </h2>
                <div class="flex flex-wrap justify-center gap-4 text-sm text-gray-600 mb-4">
                    <div class="flex items-center bg-blue-50 rounded-full px-4 py-2">
                        <i class="fas fa-user mr-2 text-blue-500"></i>
                        <span class="font-medium">{{ $compte->nom }} {{ $compte->prenom }}</span>
                    </div>
                    <div class="flex items-center bg-green-50 rounded-full px-4 py-2">
                        <i class="fas fa-id-card mr-2 text-green-500"></i>
                        <span class="font-medium">{{ $compte->numero_membre }}</span>
                    </div>
                    <div class="flex items-center bg-purple-50 rounded-full px-4 py-2">
                        <i class="fas fa-wallet mr-2 text-purple-500"></i>
                        <span class="font-medium">{{ $compte->devise }}</span>
                    </div>
                    @if(str_starts_with($compte->numero_compte, 'GS'))
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
                <div class="stat-card bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border-l-blue-400">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-wallet text-blue-600 text-xl"></i>
                        </div>
                        <span class="text-2xl font-bold text-blue-600">
                            {{ number_format($compte->solde, 2, ',', ' ') }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 font-medium">Solde Actuel</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $compte->devise }}</p>
                </div>

                <!-- Statut du Compte -->
                <div class="stat-card bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 border-l-green-400">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                        @php
                            $statutColor = $compte->statut === 'actif' ? 'text-green-600' : 'text-red-600';
                            $statutIcon = $compte->statut === 'actif' ? 'fa-check' : 'fa-pause';
                        @endphp
                        <span class="text-xl font-bold {{ $statutColor }}">
                            <i class="fas {{ $statutIcon }} mr-1"></i>
                            {{ ucfirst($compte->statut) }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 font-medium">Statut du Compte</p>
                    <p class="text-xs text-gray-500 mt-1">État actuel</p>
                </div>

                <!-- Crédits Actifs -->
                <div class="stat-card bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-6 border-l-purple-400">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-credit-card text-purple-600 text-xl"></i>
                        </div>
                        <span class="text-2xl font-bold text-purple-600">
                            {{ $compte->credits->where('statut_demande', 'approuve')->count() + $compte->creditsGroupe->where('statut_demande', 'approuve')->count() }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 font-medium">Crédits Actifs</p>
                    <p class="text-xs text-gray-500 mt-1">En cours de remboursement</p>
                </div>

                <!-- Demandes en Attente -->
                <div class="stat-card bg-gradient-to-br from-orange-50 to-red-50 rounded-xl p-6 border-l-orange-400">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-orange-600 text-xl"></i>
                        </div>
                        <span class="text-2xl font-bold text-orange-600">
                            {{ $compte->credits->where('statut_demande', 'en_attente')->count() + $compte->creditsGroupe->where('statut_demande', 'en_attente')->count() }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 font-medium">En Attente</p>
                    <p class="text-xs text-gray-500 mt-1">Demandes de crédit</p>
                </div>
            </div>

            <!-- Section Crédits Individuels -->
            @if($compte->credits->where('statut_demande', 'approuve')->count() > 0)
                @php
                    $creditActif = $compte->credits->where('statut_demande', 'approuve')->first();
                @endphp
                <!-- Quick Stats Grid for Active Credit -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Montant Accordé -->
                    <div class="stat-card bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border-l-blue-400">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-money-bill-wave text-blue-600 text-xl"></i>
                            </div>
                            <span class="text-2xl font-bold text-blue-600">
                                {{ number_format($creditActif->montant_accorde, 2, ',', ' ') }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 font-medium">Montant Accordé</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $compte->devise }}</p>
                    </div>

                    <!-- Montant Total Dû -->
                    <div class="stat-card bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-6 border-l-purple-400">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-file-invoice-dollar text-purple-600 text-xl"></i>
                            </div>
                            <span class="text-2xl font-bold text-purple-600">
                                {{ number_format($creditActif->montant_total, 2, ',', ' ') }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 font-medium">Total Dû</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $compte->devise }}</p>
                    </div>

                    <!-- Remboursement Hebdo -->
                    <div class="stat-card bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 border-l-green-400">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-calendar-week text-green-600 text-xl"></i>
                            </div>
                            <span class="text-2xl font-bold text-green-600">
                                {{ number_format($creditActif->remboursement_hebdo, 2, ',', ' ') }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 font-medium">Remb. Hebdo</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $compte->devise }}</p>
                    </div>

                    <!-- Type de Crédit -->
                    <div class="stat-card bg-gradient-to-br from-orange-50 to-red-50 rounded-xl p-6 border-l-orange-400">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-tags text-orange-600 text-xl"></i>
                            </div>
                            <span class="text-xl font-bold text-orange-600 capitalize">
                                {{ $creditActif->type_credit }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 font-medium">Type Crédit</p>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $creditActif->type_credit === 'individuel' ? 'Individuel' : 'Groupe' }}
                        </p>
                    </div>
                </div>

                <!-- Progress and Timeline Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <!-- Progress Section -->
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-6 border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-chart-bar mr-2 text-indigo-500"></i>
                            Progression du Remboursement
                        </h3>
                        
                        @php
                            $montantDejaRembourse = $creditActif->montant_accorde - $creditActif->montant_total;
                            $progress = ($montantDejaRembourse / $creditActif->montant_accorde) * 100;
                            $progress = max(0, min(100, $progress));
                        @endphp
                        
                        <div class="mb-4">
                            <div class="flex justify-between text-sm text-gray-600 mb-2">
                                <span>Progression</span>
                                <span class="font-semibold">{{ number_format($progress, 1) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="progress-bar rounded-full h-3" style="width: {{ $progress }}%"></div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 text-center">
                            <div class="bg-white rounded-lg p-3 border border-gray-200">
                                <p class="text-sm text-gray-600">Déjà Remboursé</p>
                                <p class="text-lg font-bold text-green-600">
                                    {{ number_format($montantDejaRembourse, 2, ',', ' ') }} {{ $compte->devise }}
                                </p>
                            </div>
                            <div class="bg-white rounded-lg p-3 border border-gray-200">
                                <p class="text-sm text-gray-600">Reste à Payer</p>
                                <p class="text-lg font-bold text-orange-600">
                                    {{ number_format($creditActif->montant_total, 2, ',', ' ') }} {{ $compte->devise }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline Section -->
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-history mr-2 text-blue-500"></i>
                            Chronologie du Crédit
                        </h3>
                        
                        <div class="space-y-4">
                            <div class="timeline-item">
                                <div class="bg-white rounded-lg p-4 border border-gray-200">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="font-semibold text-gray-800">Date d'Octroi</span>
                                        <span class="text-sm text-gray-500 bg-blue-100 px-2 py-1 rounded">
                                            {{ $creditActif->date_octroi->format('d/m/Y') }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600">Début du contrat de crédit</p>
                                </div>
                            </div>
                            
                            <div class="timeline-item">
                                <div class="bg-white rounded-lg p-4 border border-gray-200">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="font-semibold text-gray-800">Date d'Échéance</span>
                                        <span class="text-sm text-gray-500 bg-green-100 px-2 py-1 rounded">
                                            {{ $creditActif->date_echeance->format('d/m/Y') }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600">Date limite de remboursement</p>
                                </div>
                            </div>
                            
                            <div class="timeline-item">
                                <div class="bg-white rounded-lg p-4 border border-gray-200">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="font-semibold text-gray-800">Prochain Remboursement</span>
                                        <span class="text-sm text-gray-500 bg-purple-100 px-2 py-1 rounded">
                                            {{ number_format($creditActif->remboursement_hebdo, 2, ',', ' ') }} {{ $compte->devise }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600">Montant hebdomadaire</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @elseif($compte->creditsGroupe->where('statut_demande', 'approuve')->count() > 0)
                <!-- Section pour les crédits groupe actifs -->
                @php
                    $creditGroupeActif = $compte->creditsGroupe->where('statut_demande', 'approuve')->first();
                @endphp
                <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-xl p-6 border border-purple-200 mb-8">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-users mr-2 text-purple-500"></i>
                        Crédit Groupe Actif
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                        <div class="bg-white rounded-lg p-4 border border-purple-100">
                            <p class="text-sm text-gray-600">Montant Accordé</p>
                            <p class="text-xl font-bold text-purple-600">
                                {{ number_format($creditGroupeActif->montant_accorde ?? 0, 2, ',', ' ') }} {{ $compte->devise }}
                            </p>
                        </div>
                        <div class="bg-white rounded-lg p-4 border border-purple-100">
                            <p class="text-sm text-gray-600">Montant Total</p>
                            <p class="text-xl font-bold text-purple-600">
                                {{ number_format($creditGroupeActif->montant_total ?? 0, 2, ',', ' ') }} {{ $compte->devise }}
                            </p>
                        </div>
                        <div class="bg-white rounded-lg p-4 border border-purple-100">
                            <p class="text-sm text-gray-600">Remb. Hebdo Total</p>
                            <p class="text-xl font-bold text-purple-600">
                                {{ number_format($creditGroupeActif->remboursement_hebdo_total ?? 0, 2, ',', ' ') }} {{ $compte->devise }}
                            </p>
                        </div>
                        <div class="bg-white rounded-lg p-4 border border-purple-100">
                            <p class="text-sm text-gray-600">Caution Totale</p>
                            <p class="text-xl font-bold text-purple-600">
                                {{ number_format($creditGroupeActif->caution_totale ?? 0, 2, ',', ' ') }} {{ $compte->devise }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex space-x-4">
                        <a href="{{ route('credits.details-groupe', $creditGroupeActif->id) }}" 
                           class="bg-purple-500 hover:bg-purple-600 text-white font-semibold py-2 px-4 rounded-lg transition-all duration-200 flex items-center">
                            <i class="fas fa-eye mr-2"></i>
                            Voir Détails
                        </a>
                        <a href="{{ route('credits.echeanciers-groupe', $creditGroupeActif->id) }}" 
                           class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg transition-all duration-200 flex items-center">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            Échéanciers
                        </a>
                    </div>
                </div>
            @else
                <!-- Empty State for No Credits -->
                <div class="empty-state rounded-2xl p-12 text-center mb-8 border-2 border-dashed border-gray-300">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-credit-card text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-semibold text-gray-600 mb-3">Aucun Crédit Actif</h3>
                    <p class="text-gray-500 mb-6 max-w-md mx-auto">
                        Ce compte n'a pas encore de crédit en cours. Vous pouvez demander un nouveau crédit en cliquant sur le bouton ci-dessous.
                    </p>
                    {{-- <a 
                        href="{{ route('credits.create', $compte->id) }}" 
                        class="inline-flex items-center bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg"
                    >
                        <i class="fas fa-plus-circle mr-2"></i>
                        Demander un Premier Crédit
                    </a> --}}
                </div>
            @endif

            <!-- Credits History Table - Individuels -->
            @if($compte->credits->count() > 0)
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-8">
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-user mr-2 text-indigo-500"></i>
                        Historique des Crédits Individuels
                    </h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Type
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Montant Accordé
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Montant Restant
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Remb. Hebdo
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date Début
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date Fin
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Statut
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($compte->credits as $credit)
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <i class="fas fa-user mr-1"></i>
                                        Individuel
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ number_format($credit->montant_accorde ?? 0, 2, ',', ' ') }} {{ $compte->devise }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium {{ ($credit->montant_total ?? 0) > 0 ? 'text-orange-600' : 'text-green-600' }}">
                                        {{ number_format($credit->montant_total ?? 0, 2, ',', ' ') }} {{ $compte->devise }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ $credit->remboursement_hebdo ? number_format($credit->remboursement_hebdo, 2, ',', ' ') : 'N/A' }} {{ $compte->devise }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ $credit->date_octroi ? $credit->date_octroi->format('d/m/Y') : 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ $credit->date_echeance ? $credit->date_echeance->format('d/m/Y') : 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusColors = [
                                            'en_attente' => 'bg-yellow-100 text-yellow-800',
                                            'approuve' => 'bg-green-100 text-green-800',
                                            'rejete' => 'bg-red-100 text-red-800',
                                            'rembourse' => 'bg-blue-100 text-blue-800'
                                        ];
                                        $colorClass = $statusColors[$credit->statut_demande] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorClass }}">
                                        <i class="fas 
                                            {{ $credit->statut_demande === 'en_attente' ? 'fa-clock' : 
                                               ($credit->statut_demande === 'approuve' ? 'fa-check' : 
                                               ($credit->statut_demande === 'rembourse' ? 'fa-flag-checkered' : 'fa-times')) }} 
                                            mr-1">
                                        </i>
                                        {{ ucfirst($credit->statut_demande) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- Credits History Table - Groupe -->
            @if($compte->creditsGroupe->count() > 0)
            <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-xl border border-purple-200 overflow-hidden mb-8">
                <div class="bg-gradient-to-r from-purple-100 to-indigo-100 px-6 py-4 border-b border-purple-200">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-users mr-2 text-purple-500"></i>
                        Historique des Crédits Groupe
                    </h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-purple-50 border-b border-purple-200">
                                <th class="px-6 py-3 text-left text-xs font-medium text-purple-500 uppercase tracking-wider">
                                    Montant Accordé
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-purple-500 uppercase tracking-wider">
                                    Montant Total
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-purple-500 uppercase tracking-wider">
                                    Remb. Hebdo Total
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-purple-500 uppercase tracking-wider">
                                    Date Début
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-purple-500 uppercase tracking-wider">
                                    Date Fin
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-purple-500 uppercase tracking-wider">
                                    Statut
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-purple-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-purple-100">
                            @foreach($compte->creditsGroupe as $creditGroupe)
                            <tr class="hover:bg-purple-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ number_format($creditGroupe->montant_accorde ?? 0, 2, ',', ' ') }} {{ $compte->devise }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-purple-600">
                                        {{ number_format($creditGroupe->montant_total ?? 0, 2, ',', ' ') }} {{ $compte->devise }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ number_format($creditGroupe->remboursement_hebdo_total ?? 0, 2, ',', ' ') }} {{ $compte->devise }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ $creditGroupe->date_octroi ? $creditGroupe->date_octroi->format('d/m/Y') : 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ $creditGroupe->date_echeance ? $creditGroupe->date_echeance->format('d/m/Y') : 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusColors = [
                                            'en_attente' => 'bg-yellow-100 text-yellow-800',
                                            'approuve' => 'bg-green-100 text-green-800',
                                            'rejete' => 'bg-red-100 text-red-800',
                                            'rembourse' => 'bg-blue-100 text-blue-800'
                                        ];
                                        $colorClass = $statusColors[$creditGroupe->statut_demande] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorClass }}">
                                        <i class="fas 
                                            {{ $creditGroupe->statut_demande === 'en_attente' ? 'fa-clock' : 
                                               ($creditGroupe->statut_demande === 'approuve' ? 'fa-check' : 
                                               ($creditGroupe->statut_demande === 'rembourse' ? 'fa-flag-checkered' : 'fa-times')) }} 
                                            mr-1">
                                        </i>
                                        {{ ucfirst($creditGroupe->statut_demande) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex space-x-2">
                                        @if($creditGroupe->statut_demande === 'approuve')
                                            <a 
                                                href="{{ route('credits.details-groupe', $creditGroupe->id) }}" 
                                                class="inline-flex items-center px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs hover:bg-purple-200 transition-colors"
                                                title="Voir détails"
                                            >
                                                <i class="fas fa-eye mr-1"></i>
                                                Détails
                                            </a>
                                            <a 
                                                href="{{ route('credits.echeanciers-groupe', $creditGroupe->id) }}" 
                                                class="inline-flex items-center px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs hover:bg-green-200 transition-colors"
                                                title="Échéanciers"
                                            >
                                                <i class="fas fa-calendar mr-1"></i>
                                                Échéanciers
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            @if($compte->credits->count() === 0 && $compte->creditsGroupe->count() === 0)
            <div class="text-center py-12">
                <i class="fas fa-inbox text-gray-300 text-5xl mb-4"></i>
                <p class="text-gray-500 text-lg">Aucun crédit dans l'historique</p>
            </div>
            @endif

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 mt-8 pt-6 border-t border-gray-200">
                @if($compte->credits->where('statut_demande', 'approuve')->count() > 0 || $compte->creditsGroupe->where('statut_demande', 'approuve')->count() > 0)
                    <a 
                        href="{{ route('credits.payment', $compte->id) }}" 
                        class="flex-1 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center"
                    >
                        <i class="fas fa-credit-card mr-3"></i>
                        Effectuer un Paiement
                    </a>
                @endif
                
                @if($compte->credits->where('statut_demande', 'en_attente')->count() > 0)
                    <a 
                        href="{{ route('credits.approval', $compte->credits->where('statut_demande', 'en_attente')->first()->id) }}" 
                        class="flex-1 bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center"
                    >
                        <i class="fas fa-check-circle mr-3"></i>
                        Traiter Demande Individuelle
                    </a>
                @endif

                @if($compte->creditsGroupe->where('statut_demande', 'en_attente')->count() > 0)
                    <a 
                        href="{{ route('credits.approval-groupe', $compte->creditsGroupe->where('statut_demande', 'en_attente')->first()->id) }}" 
                        class="flex-1 bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center"
                    >
                        <i class="fas fa-users mr-3"></i>
                        Traiter Demande Groupe
                    </a>
                @endif
                
                {{-- <a 
                    href="{{ route('credits.create', $compte->id) }}" 
                    class="flex-1 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center"
                >
                    <i class="fas fa-plus-circle mr-3"></i>
                    Nouveau Crédit
                </a> --}}
                
                <a 
                    href="{{ url('/admin/comptes') }}" 
                    class="flex-1 bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center"
                >
                    <i class="fas fa-arrow-left mr-3"></i>
                    Retour aux Comptes
                </a>
            </div>

            @if($compte->credits->where('statut_demande', 'approuve')->count() > 0)
            <a 
                href="{{ route('credits.echeancier', $compte->credits->where('statut_demande', 'approuve')->first()->id) }}" 
                target="_blank"
                class="flex-1 bg-gradient-to-r from-purple-500 to-pink-600 hover:from-purple-600 hover:to-pink-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center"
            >
                <i class="fas fa-calendar-alt mr-3"></i>
                Échéancier
            </a>
            @endif

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
        // Animation pour les cartes de statistiques
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
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