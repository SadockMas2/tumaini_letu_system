<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Credit;
use Carbon\Carbon;

class VerifierRemboursements extends Command
{
    /**
     * Nom et signature de la commande artisan
     *
     * @var string
     */
    protected $signature = 'credits:verifier';

    /**
     * Description de la commande
     *
     * @var string
     */
    protected $description = 'Vérifie les crédits et applique les amendes si nécessaire';

    /**
     * Execute la commande
     */
    public function handle()
    {
        $credits = Credit::where('statut', 'en_cours')->get();

        foreach ($credits as $credit) {
            $dateOctroi = Carbon::parse($credit->date_octroi);
            $semainesPassees = $dateOctroi->diffInWeeks(Carbon::now());

            if ($semainesPassees > 0 && $credit->montant_total > $credit->montant_rembourse) {
                $reste = $credit->montant_total - $credit->montant_rembourse;
                $amende = $reste * 0.02;

                $credit->montant_total += $amende;
                $credit->save();

                $this->info("Amende appliquée sur le crédit #{$credit->id} : +{$amende}");
            }
        }
    }
}
