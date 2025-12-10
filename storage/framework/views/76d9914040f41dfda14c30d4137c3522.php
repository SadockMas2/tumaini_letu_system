<div class="p-6 space-y-6">
    <div class="text-center mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Système de Paiement des Remboursements</h3>
        <p class="text-sm text-gray-600">Choisissez le type de crédit que vous souhaitez traiter</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Crédits Individuels -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 text-center hover:shadow-lg transition-shadow">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <h4 class="text-lg font-semibold text-gray-900 mb-2">Crédits Individuels</h4>
            <p class="text-sm text-gray-600 mb-4">Paiement automatique de tous les crédits individuels actifs</p>
            <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['wire:click' => '$dispatch(\'open-modal\', { id: \'paiement-individuels\' })','color' => 'primary','icon' => 'heroicon-m-user','class' => 'w-full']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => '$dispatch(\'open-modal\', { id: \'paiement-individuels\' })','color' => 'primary','icon' => 'heroicon-m-user','class' => 'w-full']); ?>
                Traiter les Crédits Individuels
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6330f08526bbb3ce2a0da37da512a11f)): ?>
<?php $attributes = $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f; ?>
<?php unset($__attributesOriginal6330f08526bbb3ce2a0da37da512a11f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6330f08526bbb3ce2a0da37da512a11f)): ?>
<?php $component = $__componentOriginal6330f08526bbb3ce2a0da37da512a11f; ?>
<?php unset($__componentOriginal6330f08526bbb3ce2a0da37da512a11f); ?>
<?php endif; ?>
        </div>

        <!-- Crédits Groupe -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 text-center hover:shadow-lg transition-shadow">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <h4 class="text-lg font-semibold text-gray-900 mb-2">Crédits Groupe</h4>
            <p class="text-sm text-gray-600 mb-4">Gestion des remboursements par groupe avec répartition détaillée</p>
            <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['wire:click' => '$dispatch(\'open-modal\', { id: \'paiement-groupes\' })','color' => 'success','icon' => 'heroicon-m-users','class' => 'w-full']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => '$dispatch(\'open-modal\', { id: \'paiement-groupes\' })','color' => 'success','icon' => 'heroicon-m-users','class' => 'w-full']); ?>
                Gérer les Crédits Groupe
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6330f08526bbb3ce2a0da37da512a11f)): ?>
<?php $attributes = $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f; ?>
<?php unset($__attributesOriginal6330f08526bbb3ce2a0da37da512a11f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6330f08526bbb3ce2a0da37da512a11f)): ?>
<?php $component = $__componentOriginal6330f08526bbb3ce2a0da37da512a11f; ?>
<?php unset($__componentOriginal6330f08526bbb3ce2a0da37da512a11f); ?>
<?php endif; ?>
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="bg-gray-50 rounded-lg p-4 mt-6">
        <h4 class="font-semibold text-gray-900 mb-3">Aperçu des Crédits Actifs</h4>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div class="text-center">
                <div class="text-blue-600 font-semibold"><?php echo e($this->getCombinedCredits()->where('type_credit', 'individuel')->count()); ?></div>
                <div class="text-gray-600">Crédits Individuels</div>
            </div>
            <div class="text-center">
                <div class="text-green-600 font-semibold"><?php echo e($this->getCombinedCredits()->where('type_credit', 'groupe')->count()); ?></div>
                <div class="text-gray-600">Crédits Groupe</div>
            </div>
        </div>
    </div>
</div><?php /**PATH D:\APP\TUMAINI LETU\tumainiletusystem2.0\resources\views/filament/pages/choix-paiement-remboursements.blade.php ENDPATH**/ ?>