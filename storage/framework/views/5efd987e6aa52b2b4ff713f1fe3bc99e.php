<!-- resources/views/pdf/evolution-coffres.blade.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Évolution des Coffres - <?php echo e($evolution['periode']['debut']); ?> à <?php echo e($evolution['periode']['fin']); ?></title>
    <style>
        /* Même CSS que votre rapport global */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 15px; font-size: 12px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 15px; }
        .separator { border-top: 2px solid #000; margin: 12px 0; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 10px; }
        .table th, .table td { padding: 4px 3px; border: 1px solid #000; }
        .table th { background-color: #f5f5f5; font-weight: bold; }
        .total-row { background-color: #e8e8e8 !important; font-weight: bold; }
        .evolution-positive { color: #28a745; font-weight: bold; }
        .evolution-negative { color: #dc3545; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .montant { font-family: 'Courier New', monospace; }
    </style>
</head>
<body>
    <!-- En-tête -->
    <div class="header">
        <div>
            <div style="font-weight: bold; font-size: 14px;">TUMAINI LETU asbl</div>
            <div>Rapport d'Évolution des Coffres</div>
        </div>
        <div style="text-align: right;">
            <div>Période : <?php echo e($evolution['periode']['debut']); ?> à <?php echo e($evolution['periode']['fin']); ?></div>
            <div>Généré le : <?php echo e(now()->format('d/m/Y H:i')); ?></div>
        </div>
    </div>

    <div class="separator"></div>

    <!-- Synthèse globale -->
    <div style="margin-bottom: 15px;">
        <h3 style="margin-bottom: 10px;">SYNTHÈSE GLOBALE</h3>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 15px;">
            <div style="border: 1px solid #000; padding: 8px; text-align: center;">
                <div style="font-size: 9px;">USD Début</div>
                <div class="montant"><?php echo e(number_format($evolution['total_usd_debut'], 2)); ?> $</div>
            </div>
            <div style="border: 1px solid #000; padding: 8px; text-align: center;">
                <div style="font-size: 9px;">USD Fin</div>
                <div class="montant"><?php echo e(number_format($evolution['total_usd_fin'], 2)); ?> $</div>
            </div>
            <div style="border: 1px solid #000; padding: 8px; text-align: center;">
                <div style="font-size: 9px;">Évolution USD</div>
                <div class="montant <?php echo e($evolution['evolution_usd'] >= 0 ? 'evolution-positive' : 'evolution-negative'); ?>">
                    <?php echo e(number_format($evolution['evolution_usd'], 2)); ?> $
                </div>
            </div>
            <div style="border: 1px solid #000; padding: 8px; text-align: center;">
                <div style="font-size: 9px;">Taux USD</div>
                <div class="<?php echo e($evolution['total_usd_debut'] != 0 ? ($evolution['evolution_usd'] >= 0 ? 'evolution-positive' : 'evolution-negative') : ''); ?>">
                    <?php echo e($evolution['total_usd_debut'] != 0 ? number_format(($evolution['evolution_usd'] / $evolution['total_usd_debut']) * 100, 2) : 'N/A'); ?>%
                </div>
            </div>
            
            <div style="border: 1px solid #000; padding: 8px; text-align: center;">
                <div style="font-size: 9px;">CDF Début</div>
                <div class="montant"><?php echo e(number_format($evolution['total_cdf_debut'], 0)); ?> FC</div>
            </div>
            <div style="border: 1px solid #000; padding: 8px; text-align: center;">
                <div style="font-size: 9px;">CDF Fin</div>
                <div class="montant"><?php echo e(number_format($evolution['total_cdf_fin'], 0)); ?> FC</div>
            </div>
            <div style="border: 1px solid #000; padding: 8px; text-align: center;">
                <div style="font-size: 9px;">Évolution CDF</div>
                <div class="montant <?php echo e($evolution['evolution_cdf'] >= 0 ? 'evolution-positive' : 'evolution-negative'); ?>">
                    <?php echo e(number_format($evolution['evolution_cdf'], 0)); ?> FC
                </div>
            </div>
            <div style="border: 1px solid #000; padding: 8px; text-align: center;">
                <div style="font-size: 9px;">Taux CDF</div>
                <div class="<?php echo e($evolution['total_cdf_debut'] != 0 ? ($evolution['evolution_cdf'] >= 0 ? 'evolution-positive' : 'evolution-negative') : ''); ?>">
                    <?php echo e($evolution['total_cdf_debut'] != 0 ? number_format(($evolution['evolution_cdf'] / $evolution['total_cdf_debut']) * 100, 2) : 'N/A'); ?>%
                </div>
            </div>
        </div>
    </div>

    <!-- Détail par coffre -->
    <table class="table">
        <thead>
            <tr>
                <th>Coffre</th>
                <th>Devise</th>
                <th class="text-right">Solde Début</th>
                <th class="text-right">Solde Fin</th>
                <th class="text-right">Évolution</th>
                <th class="text-right">Taux</th>
                <th class="text-right">Entrées</th>
                <th class="text-right">Sorties</th>
                <th class="text-center">Opérations</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $evolution['coffres']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $coffre): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($coffre['nom']); ?></td>
                <td><?php echo e($coffre['devise']); ?></td>
                <td class="text-right montant">
                    <?php echo e(number_format($coffre['solde_debut'], $coffre['devise'] === 'USD' ? 2 : 0)); ?>

                    <?php echo e($coffre['devise'] === 'USD' ? '$' : 'FC'); ?>

                </td>
                <td class="text-right montant">
                    <?php echo e(number_format($coffre['solde_fin'], $coffre['devise'] === 'USD' ? 2 : 0)); ?>

                    <?php echo e($coffre['devise'] === 'USD' ? '$' : 'FC'); ?>

                </td>
                <td class="text-right montant <?php echo e($coffre['evolution'] >= 0 ? 'evolution-positive' : 'evolution-negative'); ?>">
                    <?php echo e(number_format($coffre['evolution'], $coffre['devise'] === 'USD' ? 2 : 0)); ?>

                    <?php echo e($coffre['devise'] === 'USD' ? '$' : 'FC'); ?>

                </td>
                <td class="text-center <?php echo e($coffre['solde_debut'] != 0 ? ($coffre['evolution'] >= 0 ? 'evolution-positive' : 'evolution-negative') : ''); ?>">
                    <?php if($coffre['solde_debut'] != 0): ?>
                        <?php echo e(number_format(($coffre['evolution'] / $coffre['solde_debut']) * 100, 2)); ?>%
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
                <td class="text-right montant">
                    <?php echo e(number_format($coffre['entrees'], $coffre['devise'] === 'USD' ? 2 : 0)); ?>

                </td>
                <td class="text-right montant">
                    <?php echo e(number_format($coffre['sorties'], $coffre['devise'] === 'USD' ? 2 : 0)); ?>

                </td>
                <td class="text-center"><?php echo e($coffre['operations']); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    <!-- Graphiques simples (optionnel) -->
    <div style="margin-top: 20px; page-break-before: always;">
        <h3>VISUALISATION DE L'ÉVOLUTION</h3>
        <div style="display: flex; justify-content: space-around; margin-top: 15px;">
            <?php
                $maxUSD = max(abs($evolution['total_usd_debut']), abs($evolution['total_usd_fin']));
                $maxCDF = max(abs($evolution['total_cdf_debut']), abs($evolution['total_cdf_fin']));
            ?>
            <div>
                <div style="text-align: center; margin-bottom: 5px;">USD</div>
                <div style="width: 200px; height: 150px; border: 1px solid #000; position: relative;">
                    <?php if($maxUSD > 0): ?>
                        <div style="position: absolute; bottom: 0; left: 25px; width: 40px; 
                             height: <?php echo e(($evolution['total_usd_debut'] / $maxUSD) * 100); ?>%; 
                             background-color: #3498db;"></div>
                        <div style="position: absolute; bottom: 0; right: 25px; width: 40px; 
                             height: <?php echo e(($evolution['total_usd_fin'] / $maxUSD) * 100); ?>%; 
                             background-color: #2ecc71;"></div>
                    <?php endif; ?>
                    <div style="position: absolute; bottom: -20px; left: 40px; font-size: 9px;">Début</div>
                    <div style="position: absolute; bottom: -20px; right: 40px; font-size: 9px;">Fin</div>
                </div>
            </div>
            <div>
                <div style="text-align: center; margin-bottom: 5px;">CDF</div>
                <div style="width: 200px; height: 150px; border: 1px solid #000; position: relative;">
                    <?php if($maxCDF > 0): ?>
                        <div style="position: absolute; bottom: 0; left: 25px; width: 40px; 
                             height: <?php echo e(($evolution['total_cdf_debut'] / $maxCDF) * 100); ?>%; 
                             background-color: #e74c3c;"></div>
                        <div style="position: absolute; bottom: 0; right: 25px; width: 40px; 
                             height: <?php echo e(($evolution['total_cdf_fin'] / $maxCDF) * 100); ?>%; 
                             background-color: #f39c12;"></div>
                    <?php endif; ?>
                    <div style="position: absolute; bottom: -20px; left: 40px; font-size: 9px;">Début</div>
                    <div style="position: absolute; bottom: -20px; right: 40px; font-size: 9px;">Fin</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Observations -->
    <div style="margin-top: 30px; padding-top: 10px; border-top: 1px solid #000;">
        <h4>OBSERVATIONS :</h4>
        <div style="margin-top: 10px; font-size: 11px;">
            <?php if($evolution['evolution_usd'] > 0): ?>
                <p>✓ Progression positive des coffres USD : +<?php echo e(number_format($evolution['evolution_usd'], 2)); ?> $</p>
            <?php elseif($evolution['evolution_usd'] < 0): ?>
                <p>⚠ Régression des coffres USD : <?php echo e(number_format($evolution['evolution_usd'], 2)); ?> $</p>
            <?php else: ?>
                <p>↔ Stabilité des coffres USD</p>
            <?php endif; ?>
            
            <?php if($evolution['evolution_cdf'] > 0): ?>
                <p>✓ Progression positive des coffres CDF : +<?php echo e(number_format($evolution['evolution_cdf'], 0)); ?> FC</p>
            <?php elseif($evolution['evolution_cdf'] < 0): ?>
                <p>⚠ Régression des coffres CDF : <?php echo e(number_format($evolution['evolution_cdf'], 0)); ?> FC</p>
            <?php else: ?>
                <p>↔ Stabilité des coffres CDF</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Signatures -->
    <div style="margin-top: 50px; display: flex; justify-content: space-between;">
        <div style="text-align: center; width: 180px; border-top: 1px solid #000; padding-top: 4px;">
            Le Gérant
        </div>
        
    </div>
</body>
</html><?php /**PATH C:\laragon\www\tumainiletusystem2.0\resources\views/pdf/evolution-coffres.blade.php ENDPATH**/ ?>