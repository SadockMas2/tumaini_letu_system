<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CompteBancaire;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompteBancairePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CompteBancaire');
    }

    public function view(AuthUser $authUser, CompteBancaire $compteBancaire): bool
    {
        return $authUser->can('View:CompteBancaire');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CompteBancaire');
    }

    public function update(AuthUser $authUser, CompteBancaire $compteBancaire): bool
    {
        return $authUser->can('Update:CompteBancaire');
    }

    public function delete(AuthUser $authUser, CompteBancaire $compteBancaire): bool
    {
        return $authUser->can('Delete:CompteBancaire');
    }

    public function restore(AuthUser $authUser, CompteBancaire $compteBancaire): bool
    {
        return $authUser->can('Restore:CompteBancaire');
    }

    public function forceDelete(AuthUser $authUser, CompteBancaire $compteBancaire): bool
    {
        return $authUser->can('ForceDelete:CompteBancaire');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CompteBancaire');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CompteBancaire');
    }

    public function replicate(AuthUser $authUser, CompteBancaire $compteBancaire): bool
    {
        return $authUser->can('Replicate:CompteBancaire');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CompteBancaire');
    }

}