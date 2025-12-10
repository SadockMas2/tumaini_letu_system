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
        .membre-card {
            border-left: 4px solid;
            transition: all 0.3s ease;
        }
        .membre-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .frais-item {
            border-left: 3px solid;
        }
        .montant-input {
            transition: all 0.3s ease;
        }
        .montant-input:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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
            <!-- Informations du groupe -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200 mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">
                    Groupe: <?php echo e($credit->groupeCompte->nom_groupe ?? 'Nom non disponible'); ?>

                </h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Nombre de membres</p>
                        <p class="text-xl font-bold text-gray-800"><?php echo e($credit->groupeCompte->membres->count() ?? 0); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Montant demandé</p>
                        <p class="text-xl font-bold text-blue-600">
                            <?php echo e(number_format(floatval($variable ?? 0), 2, ',', ' ')); ?> <?php echo e($credit->groupeCompte->devise ?? 'USD'); ?>

                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Date de demande</p>
                        <p class="text-xl font-bold text-gray-800">
                            <?php echo e($credit->date_demande->format('d/m/Y')); ?>

                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Statut</p>
                        <span class="px-3 py-1 text-sm rounded-full bg-yellow-100 text-yellow-800 font-semibold">
                            En attente
                        </span>
                    </div>
                </div>
            </div>

            <!-- Formulaire de répartition personnalisée -->
            <form action="<?php echo e(route('credits.process-approval-groupe', $credit->id)); ?>" method="POST" class="mb-8" id="approval-form">
                <?php echo csrf_field(); ?>
                
                <!-- Section de répartition par membre -->
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-6 border border-gray-200 mb-6">
                    <label class="block text-lg font-semibold text-gray-700 mb-4">
                        <i class="fas fa-edit mr-2 text-indigo-500"></i>
                        Répartition du Crédit par Membre
                    </label>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Montant Total à Accorder au Groupe
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 font-medium"><?php echo e($credit->groupeCompte->devise ?? 'USD'); ?></span>
                            </div>
                            <input 
                                type="number" 
                                name="montant_total_groupe" 
                                step="0.01"
                                min="0.01"
                                max="<?php echo e($credit->montant_demande * 1.5); ?>"
                                value="<?php echo e($credit->montant_demande); ?>"
                                class="block w-full pl-16 pr-4 py-4 text-lg border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                                placeholder="0.00"
                                required
                                id="montant_total_groupe"
                            >
                        </div>
                    </div>

                    <!-- Liste des membres avec champs de montant -->
                    <div class="space-y-4">
                        <h4 class="text-md font-semibold text-gray-800 mb-3">
                            Montants individuels pour chaque membre:
                        </h4>
                        
                        <?php $__currentLoopData = $credit->groupeCompte->membres; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $membre): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="membre-card bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-4 border-l-blue-400">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-user text-blue-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800"><?php echo e($membre->nom); ?> <?php echo e($membre->prenom); ?></p>
                                        <p class="text-xs text-gray-600"><?php echo e($membre->compte->numero_compte ?? 'N/A'); ?></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-600">Solde: <?php echo e(number_format($membre->compte->solde ?? 0, 2, ',', ' ')); ?> <?php echo e($credit->groupeCompte->devise ?? 'USD'); ?></p>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-4">
                                <label class="text-sm font-medium text-gray-700 whitespace-nowrap">
                                    Montant accordé:
                                </label>
                                <div class="relative flex-1">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 text-sm"><?php echo e($credit->groupeCompte->devise ?? 'USD'); ?></span>
                                    </div>
                                    <input 
                                        type="number" 
                                        name="montants_membres[<?php echo e($membre->id); ?>]"
                                        step="0.01"
                                        min="0"
                                        max="<?php echo e($credit->montant_demande * 1.5); ?>"
                                        value="0"
                                        class="montant-input block w-full pl-16 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                                        placeholder="0.00"
                                        data-membre-id="<?php echo e($membre->id); ?>"
                                    >
                                </div>
                            </div>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    
                    <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-blue-800 font-medium">
                                Total saisi: 
                                <span id="total-saisi" class="font-bold">0.00</span> 
                                <?php echo e($credit->groupeCompte->devise ?? 'USD'); ?>

                            </span>
                            <span class="text-sm text-gray-600" id="difference-text">
                                Différence: <span id="difference-montant">0.00</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Prévisualisation de la répartition -->
                <div id="repartition-preview" class="hidden bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                            <i class="fas fa-chart-pie mr-2 text-green-500"></i>
                            Prévisualisation de la Répartition
                        </h3>
                    </div>
                    <div class="p-6">
                        <div id="repartition-details" class="space-y-4">
                            <!-- Les détails de répartition seront générés par JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-4 mt-8">
                    <button 
                        type="submit"
                        name="action"
                        value="approuver"
                        class="flex-1 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center"
                        id="approve-btn"
                        disabled
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
                        required
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

            <!-- Informations importantes -->
            <div class="mt-8 bg-yellow-50 border border-yellow-200 rounded-xl p-6">
                <h4 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2 text-yellow-500"></i>
                    Informations Importantes
                </h4>
                <div class="space-y-2 text-sm text-yellow-800">
                    <p><strong>✓ Répartition personnalisée:</strong> Saisissez le montant spécifique pour chaque membre</p>
                    <p><strong>✓ Coefficient fixe:</strong> 1.225 appliqué au montant total accordé</p>
                    <p><strong>✓ Durée:</strong> 4 mois (16 semaines) de remboursement</p>
                    <p><strong>✓ Remboursement:</strong> Hebdomadaire, commence 2 semaines après approbation</p>
                    <p><strong>✓ Frais:</strong> Dossier, alerte, carnet et adhésion appliqués à chaque membre selon son montant</p>
                    <p><strong>✓ Caution:</strong> Bloquée dans chaque compte membre</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const membres = <?php echo json_encode($credit->groupeCompte->membres ?? [], 15, 512) ?>;
        const devise = "<?php echo e($credit->groupeCompte->devise ?? 'USD'); ?>";
        const coefficientGroupe = 1.225;

        function calculerTotaux() {
            const montantTotalGroupe = parseFloat(document.getElementById('montant_total_groupe').value) || 0;
            let totalSaisi = 0;
            
            // Calculer le total saisi dans les champs membres
            document.querySelectorAll('input[name^="montants_membres"]').forEach(input => {
                totalSaisi += parseFloat(input.value) || 0;
            });
            
            const difference = totalSaisi - montantTotalGroupe;
            
            // Mettre à jour l'affichage des totaux
            document.getElementById('total-saisi').textContent = totalSaisi.toFixed(2);
            document.getElementById('difference-montant').textContent = Math.abs(difference).toFixed(2);
            
            const differenceElement = document.getElementById('difference-text');
            if (Math.abs(difference) < 0.01) {
                differenceElement.className = 'text-sm text-green-600 font-medium';
                differenceElement.innerHTML = `✓ Équilibre parfait`;
            } else if (difference > 0) {
                differenceElement.className = 'text-sm text-red-600 font-medium';
                differenceElement.innerHTML = `Dépassement: <span id="difference-montant">${difference.toFixed(2)}</span>`;
            } else {
                differenceElement.className = 'text-sm text-orange-600 font-medium';
                differenceElement.innerHTML = `Manquant: <span id="difference-montant">${Math.abs(difference).toFixed(2)}</span>`;
            }
            
            // Activer/désactiver le bouton d'approbation
            const approveBtn = document.getElementById('approve-btn');
            if (montantTotalGroupe > 0 && Math.abs(difference) < 0.01 && totalSaisi > 0) {
                approveBtn.disabled = false;
                genererPrevisualisation();
            } else {
                approveBtn.disabled = true;
                document.getElementById('repartition-preview').classList.add('hidden');
            }
            
            return { montantTotalGroupe, totalSaisi, difference };
        }

        function genererPrevisualisation() {
            const { montantTotalGroupe, totalSaisi } = calculerTotaux();
            
            if (montantTotalGroupe > 0 && Math.abs(totalSaisi - montantTotalGroupe) < 0.01) {
                let html = '';
                let totalFraisGroupe = 0;
                let totalRemboursementGroupe = 0;
                
                document.querySelectorAll('input[name^="montants_membres"]').forEach(input => {
                    const membreId = input.getAttribute('data-membre-id');
                    const montantMembre = parseFloat(input.value) || 0;
                    
                    if (montantMembre > 0) {
                        const membre = membres.find(m => m.id == membreId);
                        if (membre) {
                            // Calculs pour chaque membre
                            const frais = calculerFraisGroupe(montantMembre);
                            const montantTotalMembre = montantMembre * coefficientGroupe;
                            const remboursementHebdo = montantTotalMembre / 16;
                            const totalFrais = frais.dossier + frais.alerte + frais.carnet + frais.adhesion;
                            
                            totalFraisGroupe += totalFrais;
                            totalRemboursementGroupe += montantTotalMembre;
                            
                            html += `
                                <div class="membre-card bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-4 border-l-green-400">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <h4 class="font-semibold text-gray-800">${membre.nom} ${membre.prenom}</h4>
                                            <p class="text-sm text-gray-600">${membre.compte?.numero_compte || 'N/A'}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-lg font-bold text-green-600">${montantMembre.toFixed(2)} ${devise}</p>
                                            <p class="text-xs text-gray-600">Montant accordé</p>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-3 text-xs">
                                        <div class="frais-item border-l-blue-400 pl-2">
                                            <span class="text-gray-600">Frais dossier:</span>
                                            <span class="font-semibold text-blue-600 block">${frais.dossier.toFixed(2)} ${devise}</span>
                                        </div>
                                        <div class="frais-item border-l-green-400 pl-2">
                                            <span class="text-gray-600">Frais alerte:</span>
                                            <span class="font-semibold text-green-600 block">${frais.alerte.toFixed(2)} ${devise}</span>
                                        </div>
                                        <div class="frais-item border-l-purple-400 pl-2">
                                            <span class="text-gray-600">Frais carnet:</span>
                                            <span class="font-semibold text-purple-600 block">${frais.carnet.toFixed(2)} ${devise}</span>
                                        </div>
                                        <div class="frais-item border-l-orange-400 pl-2">
                                            <span class="text-gray-600">Frais adhésion:</span>
                                            <span class="font-semibold text-orange-600 block">${frais.adhesion.toFixed(2)} ${devise}</span>
                                        </div>
                                        <div class="frais-item border-l-red-400 pl-2">
                                            <span class="text-gray-600">Caution:</span>
                                            <span class="font-semibold text-red-600 block">${frais.caution.toFixed(2)} ${devise}</span>
                                        </div>
                                        <div class="frais-item border-l-indigo-400 pl-2">
                                            <span class="text-gray-600">Remb. hebdo:</span>
                                            <span class="font-semibold text-indigo-600 block">${remboursementHebdo.toFixed(2)} ${devise}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3 pt-3 border-t border-gray-200">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Total frais:</span>
                                            <span class="font-bold text-orange-600">${totalFrais.toFixed(2)} ${devise}</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Total à remb.:</span>
                                            <span class="font-bold text-purple-600">${montantTotalMembre.toFixed(2)} ${devise}</span>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }
                    }
                });
                
                // Ajouter le résumé groupe
                const montantTotalAvecCoefficient = montantTotalGroupe * coefficientGroupe;
                html = `
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Montant total groupe</p>
                                    <p class="text-xl font-bold text-blue-600">${montantTotalAvecCoefficient.toFixed(2)} ${devise}</p>
                                </div>
                                <i class="fas fa-users text-blue-500 text-2xl"></i>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-4 border border-purple-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Total frais groupe</p>
                                    <p class="text-xl font-bold text-purple-600">${totalFraisGroupe.toFixed(2)} ${devise}</p>
                                </div>
                                <i class="fas fa-receipt text-purple-500 text-2xl"></i>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-4 border border-green-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Remb. hebdo total</p>
                                    <p class="text-xl font-bold text-green-600">${(totalRemboursementGroupe / 16).toFixed(2)} ${devise}</p>
                                </div>
                                <i class="fas fa-calendar-week text-green-500 text-2xl"></i>
                            </div>
                        </div>
                    </div>
                    ${html}
                `;
                
                document.getElementById('repartition-details').innerHTML = html;
                document.getElementById('repartition-preview').classList.remove('hidden');
            } else {
                document.getElementById('repartition-preview').classList.add('hidden');
            }
        }

        // Fonction pour calculer les frais groupe
        function calculerFraisGroupe(montant) {
            const frais = {
                50: {dossier: 2, alerte: 4.5, carnet: 2.5, adhesion: 1, caution: 10},
                100: {dossier: 4, alerte: 4.5, carnet: 2.5, adhesion: 1, caution: 20},
                150: {dossier: 6, alerte: 4.5, carnet: 2.5, adhesion: 1, caution: 30},
                200: {dossier: 8, alerte: 4.5, carnet: 2.5, adhesion: 1, caution: 40},
                250: {dossier: 10, alerte: 4.5, carnet: 2.5, adhesion: 1, caution: 50},
                300: {dossier: 12, alerte: 4.5, carnet: 2.5, adhesion: 1, caution: 60},
                350: {dossier: 14, alerte: 4.5, carnet: 2.5, adhesion: 1, caution: 70},
                400: {dossier: 16, alerte: 4.5, carnet: 2.5, adhesion: 1, caution: 80},
                450: {dossier: 18, alerte: 4.5, carnet: 2.5, adhesion: 1, caution: 90},
                500: {dossier: 20, alerte: 4.5, carnet: 2.5, adhesion: 1, caution: 100},
            };
            
            const montantArrondi = Math<?php /**PATH D:\APP\TUMAINI LETU\tumainiletusystem2.0\resources\views/credits/approval-groupe.blade.php ENDPATH**/ ?>