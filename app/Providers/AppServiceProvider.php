<?php

namespace App\Providers;

use App\Models\Epargne;
use App\Models\PaiementCredit;
use App\Observers\CycleObserver;
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
    }
}
