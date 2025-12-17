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

        <!-- Messages de statut -->
        <?php if(session('success')): ?>
            <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-xl">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-3 text-green-600"></i>
                    <span class="font-semibold"><?php echo e(session('success')); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if(session('error')): ?>
            <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-xl">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-3 text-red-600"></i>
                    <span class="font-semibold"><?php echo e(session('error')); ?></span>
                </div>
            </div>
        <?php endif; ?>

 <?php if(session('paiement_success')): ?>
    <!-- Affichage des résultats détaillés -->
      <!-- Affichage des résultats détaillés -->
    <div class="mb-6 bg-white rounded-xl p-6 shadow-lg">
        <h3 class="text-xl font-bold text-green-600 mb-4 flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            Paiement Groupe Terminé - <?php echo e(session('credit_groupe_nom')); ?>

        </h3>
        
        <!-- Ajout de la répartition capital/intérêts -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
            <div class="bg-blue-50 p-4 rounded-lg">
                <p class="text-sm text-blue-600">Total Prélevé</p>
                <p class="text-2xl font-bold text-blue-700"><?php echo e(number_format(session('total_paiement_groupe'), 2)); ?> USD</p>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <p class="text-sm text-green-600">Capital Remboursé</p>
                <p class="text-2xl font-bold text-green-700">
                    <?php echo e(number_format(session('capital_rembourse') ?? 0, 2)); ?> USD
                </p>
            </div>
            <div class="bg-orange-50 p-4 rounded-lg">
                <p class="text-sm text-orange-600">Intérêts Payés</p>
                <p class="text-2xl font-bold text-orange-700">
                    <?php echo e(number_format(session('interets_payes') ?? 0, 2)); ?> USD
                </p>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg">
                <p class="text-sm text-purple-600">Avec Excédent</p>
                <p class="text-2xl font-bold text-purple-700">
                    <?php echo e(collect(session('results'))->where('montant_excedent', '>', 0)->count()); ?>

                </p>
            </div>
            <div class="bg-red-50 p-4 rounded-lg">
                <p class="text-sm text-red-600">Échecs</p>
                <p class="text-2xl font-bold text-red-700">
                    <?php echo e(collect(session('results'))->where('statut', 'echec')->count()); ?>

                </p>
            </div>
        </div>
        
        
        <div class="space-y-2">
            <h4 class="font-semibold text-gray-700 mb-2">Détails par membre:</h4>
            <?php $__currentLoopData = session('results'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $result): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $bgColor = $result['statut'] === 'succes' ? 
                              ($result['montant_excedent'] > 0 ? 'bg-purple-50' : 'bg-green-50') : 
                              'bg-red-50';
                    $icon = $result['statut'] === 'succes' ? 
                           ($result['montant_excedent'] > 0 ? 'fa-plus-circle text-purple-600' : 'fa-check-circle text-green-600') : 
                           'fa-times-circle text-red-600';
                ?>
                <div class="<?php echo e($bgColor); ?> p-3 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center">
                            <i class="fas <?php echo e($icon); ?> mr-3"></i>
                            <span class="font-medium"><?php echo e($result['compte']); ?></span>
                        </div>
                        <div class="text-right">
                            <span class="font-semibold"><?php echo e(number_format($result['montant_apporte'], 2)); ?> USD</span>
                            <span class="text-sm text-gray-600 ml-2">apportés</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>Prélevé du groupe: <span class="font-semibold"><?php echo e(number_format($result['montant_preleve_groupe'], 2)); ?> USD</span></div>
                        <div>Dû: <span class="font-semibold"><?php echo e(number_format($result['montant_du'], 2)); ?> USD</span></div>
                        <?php if($result['montant_excedent'] > 0): ?>
                            <div class="col-span-2 text-purple-600">
                                <i class="fas fa-arrow-right mr-1"></i>
                                Excédent crédité au membre: <span class="font-semibold"><?php echo e(number_format($result['montant_excedent'], 2)); ?> USD</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
