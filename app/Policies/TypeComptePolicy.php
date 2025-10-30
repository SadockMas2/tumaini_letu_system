<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\TypeCompte;
use Illuminate\Auth\Access\HandlesAuthorization;

class TypeComptePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TypeCompte');
    }

    public function view(AuthUser $authUser, TypeCompte $typeCompte): bool
    {
        return $authUser->can('View:TypeCompte');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TypeCompte');
    }

    public function update(AuthUser $authUser, TypeCompte $typeCompte): bool
    {
        return $authUser->can('Update:TypeCompte');
    }

    public function delete(AuthUser $authUser, TypeCompte $typeCompte): bool
    {
        return $authUser->can('Delete:TypeCompte');
    }

    public function restore(AuthUser $authUser, TypeCompte $typeCompte): bool
    {
        return $authUser->can('Restore:TypeCompte');
    }

    public function forceDelete(AuthUser $authUser, TypeCompte $typeCompte): bool
    {
        return $authUser->can('ForceDelete:TypeCompte');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TypeCompte');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TypeCompte');
    }

    public function replicate(AuthUser $authUser, TypeCompte $typeCompte): bool
    {
        return $authUser->can('Replicate:TypeCompte');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TypeCompte');
    }

}