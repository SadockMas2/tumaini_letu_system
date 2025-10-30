<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Compte;
use Illuminate\Auth\Access\HandlesAuthorization;

class ComptePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Compte');
    }

    public function view(AuthUser $authUser, Compte $compte): bool
    {
        return $authUser->can('View:Compte');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Compte');
    }

    public function update(AuthUser $authUser, Compte $compte): bool
    {
        return $authUser->can('Update:Compte');
    }

    public function delete(AuthUser $authUser, Compte $compte): bool
    {
        return $authUser->can('Delete:Compte');
    }

    public function restore(AuthUser $authUser, Compte $compte): bool
    {
        return $authUser->can('Restore:Compte');
    }

    public function forceDelete(AuthUser $authUser, Compte $compte): bool
    {
        return $authUser->can('ForceDelete:Compte');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Compte');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Compte');
    }

    public function replicate(AuthUser $authUser, Compte $compte): bool
    {
        return $authUser->can('Replicate:Compte');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Compte');
    }

}