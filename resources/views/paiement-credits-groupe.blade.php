<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement Crédits Groupe - Tumaini Letu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
    .toggle-switch:checked + label {
        background-color: #10b981;
    }
    
    .toggle-switch:checked + label span {
        transform: translateX(1rem);
    }
    
    .mode-complement .member-card:not(.membre-selectionne) {
        opacity: 0.6;
        background-color: #f9fafb;
    }
    
    .mode-complement .member-card:not(.membre-selectionne) .paiement-input {
        background-color: #f3f4f6;
        color: #6b7280;
    }
    
    .membre-selectionne {
        border-left-color: #10b981;
        background-color: #f0fdf4;
    }
</style>
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

        <!-- Messages de statut -->
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-xl">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-3 text-green-600"></i>
                    <span class="font-semibold">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-xl">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-3 text-red-600"></i>
                    <span class="font-semibold">{{ session('error') }}</span>
                </div>
            </div>
        @endif

@if(session('paiement_success'))
    <div class="mb-6 bg-white rounded-xl p-6 shadow-lg">
        <h3 class="text-xl font-bold text-green-600 mb-4 flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            Paiement Groupe Terminé - {{ session('credit_groupe_nom') }}
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
            <div class="bg-blue-50 p-4 rounded-lg">
                <p class="text-sm text-blue-600">Total Prélevé Groupe</p>
                <p class="text-2xl font-bold text-blue-700">{{ number_format(session('total_paiement_groupe'), 2) }} USD</p>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg">
                <p class="text-sm text-purple-600">Prélevé Membres</p>
                <p class="text-2xl font-bold text-purple-700">
                    {{ number_format(collect(session('results'))->sum('montant_preleve_membre'), 2) }} USD
                </p>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <p class="text-sm text-green-600">Avec Complément</p>
                <p class="text-2xl font-bold text-green-700">
                    {{ collect(session('results'))->where('montant_preleve_membre', '>', 0)->count() }}
                </p>
            </div>
            <div class="bg-orange-50 p-4 rounded-lg">
                <p class="text-sm text-orange-600">Avec Excédent</p>
                <p class="text-2xl font-bold text-orange-700">
                    {{ collect(session('results'))->where('montant_excedent', '>', 0)->count() }}
                </p>
            </div>
            <div class="bg-red-50 p-4 rounded-lg">
                <p class="text-sm text-red-600">Échecs</p>
                <p class="text-2xl font-bold text-red-700">
                    {{ collect(session('results'))->where('statut', 'echec')->count() }}
                </p>
            </div>
        </div>
        
        <div class="space-y-2">
            <h4 class="font-semibold text-gray-700 mb-2">Détails par membre:</h4>
            @foreach(session('results') as $result)
                @php
                    if ($result['statut'] === 'succes') {
                        if ($result['montant_excedent'] > 0) {
                            $bgColor = 'bg-purple-50';
                            $icon = 'fa-plus-circle text-purple-600';
                        } elseif ($result['montant_preleve_membre'] > 0) {
                            $bgColor = 'bg-green-50';
                            $icon = 'fa-wallet text-green-600';
                        } else {
                            $bgColor = 'bg-blue-50';
                            $icon = 'fa-check-circle text-blue-600';
                        }
                    } else {
                        $bgColor = 'bg-red-50';
                        $icon = 'fa-times-circle text-red-600';
                    }
                @endphp
                <div class="{{ $bgColor }} p-3 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center">
                            <i class="fas {{ $icon }} mr-3"></i>
                            <span class="font-medium">{{ $result['compte'] }}</span>
                        </div>
                        <div class="text-right">
                            <span class="font-semibold">{{ number_format($result['montant_apporte'], 2) }} USD</span>
                            <span class="text-sm text-gray-600 ml-2">apportés</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>Prélevé du groupe: <span class="font-semibold">{{ number_format($result['montant_preleve_groupe'], 2) }} USD</span></div>
                        <div>Dû: <span class="font-semibold">{{ number_format($result['montant_du'], 2) }} USD</span></div>
                        @if($result['montant_preleve_membre'] > 0)
                            <div class="col-span-2 text-green-600">
                                <i class="fas fa-wallet mr-1"></i>
                                Complément du membre: <span class="font-semibold">{{ number_format($result['montant_preleve_membre'], 2) }} USD</span>
                            </div>
                        @endif
                        @if($result['montant_excedent'] > 0)
                            <div class="col-span-2 text-purple-600">
                                <i class="fas fa-arrow-right mr-1"></i>
                                Excédent crédité au membre: <span class="font-semibold">{{ number_format($result['montant_excedent'], 2) }} USD</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

        <!-- Main Card -->
        <div class="payment-card rounded-2xl p-8">
            <!-- Sélection du groupe -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200 mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-users mr-2 text-blue-500"></i>
                    Sélection du Groupe
                </h3>
                
                <form method="GET" action="{{ route('paiement.credits.groupe') }}" id="groupeForm">
                    <select 
                        name="selected_groupe_id"
                        onchange="document.getElementById('groupeForm').submit()"
                        class="w-full border border-gray-300 rounded-xl p-4 text-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                    >
                        <option value="">Choisir un groupe...</option>
                        @foreach($groupesActifs as $groupe)
                            <option value="{{ $groupe->id }}" 
                                {{ request('selected_groupe_id') == $groupe->id ? 'selected' : '' }}>
                                {{ $groupe->compte->nom ?? 'Groupe '.$groupe->id }} - 
                                Montant restant: {{ number_format($groupe->montant_restant, 2) }} USD
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            @if(request('selected_groupe_id'))
                @php
                    $groupeSelectionne = $groupesActifs->firstWhere('id', request('selected_groupe_id'));
                @endphp
                
                @if($groupeSelectionne)
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
    <p class="text-xs text-gray-500 mt-1">
        Capital remboursé: {{ number_format($groupeSelectionne->capital_rembourse_total, 2) }} USD
        | Total payé: {{ number_format($groupeSelectionne->total_deja_paye, 2) }} USD
    </p>
    <p class="text-xs text-green-600 mt-1">
        Dû jusqu'à présent: {{ number_format($groupeSelectionne->montant_du_jusqu_present, 2) }} USD
    </p>
    
    <div class="mt-2 p-2 bg-blue-50 rounded-lg">
        <div class="flex justify-between items-center">
            <span class="text-xs text-blue-700 font-medium">Solde du compte groupe:</span>
            <span class="text-sm font-bold text-blue-800">
                {{ number_format($groupeSelectionne->compte->solde, 2) }} USD
            </span>
        </div>
        <div class="flex justify-between items-center mt-1">
            <span class="text-xs text-red-600">Caution bloquée:</span>
            <span class="text-xs font-semibold text-red-600">
                {{ number_format(App\Models\Mouvement::getCautionBloquee($groupeSelectionne->compte->id), 2) }} USD
            </span>
        </div>
        <div class="flex justify-between items-center mt-1">
            <span class="text-xs text-green-700 font-medium">Solde disponible:</span>
            <span class="text-xs font-bold text-green-700">
                {{ number_format(App\Models\Mouvement::getSoldeDisponible($groupeSelectionne->compte->id), 2) }} USD
            </span>
        </div>
    </div>
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
                                <p class="font-semibold text-gray-800">{{ intval($groupeSelectionne->semaine_actuelle) }}/16</p>
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
                            Paiements des Membres - Semaine {{ intval($groupeSelectionne->semaine_actuelle) }}
                        </h4>

                  <!-- Mode de paiement -->
    <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-cog mr-2 text-yellow-600"></i>
                <span class="font-medium text-gray-700">Mode de paiement:</span>
            </div>
            <div class="flex space-x-4">
                <button type="button" 
                        onclick="activerMode('normal')"
                        id="btnModeNormal"
                        class="px-4 py-2 bg-green-500 text-white rounded-lg font-medium flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    Normal
                </button>
                <button type="button" 
                        onclick="activerMode('complement')"
                        id="btnModeComplement"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg font-medium flex items-center">
                    <i class="fas fa-user-check mr-2"></i>
                    Complément uniquement
                </button>
            </div>
        </div>
        <div class="mt-2 text-sm text-yellow-700" id="explicationMode">
            <i class="fas fa-info-circle mr-1"></i>
            Mode normal: Tous les membres paient normalement. Mode complément: Seuls les membres cochés seront complétés depuis leur compte.
        </div>
    </div>

    <form method="POST" action="{{ route('paiement.credits.groupe.processer') }}" id="paiementForm">
        @csrf
        <input type="hidden" name="selected_groupe_id" value="{{ request('selected_groupe_id') }}">
        <input type="hidden" name="mode_paiement" id="modePaiement" value="normal">
        
        <div class="space-y-4" id="listeMembres">
            @foreach($groupeSelectionne->membres_avec_soldes as $membre)
            @php
                // Calculer le solde disponible réel
                $soldeReel = $membre['solde_disponible'];
                $cautionMembre = DB::table('cautions')
                    ->where('compte_id', App\Models\Compte::where('client_id', $membre['membre_id'])->first()->id ?? 0)
                    ->where('statut', 'bloquee')
                    ->sum('montant');
                $soldeDisponibleReel = max(0, $soldeReel - $cautionMembre);
            @endphp
            <div class="member-card bg-white rounded-xl p-6 border-l-green-400 shadow-sm" 
                 id="membre-{{ $membre['membre_id'] }}">
                <div class="flex items-start justify-between mb-4">
                    <!-- Checkbox pour sélectionner ce membre (visible uniquement en mode complément) -->
                    <div class="flex items-center mr-4 mode-complement-only" style="display: none;">
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   id="select_membre_{{ $membre['membre_id'] }}"
                                   name="membres_complement[]"
                                   value="{{ $membre['membre_id'] }}"
                                   class="h-5 w-5 text-green-600 rounded border-gray-300 focus:ring-green-500"
                                   onchange="toggleMembreComplement({{ $membre['membre_id'] }}, this.checked)">
                            <label for="select_membre_{{ $membre['membre_id'] }}" class="ml-2 text-sm text-gray-700">
                                Sélectionner
                            </label>
                        </div>
                    </div>
                    
                    <!-- Informations du membre -->
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
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
                                    Solde: <span class="font-semibold">{{ number_format($soldeDisponibleReel, 2) }} USD</span>
                                </p>
                                <p class="text-sm font-semibold text-green-600">
                                    Dû: {{ number_format($membre['montant_du'], 2) }} USD
                                </p>
                            </div>
                        </div>

                        <!-- Champ montant -->
                        <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <label class="block text-sm font-medium text-gray-700">
                                        Montant payé par le membre:
                                    </label>
                                    <div class="flex items-center text-sm">
                                        <span class="text-gray-500 mr-2">Complément:</span>
                                        <div class="relative">
                                            <input type="checkbox" 
                                                   id="toggle_complement_{{ $membre['membre_id'] }}"
                                                   data-membre-id="{{ $membre['membre_id'] }}"
                                                   class="toggle-switch sr-only"
                                                   onchange="toggleComplement({{ $membre['membre_id'] }}, this.checked)">
                                            <label for="toggle_complement_{{ $membre['membre_id'] }}" 
                                                   class="block w-10 h-6 bg-gray-300 rounded-full cursor-pointer relative">
                                                <span class="block w-4 h-4 bg-white rounded-full absolute top-1 left-1 transition-transform duration-200"></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <span class="text-gray-500 font-medium">USD</span>
                                    </div>
                                    <input 
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        name="paiements_membres[{{ $membre['membre_id'] }}]"
                                        {{-- value="{{ old('paiements_membres.'.$membre['membre_id'], $membre['montant_du']) }}" --}}
                                        id="input_montant_{{ $membre['membre_id'] }}"
                                        class="paiement-input block w-full pl-20 pr-4 py-3 text-lg border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200"
                                        placeholder="{{ number_format($membre['montant_du'], 2) }}"
                                        oninput="updateTotal()"
                                    >
                                </div>
                                <div class="mt-2 text-sm text-gray-500">
                                    <i class="fas fa-info-circle mr-1 text-blue-500"></i>
                                    Dû cette semaine: {{ number_format($membre['montant_du'], 2) }} USD
                                    @if($soldeDisponibleReel >= $membre['montant_du'])
                                        | <span class="text-green-600">Solde suffisant: {{ number_format($soldeDisponibleReel, 2) }} USD</span>
                                    @else
                                        | <span class="text-orange-600">Solde insuffisant: {{ number_format($soldeDisponibleReel, 2) }} USD</span>
                                    @endif
                                </div>
                                
                                <!-- Message complément -->
                                <div id="message_complement_{{ $membre['membre_id'] }}" 
                                     class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded-lg hidden">
                                    <div class="flex items-center">
                                        <i class="fas fa-wallet text-blue-500 mr-2"></i>
                                        <span class="text-sm text-blue-700">
                                            <strong>Mode complément activé:</strong> Si 0 est saisi, tout sera prélevé du compte membre.
                                        </span>
                                    </div>
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
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">Solde disponible:</span>
                                        <span class="font-semibold {{ $soldeDisponibleReel >= $membre['montant_du'] ? 'text-green-600' : 'text-orange-600' }}">
                                            {{ number_format($soldeDisponibleReel, 2) }} USD
                                        </span>
                                    </div>
                                </div>
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
                                    <span id="totalPaiements" class="text-2xl font-bold text-blue-600 montant-total">
                                        0.00 USD
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
                                    id="submitBtn"
                                    class="submit-btn bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold py-4 px-12 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                                    disabled
                                >
                                    <i class="fas fa-check-circle mr-3 text-xl"></i>
                                    <span>Exécuter les Paiements</span>
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="w-24 h-24 bg-red-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-exclamation-triangle text-red-600 text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">Groupe non trouvé</h3>
                        <p class="text-gray-600">Le groupe sélectionné n'existe pas ou n'est plus actif.</p>
                    </div>
                @endif
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
let modeActuel = 'normal';
let membresComplement = new Set();

