<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MouvementCredit;
use Illuminate\Auth\Access\HandlesAuthorization;

class MouvementCreditPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MouvementCredit');
    }

    public function view(AuthUser $authUser, MouvementCredit $mouvementCredit): bool
    {
        return $authUser->can('View:MouvementCredit');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MouvementCredit');
    }

    public function update(AuthUser $authUser, MouvementCredit $mouvementCredit): bool
    {
        return $authUser->can('Update:MouvementCredit');
    }

    public function delete(AuthUser $authUser, MouvementCredit $mouvementCredit): bool
    {
        return $authUser->can('Delete:MouvementCredit');
    }

    public function restore(AuthUser $authUser, MouvementCredit $mouvementCredit): bool
    {
        return $authUser->can('Restore:MouvementCredit');
    }

    public function forceDelete(AuthUser $authUser, MouvementCredit $mouvementCredit): bool
    {
        return $authUser->can('ForceDelete:MouvementCredit');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MouvementCredit');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MouvementCredit');
    }

    public function replicate(AuthUser $authUser, MouvementCredit $mouvementCredit): bool
    {
        return $authUser->can('Replicate:MouvementCredit');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MouvementCredit');
    }

}