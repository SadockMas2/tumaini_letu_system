<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sélection des Paramètres - Remboursement par Période</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            padding: 40px;
            width: 100%;
            max-width: 800px;
            animation: slideIn 0.5s ease-out;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .title {
            color: #2d3748;
            font-size: 2rem;
            margin-bottom: 10px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .subtitle {
            color: #718096;
            font-size: 1rem;
        }

        .form-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .form-section {
            background: #f8fafc;
            padding: 25px;
            border-radius: 15px;
            border: 2px solid #e2e8f0;
        }

        .section-title {
            color: #4a5568;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
            font-size: 1.1rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #4a5568;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
            background: white;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .period-options {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .period-option {
            flex: 1;
            text-align: center;
            padding: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }

        .period-option:hover {
            border-color: #667eea;
            background: #f7fafc;
        }

        .period-option.selected {
            border-color: #667eea;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            transform: translateY(-2px);
        }

        .period-option .icon {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .period-option .label {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .date-range {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-generate {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }

        .btn-generate:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .btn-cancel {
            background: #e2e8f0;
            color: #4a5568;
        }

        .btn-cancel:hover {
            background: #cbd5e0;
        }

        .info-box {
            background: #e6fffa;
            border: 1px solid #81e6d9;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #234e52;
        }

        .info-box strong {
            color: #2c7a7b;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .form-container {
                grid-template-columns: 1fr;
            }
            
            .date-range {
                grid-template-columns: 1fr;
            }
            
            .period-options {
                flex-direction: column;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="title">📊 Rapport de Remboursement par Période</h1>
            <p class="subtitle">Sélectionnez les paramètres pour générer le rapport</p>
        </div>

        <form action="<?php echo e(route('rapport.remboursement.periode.generate')); ?>" method="POST" target="_blank">
            <?php echo csrf_field(); ?>
            
            <div class="form-container">
                <!-- Section Période -->
                <div class="form-section">
                    <h3 class="section-title">1. Type de Période</h3>
                    
                    <div class="period-options">
                        <div class="period-option" onclick="selectPeriod('jour')">
                            <div class="icon">📊</div>
                            <div class="label">Journalier</div>
                            <input type="radio" name="periode" value="jour" style="display: none;">
                        </div>
                        
                        <div class="period-option selected" onclick="selectPeriod('semaine')">
                            <div class="icon">📅</div>
                            <div class="label">Hebdomadaire</div>
                            <input type="radio" name="periode" value="semaine" checked style="display: none;">
                        </div>
                        
                        <div class="period-option" onclick="selectPeriod('mois')">
                            <div class="icon">📈</div>
                            <div class="label">Mensuel</div>
                            <input type="radio" name="periode" value="mois" style="display: none;">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_debut">Date de début</label>
                        <input type="date" id="date_debut" name="date_debut" 
                               value="<?php echo e(date('Y-m-d')); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_fin">Date de fin</label>
                        <input type="date" id="date_fin" name="date_fin" 
                               value="<?php echo e(date('Y-m-d', strtotime('+3 months'))); ?>" required>
                    </div>
                </div>

                <!-- Section Filtres -->
                <div class="form-section">
                    <h3 class="section-title">2. Filtres</h3>
                    
                    <div class="form-group">
                        <label for="type_credit">Type de crédit</label>
                        <select id="type_credit" name="type_credit">
                            <option value="all">Tous les crédits</option>
                            <option value="individuel">Crédits Individuels</option>
                            <option value="groupe">Crédits Groupe</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="agent_id">Agent</label>
                        <select id="agent_id" name="agent_id">
                            <option value="all">Tous les agents</option>
                            <?php $__currentLoopData = $agents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($agent->id); ?>"><?php echo e($agent->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="superviseur_id">Superviseur</label>
                        <select id="superviseur_id" name="superviseur_id">
                            <option value="all">Tous les superviseurs</option>
                            <?php $__currentLoopData = $superviseurs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $superviseur): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($superviseur->id); ?>"><?php echo e($superviseur->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="format_export">Format d'export</label>
                        <select id="format_export" name="format_export">
                            <option value="html">HTML (Vue navigateur)</option>
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Informations -->
            <div class="info-box">
                <strong>ℹ️ Information :</strong> Ce rapport affichera pour chaque période :
                <ul style="margin-top: 5px; margin-left: 20px;">
                    <li>Le montant total à rembourser</li>
                    <li>La répartition capital/intérêts</li>
                    <li>Les pourcentages de chaque composante</li>
                    <li>Le statut de chaque remboursement</li>
                </ul>
            </div>

            <!-- Actions -->
            <div class="actions">
                <button type="button" class="btn btn-cancel" onclick="window.close()">
                    <span>✕</span> Annuler
                </button>
                
                <button type="submit" class="btn btn-generate">
                    <span>📄</span> Générer le Rapport
                </button>
            </div>
        </form>
    </div>

    <script>
        function selectPeriod(period) {
            // Mettre à jour les classes CSS
            document.querySelectorAll('.period-option').forEach(option => {
                option.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            
            // Mettre à jour le radio bouton
            document.querySelectorAll('input[name="periode"]').forEach(radio => {
                radio.checked = radio.value === period;
            });
        }
        
        // Initialiser la sélection
        document.addEventListener('DOMContentLoaded', function() {
            selectPeriod('semaine');
        });
    </script>
</body>
</html><?php /**PATH C:\laragon\www\tumainiletusystem2.0\resources\views/filament/pages/selection-remboursement-periode.blade.php ENDPATH**/ ?>