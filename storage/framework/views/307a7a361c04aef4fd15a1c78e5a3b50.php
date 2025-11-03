<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accorder Crédit Groupe - Tumaini Letu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
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
        .repartition-card {
            transition: all 0.3s ease;
            border-left: 4px solid;
        }
        .repartition-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        #approve-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        #approve-btn:disabled:hover {
            transform: none !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
        }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="w-full max-w-7xl mx-auto py-8 px-4">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-2xl shadow-lg mb-4">
                <i class="fas fa-users text-3xl text-purple-600"></i>
            </div>
            <h1 class="text-4xl font-bold text-white mb-3">Accorder Crédit Groupe</h1>
            <p class="text-white/80 text-lg">Répartition personnalisée du crédit entre les membres</p>
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
                            <?php echo e(number_format(floatval($credit->montant_demande ?? 0), 2, ',', ' ')); ?>

                        </span>
                    </div>
                    <p class="text-sm text-gray-600 font-medium">Montant Demandé</p>
                    <p class="text-xs text-gray-500 mt-1">USD</p>
                </div>

                <div class="info-card bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-6 border-l-purple-400">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-tags text-purple-600 text-xl"></i>
                        </div>
                        <span class="text-xl font-bold text-purple-600 capitalize">
                            Groupe
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 font-medium">Type de Crédit</p>
                    <p class="text-xs text-gray-500 mt-1">Crédit Solidaire</p>
                </div>

                <div class="info-card bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 border-l-green-400">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calculator text-green-600 text-xl"></i>
                        </div>
                        <span class="text-2xl font-bold text-green-600">1.225</span>
                    </div>
                    <p class="text-sm text-gray-600 font-medium">Coefficient</p>
                    <p class="text-xs text-gray-500 mt-1">Fixé pour groupe</p>
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

            <!-- Groupe Information -->
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-6 border border-gray-200 mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-users mr-2 text-indigo-500"></i>
                    Informations du Groupe
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Nom du Groupe</p>
                        <p class="font-semibold text-gray-800"><?php echo e($compte->nom ?? 'Groupe Solidaire'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Numéro de Compte</p>
                        <p class="font-semibold text-gray-800"><?php echo e($compte->numero_compte); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Solde Actuel</p>
                        <p class="font-semibold text-gray-800">
                            <?php echo e(number_format(floatval($compte->solde ?? 0), 2, ',', ' ')); ?> USD
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

            <!-- Formulaire de répartition -->
            <form action="<?php echo e(route('credits.process-approval-groupe', $credit->id)); ?>" method="POST" class="mb-8" id="approval-form">
                <?php echo csrf_field(); ?>
                
                <!-- Section Montant Total -->
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-6 border border-gray-200 mb-6">
                    <label class="block text-lg font-semibold text-gray-700 mb-4">
                        <i class="fas fa-edit mr-2 text-indigo-500"></i>
                        Montant Total du Crédit Groupe
                    </label>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Montant Total à Accorder au Groupe
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 font-medium">USD</span>
                            </div>
                            <input 
                                type="number" 
                                name="montant_total_groupe" 
                                step="0.01"
                                min="0.01"
                                value="<?php echo e(number_format(floatval($credit->montant_demande ?? 0), 2, '.', '')); ?>"
                                class="block w-full pl-16 pr-4 py-4 text-lg border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                                placeholder="0.00"
                                required
                                id="montant_total_groupe"
                            >
                        </div>
                        <div class="mt-2 text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-1 text-blue-500"></i>
                            Ce montant sera réparti entre les membres ci-dessous
                        </div>
                    </div>

                    <!-- Prévisualisation des calculs -->
                    <div id="calculs-groupe" class="mt-6 space-y-4 hidden">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-blue-50 rounded-lg p-4 text-center">
                                <p class="text-sm text-gray-600">Montant Total avec Intérêt</p>
                                <p class="text-xl font-bold text-blue-600" id="montant-total-interet">0.00 USD</p>
                            </div>
                            <div class="bg-green-50 rounded-lg p-4 text-center">
                                <p class="text-sm text-gray-600">Remboursement Hebdo</p>
                                <p class="text-xl font-bold text-green-600" id="remboursement-hebdo-total">0.00 USD</p>
                            </div>
                            <div class="bg-purple-50 rounded-lg p-4 text-center">
                                <p class="text-sm text-gray-600">Total Frais Groupe</p>
                                <p class="text-xl font-bold text-purple-600" id="total-frais-groupe">0.00 USD</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section Répartition -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200 mb-6">
                    <label class="block text-lg font-semibold text-gray-700 mb-4">
                        <i class="fas fa-share-alt mr-2 text-blue-500"></i>
                        Répartition entre les Membres
                    </label>
                    
                    <div class="space-y-4">
                        <h4 class="text-md font-semibold text-gray-800 mb-3">
                            Répartissez le montant total entre les membres (<?php echo e($membres->count()); ?> membres):
                        </h4>
                        
                        <?php $__currentLoopData = $membres; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $membre): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="repartition-card bg-white rounded-xl p-4 border-l-blue-400 shadow-sm">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-user text-blue-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">
                                            <?php echo e($membre->nom); ?> <?php echo e($membre->prenom); ?>

                                        </p>
                                        <p class="text-xs text-gray-600">
                                            <?php echo e($membre->numero_compte); ?>

                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-600">Solde: <?php echo e(number_format(floatval($membre->solde ?? 0), 2, ',', ' ')); ?> USD</p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Montant accordé au membre:
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 text-sm">USD</span>
                                        </div>
                                        <input 
                                            type="number" 
                                            name="montants_membres[<?php echo e($membre->id); ?>]"
                                            step="0.01"
                                            min="0"
                                            value="0"
                                            class="montant-membre block w-full pl-16 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                                            placeholder="0.00"
                                            data-membre-id="<?php echo e($membre->id); ?>"
                                        >
                                    </div>
                                </div>
                                
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <div class="text-xs text-gray-600 mb-1">Détails pour ce membre:</div>
                                    <div class="space-y-1 text-xs">
                                        <div class="flex justify-between">
                                            <span>Frais dossier:</span>
                                            <span class="font-semibold" id="frais-dossier-<?php echo e($membre->id); ?>">0.00 USD</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>Caution (20%):</span>
                                            <span class="font-semibold" id="caution-<?php echo e($membre->id); ?>">0.00 USD</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>Remb. hebdo:</span>
                                            <span class="font-semibold" id="remboursement-<?php echo e($membre->id); ?>">0.00 USD</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    
                    <!-- Total et équilibre -->
                    <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-blue-800 font-medium">
                                Total saisi pour les membres: 
                            </span>
                            <span id="total-saisi-membres" class="text-lg font-bold text-blue-600">0.00 USD</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium" id="difference-text">
                                Équilibre:
                            </span>
                            <span id="difference-montant" class="text-sm font-bold">0.00 USD</span>
                        </div>
                    </div>
                </div>

               <div class="flex flex-col sm:flex-row gap-4 mt-8">
    <!-- BOUTON APPROUVER CORRIGÉ - COMME CRÉDIT INDIVIDUEL -->
               <!-- BOUTON APPROUVER SIMPLIFIÉ -->
            <div class="flex flex-col sm:flex-row gap-4 mt-8">
    <!-- BOUTON APPROUVER SIMPLIFIÉ -->
    <button 
        type="submit"
        name="action" 
        value="approuver"
        id="approve-btn"
        class="flex-1 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center"
        onclick="return validateApprovalGroupe()"
    >
        <i class="fas fa-check-circle mr-3 text-xl"></i>
        Approuver le Crédit Groupe
    </button>

    <button 
        type="button"
        id="rejectBtn"
        class="flex-1 bg-gradient-to-r from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center"
    >
        <i class="fas fa-times-circle mr-3 text-xl"></i>
        Rejeter la Demande
    </button>