function activerMode(mode) {
    if (mode === 'normal' && membresComplement.size > 0) {
        if (!confirm('Passer en mode normal désélectionnera tous les membres en complément. Continuer?')) {
            return;
        }
        // Décocher tous les membres
        document.querySelectorAll('[name="membres_complement[]"]').forEach(cb => {
            cb.checked = false;
            const membreId = cb.value;
            const card = document.getElementById(`membre-${membreId}`);
            if (card) {
                card.classList.remove('membre-selectionne');
            }
        });
        membresComplement.clear();
    }
    
    modeActuel = mode;
    document.getElementById('modePaiement').value = mode;
    
    const btnNormal = document.getElementById('btnModeNormal');
    const btnComplement = document.getElementById('btnModeComplement');
    const explication = document.getElementById('explicationMode');
    const membres = document.querySelectorAll('.member-card');
    const inputs = document.querySelectorAll('.paiement-input');
    const elementsComplement = document.querySelectorAll('.mode-complement-only');
    
    if (mode === 'normal') {
        // Mode normal
        btnNormal.className = 'px-4 py-2 bg-green-500 text-white rounded-lg font-medium flex items-center';
        btnComplement.className = 'px-4 py-2 bg-gray-300 text-gray-700 rounded-lg font-medium flex items-center';
        explication.innerHTML = '<i class="fas fa-info-circle mr-1"></i> Mode normal: Tous les membres paient normalement depuis le compte groupe.';
        
        // Cacher les checkboxes de sélection
        elementsComplement.forEach(el => el.style.display = 'none');
        
        // Réinitialiser tous les membres
        membres.forEach(card => {
            card.classList.remove('mode-complement');
            card.classList.remove('membre-selectionne');
        });
        
        // Réinitialiser les valeurs par défaut (montant dû)
        // inputs.forEach(input => {
        //     const membreId = input.id.replace('input_montant_', '');
        //     // Récupérer le montant dû depuis l'attribut placeholder
        //     const placeholder = input.getAttribute('placeholder');
        //     const montantDu = parseFloat(placeholder) || 0;
        //     input.value = montantDu.toFixed(2);
        //     input.disabled = false;
        // });
        
        // Masquer les messages complément
        document.querySelectorAll('[id^="message_complement_"]').forEach(el => {
            el.classList.add('hidden');
        });
        
        // Décocher tous les toggles
        document.querySelectorAll('[id^="toggle_complement_"]').forEach(toggle => {
            toggle.checked = false;
            const label = toggle.nextElementSibling;
            label.style.backgroundColor = '#d1d5db';
            label.querySelector('span').style.transform = 'translateX(0)';
        });
        
        membresComplement.clear();
        
    } else {
        // Mode complément
        btnNormal.className = 'px-4 py-2 bg-gray-300 text-gray-700 rounded-lg font-medium flex items-center';
        btnComplement.className = 'px-4 py-2 bg-blue-500 text-white rounded-lg font-medium flex items-center';
        explication.innerHTML = '<i class="fas fa-info-circle mr-1"></i> Mode complément: Seuls les membres sélectionnés seront complétés depuis leur compte. Les autres seront ignorés.';
        
        // Afficher les checkboxes de sélection
        elementsComplement.forEach(el => el.style.display = 'flex');
        
        // Activer le mode complément sur tous les membres
        membres.forEach(card => {
            card.classList.add('mode-complement');
        });
        
        // Réinitialiser les membres sélectionnés
        membresComplement.clear();
        
        // Décocher toutes les cases
        document.querySelectorAll('[name="membres_complement[]"]').forEach(cb => {
            cb.checked = false;
        });
    }
    
    updateTotal();
}