<?php endif; ?>

        <!-- Main Card -->
        <div class="payment-card rounded-2xl p-8">
            <!-- Sélection du groupe -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200 mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-users mr-2 text-blue-500"></i>
                    Sélection du Groupe
                </h3>
                
                <form method="GET" action="<?php echo e(route('paiement.credits.groupe')); ?>" id="groupeForm">
                    <select 
                        name="selected_groupe_id"
                        onchange="document.getElementById('groupeForm').submit()"
                        class="w-full border border-gray-300 rounded-xl p-4 text-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                    >
                        <option value="">Choisir un groupe...</option>
                        <?php $__currentLoopData = $groupesActifs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $groupe): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($groupe->id); ?>" 
                                <?php echo e(request('selected_groupe_id') == $groupe->id ? 'selected' : ''); ?>>
                                <?php echo e($groupe->compte->nom ?? 'Groupe '.$groupe->id); ?> - 
                                Montant restant: <?php echo e(number_format($groupe->montant_restant, 2)); ?> USD
                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </form>
            </div>

            <?php if(request('selected_groupe_id')): ?>
                <?php
                    $groupeSelectionne = $groupesActifs->firstWhere('id', request('selected_groupe_id'));
                ?>
                
                <?php if($groupeSelectionne): ?>
                    <!-- Statistiques du groupe -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="info-card bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border-l-blue-400">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-chart-line text-blue-600 text-xl"></i>
                                </div>
                                <span class="text-2xl font-bold text-blue-600">
                                    <?php echo e(number_format($groupeSelectionne->montant_total, 2)); ?>

                                </span>
                            </div>
                            <p class="text-sm text-gray-600 font-medium">Montant Total</p>
                            <p class="text-xs text-gray-500 mt-1">USD</p>
                        </div>

<!-- Dans les statistiques du groupe -->
<div class="info-card bg-gradient-to-br from-orange-50 to-red-50 rounded-xl p-6 border-l-orange-400">
    <div class="flex items-center justify-between mb-3">
        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
            <i class="fas fa-balance-scale text-orange-600 text-xl"></i>
        </div>
        <span class="text-2xl font-bold text-orange-600">
            <?php echo e(number_format($groupeSelectionne->montant_restant, 2)); ?>

        </span>
    </div>
    <p class="text-sm text-gray-600 font-medium">Montant Restant</p>
    <p class="text-xs text-gray-500 mt-1">
        Capital remboursé: <?php echo e(number_format($groupeSelectionne->capital_rembourse_total, 2)); ?> USD
        | Total payé: <?php echo e(number_format($groupeSelectionne->total_deja_paye, 2)); ?> USD
    </p>
    <p class="text-xs text-green-600 mt-1">
        Dû jusqu'à présent: <?php echo e(number_format($groupeSelectionne->montant_du_jusqu_present, 2)); ?> USD
    </p>

    
</div>
    <p class="text-sm text-gray-600 font-medium">Montant Restant</p>
    <p class="text-xs text-gray-500 mt-1">
        Dû jusqu'à présent: <?php echo e(number_format($groupeSelectionne->montant_du_jusqu_present ?? 0, 2)); ?> USD
        | Payé: <?php echo e(number_format($groupeSelectionne->total_deja_paye ?? 0, 2)); ?> USD
    </p>
