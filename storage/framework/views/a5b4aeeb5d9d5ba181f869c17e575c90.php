<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accorder un Crédit - Tumaini Letu</title>
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
        .approval-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }
        .info-card {
            transition: all 0.3s ease;
            border-left: 4px solid;
        }
        .frais-item {
            border-left: 3px solid;
        }
        .montant-total {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .echeancier-table {
            border-collapse: collapse;
            width: 100%;
        }
        .echeancier-table th, .echeancier-table td {
            border: 1px solid #e2e8f0;
            padding: 8px 12px;
            text-align: left;
        }
        .echeancier-table th {
            background-color: #f8fafc;
            font-weight: 600;
        }
        .echeancier-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="w-full max-w-6xl mx-4 my-8">
        <!-- Header Section -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-2xl shadow-lg mb-4">
                <i class="fas fa-hand-holding-usd text-3xl text-purple-600"></i>
            </div>
            <h1 class="text-4xl font-bold text-white mb-3">Accorder un Crédit</h1>
            <p class="text-white/80 text-lg">Examen et approbation de la demande</p>
        </div>

        <!-- Main Card -->
        <div class="approval-card rounded-2xl p-8">
            <!-- Credit Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="info-card bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border-l-blue-400">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-money-bill-wave text-blue-600 text-xl"></i>
                        </div>
                        <span class="text-2xl font-bold text-blue-600">
                            <?php echo e(number_format($credit->montant_demande, 2, ',', ' ')); ?>

                        </span>
                    </div>
                    <p class="text-sm text-gray-600 font-medium">Montant Demandé</p>
                    <p class="text-xs text-gray-500 mt-1"><?php echo e($credit->compte->devise); ?></p>
                </div>

                <div class="info-card bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-6 border-l-purple-400">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-tags text-purple-600 text-xl"></i>
                        </div>
                        <span class="text-xl font-bold text-purple-600 capitalize">
                            <?php echo e($credit->type_credit); ?>

                        </span>
                    </div>
                    <p class="text-sm text-gray-600 font-medium">Type de Crédit</p>
                    <p class="text-xs text-gray-500 mt-1">
                        <?php echo e($credit->type_credit === 'individuel' ? 'Individuel' : 'Groupe'); ?>

                    </p>
                </div>

                <div class="info-card bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 border-l-green-400">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calculator text-green-600 text-xl"></i>
                        </div>
                        <span class="text-2xl font-bold text-green-600">
                            <?php echo e($credit->type_credit === 'groupe' ? '1.225' : 'Variable'); ?>

                        </span>
                    </div>
                    <p class="text-sm text-gray-600 font-medium">Coefficient</p>
                    <p class="text-xs text-gray-500 mt-1">
                        <?php echo e($credit->type_credit === 'groupe' ? 'Fixé' : 'Selon montant'); ?>

                    </p>
                </div>

                <div class="info-card bg-gradient-to-br from-orange-50 to-red-50 rounded-xl p-6 border-l-orange-400">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-alt text-orange-600 text-xl"></i>
                        </div>
                        <span class="text-2xl font-bold text-orange-600">4</span>
                    </div>
                    <p class="text-sm text-gray-600 font-medium">Durée</p>
                    <p class="text-xs text-gray-500 mt-1">Mois</p>
                </div>
            </div>

            <!-- Client Information -->
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-6 border border-gray-200 mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-user-circle mr-2 text-indigo-500"></i>
                    Informations du Client
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Nom du Client</p>
                        <p class="font-semibold text-gray-800"><?php echo e($credit->compte->nom); ?> <?php echo e($credit->compte->prenom); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Numéro de Compte</p>
                        <p class="font-semibold text-gray-800"><?php echo e($credit->compte->numero_compte); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Solde Actuel</p>
                        <p class="font-semibold text-gray-800">
                            <?php echo e(number_format($credit->compte->solde, 2, ',', ' ')); ?> <?php echo e($credit->compte->devise); ?>

                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Date de Demande</p>
                        <p class="font-semibold text-gray-800">
                            <?php echo e($credit->date_demande->format('d/m/Y H:i')); ?>

                        </p>
                    </div>
                </div>
            </div>

            <!-- Calculs détaillés -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Frais détaillés -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                            <i class="fas fa-receipt mr-2 text-blue-500"></i>
                            Frais détaillés
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <?php if($credit->type_credit === 'groupe'): ?>
                            <!-- Frais Groupe -->
                            <div class="frais-item border-l-blue-400 bg-blue-50 rounded-r-lg p-4">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-semibold text-gray-800">Frais de Dossier</span>
                                    <span class="text-lg font-bold text-blue-600">
                                        <?php echo e(number_format($frais['dossier'], 2, ',', ' ')); ?> <?php echo e($credit->compte->devise); ?>

                                    </span>
                                </div>
                            </div>
                            <div class="frais-item border-l-green-400 bg-green-50 rounded-r-lg p-4">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-semibold text-gray-800">Frais d'Alerte</span>
                                    <span class="text-lg font-bold text-green-600">
                                        <?php echo e(number_format($frais['alerte'], 2, ',', ' ')); ?> <?php echo e($credit->compte->devise); ?>

                                    </span>
                                </div>
                            </div>
                            <div class="frais-item border-l-purple-400 bg-purple-50 rounded-r-lg p-4">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-semibold text-gray-800">Frais de Carnet</span>
                                    <span class="text-lg font-bold text-purple-600">
                                        <?php echo e(number_format($frais['carnet'], 2, ',', ' ')); ?> <?php echo e($credit->compte->devise); ?>

                                    </span>
                                </div>
                            </div>
                            
                            <div class="frais-item border-l-red-400 bg-red-50 rounded-r-lg p-4">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-semibold text-gray-800">Caution (Bloquée)</span>
                                    <span class="text-lg font-bold text-red-600">
                                        <?php echo e(number_format($frais['caution'], 2, ',', ' ')); ?> <?php echo e($credit->compte->devise); ?>

                                    </span>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Frais Individuel -->
                            <div class="frais-item border-l-blue-400 bg-blue-50 rounded-r-lg p-4">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-semibold text-gray-800">Frais de Dossier</span>
                                    <span class="text-lg font-bold text-blue-600">
                                        <?php echo e(number_format($frais['dossier'], 2, ',', ' ')); ?> <?php echo e($credit->compte->devise); ?>

                                    </span>
                                </div>
                            </div>
                            <div class="frais-item border-l-green-400 bg-green-50 rounded-r-lg p-4">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-semibold text-gray-800">Frais d'Alerte</span>
                                    <span class="text-lg font-bold text-green-600">
                                        <?php echo e(number_format($frais['alerte'], 2, ',', ' ')); ?> <?php echo e($credit->compte->devise); ?>

                                    </span>
                                </div>
                            </div>
                            
                            <div class="frais-item border-l-red-400 bg-red-50 rounded-r-lg p-4">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-semibold text-gray-800">Caution (Bloquée)</span>
                                    <span class="text-lg font-bold text-red-600">
                                        <?php echo e(number_format($frais['caution'], 2, ',', ' ')); ?> <?php echo e($credit->compte->devise); ?>

                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Total Frais -->
                        <div class="bg-gradient-to-r from-gray-100 to-gray-200 rounded-lg p-4 mt-4">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-gray-800 text-lg">Total des Frais à Payer</span>
                                <span class="text-xl font-bold text-gray-800">
                                    <?php
                                        $totalFrais = $frais['dossier'] + $frais['alerte'] + ($frais['carnet'] ?? 0) ;
                                    ?>
                                    <?php echo e(number_format($totalFrais, 2, ',', ' ')); ?> <?php echo e($credit->compte->devise); ?>

                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Remboursement -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                            <i class="fas fa-chart-line mr-2 text-green-500"></i>
                            Plan de Remboursement
                        </h3>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="text-center">
                            <p class="text-sm text-gray-600 mb-2">Montant Total à Rembourser</p>
                            <p class="text-3xl font-bold montant-total">
                                <?php echo e(number_format($montantTotal, 2, ',', ' ')); ?> <?php echo e($credit->compte->devise); ?>

                            </p>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-blue-50 rounded-lg p-4 text-center">
                                <p class="text-sm text-gray-600">Remboursement Hebdo</p>
                                <p class="text-xl font-bold text-blue-600">
                                    <?php echo e(number_format($remboursementHebdo, 2, ',', ' ')); ?> <?php echo e($credit->compte->devise); ?>

                                </p>
                            </div>
                            <div class="bg-green-50 rounded-lg p-4 text-center">
                                <p class="text-sm text-gray-600">Nombre de Semaines</p>
                                <p class="text-xl font-bold text-green-600">16</p>
                            </div>
                        </div>
                        
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-start">
                                <i class="fas fa-info-circle text-yellow-500 mt-1 mr-3"></i>
                                <div class="text-sm text-yellow-800">
                                    <strong>Note:</strong> Le client devra payer <?php echo e(number_format($totalFrais, 2, ',', ' ')); ?> <?php echo e($credit->compte->devise); ?> 
                                    de frais avant de recevoir le crédit. La caution de <?php echo e(number_format($frais['caution'], 2, ',', ' ')); ?> <?php echo e($credit->compte->devise); ?> 
                                    sera bloquée dans son compte.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Échéancier de remboursement -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-8">
                <div class="bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-calendar-week mr-2 text-purple-500"></i>
                        Échéancier de Remboursement
                    </h3>
                </div>
                <div class="p-6">
                    <div class="mb-4 text-sm text-gray-600">
                        <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                        Le remboursement commence 2 semaines après l'approbation du crédit
                    </div>
                    <div class="overflow-x-auto">
                        <table class="echeancier-table">
                            <thead>
                                <tr>
                                    <th>Semaine</th>
                                    <th>Date Échéance</th>
                                    <th>Montant à Payer</th>
                                    <th>Capital Restant</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody id="echeancier-body">
                                <!-- L'échéancier sera généré par JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Formulaire d'approbation -->
            <?php if($credit->statut_demande === 'en_attente'): ?>
            <form action="<?php echo e(route('credits.process-approval', $credit->id)); ?>" method="POST" class="space-y-6" id="approval-form">
                <?php echo csrf_field(); ?>

              <!-- NOUVELLE SECTION : Sélection Agent et Superviseur -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200">
        <label class="block text-lg font-semibold text-gray-700 mb-4">
            <i class="fas fa-user-tie mr-2 text-blue-500"></i>
            Personnel en charge du crédit
        </label>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Agent -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Agent en charge *
                </label>
                <select 
                    name="agent_id" 
                    class="block w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                    required
                >
                    <option value="">Sélectionner un agent...</option>
                    <?php $__currentLoopData = $agents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($agent->id); ?>" 
                            <?php if(old('agent_id') == $agent->id || $credit->agent_id == $agent->id): ?> selected <?php endif; ?>>
                            <?php echo e($agent->name); ?> - <?php echo e($agent->email); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <p class="mt-1 text-xs text-gray-500">
                    Agent qui suivra ce crédit
                </p>
            </div>
            
            <!-- Superviseur -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Superviseur en charge *
                </label>
                <select 
                    name="superviseur_id" 
                    class="block w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                    required
                >
                    <option value="">Sélectionner un superviseur...</option>
                    <?php $__currentLoopData = $superviseurs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $superviseur): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($superviseur->id); ?>"
                            <?php if(old('superviseur_id') == $superviseur->id || $credit->superviseur_id == $superviseur->id): ?> selected <?php endif; ?>>
                            <?php echo e($superviseur->name); ?> - <?php echo e($superviseur->email); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <p class="mt-1 text-xs text-gray-500">
                    Superviseur responsable du suivi
                </p>
            </div>
        </div>
        
        <!-- Information supplémentaire -->
        <div class="mt-4 bg-blue-100 border border-blue-200 rounded-lg p-3">
            <div class="flex items-center">
                <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                <p class="text-sm text-blue-800">
                    L'agent sera responsable du suivi hebdomadaire. Le superviseur supervisera l'ensemble du dossier.
                </p>
            </div>
        </div>
    </div>
    
    <!-- Montant Accordé (section existante) -->
    <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-6 border border-gray-200">
        <label class="block text-lg font-semibold text-gray-700 mb-4">
            <i class="fas fa-edit mr-2 text-indigo-500"></i>
            Montant à Accorder
        </label>
        
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-gray-500 font-medium"><?php echo e($credit->compte->devise); ?></span>
            </div>
            <input 
                type="number" 
                name="montant_accorde" 
                step="0.01"
                min="0.01"
                value="<?php echo e($credit->montant_demande); ?>"
                class="block w-full pl-16 pr-4 py-4 text-lg border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                placeholder="0.00"
                required
                id="montant_accorde"
            >
        </div>
        
        <div class="mt-3 text-sm text-gray-500">
            <i class="fas fa-info-circle mr-1 text-blue-500"></i>
            Vous pouvez modifier le montant à accorder
        </div>
    </div>

                <!-- Actions -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Approve Button -->
                    <button 
                    type="submit"
                    name="action"
                    value="approuver"
                    class="bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center"
                    id="approve-btn"
                    onclick="return submitApproval()"
                >

                    <!-- Reject Button -->
                    <button 
                        type="button"
                        id="rejectBtn"
                        class="bg-gradient-to-r from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center"
                    >
                        <i class="fas fa-times-circle mr-3 text-xl"></i>
                        Rejeter la Demande
                    </button>
                </div>

                <!-- Rejection Reason (Hidden by default) -->
                <div id="rejectionSection" class="hidden bg-gradient-to-r from-red-50 to-pink-50 rounded-xl p-6 border border-red-200">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-comment-alt mr-2 text-red-500"></i>
                        Motif du Rejet
                    </h4>
                    <textarea 
                        name="motif_rejet" 
                        rows="4"
                        class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-red-500 focus:border-red-500"
                        placeholder="Veuillez indiquer le motif du rejet de cette demande..."
                        id="motif_rejet"
                    ></textarea>
                    <div class="mt-4 flex justify-end space-x-4">
                        <button 
                            type="button"
                            id="cancelReject"
                            class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-6 rounded-lg transition-all duration-200"
                        >
                            Annuler
                        </button>
                        <button 
                            type="submit"
                            name="action"
                            value="rejeter"
                            class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-6 rounded-lg transition-all duration-200"
                            onclick="return handleRejection()"
                        >
                            Confirmer le Rejet
                        </button>
                    </div>
                </div>
            </form>
            <?php else: ?>
                <!-- Statut déjà traité -->
                <div class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-xl p-6 border border-yellow-200 text-center">
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">
                        Demande déjà traitée
                    </h3>
                    <p class="text-gray-600">
                        Statut actuel: 
                        <span class="font-semibold capitalize <?php echo e($credit->statut_demande === 'approuve' ? 'text-green-600' : 'text-red-600'); ?>">
                            <?php echo e($credit->statut_demande); ?>

                        </span>
                    </p>
                    <?php if($credit->motif_rejet): ?>
                        <div class="mt-4 bg-white rounded-lg p-4 border border-yellow-200">
                            <p class="text-sm text-gray-600 font-medium">Motif du rejet:</p>
                            <p class="text-gray-700 mt-1"><?php echo e($credit->motif_rejet); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 mt-8 pt-6 border-t border-gray-200">
                <a 
                    href="<?php echo e(route('comptes.details', $credit->compte_id)); ?>" 
                    class="flex-1 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg flex items-center justify-center"
                >
                    <i class="fas fa-eye mr-3"></i>
                    Voir Détails du Compte
                </a>
                
                <a 
                    href="<?php echo e(url('/admin/comptes')); ?>" 
                    class="flex-1 bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg flex items-center justify-center"
                >
                    <i class="fas fa-arrow-left mr-3"></i>
                    Retour aux Comptes
                </a>
            </div>

            <!-- Security Notice -->
            <div class="mt-6 text-center">
                <div class="inline-flex items-center text-xs text-gray-500 bg-gray-100 rounded-full px-4 py-2">
                    <i class="fas fa-shield-alt mr-2 text-green-500"></i>
                    Opération sécurisée - Toutes les actions sont tracées
                </div>
            </div>
        </div>
    </div>

