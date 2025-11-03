<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Échéancier de Remboursement - <?php echo e($credit->compte->numero_compte); ?></title>
    <style>
        /* Styles pour l'impression A4 */
        @page {
            size: A4;
            margin: 1cm;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #000;
            font-size: 11px;
            line-height: 1.3;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            align-items: flex-start;
        }
        
        .header img {
            height: 65px;
            max-width: 120px;
            object-fit: contain;
        }
        
        .header-info {
            text-align: right;
            font-size: 9px;
            flex: 1;
            margin-left: 12px;
        }
        
        .separator {
            border-top: 2px solid #000;
            margin: 12px 0;
        }
        
        .ref-date {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-weight: bold;
            font-size: 10px;
        }
        
        .client-info {
            margin-bottom: 12px;
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6px;
            margin-bottom: 12px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
        }
        
        .info-label {
            font-weight: bold;
            min-width: 110px;
        }
        
        .echeancier-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 9px;
        }
        
        .echeancier-table th,
        .echeancier-table td {
            border: 1px solid #000;
            padding: 5px 3px;
            text-align: center;
        }
        
        .echeancier-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .echeancier-table .semaine {
            width: 6%;
        }
        
        .echeancier-table .date {
            width: 12%;
        }
        
        .echeancier-table .capital-hebdo {
            width: 12%;
        }
        
        .echeancier-table .interet-hebdo {
            width: 12%;
        }
        
        .echeancier-table .montant {
            width: 12%;
        }
        
        .echeancier-table .capital {
            width: 12%;
        }
        
        .total-section {
            margin-top: 15px;
            padding: 8px;
            background-color: #f8f9fa;
            border: 1px solid #000;
        }
        
        .total-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            text-align: center;
        }
        
        .total-item {
            padding: 4px;
        }
        
        .total-value {
            font-weight: bold;
            font-size: 12px;
            margin-top: 3px;
        }
        
        .signature-section {
            margin-top: 25px;
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
            margin-top: 15px;
            text-align: center;
            font-size: 8px;
            color: #666;
        }
        
        /* Styles pour l'impression */
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
        
        /* Couleurs pour les montants */
        .montant-positif { color: #dc3545; }
        .montant-negatif { color: #28a745; }
    </style>
</head>
<body>
    <?php
        $creditActif = $compte->credits->where('statut_demande', 'approuve')->first();
        $dateDebut = $creditActif->date_octroi->copy()->addWeeks(2); // Début dans 2 semaines
        $montantHebdo = $creditActif->remboursement_hebdo;
        $capitalHebdomadaire = $creditActif->montant_accorde / 16;
        $interetHebdomadaire = $montantHebdo - $capitalHebdomadaire;
        $capitalRestant = $creditActif->montant_total;
        $capitalPrincipalRestant = $creditActif->montant_accorde;
        $echeances = [];
        
        for ($semaine = 1; $semaine <= 16; $semaine++) {
            $dateEcheance = $dateDebut->copy()->addWeeks($semaine - 1);
            
            // Calcul du capital restant (principal)
            $capitalPrincipalRestant -= $capitalHebdomadaire;
            if ($capitalPrincipalRestant < 0) $capitalPrincipalRestant = 0;
            
            // Calcul du capital total restant (principal + intérêts)
            $capitalRestant -= $montantHebdo;
            if ($capitalRestant < 0) $capitalRestant = 0;
            
            $echeances[] = [
                'semaine' => $semaine,
                'date' => $dateEcheance,
                'capital_hebdo' => $capitalHebdomadaire,
                'interet_hebdo' => $interetHebdomadaire,
                'montant_total' => $montantHebdo,
                'capital_restant' => $capitalRestant,
                'capital_principal_restant' => $capitalPrincipalRestant
            ];
        }
        
        // Pour la dernière échéance, ajuster les montants pour équilibrer
        $echeances[15]['capital_hebdo'] = $creditActif->montant_accorde - ($capitalHebdomadaire * 15);
        $echeances[15]['montant_total'] = $echeances[15]['capital_hebdo'] + $interetHebdomadaire;
        $echeances[15]['capital_restant'] = 0;
        $echeances[15]['capital_principal_restant'] = 0;
    ?>

    <div class="header">
        <div class="logo">
            <?php if(file_exists(public_path('images/logo-tumaini1.png'))): ?>
                <img src="<?php echo e(asset('images/logo-tumaini1.png')); ?>" alt="TUMAINI LETU asbl">
            <?php elseif(file_exists(public_path('images/logo-tumaini1.jpg'))): ?>
                <img src="<?php echo e(asset('images/logo-tumaini1.jpg')); ?>" alt="TUMAINI LETU asbl">
            <?php elseif(file_exists(public_path('images/logo-tumaini1.jpeg'))): ?>
                <img src="<?php echo e(asset('images/logo-tumaini1.jpeg')); ?>" alt="TUMAINI LETU asbl">
            <?php elseif(file_exists(public_path('images/logo-tumaini1.svg'))): ?>
                <img src="<?php echo e(asset('images/logo-tumaini1.svg')); ?>" alt="TUMAINI LETU asbl">
            <?php else: ?>
                <div style="height: 65px; width: 120px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border: 1px dashed #ccc; font-size: 9px;">
                    Logo TUMAINI LETU
                </div>
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
        <div>RÉFÉRENCE : ÉCH-<?php echo e($creditActif->id); ?>-<?php echo e(date('Ymd')); ?></div>
        <div>DATE : <?php echo e(now()->format('d/m/Y')); ?></div>
        <div>PÉRIODE : 16 SEMAINES</div>
    </div>

    <div class="separator"></div>

    <!-- Informations du client et du crédit -->
    <div class="client-info">
        <div style="text-align: center; font-weight: bold; margin-bottom: 8px; font-size: 13px;">
            ÉCHÉANCIER DE REMBOURSEMENT
        </div>
        
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Numéro Compte :</span>
                <span><?php echo e($compte->numero_compte); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Membre :</span>
                <span><?php echo e($compte->nom); ?> <?php echo e($compte->prenom); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Type Crédit :</span>
                <span><?php echo e(ucfirst($creditActif->type_credit)); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Date Octroi :</span>
                <span><?php echo e($creditActif->date_octroi->format('d/m/Y')); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Montant Accordé :</span>
                <span><?php echo e(number_format($creditActif->montant_accorde, 2, ',', ' ')); ?> USD</span>
            </div>
            <div class="info-item">
                <span class="info-label">Montant Total :</span>
                <span><?php echo e(number_format($creditActif->montant_total, 2, ',', ' ')); ?> USD</span>
            </div>
            <div class="info-item">
                <span class="info-label">Date Début Remb. :</span>
                <span><?php echo e($dateDebut->format('d/m/Y')); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Date Fin :</span>
                <span><?php echo e($creditActif->date_echeance->format('d/m/Y')); ?></span>
            </div>
        </div>
    </div>

    <!-- Tableau des échéances -->
    <table class="echeancier-table">
        <thead>
            <tr>
                <th class="semaine">Semaine</th>
                <th class="date">Date Échéance</th>
                <th class="capital-hebdo">Capital Hebdo</th>
                <th class="interet-hebdo">Intérêt Hebdo</th>
                <th class="montant">Montant à Payer</th>
                <th class="capital">Capital Restant</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $echeances; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $echeance): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td class="semaine"><?php echo e($echeance['semaine']); ?></td>
                <td class="date"><?php echo e($echeance['date']->format('d/m/Y')); ?></td>
                <td class="capital-hebdo"><?php echo e(number_format($echeance['capital_hebdo'], 2, ',', ' ')); ?> USD</td>
                <td class="interet-hebdo"><?php echo e(number_format($echeance['interet_hebdo'], 2, ',', ' ')); ?> USD</td>
                <td class="montant"><?php echo e(number_format($echeance['montant_total'], 2, ',', ' ')); ?> USD</td>
                <td class="capital"><?php echo e(number_format($echeance['capital_restant'], 2, ',', ' ')); ?> USD</td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    <!-- Section des totaux -->
    <div class="total-section">
        <div class="total-grid">
            <div class="total-item">
                <div>Montant Accordé</div>
                <div class="total-value"><?php echo e(number_format($creditActif->montant_accorde, 2, ',', ' ')); ?> USD</div>
            </div>
            <div class="total-item">
                <div>Total Intérêts</div>
                <div class="total-value"><?php echo e(number_format($creditActif->montant_total - $creditActif->montant_accorde, 2, ',', ' ')); ?> USD</div>
            </div>
            <div class="total-item">
                <div>Montant Total</div>
                <div class="total-value"><?php echo e(number_format($creditActif->montant_total, 2, ',', ' ')); ?> USD</div>
            </div>
            <div class="total-item">
                <div>Durée Totale</div>
                <div class="total-value">16 Semaines</div>
            </div>
        </div>
    </div>

    <!-- Notes importantes -->
    <div style="margin-top: 12px; padding: 8px; background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; font-size: 9px;">
        <strong>Notes importantes :</strong><br>
        • Le remboursement commence 2 semaines après la date d'octroi du crédit<br>
        • Paiement hebdomadaire obligatoire chaque <?php echo e($dateDebut->format('l')); ?><br>
        • En cas de retard, des pénalités de 5% seront appliquées<br>
        • La caution sera débloquée après remboursement complet
    </div>

    <!-- Signatures -->
    <div class="signature-section">
        <div class="signature">
            Signature du Membre<br>
            <div style="margin-top: 35px;"><?php echo e($compte->nom); ?> <?php echo e($compte->prenom); ?></div>
        </div>
        <div class="signature">
            Signature du Responsable<br>
            <div style="margin-top: 35px;">Tumaini Letu asbl</div>
        </div>
    </div>

    <!-- Pied de page -->
    <div class="footer">
        <div class="separator"></div>
        Document généré le <?php echo e(now()->format('d/m/Y à H:i')); ?> | Tumaini Letu asbl - Échéancier de remboursement
    </div>

    <!-- Boutons d'action (non imprimés) -->
    <div class="no-print" style="margin-top: 25px; text-align: center;">
        <button onclick="window.print()" style="padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 4px; font-size: 11px;">
            📄 Imprimer l'Échéancier
        </button>
        <button onclick="window.close()" style="padding: 8px 16px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 4px; font-size: 11px;">
            ❌ Fermer
        </button>
        <button onclick="downloadPDF()" style="padding: 8px 16px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 4px; font-size: 11px;">
            💾 Télécharger PDF
        </button>
    </div>

    <script>
        function downloadPDF() {
            window.print();
        }

        // Option d'impression automatique
        // window.onload = function() {
        //     setTimeout(() => {
        //         window.print();
        //     }, 1000);
        // }
    </script>
</body>
</html><?php /**PATH C:\STORAGE\TUMAINI LETU\System\tumainiletusystem\tumainiletusystem2.0\resources\views/credits/echeancier.blade.php ENDPATH**/ ?>