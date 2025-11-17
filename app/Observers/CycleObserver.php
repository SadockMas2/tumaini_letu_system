<?php

namespace App\Observers;

use App\Models\Cycle;
use App\Models\Epargne;

class CycleObserver
{
    /**
     * Après la création d'une épargne, synchroniser le compteur
     */
    public function created(Epargne $epargne)
    {
        if ($epargne->cycle) {
            $epargne->cycle->synchroniserCompteurEpargnes();
        }
    }

    /**
     * Après la mise à jour d'une épargne, synchroniser le compteur
     */
    public function updated(Epargne $epargne)
    {
        if ($epargne->cycle) {
            $epargne->cycle->synchroniserCompteurEpargnes();
        }
    }

    /**
     * Après la suppression d'une épargne, synchroniser le compteur
     */
    public function deleted(Epargne $epargne)
    {
        if ($epargne->cycle) {
            $epargne->cycle->synchroniserCompteurEpargnes();
        }
    }
}