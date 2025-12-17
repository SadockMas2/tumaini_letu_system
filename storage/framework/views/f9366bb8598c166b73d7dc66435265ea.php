<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de Crédit - Tumaini Letu</title>
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
        .credit-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(20, 9, 9, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        .info-item {
            transition: all 0.3s ease;
        }
        .info-item:hover {
            transform: translateY(-2px);
        }
        .form-input:focus {
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .type-option {
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .type-option.selected {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="w-full max-w-4xl mx-4">
        <!-- Header Section -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-2xl shadow-lg mb-4">
                <i class="fas fa-hand-holding-usd text-3xl text-purple-600"></i>
            </div>
            <h1 class="text-4xl font-bold text-white mb-3">Demande de Crédit</h1>
            <p class="text-white/80 text-lg">Système de Gestion Financière Tumaini Letu</p>
        </div>

        <!-- Main Card -->
        <div class="credit-card rounded-2xl p-8">
            <!-- Account Overview -->
            <div class="text-center mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-2">
                    Compte <span class="text-purple-600"><?php echo e($compte->numero_compte); ?></span>
                </h2>
                <div class="flex flex-wrap justify-center gap-4 text-sm text-gray-600 mb-4">
                    <div class="flex items-center bg-blue-50 rounded-full px-4 py-2">
                        <i class="fas fa-user mr-2 text-blue-500"></i>
                        <span class="font-medium"><?php echo e($compte->nom); ?> <?php echo e($compte->prenom); ?></span>
                    </div>
                    <div class="flex items-center bg-green-50 rounded-full px-4 py-2">
                        <i class="fas fa-id-card mr-2 text-green-500"></i>
                        <span class="font-medium"><?php echo e($compte->numero_membre); ?></span>
                    </div>
                    <div class="flex items-center bg-purple-50 rounded-full px-4 py-2">
                        <i class="fas fa-tag mr-2 text-purple-500"></i>
                        <span class="font-medium capitalize"><?php echo e($compte->type_compte); ?></span>
                    </div>
                </div>
            </div>

            <!-- Account Details Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="info-item bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-100">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-wallet text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Solde Actuel</p>
                            <p class="text-xl font-bold text-gray-800">
                                <?php echo e(number_format($compte->solde, 2, ',', ' ')); ?> <?php echo e($compte->devise); ?>

                            </p>
                        </div>
                    </div>
                </div>

                <div class="info-item bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-6 border border-purple-100">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-money-bill-wave text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Type de Compte</p>
                            <p class="text-xl font-bold text-gray-800 capitalize"><?php echo e($compte->type_compte); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Credit Application Form -->
            <form action="<?php echo e(route('credits.store')); ?>" method="POST" class="space-y-6" id="creditForm">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="compte_id" value="<?php echo e($compte->id); ?>">
                <input type="hidden" name="type_compte" value="<?php echo e($compte->type_compte); ?>">

                <!-- Form Header -->
                <div class="text-center mb-6">
                    <h3 class="text-xl font-semibold text-gray-800">
                        <i class="fas fa-edit mr-2 text-indigo-500"></i>
                        Informations du Crédit
                    </h3>
                    <p class="text-gray-600 mt-2">Remplissez les détails de votre demande de crédit</p>
                </div>

                <!-- Type de Crédit (Auto-sélection basé sur le type de compte) -->
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-6 border border-gray-200">
                    <label class="block text-lg font-semibold text-gray-700 mb-4">
                        <i class="fas fa-tags mr-2 text-indigo-500"></i>
                        Type de Crédit
                    </label>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="type-option rounded-xl p-4 border-2 border-gray-200 cursor-pointer text-center"
                             id="option-individuel"
                             onclick="selectType('individuel')">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-user text-blue-600 text-xl"></i>
                            </div>
                            <h4 class="font-semibold text-gray-800">Crédit Individuel</h4>
                            <p class="text-sm text-gray-600 mt-1">Pour les membres individuels</p>
                            <div class="mt-2 text-xs text-blue-600">
                                <i class="fas fa-info-circle mr-1"></i>
                                Taux variables selon montant
                            </div>
                        </div>

                        <div class="type-option rounded-xl p-4 border-2 border-gray-200 cursor-pointer text-center"
                             id="option-groupe"
                             onclick="selectType('groupe')">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-users text-green-600 text-xl"></i>
                            </div>
                            <h4 class="font-semibold text-gray-800">Crédit de Groupe</h4>
                            <p class="text-sm text-gray-600 mt-1">Pour les groupes solidaires</p>
                            <div class="mt-2 text-xs text-green-600">
                                <i class="fas fa-info-circle mr-1"></i>
                                Taux fixe: 1.225
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="type_credit" id="type_credit" required>
                    
                    <!-- Message d'information sur la sélection automatique -->
                    <div id="auto-selection-message" class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg hidden">
                        <div class="flex items-center">
                            <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                            <span class="text-sm text-blue-800">
                                Sélection automatique basée sur votre type de compte: 
                                <strong class="capitalize" id="selected-type-text"></strong>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Amount Input -->
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-6 border border-gray-200">
                    <label class="block text-lg font-semibold text-gray-700 mb-4">
                        <i class="fas fa-money-check-alt mr-2 text-green-500"></i>
                        Montant du Crédit Demandé
                    </label>
                    
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 font-medium"><?php echo e($compte->devise); ?></span>
                        </div>
                        <input 
                            type="number" 
                            name="montant_demande" 
                            step="0.01"
                            min="0.01"
                            class="form-input block w-full pl-16 pr-4 py-4 text-lg border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                            placeholder="0.00"
                            required
                            id="montant_demande"
                        >
                    </div>
                    
                    <div class="mt-3 text-sm text-gray-500">
                        <i class="fas fa-info-circle mr-1 text-blue-500"></i>
                        Entrez le montant que vous souhaitez emprunter
                    </div>
                </div>

                <!-- Information selon le type -->
                <div id="info-individuel" class="hidden bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                        <div class="text-sm text-blue-800">
                            <strong>Crédit Individuel:</strong> Taux variables selon le montant accordé.
                            Durée: 4 mois. Remboursement hebdomadaire.
                        </div>
                    </div>
                </div>

                <div id="info-groupe" class="hidden bg-green-50 border border-green-200 rounded-xl p-4">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-green-500 mt-1 mr-3"></i>
                        <div class="text-sm text-green-800">
                            <strong>Crédit de Groupe:</strong> Coefficient fixe 1.225. 
                            Durée: 4 mois (16 semaines). Remboursement hebdomadaire.
                        </div>
                    </div>
                </div>

                <!-- Terms and Conditions -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-yellow-500 mt-1 mr-3"></i>
                        <div class="text-sm text-yellow-800">
                            <strong>Important:</strong> Le remboursement se fait chaque semaine après l'approbation du crédit.
                            Des frais supplémentaires (dossier, alerte, carnet, adhésion) s'appliquent selon le montant.
                            Une caution sera bloquée dans votre compte.
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 pt-6">
                    <button 
                        type="submit" 
                        class="flex-1 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center"
                        id="submit-btn"
                        disabled
                    >
                        <i class="fas fa-paper-plane mr-3"></i>
                        Soumettre la Demande
                    </button>
                    
                    <a 
                        href="<?php echo e(url('/admin/comptes')); ?>" 
                        class="flex-1 bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center"
                    >
                        <i class="fas fa-arrow-left mr-3"></i>
                        Retour aux Comptes
                    </a>
                </div>
            </form>

            <!-- Security Notice -->
            <div class="mt-8 text-center">
                <div class="inline-flex items-center text-xs text-gray-500 bg-gray-100 rounded-full px-4 py-2">
                    <i class="fas fa-shield-alt mr-2 text-green-500"></i>
                    Votre demande est traitée de manière sécurisée et confidentielle
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8">
            <p class="text-white/60 text-sm">
                &copy; 2025 Tumaini Letu System. Service financier de confiance.
            </p>
        </div>
    </div>

     <script>
        // Collez tout votre script JavaScript ici
        let selectedType = null;
        const compteType = "<?php echo e(strtolower($compte->type_compte)); ?>";

        // Auto-sélection basée sur le type de compte
        document.addEventListener('DOMContentLoaded', function() {
            if (compteType.includes('individuel') || compteType.includes('personnel')) {
                selectType('individuel');
                showAutoSelectionMessage('individuel');
            } else if (compteType.includes('groupe') || compteType.includes('solidaire')) {
                selectType('groupe');
                showAutoSelectionMessage('groupe');
            }
        });

        function showAutoSelectionMessage(type) {
            const messageDiv = document.getElementById('auto-selection-message');
            const typeText = document.getElementById('selected-type-text');
            
            typeText.textContent = type === 'individuel' ? 'Crédit Individuel' : 'Crédit de Groupe';
            messageDiv.classList.remove('hidden');
        }

        function selectType(type) {
            // Si le type est imposé par le type de compte, on ne permet pas de changer
            if ((compteType.includes('individuel') || compteType.includes('personnel')) && type !== 'individuel') {
                showTypeError("Votre compte est de type individuel. Seul le crédit individuel est autorisé.");
                return;
            }
            
            if ((compteType.includes('groupe') || compteType.includes('solidaire')) && type !== 'groupe') {
                showTypeError("Votre compte est de type groupe. Seul le crédit de groupe est autorisé.");
                return;
            }

            selectedType = type;
            document.getElementById('type_credit').value = type;
            
            // Reset all options
            document.querySelectorAll('.type-option').forEach(option => {
                option.classList.remove('selected', 'border-blue-500', 'border-green-500', 'bg-blue-50', 'bg-green-50');
                option.classList.add('border-gray-200');
            });
            
            // Select current option
            const currentOption = document.getElementById(`option-${type}`);
            if (type === 'individuel') {
                currentOption.classList.add('selected', 'border-blue-500', 'bg-blue-50');
            } else {
                currentOption.classList.add('selected', 'border-green-500', 'bg-green-50');
            }
            
            // Show/hide info boxes
            document.getElementById('info-individuel').classList.add('hidden');
            document.getElementById('info-groupe').classList.add('hidden');
            document.getElementById(`info-${type}`).classList.remove('hidden');
            
            // Enable submit button if amount is filled
            checkFormValidity();
        }

        function showTypeError(message) {
            // Créer ou mettre à jour le message d'erreur
            let errorDiv = document.getElementById('type-error-message');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.id = 'type-error-message';
                errorDiv.className = 'mt-4 p-3 bg-red-50 border border-red-200 rounded-lg';
                const typeSection = document.querySelector('.bg-gradient-to-r.from-gray-50');
                typeSection.parentNode.insertBefore(errorDiv, typeSection.nextSibling);
            }
            
            errorDiv.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                    <span class="text-sm text-red-800">${message}</span>
                </div>
            `;
            
            // Supprimer le message après 5 secondes
            setTimeout(() => {
                errorDiv.remove();
            }, 5000);
        }

        function checkFormValidity() {
            const montant = document.getElementById('montant_demande').value;
            const submitBtn = document.getElementById('submit-btn');
            
            if (selectedType && montant && parseFloat(montant) > 0) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = `<i class="fas fa-paper-plane mr-3"></i>Soumettre la Demande (${selectedType === 'individuel' ? 'Individuel' : 'Groupe'})`;
            } else {
                submitBtn.disabled = true;
                submitBtn.innerHTML = `<i class="fas fa-paper-plane mr-3"></i>Soumettre la Demande`;
            }
        }

        // Event listeners
        document.getElementById('montant_demande').addEventListener('input', checkFormValidity);

        // Animation pour les champs de formulaire
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('ring-2', 'ring-indigo-200', 'bg-white');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('ring-2', 'ring-indigo-200', 'bg-white');
            });
        });

        // Validation
        document.querySelector('input[name="montant_demande"]').addEventListener('input', function() {
            const value = parseFloat(this.value) || 0;
            if (value < 0) {
                this.value = 0;
            }
        });
    </script>
</body>
</html><?php /**PATH C:\laragon\www\tumainiletusystem2.0\resources\views/credits/create.blade.php ENDPATH**/ ?>