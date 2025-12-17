<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Échéancier de Remboursement - <?php echo e($credit->compte->numero_compte); ?></title>
    <style>
        @page {
            size: A4;
            margin: 0.5cm;
        }
        
        body {
            font-family: 'Arial Narrow', Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #000;
            font-size: 12px;
            line-height: 1.1;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 5px;
        }
        
        .header img {
            height: 55px;
            max-width: 100px;
            object-fit: contain;
        }
        
        .header-info {
            text-align: right;
            font-size: 12px;
            flex: 1;
            margin-left: 8px;
        }
        
        .separator {
            border-top: 1px solid #000;
            margin: 5px 0;
        }
        
        .ref-date {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-weight: bold;
            font-size: 12px;
        }
        
        .client-info {
            margin-bottom: 8px;
            padding: 6px;
            background-color: #f8f9fa;
            border-radius: 3px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 4px;
            margin-bottom: 8px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 1px 0;
        }
        
        .info-label {
            font-weight: bold;
            min-width: 85px;
        }
        
        .echeancier-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 14px;
        }
        
        .echeancier-table th,
        .echeancier-table td {
            border: 1px solid #000;
            padding: px 2px;
            text-align: center;
        }
        
        .echeancier-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .echeancier-table .semaine {
            width: 5%;
        }
        
        .echeancier-table .date {
            width: 10%;
        }
        
        .echeancier-table .capital-hebdo {
            width: 10%;
        }
        
        .echeancier-table .interet-hebdo {
            width: 10%;
        }
        
        .echeancier-table .montant {
            width: 10%;
        }
        
        .echeancier-table .capital {
            width: 10%;
        }
        
        .total-section {
            margin-top: 90px;
            padding: 6px;
            background-color: #f8f9fa;
            border: 1px solid #000;
        }
        
        .total-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            text-align: center;
        }
        
        .total-item {
            padding: 3px;
        }
        
        .total-value {
            font-weight: bold;
            font-size: 10px;
            margin-top: 2px;
        }
        
        .signature-section {
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature {
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 3px;
            width: 150px;
            font-size: 9px;
        }
        
        .footer {
            margin-top: 10px;
            text-align: center;
            font-size: 7px;
            color: #666;
        }
        
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
        
        .notes {
            margin-top: 8px;
            padding: 5px;
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 3px;
            font-size: 8px;
        }
        
        .title {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            margin: 5px 0 8px 0;
        }
        
        .table-container {
            max-height: 400px;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <?php
        $creditActif = $compte->credits->where('statut_demande', 'approuve')->first();
        $dateDebut = $creditActif->date_octroi->copy()->addWeeks(2);
        $montantHebdo = $creditActif->remboursement_hebdo;
        
        // Pourcentages pour les intérêts hebdomadaires
        $pourcentageInterets = [
            14.4154589019, 12.5668588387, 11.5077233696, 10.4164781435,
            9.2926366489, 9.1352258629, 8.9432727627, 6.7153178136,
            4.4503879929, 3.1475102776, 2.8057116447, 1.8057116447,
            1.8057116447, 1.4057116447, 1.3057116447, 0.2805711645
        ];
        
        // Pourcentages pour le capital hebdomadaire
        $pourcentageCapital = [
            4.66369746885061, 5.02282473831897, 5.22858283417971, 5.44057888793057,
            5.65890741408505, 5.68948758492370, 5.72677828621991, 6.15960277500698,
            6.59961036681905, 6.85272023393667, 6.91912140538681, 7.11339126213323,
            7.11339126213323, 7.19109920483180, 7.21052619050644, 7.40968008473729
        ];
        
        $capitalRestant = $creditActif->montant_total;
        $capitalPrincipalRestant = $creditActif->montant_accorde;
        $echeances = [];
        $totalCapitalPaye = 0;
        $totalInteretsPayes = 0;
        
        for ($semaine = 1; $semaine <= 16; $semaine++) {
            $dateEcheance = $dateDebut->copy()->addWeeks($semaine - 1);
            
            // Calcul des montants basés sur les pourcentages
            $capitalHebdomadaire = ($creditActif->montant_accorde * $pourcentageCapital[$semaine - 1]) / 100;
            $interetHebdomadaire = ($creditActif->montant_accorde * $pourcentageInterets[$semaine - 1]) / 100;
            
            // Pour la dernière échéance, ajuster pour équilibrer
            if ($semaine == 16) {
                $capitalHebdomadaire = $capitalPrincipalRestant;
                $interetHebdomadaire = $montantHebdo - $capitalHebdomadaire;
            }
            
            // Limiter le capital au capital principal restant
            if ($capitalHebdomadaire > $capitalPrincipalRestant) {
                $capitalHebdomadaire = $capitalPrincipalRestant;
                $interetHebdomadaire = $montantHebdo - $capitalHebdomadaire;
            }
            
            // Mettre à jour les totaux
            $totalCapitalPaye += $capitalHebdomadaire;
            $totalInteretsPayes += $interetHebdomadaire;
            
            // Mettre à jour les soldes
            $capitalPrincipalRestant -= $capitalHebdomadaire;
            $capitalRestant -= $montantHebdo;
            
            if ($capitalPrincipalRestant < 0) $capitalPrincipalRestant = 0;
            if ($capitalRestant < 0) $capitalRestant = 0;
            
            $echeances[] = [
                'semaine' => $semaine,
                'date' => $dateEcheance,
                'capital_hebdo' => $capitalHebdomadaire,
                'interet_hebdo' => $interetHebdomadaire,
                'montant_total' => $montantHebdo,
                'capital_restant' => $capitalRestant,
            ];
        }
        
        // Ajustement final
        $ajustementCapital = $creditActif->montant_accorde - $totalCapitalPaye;
        if (abs($ajustementCapital) > 0.01) {
            $echeances[15]['capital_hebdo'] += $ajustementCapital;
            $echeances[15]['interet_hebdo'] = $montantHebdo - $echeances[15]['capital_hebdo'];
            $echeances[15]['capital_restant'] = 0;
        }
    ?>

    <div class="header">
        <div class="logo">
            <?php if(file_exists(public_path('images/logo-tumaini1.png'))): ?>
                <img src="<?php echo e(asset('images/logo-tumaini1.png')); ?>" alt="TUMAINI LETU asbl">
            <?php else: ?>
                <div style="height: 55px; width: 100px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border: 1px dashed #ccc; font-size: 8px;">
                    TUMAINI LETU
                </div>
            <?php endif; ?>
        </div>
        <div class="header-info">
            <div><strong>Tumaini Letu asbl</strong></div>
            <div>Siège social 005, avenue du port, quartier les volcans - Goma</div>
            <div>NUM BED : 14453756111</div>
            <div>Tel : +243982618321</div>
            <div>Email : tumainiletu@gmail.com</div>
        </div>
    </div>

    <div class="separator"></div>

    <div class="ref-date">
        <div>RÉF : ÉCH-<?php echo e($creditActif->id); ?>-<?php echo e(date('Ymd')); ?></div>
        <div>DATE : <?php echo e(now()->format('d/m/Y')); ?></div>
        <div>PÉRIODE : 16 SEMAINES</div>
    </div>

    <div class="separator"></div>

    <div class="title">ÉCHÉANCIER DE REMBOURSEMENT</div>

    <div class="client-info">
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Compte :</span>
                <span><?php echo e($compte->numero_compte); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Membre :</span>
                <span><?php echo e($compte->nom); ?> <?php echo e($compte->postnom); ?> <?php echo e($compte->prenom); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Type :</span>
                <span><?php echo e(ucfirst($creditActif->type_credit)); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Date Octroi :</span>
                <span><?php echo e($creditActif->date_octroi->format('d/m/Y')); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Montant Accordé :</span>
                <span><?php echo e(number_format($creditActif->montant_accorde, 2, ',', ' ')); ?> $</span>
            </div>
            <div class="info-item">
                <span class="info-label">Montant Total :</span>
                <span><?php echo e(number_format($creditActif->montant_total, 2, ',', ' ')); ?> $</span>
            </div>
            <div class="info-item">
                <span class="info-label">Début Remb. :</span>
                <span><?php echo e($dateDebut->format('d/m/Y')); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Fin :</span>
                <span><?php echo e($creditActif->date_echeance->format('d/m/Y')); ?></span>
            </div>
        </div>
    </div>

    <div class="table-container">
        <table class="echeancier-table">
            <thead>
                <tr>
                    <th class="semaine">Sem</th>
                    <th class="date">Date</th>
                    <th class="capital-hebdo">Capital</th>
                    <th class="interet-hebdo">Intérêt</th>
                    <th class="montant">À Payer</th>
                    <th class="capital">Reste</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $echeances; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $echeance): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td class="semaine"><?php echo e($echeance['semaine']); ?></td>
                    <td class="date"><?php echo e($echeance['date']->format('d/m/Y')); ?></td>
                    <td class="capital-hebdo"><?php echo e(number_format($echeance['capital_hebdo'], 2, ',', ' ')); ?> </td>
                    <td class="interet-hebdo"><?php echo e(number_format($echeance['interet_hebdo'], 2, ',', ' ')); ?> </td>
                    <td class="montant"><?php echo e(number_format($echeance['montant_total'], 2, ',', ' ')); ?> </td>
                    <td class="capital"><?php echo e(number_format($echeance['capital_restant'], 2, ',', ' ')); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>

    <div class="total-section">
        <div class="total-grid">
            <div class="total-item">
                <div>Montant Accordé</div>
                <div class="total-value"><?php echo e(number_format($creditActif->montant_accorde, 2, ',', ' ')); ?> $</div>
            </div>
            <div class="total-item">
                <div>Total Intérêts</div>
                <div class="total-value"><?php echo e(number_format($creditActif->montant_total - $creditActif->montant_accorde, 2, ',', ' ')); ?> $</div>
            </div>
            <div class="total-item">
                <div>Montant Total</div>
                <div class="total-value"><?php echo e(number_format($creditActif->montant_total, 2, ',', ' ')); ?> $</div>
            </div>
            <div class="total-item">
                <div>Remb. Hebdo</div>
                <div class="total-value"><?php echo e(number_format($montantHebdo, 2, ',', ' ')); ?> $</div>
            </div>
        </div>
    </div>

    <div class="notes">
        <strong>Notes :</strong><br>
        • Remboursement hebdomadaire fixe : <?php echo e(number_format($montantHebdo, 2, ',', ' ')); ?> USD<br>
        • Jour de paiement : chaque <?php echo e($dateDebut->locale('fr')->translatedFormat('l')); ?><br>
        • Début : 2 semaines après l'octroi<br>
        • Pénalité retard : 5%<br>
        • Caution débloquée après remboursement complet
    </div>

    <div class="signature-section">
        <div class="signature">
            Membre<br>
            <div style="margin-top: 25px;"><?php echo e($compte->nom); ?> <?php echo e($compte->postnom); ?> <?php echo e($compte->prenom); ?></div>
        </div>
        <div class="signature">
            Responsable<br>
            <div style="margin-top: 25px;">Tumaini Letu asbl</div>
        </div>
    </div>

    <div class="footer">
        <div class="separator"></div>
        Document généré le <?php echo e(now()->format('d/m/Y H:i')); ?> | Tumaini Letu asbl
    </div>

    <div class="no-print" style="text-align: center; margin-top: 15px;">
        <button onclick="window.print()" style="padding: 6px 12px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; margin: 3px; font-size: 10px;">
            📄 Imprimer
        </button>
        <button onclick="window.close()" style="padding: 6px 12px; background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer; margin: 3px; font-size: 10px;">
            ❌ Fermer
        </button>
    </div>

    <script>
        function downloadPDF() {
            window.print();
        }
    </script>
</body>
</html><?php /**PATH C:\laragon\www\tumainiletusystem2.0\resources\views/credits/echeancier.blade.php ENDPATH**/ ?>