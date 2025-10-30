<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CompteTransitoire;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompteTransitoirePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CompteTransitoire');
    }

    public function view(AuthUser $authUser, CompteTransitoire $compteTransitoire): bool
    {
        return $authUser->can('View:CompteTransitoire');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CompteTransitoire');
    }

    public function update(AuthUser $authUser, CompteTransitoire $compteTransitoire): bool
    {
        return $authUser->can('Update:CompteTransitoire');
    }

    public function delete(AuthUser $authUser, CompteTransitoire $compteTransitoire): bool
    {
        return $authUser->can('Delete:CompteTransitoire');
    }

    public function restore(AuthUser $authUser, CompteTransitoire $compteTransitoire): bool
    {
        return $authUser->can('Restore:CompteTransitoire');
    }

    public function forceDelete(AuthUser $authUser, CompteTransitoire $compteTransitoire): bool
    {
        return $authUser->can('ForceDelete:CompteTransitoire');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CompteTransitoire');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CompteTransitoire');
    }

    public function replicate(AuthUser $authUser, CompteTransitoire $compteTransitoire): bool
    {
        return $authUser->can('Replicate:CompteTransitoire');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CompteTransitoire');
    }

}