</div>

                        <div class="info-card bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 border-l-green-400">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-calendar-week text-green-600 text-xl"></i>
                                </div>
                                <span class="text-2xl font-bold text-green-600">
                                    <?php echo e(number_format($groupeSelectionne->remboursement_hebdo_total, 2)); ?>

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
                                <p class="font-semibold text-gray-800"><?php echo e($groupeSelectionne->compte->nom ?? 'Groupe Solidaire'); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Numéro de Compte</p>
                                <p class="font-semibold text-gray-800"><?php echo e($groupeSelectionne->compte->numero_compte); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Semaine Actuelle</p>
                                <p class="font-semibold text-gray-800"><?php echo e($groupeSelectionne->semaine_actuelle ?? 1); ?>/16</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Date Échéance</p>
                                <p class="font-semibold text-gray-800">
                                    <?php echo e($groupeSelectionne->date_echeance->format('d/m/Y')); ?>

                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Formulaire de paiement des membres -->
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-6 border border-green-200 mb-6">
                        <h4 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
                            <i class="fas fa-money-bill-wave mr-2 text-green-500"></i>
                            Paiements des Membres - Semaine <?php echo e($groupeSelectionne->semaine_actuelle ?? 1); ?>

                        </h4>

                        <form method="POST" action="<?php echo e(route('paiement.credits.groupe.processer')); ?>" id="paiementForm">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="selected_groupe_id" value="<?php echo e(request('selected_groupe_id')); ?>">
                            
                            <div class="space-y-4">
                                <?php $__currentLoopData = $groupeSelectionne->membres_avec_soldes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $membre): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="member-card bg-white rounded-xl p-6 border-l-green-400 shadow-sm">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center">
                                            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mr-4">
                                                <i class="fas fa-user text-green-600 text-lg"></i>
                                            </div>
                                            <div>
                                                <p class="text-lg font-semibold text-gray-800"><?php echo e($membre['nom_complet']); ?></p>
                                                <p class="text-sm text-gray-600"><?php echo e($membre['numero_compte']); ?></p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm text-gray-600">
                                                Solde: <span class="font-semibold"><?php echo e(number_format($membre['solde_disponible'], 2)); ?> USD</span>
                                            </p>
                                            <p class="text-sm font-semibold text-green-600">
                                                Dû: <?php echo e(number_format($membre['montant_du'], 2)); ?> USD
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
                                                    name="paiements_membres[<?php echo e($membre['membre_id']); ?>]"
                                                    value="<?php echo e(old('paiements_membres.'.$membre['membre_id'], 0)); ?>"
                                                    class="paiement-input block w-full pl-20 pr-4 py-3 text-lg border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200"
                                                    placeholder="0.00"
                                                    oninput="updateTotal()"
                                                >
                                                </div>
                                                    <div class="mt-2 text-sm text-gray-500">
                                                            <i class="fas fa-info-circle mr-1 text-blue-500"></i>
                                                            Dû cette semaine: <?php echo e(number_format($membre['montant_du'], 2)); ?> USD
                                                            <?php if($membre['solde_disponible'] > 0): ?>
                                                                | Solde disponible: <?php echo e(number_format($membre['solde_disponible'], 2)); ?> USD
                                                            <?php else: ?>
                                                                | <span class="text-orange-600">Solde insuffisant - Paiement depuis compte groupe</span>
                                                            <?php endif; ?>
                                                        </div>
                                        </div>

                                        <div class="bg-gray-50 rounded-xl p-4">
                                            <div class="text-sm text-gray-600 mb-3 font-medium">Détails du crédit:</div>
                                            <div class="space-y-2 text-sm">
                                                <div class="flex justify-between items-center">
                                                    <span class="text-gray-600">Montant accordé:</span>
                                                    <span class="font-semibold text-blue-600"><?php echo e(number_format($membre['montant_accorde'], 2)); ?> USD</span>
                                                </div>
                                                <div class="flex justify-between items-center">
                                                    <span class="text-gray-600">Montant total:</span>
                                                    <span class="font-semibold text-purple-600"><?php echo e(number_format($membre['montant_total'], 2)); ?> USD</span>
                                                </div>
                                                <div class="flex justify-between items-center">
                                                    <span class="text-gray-600">Remboursement hebdo:</span>
                                                    <span class="font-semibold text-green-600"><?php echo e(number_format($membre['montant_du'], 2)); ?> USD</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                                        <?php echo e(number_format($groupeSelectionne->remboursement_hebdo_total, 2)); ?> USD
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
                <?php else: ?>
                    <div class="text-center py-12">
                        <div class="w-24 h-24 bg-red-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-exclamation-triangle text-red-600 text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">Groupe non trouvé</h3>
                        <p class="text-gray-600">Le groupe sélectionné n'existe pas ou n'est plus actif.</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- Message quand aucun groupe n'est sélectionné -->
                <div class="text-center py-12">
                    <div class="w-24 h-24 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-users text-blue-600 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Aucun groupe sélectionné</h3>
                    <p class="text-gray-600">Veuillez sélectionner un groupe dans la liste déroulante pour commencer les paiements.</p>
                </div>
            <?php endif; ?>

            <!-- Boutons d'action -->
            <div class="flex flex-col sm:flex-row gap-4 mt-8 pt-8 border-t border-gray-200">
                <a 
                    href="<?php echo e(url('/admin/microfinance-overviews')); ?>" 
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
        function updateTotal() {
            let total = 0;
            const inputs = document.querySelectorAll('.paiement-input');
            
            inputs.forEach(input => {
                const value = parseFloat(input.value) || 0;
                total += value;
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
            submitBtn.disabled = total <= 0;
        }

        function confirmPayment() {
            const totalPaiements = parseFloat(document.getElementById('totalPaiements').textContent) || 0;
            const remboursementAttendu = <?php echo e($groupeSelectionne->remboursement_hebdo_total ?? 0); ?>;
            
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
        });

        // Animation pour les cartes membres
        document.addEventListener('DOMContentLoaded', function() {
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
</html><?php /**PATH C:\laragon\www\tumainiletusystem2.0\resources\views/paiement-credits-groupe.blade.php ENDPATH**/ ?>