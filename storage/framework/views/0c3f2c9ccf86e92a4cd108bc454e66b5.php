<?php if (isset($component)) { $__componentOriginal166a02a7c5ef5a9331faf66fa665c256 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-panels::components.page.index','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-panels::page'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <?php if (isset($component)) { $__componentOriginal4af1e0a8ab5c0dda93279f6800da3911 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4af1e0a8ab5c0dda93279f6800da3911 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-panels::components.header.index','data' => ['actions' => $this->getHeaderActions()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-panels::header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['actions' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($this->getHeaderActions())]); ?>
         <?php $__env->slot('heading', null, []); ?> 
            <?php echo e($this->getTitle()); ?>

         <?php $__env->endSlot(); ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4af1e0a8ab5c0dda93279f6800da3911)): ?>
<?php $attributes = $__attributesOriginal4af1e0a8ab5c0dda93279f6800da3911; ?>
<?php unset($__attributesOriginal4af1e0a8ab5c0dda93279f6800da3911); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4af1e0a8ab5c0dda93279f6800da3911)): ?>
<?php $component = $__componentOriginal4af1e0a8ab5c0dda93279f6800da3911; ?>
<?php unset($__componentOriginal4af1e0a8ab5c0dda93279f6800da3911); ?>
<?php endif; ?>

    
    <!--[if BLOCK]><![endif]--><?php if($this->agent_id || $this->date_debut || $this->date_fin): ?>
    <div class="p-4 mb-4 bg-blue-50 rounded-lg">
        <h3 class="text-sm font-semibold text-blue-800">Filtres Actifs:</h3>
        <div class="flex flex-wrap gap-2 mt-2">
            <!--[if BLOCK]><![endif]--><?php if($this->agent_id): ?>
                <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">
                    Agent: <?php echo e(\App\Models\User::find($this->agent_id)->name ?? 'N/A'); ?>

                </span>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            <!--[if BLOCK]><![endif]--><?php if($this->date_debut): ?>
                <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">
                    Début: <?php echo e(\Carbon\Carbon::parse($this->date_debut)->format('d/m/Y')); ?>

                </span>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            <!--[if BLOCK]><![endif]--><?php if($this->date_fin): ?>
                <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">
                    Fin: <?php echo e(\Carbon\Carbon::parse($this->date_fin)->format('d/m/Y')); ?>

                </span>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        </div>
    </div>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

    
    <?php echo e($this->getTable()); ?>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $attributes = $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $component = $__componentOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?><?php /**PATH D:\APP\TUMAINI LETU\tumainiletusystem2.0\resources\views/filament/resources/microfinance-overview-resource/pages/rapports-microfinance.blade.php ENDPATH**/ ?>