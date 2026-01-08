<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport Charges Classe 6 - Tumaini Letu</title>
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 15px;
            color: #000;
            font-size: 12px;
            line-height: 1.3;
            background: white;
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            align-items: flex-start;
        }
        .header img {
            height: 70px;
            max-width: 140px;
            object-fit: contain;
        }
        .header-info {
            text-align: right;
            font-size: 11px;
            flex: 1;
            margin-left: 15px;
        }
        .institution-name {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 2px;
        }

        .separator {
            border-top: 2px solid #000;
            margin: 12px 0;
        }

        .ref-date {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-weight: bold;
            font-size: 11px;
        }

        .section {
            margin-bottom: 15px;
        }
        .section-title {
            font-weight: bold;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #000;
            font-size: 13px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 10px;
        }
        .table th {
            background-color: #f5f5f5;
            padding: 6px 4px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #000;
        }
        .table td {
            padding: 4px 3px;
            border: 1px solid #000;
            vertical-align: top;
        }
        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .total-row {
            background-color: #e8e8e8 !important;
            font-weight: bold;
            border-top: 2px solid #000;
        }

        .totals-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            margin-bottom: 12px;
        }
        .total-card {
            padding: 6px;
            border: 1px solid #000;
            border-radius: 3px;
            text-align: center;
            background-color: #f5f5f5;
        }
        .total-label {
            font-size: 9px;
            color: #000;
            margin-bottom: 2px;
        }
        .total-value {
            font-size: 11px;
            font-weight: bold;
            color: #000;
        }

        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .signature {
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 4px;
            width: 180px;
            font-size: 10px;
        }

        .footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #000;
            text-align: center;
            color: #000;
            font-size: 9px;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .montant { font-family: 'Courier New', monospace; }

        .devise-usd { color: #1e40af; }
        .devise-cdf { color: #dc2626; }
        
        .devise-header {
            background-color: #e0f2fe;
            padding: 8px;
            margin-bottom: 8px;
            border-left: 4px solid #1e40af;
            font-weight: bold;
        }
        .devise-header.cdf {
            background-color: #fef2f2;
            border-left-color: #dc2626;
        }
        
        .full-libelle {
            white-space: pre-wrap;
            word-wrap: break-word;
            line-height: 1.3;
            font-size: 10px;
        }
        
        .libelle-cell {
            min-width: 250px;
            white-space: normal !important;
        }
    </style>
</head>
<body>
    <!-- En-tête -->
    <div class="header">
        <div class="logo">
            <img src="<?php echo e($rapport['logo_base64']); ?>" alt="TUMAINI LETU asbl">
        </div>
        <div class="header-info">
            <div class="institution-name">Tumaini Letu asbl</div>
            <div>Siège social 005, avenue du port, quartier les volcans - Goma - Rd Congo</div>
            <div>NUM BED : 14453756111</div>
            <div>Tel : +243982618321</div>
            <div>Email : tumailetu@gmail.com</div>
        </div>
    </div>

    <div class="separator"></div>

    <!-- Informations du rapport -->
    <div class="ref-date">
        <div>N/REF : CLASSE6-<?php echo e(\Carbon\Carbon::now()->format('Ymd-His')); ?></div>
        <div>Période : <?php echo e($rapport['periode']['debut']); ?> au <?php echo e($rapport['periode']['fin']); ?></div>
        <div>Généré le : <?php echo e(\Carbon\Carbon::now()->format('d/m/Y H:i')); ?></div>
    </div>

    <div class="separator"></div>

    <!-- Titre du rapport -->
    <div class="section">
        <div style="text-align: center; margin-bottom: 15px;">
            <h2 style="font-size: 16px; font-weight: bold; color: #000;">RAPPORT DES CHARGES - CLASSE 6</h2>
            <p style="font-size: 12px; color: #000;">Détail des opérations de charges par période</p>
        </div>
    </div>

    <!-- Synthèse des totaux -->
    <div class="section">
        <div class="section-title">SYNTHÈSE DES CHARGES</div>
        <div class="totals-grid">
            <div class="total-card">
                <div class="total-label">CHARGES USD</div>
                <div class="total-value devise-usd montant"><?php echo e(number_format($rapport['totaux_generaux']['total_usd'], 2)); ?> $</div>
            </div>
            <div class="total-card">
                <div class="total-label">CHARGES CDF</div>
                <div class="total-value devise-cdf montant"><?php echo e(number_format($rapport['totaux_generaux']['total_cdf'], 2)); ?> FC</div>
            </div>
            <div class="total-card">
                <div class="total-label">OPÉRATIONS USD</div>
                <div class="total-value"><?php echo e($rapport['totaux_generaux']['operations_usd']); ?></div>
            </div>
            <div class="total-card">
                <div class="total-label">OPÉRATIONS CDF</div>
                <div class="total-value"><?php echo e($rapport['totaux_generaux']['operations_cdf']); ?></div>
            </div>
        </div>
    </div>

    <div class="separator"></div>

    <!-- Section USD -->
    <div class="section">
        <div class="devise-header">
            DOLLARS AMÉRICAINS (USD) - <?php echo e(number_format($rapport['totaux_generaux']['total_usd'], 2)); ?> $
        </div>
        
        <?php if(count($rapport['operations']['usd']) > 0): ?>
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 10%">Date</th>
                    <th style="width: 12%">Compte</th>
                    <th style="width: 18%">Type Opération</th>
                    <th style="width: 35%" class="libelle-cell">Libellé</th>
                    <th style="width: 12%" class="text-right">Montant</th>
                    <th style="width: 13%">Référence</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $rapport['operations']['usd']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $operation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($operation['date']); ?></td>
                    <td><?php echo e($operation['compte']); ?></td>
                    <td><?php echo e($operation['type_operation']); ?></td>
                    <td class="libelle-cell">
                        <div class="full-libelle">
                            <?php echo e($operation['libelle']); ?>

                        </div>
                    </td>
                    <td class="text-right devise-usd montant"><?php echo e(number_format($operation['montant'], 2)); ?></td>
                    <td style="font-size: 9px;"><?php echo e($operation['reference']); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                
                <!-- Total USD -->
                <tr class="total-row">
                    <td colspan="4"><strong>TOTAL USD</strong></td>
                    <td class="text-right devise-usd montant"><strong><?php echo e(number_format($rapport['totaux_generaux']['total_usd'], 2)); ?> $</strong></td>
                    <td class="text-center"><strong><?php echo e($rapport['totaux_generaux']['operations_usd']); ?> op.</strong></td>
                </tr>
            </tbody>
        </table>
        <?php else: ?>
        <p style="text-align: center; color: #666; font-style: italic; padding: 10px;">Aucune charge USD pendant cette période</p>
        <?php endif; ?>
    </div>

    <!-- Section CDF -->
    <div class="section">
        <div class="devise-header cdf">
            FRANCS CONGOLAIS (CDF) - <?php echo e(number_format($rapport['totaux_generaux']['total_cdf'], 2)); ?> FC
        </div>
        
        <?php if(count($rapport['operations']['cdf']) > 0): ?>
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 10%">Date</th>
                    <th style="width: 12%">Compte</th>
                    <th style="width: 18%">Type Opération</th>
                    <th style="width: 35%" class="libelle-cell">Libellé</th>
                    <th style="width: 12%" class="text-right">Montant</th>
                    <th style="width: 13%">Référence</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $rapport['operations']['cdf']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $operation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($operation['date']); ?></td>
                    <td><?php echo e($operation['compte']); ?></td>
                    <td><?php echo e($operation['type_operation']); ?></td>
                    <td class="libelle-cell">
                        <div class="full-libelle">
                            <?php echo e($operation['libelle']); ?>

                        </div>
                    </td>
                    <td class="text-right devise-cdf montant"><?php echo e(number_format($operation['montant'], 2)); ?></td>
                    <td style="font-size: 9px;"><?php echo e($operation['reference']); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                
                <!-- Total CDF -->
                <tr class="total-row">
                    <td colspan="4"><strong>TOTAL CDF</strong></td>
                    <td class="text-right devise-cdf montant"><strong><?php echo e(number_format($rapport['totaux_generaux']['total_cdf'], 2)); ?> FC</strong></td>
                    <td class="text-center"><strong><?php echo e($rapport['totaux_generaux']['operations_cdf']); ?> op.</strong></td>
                </tr>
            </tbody>
        </table>
        <?php else: ?>
        <p style="text-align: center; color: #666; font-style: italic; padding: 10px;">Aucune charge CDF pendant cette période</p>
        <?php endif; ?>
    </div>

    <!-- Total général -->
    <div class="section">
        <div class="section-title">TOTAL GÉNÉRAL</div>
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 40%">Description</th>
                    <th style="width: 30%" class="text-right">Montant USD</th>
                    <th style="width: 30%" class="text-right">Montant CDF</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total des charges</td>
                    <td class="text-right devise-usd montant"><?php echo e(number_format($rapport['totaux_generaux']['total_usd'], 2)); ?> $</td>
                    <td class="text-right devise-cdf montant"><?php echo e(number_format($rapport['totaux_generaux']['total_cdf'], 2)); ?> FC</td>
                </tr>
                <tr class="total-row">
                    <td><strong>GRAND TOTAL</strong></td>
                    <td colspan="2" class="text-center">
                        <strong><?php echo e(number_format($rapport['totaux_generaux']['total_usd'] + $rapport['totaux_generaux']['total_cdf'], 2)); ?> 
                        (<?php echo e($rapport['totaux_generaux']['total_operations']); ?> opérations)</strong>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Signatures -->
    <div class="signature-section">
        <div class="signature" style="text-align: left;">
            Le Comptable
        </div>
        <div class="signature" style="text-align: right;">
            Le Gérant
        </div>
    </div>

    <!-- Pied de page -->
    <div class="footer">
        <div>Rapport généré automatiquement par le Système de Gestion Comptable Tumaini Letu</div>
        <div>Document confidentiel - Charges Classe 6 - <?php echo e(\Carbon\Carbon::now()->format('d/m/Y H:i:s')); ?></div>
    </div>
</body>
</html><?php /**PATH C:\laragon\www\tumainiletusystem2.0\resources\views/pdf/rapport-classe6-charges-simple.blade.php ENDPATH**/ ?>