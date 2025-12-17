<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bordereau - <?php echo e($mouvement->type); ?></title>
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #000;
            font-size: 11px;
            line-height: 1.3;
            background: #f5f5f5;
        }
        
        /* Page A4 centrée */
        @page {
            size: A4;
            margin: 5mm;
        }
        
        /* Conteneur A4 optimisé */
        .a4-container {
            width: 200mm;
            height: 287mm;
            margin: 5mm auto;
            padding: 10mm 15mm;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        /* Bordereau compact */
        .bordereau {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 120mm;
            padding: 5mm;
            border: 1px solid #eee;
            border-radius: 3px;
        }
        
        /* Ligne de coupure */
        .cut-line {
            text-align: center;
            margin: 10px 0;
            padding: 5px 0;
            color: #666;
            font-size: 10px;
            border-top: 1px dashed #ccc;
            border-bottom: 1px dashed #ccc;
        }
        
        /* Style compact amélioré */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .header img {
            height: 50px;
            width: auto;
            max-width: 100px;
        }
        
        .header-info {
            text-align: right;
            font-size: 9px;
            line-height: 1.2;
        }
        
        .header-info div {
            margin-bottom: 2px;
        }
        
        .separator {
            border-top: 1px solid #000;
            margin: 6px 0;
        }
        
        .ref-date {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 8px;
        }
        
        .content {
            flex: 1;
        }
        
        .content-line {
            display: flex;
            justify-content: space-between;
            margin: 4px 0;
            font-size: 10px;
        }
        
        .content-line strong {
            width: 45%;
            font-weight: 600;
            
        }
        
        .content-line span {
            width: 53%;
            text-align: right;
        }
        
        .montant-section {
            background-color: #f8f9fa;
            padding: 6px;
            margin: 6px 0;
            border-radius: 3px;
            border-left: 3px solid #007bff;
        }
        
        .devise-usd { 
            color: #28a745; 
            font-weight: bold;
        }
        
        .devise-cdf { 
            color: #fd7e14; 
            font-weight: bold;
        }
        
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        
        .signature {
            width: 48%;
            text-align: center;
            padding-top: 5px;
            border-top: 1px solid #000;
            font-size: 9px;
        }
        
        /* Impression */
        @media print {
            body {
                background: white;
            }
            
            .a4-container {
                width: 100%;
                height: 100%;
                margin: 0;
                padding: 8mm 12mm;
                box-shadow: none;
                border: none;
            }
            
            .no-print {
                display: none !important;
            }
            
            .bordereau {
                border: none;
                page-break-inside: avoid;
            }
            
            .cut-line {
                border-top: 1px dashed #000;
                border-bottom: 1px dashed #000;
            }
        }
        
        /* Contrôles */
        .controls {
            position: fixed;
            top: 10px;
            right: 10px;
            background: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            font-size: 12px;
        }
        
        .controls button {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 15px;
            margin: 0 5px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
        
        /* Étiquettes */
        .copy-label {
            position: absolute;
            top: 2px;
            right: 2px;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 2px;
            font-size: 9px;
            font-weight: bold;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="controls no-print">
        <button onclick="window.print()">🖨️ Imprimer</button>
        <button onclick="window.close()">❌ Fermer</button>
    </div>

    <div class="a4-container">
        <!-- PREMIER BORDEREAU -->
        <div class="bordereau">
            <div class="header">
                <div class="logo">
                    <?php if(file_exists(public_path('images/logo-tumaini1.png'))): ?>
                        <img src="<?php echo e(asset('images/logo-tumaini1.png')); ?>" alt="TUMAINI LETU asbl">
                    <?php else: ?>
                        <div style="height: 50px; width: 100px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border: 1px solid #dee2e6; font-size: 8px; color: #666;">
                            LOGO
                        </div>
                    <?php endif; ?>
                </div>
                <div class="header-info">
                    <div><strong>Tumaini Letu asbl</strong></div>
                    <div>Siège social 005, avenue du port</div>
                    <div>Quartier les volcans - Goma</div>
                    <div>NUM BED : 14453756111</div>
                    <div>Tel : +243982618321</div>
                    <div>Email : tumainiletu@gmail.com</div>
                </div>
            </div>

            <div class="separator"></div>

            <div class="ref-date">
                <div>N/REF : <?php echo e($mouvement->numero_reference ?? str_pad($mouvement->id, 7, '0', STR_PAD_LEFT)); ?></div>
                <div>Date : <?php echo e($mouvement->created_at->format('d/m/Y')); ?></div>
                <div>Opérateur : <?php echo e($mouvement->operateur_abrege ?? 'N/A'); ?></div>
            </div>

            <div class="separator"></div>

            <div class="content">
                <div class="content-line">
                    <strong>Type de mouvement :</strong>
                    <span><?php echo e(ucfirst($mouvement->type)); ?></span>
                </div>
                
                <div class="montant-section">
                    <div class="content-line">
                        <strong><?php echo e($mouvement->type === 'depot' ? 'Entrée' : 'Sortie'); ?> :</strong style="color: #000">
                        <span class="<?php echo e($mouvement->devise === 'USD' ? 'devise-usd' : 'devise-cdf'); ?>" style="color: #000">
                            <?php echo e(number_format($mouvement->montant, 2, ',', ' ')); ?> <?php echo e($mouvement->devise); ?>

                        </span>
                    </div>
                </div>

                <div class="content-line">
                    <strong>Numéro du compte :</strong>
                    <span><?php echo e($mouvement->numero_compte); ?></span>
                </div>
                
                <div class="content-line">
                    <strong>Agence :</strong>
                    <span>Goma</span>
                </div>
                
                <div class="content-line">
                    <strong>Intitulé du compte :</strong>
                    <span><?php echo e($mouvement->client_nom); ?></span>
                </div>
                
                <div class="content-line">
                    <strong>Solde après opération :</strong>
                    <span  class ="<?php echo e($mouvement->devise === 'USD' ? 'devise-usd' : 'devise-cdf'); ?>"style="color: #000">
                        <?php echo e(number_format($mouvement->solde_apres, 2, ',', ' ')); ?> <?php echo e($mouvement->devise); ?>

                    </span>
                </div>

                <div class="content-line">
                    <strong>Devise :</strong>
                    <span class="<?php echo e($mouvement->devise === 'USD' ? 'devise-usd' : 'devise-cdf'); ?>" style="color: #000">
                        <?php echo e($mouvement->devise); ?>

                    </span>
                </div>

                <div class="separator"></div>

                <div class="content-line">
                    <strong>Libellé :</strong>
                    <span>Bordereau d'<?php echo e($mouvement->type === 'depot' ? 'entrée' : 'sortie'); ?> / <?php echo e($mouvement->client_nom); ?></span>
                </div>
                
                <div class="content-line">
                    <strong>ID du Membre :</strong>
                    <span><?php echo e($mouvement->compte->id_client ?? 'N/A'); ?></span>
                </div>

                <?php if($mouvement->type_mouvement): ?>
                <div class="content-line">
                    <strong>Type d'opération :</strong>
                    <span>
                        <?php switch($mouvement->type_mouvement):
                            case ('versement_agent'): ?>
                                Versement Agent
                                <?php break; ?>
                            <?php case ('paiement_credit'): ?>
                                Paiement Crédit
                                <?php break; ?>
                            <?php default: ?>
                                <?php echo e(ucfirst(str_replace('_', ' ', $mouvement->type_mouvement))); ?>

                        <?php endswitch; ?>
                    </span>
                </div>
                <?php endif; ?>

                <div class="separator"></div>

                <div class="content-line">
                    <strong>Nom du <?php echo e($mouvement->type === 'depot' ? 'déposant' : 'retirant'); ?> :</strong>
                    <span><?php echo e($mouvement->nom_deposant); ?></span>
                </div>

                <?php if($mouvement->description): ?>
                <div class="content-line">
                    <strong>Description :</strong>
                    <span><?php echo e($mouvement->description); ?></span>
                </div>
                <?php endif; ?>

                <?php if(in_array($mouvement->type_mouvement, ['versement_agent', 'paiement_credit', 'depense_diverse'])): ?>
                <div class="content-line">
                    <strong>Type d'opération :</strong>
                    <span class="<?php echo e($mouvement->devise === 'USD' ? 'devise-usd' : 'devise-cdf'); ?>">
                        <?php switch($mouvement->type_mouvement):
                            case ('versement_agent'): ?>
                                Versement Agent Collecteur
                                <?php break; ?>
                            <?php case ('paiement_credit'): ?>
                                Paiement de Crédit
                                <?php break; ?>
                        <?php endswitch; ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <div class="signatures">
                <div class="signature" style="text-align: left;">
                    Signature du <?php echo e($mouvement->type === 'depot' ? 'déposant' : 'retirant'); ?>

                </div>
                <div class="signature" style="text-align: right;">
                    Signature de l'agent
                </div>
            </div>
        </div>

        <!-- LIGNE DE COUPURE -->
        <div class="cut-line">
            ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        </div>

        <!-- DEUXIÈME BORDEREAU -->
        <div class="bordereau">
            <div class="header">
                <div class="logo">
                    <?php if(file_exists(public_path('images/logo-tumaini1.png'))): ?>
                        <img src="<?php echo e(asset('images/logo-tumaini1.png')); ?>" alt="TUMAINI LETU asbl">
                    <?php else: ?>
                        <div style="height: 50px; width: 100px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border: 1px solid #dee2e6; font-size: 8px; color: #666;">
                            LOGO
                        </div>
                    <?php endif; ?>
                </div>
                <div class="header-info">
                    <div><strong>Tumaini Letu asbl</strong></div>
                    <div>Siège social 005, avenue du port</div>
                    <div>Quartier les volcans - Goma</div>
                    <div>NUM BED : 14453756111</div>
                    <div>Tel : +243982618321</div>
                    <div>Email : tumainiletu@gmail.com</div>
                </div>
            </div>

            <div class="separator"></div>

            <div class="ref-date">
                <div>N/REF : <?php echo e($mouvement->numero_reference ?? str_pad($mouvement->id, 7, '0', STR_PAD_LEFT)); ?></div>
                <div>Date : <?php echo e($mouvement->created_at->format('d/m/Y')); ?></div>
                <div>Opérateur : <?php echo e($mouvement->operateur_abrege ?? 'N/A'); ?></div>
            </div>

            <div class="separator"></div>

            <div class="content">
                <div class="content-line">
                    <strong>Type de mouvement :</strong>
                    <span><?php echo e(ucfirst($mouvement->type)); ?></span>
                </div>
                
                <div class="montant-section">
                    <div class="content-line">
                        <strong><?php echo e($mouvement->type === 'depot' ? 'Entrée' : 'Sortie'); ?> :</strong >
                        <span class="<?php echo e($mouvement->devise === 'USD' ? 'devise-usd' : 'devise-cdf'); ?>"style="color: #000">
                            <?php echo e(number_format($mouvement->montant, 2, ',', ' ')); ?> <?php echo e($mouvement->devise); ?>

                        </span>
                    </div>
                </div>

                <div class="content-line">
                    <strong>Numéro du compte :</strong>
                    <span><?php echo e($mouvement->numero_compte); ?></span>
                </div>
                
                <div class="content-line">
                    <strong>Agence :</strong>
                    <span>Goma</span>
                </div>
                
                <div class="content-line">
                    <strong>Intitulé du compte :</strong>
                    <span><?php echo e($mouvement->client_nom); ?></span>
                </div>
                
                <div class="content-line">
                    <strong>Solde après opération :</strong>
                    <span class="<?php echo e($mouvement->devise === 'USD' ? 'devise-usd' : 'devise-cdf'); ?>"style="color: #000">
                        <?php echo e(number_format($mouvement->solde_apres, 2, ',', ' ')); ?> <?php echo e($mouvement->devise); ?>

                    </span>
                </div>

                <div class="content-line">
                    <strong>Devise :</strong>
                    <span class="<?php echo e($mouvement->devise === 'USD' ? 'devise-usd' : 'devise-cdf'); ?>"style="color: #000">
                        <?php echo e($mouvement->devise); ?>

                    </span>
                </div>

                <div class="separator"></div>

                <div class="content-line">
                    <strong>Libellé :</strong>
                    <span>Bordereau d'<?php echo e($mouvement->type === 'depot' ? 'entrée' : 'sortie'); ?> / <?php echo e($mouvement->client_nom); ?></span>
                </div>
                
                <div class="content-line">
                    <strong>ID du Membre :</strong>
                    <span><?php echo e($mouvement->compte->id_client ?? 'N/A'); ?></span>
                </div>

                <?php if($mouvement->type_mouvement): ?>
                <div class="content-line">
                    <strong>Type d'opération :</strong>
                    <span>
                        <?php switch($mouvement->type_mouvement):
                            case ('versement_agent'): ?>
                                Versement Agent
                                <?php break; ?>
                            <?php case ('paiement_credit'): ?>
                                Paiement Crédit
                                <?php break; ?>
                            <?php default: ?>
                                <?php echo e(ucfirst(str_replace('_', ' ', $mouvement->type_mouvement))); ?>

                        <?php endswitch; ?>
                    </span>
                </div>
                <?php endif; ?>

                <div class="separator"></div>

                <div class="content-line">
                    <strong>Nom du <?php echo e($mouvement->type === 'depot' ? 'déposant' : 'retirant'); ?> :</strong>
                    <span><?php echo e($mouvement->nom_deposant); ?></span>
                </div>

                <?php if($mouvement->description): ?>
                <div class="content-line">
                    <strong>Description :</strong>
                    <span><?php echo e($mouvement->description); ?></span>
                </div>
                <?php endif; ?>

                <?php if(in_array($mouvement->type_mouvement, ['versement_agent', 'paiement_credit', 'depense_diverse'])): ?>
                <div class="content-line">
                    <strong>Type d'opération :</strong>
                    <span class="<?php echo e($mouvement->devise === 'USD' ? 'devise-usd' : 'devise-cdf'); ?>">
                        <?php switch($mouvement->type_mouvement):
                            case ('versement_agent'): ?>
                                Versement Agent Collecteur
                                <?php break; ?>
                            <?php case ('paiement_credit'): ?>
                                Paiement de Crédit
                                <?php break; ?>
                        <?php endswitch; ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <div class="signatures">
                <div class="signature" style="text-align: left;">
                    Signature du <?php echo e($mouvement->type === 'depot' ? 'déposant' : 'retirant'); ?>

                </div>
                <div class="signature" style="text-align: right;">
                    Signature de l'agent
                </div>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            // Ajustement pour centrer le contenu
            const bordereaux = document.querySelectorAll('.bordereau');
            bordereaux.forEach(bordereau => {
                const contentHeight = bordereau.scrollHeight;
                const containerHeight = bordereau.clientHeight;
                
                if (contentHeight < containerHeight) {
                    // Calculer l'espace à ajouter en haut
                    const space = (containerHeight - contentHeight) / 2;
                    bordereau.style.paddingTop = space + 'px';
                }
            });
        };
    </script>
</body>
</html><?php /**PATH C:\laragon\www\tumainiletusystem2.0\resources\views/bordereau-mouvement.blade.php ENDPATH**/ ?>