</div>

<!-- Section rejet -->
                            <!-- Section rejet -->
                <div id="rejectionSection" class="hidden bg-gradient-to-r from-red-50 to-pink-50 rounded-xl p-6 border border-red-200 mt-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-comment-alt mr-2 text-red-500"></i>
                        Motif du Rejet
                    </h4>
                    <textarea 
                        name="motif_rejet" 
                        rows="4"
                        class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-red-500 focus:border-red-500"
                        placeholder="Veuillez indiquer le motif du rejet de cette demande..."
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
                        >
                            Confirmer le Rejet
                        </button>
                    </div>
                </div>
            </form>

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
        </div>
    </div>
<script>
    // Configuration
    const coefficientGroupe = 1.225;
    const dureeSemaines = 16;
    const devise = "USD";

    // FONCTION AMÉLIORÉE AVEC SUGGESTION AUTOMATIQUE
    function validateApprovalGroupe() {
        console.log('🟢 Validation crédit groupe...');
        
        const montantTotalGroupe = parseFloat(document.getElementById('montant_total_groupe').value) || 0;
        let totalSaisiMembres = 0;
        let membresAvecMontant = 0;
        
        // Calcul simple des totaux
        document.querySelectorAll('.montant-membre').forEach(input => {
            const montant = parseFloat(input.value) || 0;
            totalSaisiMembres += montant;
            if (montant > 0) membresAvecMontant++;
        });
        
        const difference = totalSaisiMembres - montantTotalGroupe;
        
        // VALIDATION AMÉLIORÉE
        if (montantTotalGroupe <= 0) {
            alert('❌ Veuillez saisir un montant total valide.');
            return false;
        }
        
        if (Math.abs(difference) > 0.01) {
            // SUGGESTION AUTOMATIQUE
            const suggestion = Math.abs(difference).toFixed(2);
            const message = difference > 0 
                ? `❌ Trop alloué aux membres! Retirez ${suggestion} USD de la répartition.`
                : `❌ Montant insuffisant! Ajoutez ${suggestion} USD à la répartition.`;
            
            alert(message);
            return false;
        }
        
        if (membresAvecMontant === 0) {
            alert('❌ Attribuez un montant à au moins un membre.');
            return false;
        }
        
        // CONFIRMATION DÉTAILLÉE
        const confirmation = confirm(
            `Êtes-vous sûr de vouloir approuver ce crédit groupe ?\n\n` +
            `📊 Montant total: ${montantTotalGroupe.toFixed(2)} USD\n` +
            `👥 Réparti entre: ${membresAvecMontant} membre(s)\n` +
            `✅ Équilibre parfait`
        );
        
        return confirmation;
    }

    // GESTION AMÉLIORÉE DE L'ÉQUILIBRE EN TEMPS RÉEL
    function calculerTotaux() {
        const montantTotalGroupe = parseFloat(document.getElementById('montant_total_groupe').value) || 0;
        let totalSaisiMembres = 0;
        let membresAvecMontant = 0;
        
        document.querySelectorAll('.montant-membre').forEach(input => {
            const montant = parseFloat(input.value) || 0;
            totalSaisiMembres += montant;
            if (montant > 0) membresAvecMontant++;
        });
        
        const difference = totalSaisiMembres - montantTotalGroupe;
        const alerteEquilibre = document.getElementById('alerte-equilibre');
        const messageEquilibre = document.getElementById('message-equilibre');
        
        // Affichage des totaux
        document.getElementById('total-saisi-membres').textContent = `${totalSaisiMembres.toFixed(2)} ${devise}`;
        
        // Gestion de l'alerte d'équilibre
        if (Math.abs(difference) < 0.01) {
            document.getElementById('difference-text').innerHTML = '✅ <span class="text-green-600">Équilibre parfait</span>';
            document.getElementById('difference-montant').innerHTML = '<span class="text-green-600 font-bold">0.00 USD</span>';
            alerteEquilibre.classList.add('hidden');
            
            // Activer le bouton d'approbation
            document.getElementById('approve-btn').disabled = false;
            
        } else {
            const absDifference = Math.abs(difference).toFixed(2);
            if (difference > 0) {
                document.getElementById('difference-text').innerHTML = '❌ <span class="text-red-600">Trop alloué</span>';
                document.getElementById('difference-montant').innerHTML = `<span class="text-red-600 font-bold">+${absDifference} USD</span>`;
                messageEquilibre.textContent = `Vous avez alloué ${absDifference} USD de trop. Réduisez les montants des membres.`;
            } else {
                document.getElementById('difference-text').innerHTML = '❌ <span class="text-orange-600">Montant manquant</span>';
                document.getElementById('difference-montant').innerHTML = `<span class="text-orange-600 font-bold">-${absDifference} USD</span>`;
                messageEquilibre.textContent = `Il manque ${absDifference} USD dans la répartition. Augmentez les montants des membres.`;
            }
            
            alerteEquilibre.classList.remove('hidden');
            
            // Désactiver le bouton d'approbation
            document.getElementById('approve-btn').disabled = true;
        }
    }

    // FONCTION POUR ÉQUILIBRER AUTOMATIQUEMENT
    function equilibrerRepartition() {
        const montantTotalGroupe = parseFloat(document.getElementById('montant_total_groupe').value) || 0;
        let totalSaisiMembres = 0;
        const inputsMembres = document.querySelectorAll('.montant-membre');
        const membresAvecMontant = [];
        
        // Calculer le total actuel et identifier les membres avec montant
        inputsMembres.forEach(input => {
            const montant = parseFloat(input.value) || 0;
            totalSaisiMembres += montant;
            if (montant > 0) {
                membresAvecMontant.push(input);
            }
        });
        
        const difference = montantTotalGroupe - totalSaisiMembres;
        
        if (Math.abs(difference) > 0.01 && membresAvecMontant.length > 0) {
            // Répartir équitablement la différence
            const ajustementParMembre = difference / membresAvecMontant.length;
            
            membresAvecMontant.forEach(input => {
                const nouveauMontant = (parseFloat(input.value) || 0) + ajustementParMembre;
                input.value = Math.max(0, nouveauMontant).toFixed(2);
            });
            
            // Recalculer
            calculerTotaux();
            
            alert(`✅ Répartition équilibrée automatiquement!`);
        }
    }

    // GESTION SIMPLE DU REJET
    function showRejection() {
        document.getElementById('rejectionSection').classList.remove('hidden');
        document.getElementById('rejectBtn').classList.add('hidden');
    }

    function hideRejection() {
        document.getElementById('rejectionSection').classList.add('hidden');
        document.getElementById('rejectBtn').classList.remove('hidden');
        document.querySelector('textarea[name="motif_rejet"]').value = '';
    }

    // INITIALISATION
    document.addEventListener('DOMContentLoaded', function() {
        // Événements basiques
        document.getElementById('montant_total_groupe')?.addEventListener('input', calculerTotaux);
        document.querySelectorAll('.montant-membre').forEach(input => {
            input.addEventListener('input', calculerTotaux);
        });

        document.getElementById('rejectBtn')?.addEventListener('click', showRejection);
        document.getElementById('cancelReject')?.addEventListener('click', hideRejection);

        // Ajouter le bouton d'équilibrage automatique
        const boutonEquilibrage = document.createElement('button');
        boutonEquilibrage.type = 'button';
        boutonEquilibrage.innerHTML = '<i class="fas fa-balance-scale mr-2"></i>Équilibrer Automatiquement';
        boutonEquilibrage.className = 'bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg transition-all duration-200 mt-2';
        boutonEquilibrage.onclick = equilibrerRepartition;
        
        const sectionEquilibre = document.querySelector('.mt-6.p-4.bg-blue-50');
        sectionEquilibre.appendChild(boutonEquilibrage);

        // Calcul initial
        calculerTotaux();
    });
</script>
</body>
</html><?php /**PATH C:\STORAGE\TUMAINI LETU\System\tumainiletusystem\tumainiletusystem2.0\resources\views/credits/approval-groupe-final.blade.php ENDPATH**/ ?>