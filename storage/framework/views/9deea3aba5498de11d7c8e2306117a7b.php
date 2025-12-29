<div class="p-6 space-y-4">
    <h3 class="text-lg font-semibold text-gray-900">Rapport de Performance</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="p-4 bg-blue-50 rounded-lg">
            <h4 class="font-medium text-blue-800">Total Crédits Actifs</h4>
            <p class="text-2xl font-bold text-blue-600"><?php echo e($this->rapportPerformanceData['totalCredits'] ?? 0); ?></p>
        </div>
        
        <div class="p-4 bg-green-50 rounded-lg">
            <h4 class="font-medium text-green-800">Montant Total Crédits</h4>
            <p class="text-2xl font-bold text-green-600">
                <?php echo e(\App\Helpers\CurrencyHelper::format($this->rapportPerformanceData['totalMontant'] ?? 0)); ?>

            </p>
        </div>
        
        <div class="p-4 bg-yellow-50 rounded-lg">
            <h4 class="font-medium text-yellow-800">Montant Collecté</h4>
            <p class="text-2xl font-bold text-yellow-600">
                <?php echo e(\App\Helpers\CurrencyHelper::format($this->rapportPerformanceData['totalPaiements'] ?? 0)); ?>

            </p>
        </div>
        
        <div class="p-4 bg-<?php echo e(($this->rapportPerformanceData['tauxRemboursement'] ?? 0) > 90 ? 'green' : 'red'); ?>-50 rounded-lg">
            <h4 class="font-medium text-<?php echo e(($this->rapportPerformanceData['tauxRemboursement'] ?? 0) > 90 ? 'green' : 'red'); ?>-800">Taux de Remboursement</h4>
            <p class="text-2xl font-bold text-<?php echo e(($this->rapportPerformanceData['tauxRemboursement'] ?? 0) > 90 ? 'green' : 'red'); ?>-600">
                <?php echo e($this->rapportPerformanceData['tauxRemboursement'] ?? 0); ?>%
            </p>
        </div>
    </div>
</div><?php /**PATH C:\laragon\www\tumainiletusystem2.0\resources\views/filament/pages/rapport-performance.blade.php ENDPATH**/ ?>