<script>
    // Variables globales
    const devise = "<?php echo e($credit->compte->devise); ?>";
   const remboursementHebdo = <?php echo e(floatval($remboursementHebdo ?? 0)); ?>;
const montantTotal = <?php echo e(floatval($montantTotal ?? 0)); ?>;

    // Fonction pour soumettre l'approbation
    function submitApproval() {
        const montantAccorde = document.getElementById('montant_accorde').value;
        const form = document.getElementById('approval-form');
        
        if (!montantAccorde || parseFloat(montantAccorde) <= 0) {
            alert('Veuillez saisir un montant valide à accorder.');
            return false;
        }
        
        const confirmation = confirm(`Êtes-vous sûr de vouloir approuver ce crédit de ${parseFloat(montantAccorde).toFixed(2)} ${devise} ?\n\nLe solde du compte sera augmenté et les frais seront appliqués.`);
        
        if (confirmation) {
            // Afficher l'indicateur de chargement
            const approveBtn = document.getElementById('approve-btn');
            approveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-3 text-xl"></i> Traitement en cours...';
            approveBtn.disabled = true;
            
            // Créer un champ caché pour l'action
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'approuver';
            form.appendChild(actionInput);
            
            // Soumettre le formulaire
            form.submit();
            return true;
        }
        
        return false;
    }

    // Fonction pour gérer le rejet
    function handleRejection(event) {
        event.preventDefault();
        
        const motifRejet = document.getElementById('motif_rejet').value;
        
        if (!motifRejet.trim()) {
            alert('Veuillez saisir le motif du rejet.');
            return false;
        }
        
        const confirmation = confirm('Êtes-vous sûr de vouloir rejeter cette demande de crédit ?');
        
        if (confirmation) {
            return true;
        }
        
        return false;
    }

    // Générer l'échéancier
    function genererEcheancier() {
        const montantAccorde = parseFloat(document.getElementById('montant_accorde').value) || 0;
        const echeancierBody = document.getElementById('echeancier-body');
        
        if (montantAccorde > 0) {
            let html = '';
            let capitalRestant = montantTotal;
            const dateDebut = new Date();
            dateDebut.setDate(dateDebut.getDate() + 14); // Début dans 2 semaines
            
            for (let semaine = 1; semaine <= 16; semaine++) {
                const dateEcheance = new Date(dateDebut);
                dateEcheance.setDate(dateDebut.getDate() + ((semaine - 1) * 7));
                
                capitalRestant -= remboursementHebdo;
                if (capitalRestant < 0) capitalRestant = 0;
                
                const montantPaye = semaine === 16 ? capitalRestant + remboursementHebdo : remboursementHebdo;
                
                html += `
                    <tr>
                        <td class="font-semibold">Semaine ${semaine}</td>
                        <td>${dateEcheance.toLocaleDateString('fr-FR')}</td>
                        <td class="font-bold text-green-600">${montantPaye.toFixed(2)} ${devise}</td>
                        <td class="font-semibold ${capitalRestant === 0 ? 'text-green-600' : 'text-orange-600'}">
                            ${capitalRestant.toFixed(2)} ${devise}
                        </td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                À venir
                            </span>
                        </td>
                    </tr>
                `;
            }
            
            echeancierBody.innerHTML = html;
        } else {
            echeancierBody.innerHTML = '<tr><td colspan="5" class="text-center text-gray-500 py-4">Veuillez entrer un montant pour voir l\'échéancier</td></tr>';
        }
    }

    // Mettre à jour l'échéancier quand le montant change
    document.getElementById('montant_accorde').addEventListener('input', genererEcheancier);

    // Gestion du rejet
    document.getElementById('rejectBtn').addEventListener('click', function() {
        document.getElementById('rejectionSection').classList.remove('hidden');
        this.classList.add('hidden');
    });

    document.getElementById('cancelReject').addEventListener('click', function() {
        document.getElementById('rejectionSection').classList.add('hidden');
        document.getElementById('rejectBtn').classList.remove('hidden');
        document.getElementById('motif_rejet').value = '';
    });

    // Écouteur pour le bouton d'approbation
    document.getElementById('approve-btn').addEventListener('click', submitApproval);

    // Générer l'échéancier au chargement
    document.addEventListener('DOMContentLoaded', genererEcheancier);


    // AJOUTEZ CE CODE DE DÉBOGAGE DANS LES DEUX FICHIERS
