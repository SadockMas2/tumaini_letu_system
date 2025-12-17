<?php

namespace App\Providers;

use App\Models\CompteEpargne;
use App\Models\Epargne;
use App\Models\Mouvement;
use App\Models\MouvementEpargne;
use App\Models\PaiementCredit;
use App\Observers\CycleObserver;
use App\Observers\EpargneObserver;
use App\Observers\MouvementEpargneObserver;
use App\Observers\PaiementCreditObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Epargne::observe(CycleObserver::class);
        PaiementCredit::observe(PaiementCreditObserver::class);
        Mouvement::observe(\App\Observers\MouvementObserver::class);
        CompteEpargne::observe(\App\Observers\CompteEpargneObserver::class);
        // MouvementEpargne::observe(MouvementEpargneObserver::class); 
        Epargne::observe(EpargneObserver::class);
   
    }
}
