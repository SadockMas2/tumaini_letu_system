<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CompteSpecial;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompteSpecialPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CompteSpecial');
    }

    public function view(AuthUser $authUser, CompteSpecial $compteSpecial): bool
    {
        return $authUser->can('View:CompteSpecial');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CompteSpecial');
    }

    public function update(AuthUser $authUser, CompteSpecial $compteSpecial): bool
    {
        return $authUser->can('Update:CompteSpecial');
    }

    public function delete(AuthUser $authUser, CompteSpecial $compteSpecial): bool
    {
        return $authUser->can('Delete:CompteSpecial');
    }

    public function restore(AuthUser $authUser, CompteSpecial $compteSpecial): bool
    {
        return $authUser->can('Restore:CompteSpecial');
    }

    public function forceDelete(AuthUser $authUser, CompteSpecial $compteSpecial): bool
    {
        return $authUser->can('ForceDelete:CompteSpecial');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CompteSpecial');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CompteSpecial');
    }

    public function replicate(AuthUser $authUser, CompteSpecial $compteSpecial): bool
    {
        return $authUser->can('Replicate:CompteSpecial');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CompteSpecial');
    }

}