function toggleMembreComplement(membreId, estSelectionne) {
    const card = document.getElementById(`membre-${membreId}`);
    const input = document.getElementById(`input_montant_${membreId}`);
    const toggle = document.getElementById(`toggle_complement_${membreId}`);
    const message = document.getElementById(`message_complement_${membreId}`);
    
    if (estSelectionne) {
        // Ajouter à la sélection
        card.classList.add('membre-selectionne');
        membresComplement.add(membreId);
        
        // Activer automatiquement le toggle complément
        toggle.checked = true;
        const label = toggle.nextElementSibling;
        label.style.backgroundColor = '#10b981';
        label.querySelector('span').style.transform = 'translateX(1rem)';
        
        // Afficher le message
        message.classList.remove('hidden');
        
        // Si le champ est vide ou 0, on met 0 pour forcer le complément
        if (!input.value || parseFloat(input.value) === 0) {
            input.value = '0.00';
        }
        
        // Activer automatiquement le mode complément si ce n'est pas déjà fait
        if (modeActuel === 'normal') {
            activerMode('complement');
        }
    } else {
        // Retirer de la sélection
        card.classList.remove('membre-selectionne');
        membresComplement.delete(membreId);
        
        // Désactiver le toggle complément
        toggle.checked = false;
        const label = toggle.nextElementSibling;
        label.style.backgroundColor = '#d1d5db';
        label.querySelector('span').style.transform = 'translateX(0)';
        
        // Masquer le message
        message.classList.add('hidden');
        
        // Si plus aucun membre n'est sélectionné, revenir au mode normal
        if (membresComplement.size === 0) {
            activerMode('normal');
        }
    }
    
    updateTotal();
}