console.log('=== DÉBUT DÉBOGAGE ===');

// Vérifier si le formulaire existe
const form = document.getElementById('approval-form');
console.log('Formulaire trouvé:', !!form);

// Vérifier les boutons
const approveBtn = document.getElementById('approve-btn');
console.log('Bouton approbation trouvé:', !!approveBtn);

// Écouter la soumission du formulaire
form.addEventListener('submit', function(e) {
    console.log('📤 FORMULAIRE SOUMIS');
    console.log('Action:', e.submitter?.value);
    console.log('Données:', new FormData(form));
});

// Vérifier les erreurs de validation
form.addEventListener('invalid', function(e) {
    console.log('❌ ERREUR VALIDATION:', e.target.name);
}, true);

console.log('=== FIN DÉBOGAGE ===');


</script>

<script>
// Validation améliorée pour la sélection du personnel
function validatePersonnelSelection() {
    const agentSelect = document.querySelector('select[name="agent_id"]');
    const superviseurSelect = document.querySelector('select[name="superviseur_id"]');
    
    if (!agentSelect.value) {
        alert('❌ Veuillez sélectionner un agent en charge.');
        agentSelect.focus();
        return false;
    }
    
    if (!superviseurSelect.value) {
        alert('❌ Veuillez sélectionner un superviseur en charge.');
        superviseurSelect.focus();
        return false;
    }
    
    // Empêcher la même personne d'être agent et superviseur
    if (agentSelect.value === superviseurSelect.value) {
        alert('❌ Une même personne ne peut pas être à la fois agent et superviseur.');
        return false;
    }
    
    return true;
}

