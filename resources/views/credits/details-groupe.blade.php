<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du Crédit Groupe - Tumaini Letu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
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
        .progress-bar {
            background: linear-gradient(90deg, #10b981, #059669);
            height: 8px;
            border-radius: 4px;
            transition: width 1s ease-in-out;
        }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="w-full max-w-7xl mx-auto py-8 px-4">
        <!-- Header Section -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-2xl shadow-lg mb-4">
                <i class="fas fa-users text-3xl text-green-600"></i>
            </div>
            <h1 class="text-4xl font-bold text-white mb-3">Détails du Crédit Groupe</h1>
            <p class="text-white/80 text-lg">Vue d'ensemble complète du crédit groupe</p>
        </div>

        <!-- Main Details Card -->
        <div class="details-card rounded-2xl p-8">
            <!-- Credit Group Header -->
            <div class="text-center mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-2">
                    Crédit Groupe <span class="text-green-600">#{{ $credit->id }}</span>
                </h2>
                <div class="flex flex-wrap justify-center gap-4 text-sm text-gray-600 mb-4">
                    <div class="flex items-center bg-green-50 rounded-full px-4 py-2">
                        <i class="fas fa-users mr-2 text-green-500"></i>
                        <span class="font-medium">{{ $credit->compte->nom }}</span>
                    </div>
                    <div class="flex items-center bg-blue-50 rounded-full px-4 py-2">
                        <i class="fas fa-id-card mr-2 text-blue-500"></i>
                        <span class="font-medium">{{ $credit->compte->numero_compte }}</span>
                    </div>
                    <div class="flex items-center bg-purple-50 rounded-full px-4 py-2">
                        <i class="fas fa-wallet mr-2 text-purple-500"></i>
                        <span class="font-medium">{{ $credit->compte->devise }}</span>
                    </div>
                </div>
            </div>

            <!-- Credit Information Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Montant Accordé -->
                <div class="stat-card bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 border-l-green-400">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
                        </div>
                        <span class="text-2xl font-bold text-green-600">
                            {{ number_format($credit->montant_accorde ?? 0, 2, ',', ' ') }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 font-medium">Montant Accordé</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $credit->compte->devise }}</p>
                </div>

                <!-- Montant Total -->
                <div class="stat-card bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-6 border-l-purple-400">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-file-invoice-dollar text-purple-600 text-xl"></i>
                        </div>
                        <span class="text-2xl font-bold text-purple-600">
                            {{ number_format($credit->montant_total ?? 0, 2, ',', ' ') }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 font-medium">Total à Rembourser</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $credit->compte->devise }}</p>
                </div>

                <!-- Remboursement Hebdo -->
                <div class="stat-card bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border-l-blue-400">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-week text-blue-600 text-xl"></i>
                        </div>
                        <span class="text-2xl font-bold text-blue-600">
                            {{ number_format($credit->remboursement_hebdo_total ?? 0, 2, ',', ' ') }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 font-medium">Remb. Hebdo Total</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $credit->compte->devise }}</p>
                </div>

                <!-- Caution Totale -->
                <div class="stat-card bg-gradient-to-br from-orange-50 to-red-50 rounded-xl p-6 border-l-orange-400">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-shield-alt text-orange-600 text-xl"></i>
                        </div>
                        <span class="text-2xl font-bold text-orange-600">
                            {{ number_format($credit->caution_totale ?? 0, 2, ',', ' ') }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 font-medium">Caution Totale</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $credit->compte->devise }}</p>
                </div>
            </div>

            <!-- Timeline Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Dates importantes -->
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-history mr-2 text-blue-500"></i>
                        Chronologie du Crédit
                    </h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                            <div>
                                <span class="font-semibold text-gray-800">Date de Demande</span>
                                <p class="text-sm text-gray-600">Soumission initiale</p>
                            </div>
                            <span class="text-sm text-gray-500 bg-blue-100 px-2 py-1 rounded">
                                {{ $credit->date_demande->format('d/m/Y') }}
                            </span>
                        </div>
                        
                        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                            <div>
                                <span class="font-semibold text-gray-800">Date d'Octroi</span>
                                <p class="text-sm text-gray-600">Approbation du crédit</p>
                            </div>
                            <span class="text-sm text-gray-500 bg-green-100 px-2 py-1 rounded">
                                {{ $credit->date_octroi->format('d/m/Y') }}
                            </span>
                        </div>
                        
                        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                            <div>
                                <span class="font-semibold text-gray-800">Date d'Échéance</span>
                                <p class="text-sm text-gray-600">Fin du remboursement</p>
                            </div>
                            <span class="text-sm text-gray-500 bg-purple-100 px-2 py-1 rounded">
                                {{ $credit->date_echeance->format('d/m/Y') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Progression -->
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-6 border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-chart-bar mr-2 text-indigo-500"></i>
                        État du Remboursement
                    </h3>
                    
                   @php
    // Calculer le montant déjà remboursé à partir des paiements réels
    $totalPaiementsGroupe = \App\Models\PaiementCredit::where('credit_groupe_id', $credit->id)
        ->where('type_paiement', \App\Enums\TypePaiement::GROUPE->value)
        ->sum('montant_paye');
    
    $montantDejaRembourse = $totalPaiementsGroupe;
    
    // Calculer la progression basée sur les paiements réels
    $progress = ($credit->montant_accorde > 0) ? 
        ($montantDejaRembourse / $credit->montant_accorde) * 100 : 0;
    $progress = max(0, min(100, $progress));
    
    // Calculer le reste à payer
    $resteAPayer = max(0, $credit->montant_total - $montantDejaRembourse);
@endphp
                    
                    <div class="mb-4">
                        <div class="flex justify-between text-sm text-gray-600 mb-2">
                            <span>Progression globale</span>
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
                                {{ number_format($montantDejaRembourse, 2, ',', ' ') }} {{ $credit->compte->devise }}
                            </p>
                        </div>
                        <div class="bg-white rounded-lg p-3 border border-gray-200">
                            <p class="text-sm text-gray-600">Reste à Payer</p>
                           <p class="text-lg font-bold text-orange-600">
                                {{ number_format($resteAPayer, 2, ',', ' ') }} {{ $credit->compte->devise }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Répartition des Membres -->
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-6 border border-green-200 mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-users mr-2 text-green-500"></i>
                    Répartition entre les Membres
                </h3>
                
                @if($credit->repartition_membres && count($credit->repartition_membres) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-green-50 border-b border-green-200">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-green-500 uppercase tracking-wider">
                                        Membre
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-green-500 uppercase tracking-wider">
                                        Montant Accordé
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-green-500 uppercase tracking-wider">
                                        Remb. Hebdo
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-green-500 uppercase tracking-wider">
                                        Caution
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-green-500 uppercase tracking-wider">
                                        Total à Payer
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-green-500 uppercase tracking-wider">
                                        Statut
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($credit->repartition_membres as $membreId => $details)
    @php
        $compteMembre = \App\Models\Compte::where('client_id', $membreId)->first();
        
        // Calculer les paiements de ce membre pour ce crédit groupe
        $montantPayeParMembre = 0;
        if ($compteMembre) {
            $montantPayeParMembre = \App\Models\PaiementCredit::where('compte_id', $compteMembre->id)
                ->where('credit_groupe_id', $credit->id)
                ->sum('montant_paye');
        }
        
        // CORRECTION : Utiliser les bonnes valeurs
        $montantAccordeMembre = $details['montant_accorde'] ?? 0;
        $montantTotalDuMembre = $details['montant_total'] ?? 0;
        $remboursementHebdoMembre = $details['remboursement_hebdo'] ?? 0;
        $cautionMembre = $details['caution'] ?? 0;
        
        // Calculer la progression
        $montantRestantMembre = max(0, $montantTotalDuMembre - $montantPayeParMembre);
        $progressionMembre = ($montantTotalDuMembre > 0) 
            ? ($montantPayeParMembre / $montantTotalDuMembre) * 100 
            : 0;
        $progressionMembre = min(100, max(0, $progressionMembre));
        
        // Debug info
        // dd([
        //     'membre' => $membreId,
        //     'montant_paye' => $montantPayeParMembre,
        //     'montant_total' => $montantTotalDuMembre,
        //     'progression' => $progressionMembre
        // ]);
    @endphp
    
    @if($compteMembre)
        <tr class="border-b border-green-100 hover:bg-green-50 transition-colors">
            <td class="px-4 py-3">
                <div class="font-medium text-gray-800">{{ $compteMembre->nom }} {{ $compteMembre->prenom }}</div>
                <div class="text-xs text-gray-500">{{ $compteMembre->numero_compte }}</div>
            </td>
            <td class="px-4 py-3 text-right">
                {{ number_format($montantAccordeMembre, 2, ',', ' ') }} {{ $credit->compte->devise }}
            </td>
            <td class="px-4 py-3 text-right">
                {{ number_format($remboursementHebdoMembre, 2, ',', ' ') }} {{ $credit->compte->devise }}
            </td>
            <td class="px-4 py-3 text-right">
                {{ number_format($cautionMembre, 2, ',', ' ') }} {{ $credit->compte->devise }}
            </td>
            <td class="px-4 py-3 text-right">
                {{ number_format($montantTotalDuMembre, 2, ',', ' ') }} {{ $credit->compte->devise }}
                <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ $progressionMembre }}%"></div>
                </div>
                <div class="text-xs text-gray-500 mt-1">
                    {{ number_format($progressionMembre, 1) }}% (Payé: {{ number_format($montantPayeParMembre, 2, ',', ' ') }})
                </div>
            </td>
            <td class="px-4 py-3 text-center">
                @if($montantRestantMembre <= 0)
                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                        <i class="fas fa-check mr-1"></i> Payé
                    </span>
                @elseif($montantPayeParMembre > 0)
                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                        <i class="fas fa-clock mr-1"></i> Partiel
                    </span>
                @else
                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                        <i class="fas fa-clock mr-1"></i> En attente
                    </span>
                @endif
            </td>
        </tr>
    @endif
@endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-green-100">
                                    <td class="px-4 py-3 font-semibold text-gray-800">TOTAL</td>
                                    <td class="px-4 py-3 text-right font-bold text-green-700">
                                        {{ number_format($credit->montant_accorde, 2, ',', ' ') }} {{ $credit->compte->devise }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-blue-700">
                                        {{ number_format($credit->remboursement_hebdo_total, 2, ',', ' ') }} {{ $credit->compte->devise }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-orange-700">
                                        {{ number_format($credit->caution_totale, 2, ',', ' ') }} {{ $credit->compte->devise }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-purple-700">
                                        {{ number_format($credit->montant_total, 2, ',', ' ') }} {{ $credit->compte->devise }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">
                                            Groupe
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-users text-gray-300 text-4xl mb-3"></i>
                        <p class="text-gray-500">Aucune répartition disponible</p>
                    </div>
                @endif
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 mt-8 pt-6 border-t border-gray-200">
                <a 
                    href="{{ route('credits.echeanciers-groupe', $credit->id) }}" 
                    class="flex-1 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center"
                >
                    <i class="fas fa-calendar-alt mr-3"></i>
                    Voir les Échéanciers
                </a>
                
                <a 
                    href="{{ route('comptes.details', $credit->compte_id) }}" 
                    class="flex-1 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center"
                >
                    <i class="fas fa-eye mr-3"></i>
                    Voir Détails du Groupe
                </a>
                
                <a 
                    href="{{ url('/admin/comptes') }}" 
                    class="flex-1 bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center"
                >
                    <i class="fas fa-arrow-left mr-3"></i>
                    Retour aux Comptes
                </a>
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