function toggleComplement(membreId, actif) {
    const input = document.getElementById(`input_montant_${membreId}`);
    const message = document.getElementById(`message_complement_${membreId}`);
    
    if (actif) {
        // Activer le mode complément pour ce membre
        const label = document.querySelector(`label[for="toggle_complement_${membreId}"]`);
        label.style.backgroundColor = '#10b981';
        label.querySelector('span').style.transform = 'translateX(1rem)';
        
        // Afficher le message
        message.classList.remove('hidden');
        
        // Si le champ est vide ou 0, on met 0 pour forcer le complément
        if (!input.value || parseFloat(input.value) === 0) {
            input.value = '0.00';
        }
    } else {
        // Désactiver le mode complément pour ce membre
        const label = document.querySelector(`label[for="toggle_complement_${membreId}"]`);
        label.style.backgroundColor = '#d1d5db';
        label.querySelector('span').style.transform = 'translateX(0)';
        
        // Masquer le message
        message.classList.add('hidden');
        
        // Remettre le montant dû par défaut
        // const placeholder = input.getAttribute('placeholder');
        // const montantDu = parseFloat(placeholder) || 0;
        // if (montantDu) {
        //     input.value = montantDu.toFixed(2);
        // }
    }
    
    updateTotal();
}

function updateTotal() {
    let total = 0;
    const inputs = document.querySelectorAll('.paiement-input');
    
    inputs.forEach(input => {
        if (modeActuel === 'normal' || membresComplement.has(input.id.replace('input_montant_', ''))) {
            const value = parseFloat(input.value) || 0;
            total += value;
        }
    });
    
    const totalElement = document.getElementById('totalPaiements');
    const submitBtn = document.getElementById('submitBtn');
    
    totalElement.textContent = total.toFixed(2) + ' USD';
    
    // Animation
    totalElement.style.transform = 'scale(1.1)';
    setTimeout(() => {
        totalElement.style.transform = 'scale(1)';
    }, 300);
    
    // Activer/désactiver le bouton
    if (modeActuel === 'normal') {
        // Mode normal: besoin d'un total > 0
        submitBtn.disabled = total <= 0;
    } else {
        // Mode complément: besoin d'au moins un membre sélectionné (peut avoir 0)
        submitBtn.disabled = membresComplement.size === 0;
    }
}