// Modifiez la fonction de validation existante pour inclure cette validation
function validateApproval() {
    // Validation du montant
    const montantAccorde = document.getElementById('montant_accorde').value;
    if (!montantAccorde || parseFloat(montantAccorde) <= 0) {
        alert('❌ Veuillez saisir un montant valide à accorder.');
        return false;
    }
    
    // Validation du personnel
    if (!validatePersonnelSelection()) {
        return false;
    }
    
    // Confirmation finale
    const agentName = document.querySelector('select[name="agent_id"] option:checked').text;
    const superviseurName = document.querySelector('select[name="superviseur_id"] option:checked').text;
    
    const confirmationMessage = `
Êtes-vous sûr de vouloir approuver ce crédit ?

📊 Détails :
• Montant : ${parseFloat(montantAccorde).toFixed(2)} ${devise}
• Agent en charge : ${agentName.split(' - ')[0]}
• Superviseur : ${superviseurName.split(' - ')[0]}

⚠️ Cette action est irréversible.
    `.trim();
    
    return confirm(confirmationMessage);
}

// Pour le crédit groupe
function validateApprovalGroupe() {
    // ... validation existante du montant et de la répartition ...
    
    // Ajouter la validation du personnel
    if (!validatePersonnelSelection()) {
        return false;
    }
    
    // Récupérer les noms
    const agentName = document.querySelector('select[name="agent_id"] option:checked').text;
    const superviseurName = document.querySelector('select[name="superviseur_id"] option:checked').text;
    
    // Ajouter au message de confirmation
    const messageConfirmation += 
        `\n👤 Personnel en charge :` +
        `\n• Agent : ${agentName.split(' - ')[0]}` +
        `\n• Superviseur : ${superviseurName.split(' - ')[0]}`;
    
    return confirm(messageConfirmation);
}

