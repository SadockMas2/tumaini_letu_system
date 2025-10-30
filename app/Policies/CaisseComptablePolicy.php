<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CaisseComptable;
use Illuminate\Auth\Access\HandlesAuthorization;

class CaisseComptablePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CaisseComptable');
    }

    public function view(AuthUser $authUser, CaisseComptable $caisseComptable): bool
    {
        return $authUser->can('View:CaisseComptable');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CaisseComptable');
    }

    public function update(AuthUser $authUser, CaisseComptable $caisseComptable): bool
    {
        return $authUser->can('Update:CaisseComptable');
    }

    public function delete(AuthUser $authUser, CaisseComptable $caisseComptable): bool
    {
        return $authUser->can('Delete:CaisseComptable');
    }

    public function restore(AuthUser $authUser, CaisseComptable $caisseComptable): bool
    {
        return $authUser->can('Restore:CaisseComptable');
    }

    public function forceDelete(AuthUser $authUser, CaisseComptable $caisseComptable): bool
    {
        return $authUser->can('ForceDelete:CaisseComptable');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CaisseComptable');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CaisseComptable');
    }

    public function replicate(AuthUser $authUser, CaisseComptable $caisseComptable): bool
    {
        return $authUser->can('Replicate:CaisseComptable');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CaisseComptable');
    }

}