function confirmPayment() {
    const totalPaiements = parseFloat(document.getElementById('totalPaiements').textContent) || 0;
    const remboursementAttendu = {{ $groupeSelectionne->remboursement_hebdo_total ?? 0 }};
    
    if (modeActuel === 'normal' && totalPaiements <= 0) {
        alert('Veuillez entrer des montants pour les paiements.');
        return false;
    }
    
    if (modeActuel === 'complement' && membresComplement.size === 0) {
        alert('Veuillez sélectionner au moins un membre en mode complément.');
        return false;
    }

    let message = `Êtes-vous sûr de vouloir exécuter les paiements ?\n\n`;
    message += `📊 Mode: ${modeActuel === 'normal' ? 'Normal' : 'Complément uniquement'}\n`;
    
    if (modeActuel === 'complement') {
        message += `👥 Membres en complément: ${membresComplement.size}\n`;
        
        // Vérifier si des membres ont 0
        let membresAvecZero = 0;
        membresComplement.forEach(membreId => {
            const input = document.getElementById(`input_montant_${membreId}`);
            if (input && parseFloat(input.value) === 0) {
                membresAvecZero++;
            }
        });
        
        if (membresAvecZero > 0) {
            message += `⚠️ ${membresAvecZero} membre(s) avec 0: tout sera prélevé de leur compte\n`;
        }
        
        message += `ℹ️ Seuls les membres sélectionnés seront traités.\n`;
        message += `💰 Total collecté: ${totalPaiements.toFixed(2)} USD\n`;
        message += `📈 Remboursement attendu: ${remboursementAttendu.toFixed(2)} USD`;
    } else {
        message += `💰 Total à collecter: ${totalPaiements.toFixed(2)} USD\n`;
        message += `📈 Remboursement attendu: ${remboursementAttendu.toFixed(2)} USD\n\n`;
        
        if (totalPaiements < remboursementAttendu) {
            message += `⚠️ Attention: Le total collecté est inférieur au remboursement attendu.\n`;
            message += `Le système complétera depuis les comptes des membres si nécessaire.`;
        } else {
            message += `✅ Tous les membres ont payé leur part.`;
        }
    }
    
    message += `\n\nVoulez-vous continuer?`;
    
    return confirm(message);
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les toggles
    document.querySelectorAll('[id^="toggle_complement_"]').forEach(toggle => {
        const label = toggle.nextElementSibling;
        label.style.backgroundColor = '#d1d5db';
        label.querySelector('span').style.transition = 'transform 0.2s';
    });
    
    // Ajouter la confirmation au formulaire
    const form = document.getElementById('paiementForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!confirmPayment()) {
                e.preventDefault();
            }
        });
    }
    
    // Initialiser le total
    updateTotal();
    
    // Animation pour les cartes membres
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
});
</script>
</body>
</html>