// Ajoutez ces écouteurs d'événements
document.addEventListener('DOMContentLoaded', function() {
    // Écouter la soumission du formulaire
    const form = document.getElementById('approval-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Vérifier si c'est un bouton d'approbation qui a déclenché la soumission
            const submitter = e.submitter;
            if (submitter && submitter.value === 'approuver') {
                // Empêcher la soumission si validation échoue
                if (!validateApproval()) {
                    e.preventDefault();
                }
            }
        });
    }
});
</script>

<style>
/* Style pour les sélecteurs Agent/Superviseur */
.personnel-select {
    transition: all 0.3s ease;
    background: white;
    border: 2px solid #e2e8f0;
}

.personnel-select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    outline: none;
}

.personnel-select:hover {
    border-color: #93c5fd;
}

/* Style pour les options */
.personnel-select option {
    padding: 10px;
    background: white;
}

.personnel-select option:checked {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: bold;
}

/* Carte de personnel */
.personnel-card {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-left: 4px solid #3b82f6;
    transition: all 0.3s ease;
}

.personnel-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

/* Badge pour le rôle */
.role-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    margin-left: 8px;
}

.role-agent {
    background: #dbeafe;
    color: #1e40af;
    border: 1px solid #93c5fd;
}

.role-superviseur {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fbbf24;
}

/* Animation pour la sélection */
@keyframes pulse-select {
    0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
    100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
}

.select-animate {
    animation: pulse-select 1.5s infinite;
}
</style>
</body>
</html><?php /**PATH C:\laragon\www\tumainiletusystem2.0\resources\views/credits/approval.blade.php ENDPATH**/ ?>