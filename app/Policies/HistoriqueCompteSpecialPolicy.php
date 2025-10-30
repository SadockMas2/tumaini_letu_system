<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\HistoriqueCompteSpecial;
use Illuminate\Auth\Access\HandlesAuthorization;

class HistoriqueCompteSpecialPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:HistoriqueCompteSpecial');
    }

    public function view(AuthUser $authUser, HistoriqueCompteSpecial $historiqueCompteSpecial): bool
    {
        return $authUser->can('View:HistoriqueCompteSpecial');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:HistoriqueCompteSpecial');
    }

    public function update(AuthUser $authUser, HistoriqueCompteSpecial $historiqueCompteSpecial): bool
    {
        return $authUser->can('Update:HistoriqueCompteSpecial');
    }

    public function delete(AuthUser $authUser, HistoriqueCompteSpecial $historiqueCompteSpecial): bool
    {
        return $authUser->can('Delete:HistoriqueCompteSpecial');
    }

    public function restore(AuthUser $authUser, HistoriqueCompteSpecial $historiqueCompteSpecial): bool
    {
        return $authUser->can('Restore:HistoriqueCompteSpecial');
    }

    public function forceDelete(AuthUser $authUser, HistoriqueCompteSpecial $historiqueCompteSpecial): bool
    {
        return $authUser->can('ForceDelete:HistoriqueCompteSpecial');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:HistoriqueCompteSpecial');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:HistoriqueCompteSpecial');
    }

    public function replicate(AuthUser $authUser, HistoriqueCompteSpecial $historiqueCompteSpecial): bool
    {
        return $authUser->can('Replicate:HistoriqueCompteSpecial');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:HistoriqueCompteSpecial');
    }

}