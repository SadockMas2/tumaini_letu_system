<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Mouvement;
use Illuminate\Auth\Access\HandlesAuthorization;

class MouvementPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Mouvement');
    }

    public function view(AuthUser $authUser, Mouvement $mouvement): bool
    {
        return $authUser->can('View:Mouvement');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Mouvement');
    }

    public function update(AuthUser $authUser, Mouvement $mouvement): bool
    {
        return $authUser->can('Update:Mouvement');
    }

    public function delete(AuthUser $authUser, Mouvement $mouvement): bool
    {
        return $authUser->can('Delete:Mouvement');
    }

    public function restore(AuthUser $authUser, Mouvement $mouvement): bool
    {
        return $authUser->can('Restore:Mouvement');
    }

    public function forceDelete(AuthUser $authUser, Mouvement $mouvement): bool
    {
        return $authUser->can('ForceDelete:Mouvement');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Mouvement');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Mouvement');
    }

    public function replicate(AuthUser $authUser, Mouvement $mouvement): bool
    {
        return $authUser->can('Replicate:Mouvement');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Mouvement');
    }

}