<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Échéancier du Crédit Groupe - <?php echo e($credit->compte->nom); ?></title>
    <style>
        @page { size: A4; margin: 1cm; }
        body {
            font-family: Arial, sans-serif;
            margin: 0; padding: 0;
            color: #000;
            font-size: 11px;
            line-height: 1.3;
        }
        .header { display: flex; justify-content: space-between; margin-bottom: 12px; align-items: flex-start; }
        .header img { height: 65px; max-width: 120px; object-fit: contain; }
        .header-info { text-align: right; font-size: 9px; flex: 1; margin-left: 12px; }
        .separator { border-top: 2px solid #000; margin: 12px 0; }
        .ref-date { display: flex; justify-content: space-between; margin-bottom: 12px; font-weight: bold; font-size: 10px; }
        .client-info {
            margin-bottom: 12px; padding: 8px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px; margin-bottom: 12px; }
        .info-item { display: flex; justify-content: space-between; padding: 2px 0; }
        .info-label { font-weight: bold; min-width: 110px; }
        .echeancier-table {
            width: 100%; border-collapse: collapse;
            margin: 12px 0; font-size: 9px;
        }
        .echeancier-table th, .echeancier-table td {
            border: 1px solid #000; padding: 5px 3px; text-align: center;
        }
        .echeancier-table th {
            background-color: #f0f0f0; font-weight: bold;
        }
        .total-section {
            margin-top: 15px; padding: 8px;
            background-color: #f8f9fa; border: 1px solid #000;
        }
        .total-grid {
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 8px; text-align: center;
        }
        .total-item { padding: 4px; }
        .total-value { font-weight: bold; font-size: 12px; margin-top: 3px; }
        .signature-section {
            margin-top: 25px; display: flex; justify-content: space-between;
        }
        .signature {
            text-align: center; border-top: 1px solid #000;
            padding-top: 4px; width: 180px; font-size: 10px;
        }
        .footer { margin-top: 15px; text-align: center; font-size: 8px; color: #666; }
        @media print { .no-print { display: none; } body { margin: 0; } }
        .btn {
            padding: 10px 20px; margin: 5px; border: none;
            border-radius: 5px; cursor: pointer;
            font-size: 14px; text-decoration: none;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
    </style>
</head>
<body>

    <div class="screen-header no-print" style="text-align:center; margin-bottom:20px; padding:20px; background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:white; border-radius:10px;">
        <h1 style="margin:0; font-size:28px;">Échéancier du Crédit Groupe</h1>
        <p style="margin:5px 0; font-size:16px; opacity:0.9;">
            Groupe: <?php echo e($credit->compte->nom); ?> - <?php echo e($credit->compte->numero_compte); ?>

        </p>
    </div>

    <div class="screen-actions no-print" style="text-align:center; margin:20px 0;">
        <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Imprimer</button>
        <a href="<?php echo e(route('credits.details-groupe', $credit->id)); ?>" class="btn btn-success"><i class="fas fa-arrow-left"></i> Retour</a>
    </div>

    <div>
        <div class="header">
            <div class="logo">
                <?php if(file_exists(public_path('images/logo-tumaini1.png'))): ?>
                    <img src="<?php echo e(asset('images/logo-tumaini1.png')); ?>" alt="TUMAINI LETU asbl">
                <?php else: ?>
                    <div style="height:65px;width:120px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;border:1px dashed #ccc;font-size:9px;">TUMAINI LETU</div>
                <?php endif; ?>
            </div>
            <div class="header-info">
                <div><strong>Tumaini Letu asbl</strong></div>
                <div>Siège social 005, avenue du port, quartier les volcans - Goma - Rd Congo</div>
                <div>NUM BED : 14453756111</div>
                <div>Tel : +243982618321</div>
                <div>Email : tumainiletu@gmail.com</div>
            </div>
        </div>

        <div class="separator"></div>

        <div class="ref-date">
            <div>RÉFÉRENCE : ÉCH-G<?php echo e($credit->id); ?>-<?php echo e(date('Ymd')); ?></div>
            <div>DATE : <?php echo e(now()->format('d/m/Y')); ?></div>
            <div>PÉRIODE : 16 SEMAINES</div>
        </div>

        <div class="separator"></div>

        <div class="client-info">
            <div style="text-align:center; font-weight:bold; margin-bottom:8px; font-size:13px;">ÉCHÉANCIER DE REMBOURSEMENT - CRÉDIT GROUPE</div>
            <div class="info-grid">
                <div class="info-item"><span class="info-label">Numéro Compte :</span><span><?php echo e($credit->compte->numero_compte); ?></span></div>
                <div class="info-item"><span class="info-label">Nom du Groupe :</span><span><?php echo e($credit->compte->nom); ?></span></div>
                <div class="info-item"><span class="info-label">Type Crédit :</span><span>Groupe Solidaire</span></div>
                <div class="info-item"><span class="info-label">Date Octroi :</span><span><?php echo e($credit->date_octroi->format('d/m/Y')); ?></span></div>
                <div class="info-item"><span class="info-label">Montant Accordé :</span><span><?php echo e(number_format($credit->montant_accorde, 2, ',', ' ')); ?> USD</span></div>
                <div class="info-item"><span class="info-label">Montant Total :</span><span><?php echo e(number_format($credit->montant_total, 2, ',', ' ')); ?> USD</span></div>
                <div class="info-item"><span class="info-label">Remb. Hebdo Total :</span><span><?php echo e(number_format($credit->remboursement_hebdo_total, 2, ',', ' ')); ?> USD</span></div>
                <div class="info-item"><span class="info-label">Date Fin :</span><span><?php echo e($credit->date_echeance->format('d/m/Y')); ?></span></div>
            </div>
        </div>

        <?php
            $dateDebut = $credit->date_octroi->copy()->addWeeks(2);
            $capitalHebdo = $credit->montant_accorde / 16;
            $interetHebdo = ($credit->montant_total - $credit->montant_accorde) / 16;
            $capitalRestant = $credit->montant_total;
            $capitalPrincipalRestant = $credit->montant_accorde;
            $echeances = [];

            for ($semaine = 1; $semaine <= 16; $semaine++) {
                $dateEcheance = $dateDebut->copy()->addWeeks($semaine - 1);

                $capitalPrincipalRestant -= $capitalHebdo;
                if ($capitalPrincipalRestant < 0) $capitalPrincipalRestant = 0;

                $capitalRestant -= ($capitalHebdo + $interetHebdo);
                if ($capitalRestant < 0) $capitalRestant = 0;

                $echeances[] = [
                    'semaine' => $semaine,
                    'date' => $dateEcheance,
                    'capital_hebdo' => $capitalHebdo,
                    'interet_hebdo' => $interetHebdo,
                    'montant_total' => $capitalHebdo + $interetHebdo,
                    'capital_restant' => $capitalRestant,
                ];
            }

            // Ajuster la dernière échéance pour équilibre
            $echeances[15]['capital_hebdo'] = $credit->montant_accorde - ($capitalHebdo * 15);
            $echeances[15]['montant_total'] = $echeances[15]['capital_hebdo'] + $interetHebdo;
            $echeances[15]['capital_restant'] = 0;
        ?>

        <table class="echeancier-table">
            <thead>
                <tr>
                    <th>Semaine</th>
                    <th>Date Échéance</th>
                    <th>Capital Hebdo</th>
                    <th>Intérêt Hebdo</th>
                    <th>Montant à Payer</th>
                    <th>Capital Restant</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $echeances; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $echeance): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($echeance['semaine']); ?></td>
                    <td><?php echo e($echeance['date']->format('d/m/Y')); ?></td>
                    <td><?php echo e(number_format($echeance['capital_hebdo'], 2, ',', ' ')); ?> USD</td>
                    <td><?php echo e(number_format($echeance['interet_hebdo'], 2, ',', ' ')); ?> USD</td>
                    <td><?php echo e(number_format($echeance['montant_total'], 2, ',', ' ')); ?> USD</td>
                    <td><?php echo e(number_format($echeance['capital_restant'], 2, ',', ' ')); ?> USD</td>
                    <td>À venir</td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-grid">
                <div class="total-item"><div>Montant Accordé</div><div class="total-value"><?php echo e(number_format($credit->montant_accorde, 2, ',', ' ')); ?> USD</div></div>
                <div class="total-item"><div>Total Intérêts</div><div class="total-value"><?php echo e(number_format($credit->montant_total - $credit->montant_accorde, 2, ',', ' ')); ?> USD</div></div>
                <div class="total-item"><div>Montant Total</div><div class="total-value"><?php echo e(number_format($credit->montant_total, 2, ',', ' ')); ?> USD</div></div>
                <div class="total-item"><div>Durée Totale</div><div class="total-value">16 Semaines</div></div>
            </div>
        </div>

        <div style="margin-top:12px;padding:8px;background-color:#fff3cd;border:1px solid #ffeaa7;border-radius:4px;font-size:9px;">
            <strong>Notes importantes :</strong><br>
            • Le remboursement commence 2 semaines après la date d'octroi du crédit<br>
            • Paiement hebdomadaire obligatoire chaque <?php echo e($dateDebut->locale('fr')->translatedFormat('l')); ?><br>
            • En cas de retard, des pénalités de 5% seront appliquées<br>
            • La caution totale de <?php echo e(number_format($credit->caution_totale, 2, ',', ' ')); ?> USD sera débloquée après remboursement complet<br>
            • Solidarité groupe : tout retard affecte l'ensemble des membres
        </div>

        <div class="signature-section">
            <div class="signature">Signature du Responsable Groupe<br><div style="margin-top:35px;"><?php echo e($credit->compte->nom); ?></div></div>
            <div class="signature">Signature du Responsable<br><div style="margin-top:35px;">Tumaini Letu asbl</div></div>
        </div>

        <div class="footer">
            <div class="separator"></div>
            Document généré le <?php echo e(now()->format('d/m/Y à H:i')); ?> | Tumaini Letu asbl
        </div>
    </div>

    <div class="no-print" style="margin-top:25px;text-align:center;padding:20px;">
        <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Imprimer</button>
        <button onclick="window.close()" class="btn btn-primary" style="background:#6c757d;"><i class="fas fa-times"></i> Fermer</button>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
<?php /**PATH D:\APP\TUMAINI LETU\tumainiletusystem2.0\resources\views/credits/echeanciers-groupe.blade.php ENDPATH**/ ?>