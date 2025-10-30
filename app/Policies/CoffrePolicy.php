<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Coffre;
use Illuminate\Auth\Access\HandlesAuthorization;

class CoffrePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Coffre');
    }

    public function view(AuthUser $authUser, Coffre $coffre): bool
    {
        return $authUser->can('View:Coffre');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Coffre');
    }

    public function update(AuthUser $authUser, Coffre $coffre): bool
    {
        return $authUser->can('Update:Coffre');
    }

    public function delete(AuthUser $authUser, Coffre $coffre): bool
    {
        return $authUser->can('Delete:Coffre');
    }

    public function restore(AuthUser $authUser, Coffre $coffre): bool
    {
        return $authUser->can('Restore:Coffre');
    }

    public function forceDelete(AuthUser $authUser, Coffre $coffre): bool
    {
        return $authUser->can('ForceDelete:Coffre');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Coffre');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Coffre');
    }

    public function replicate(AuthUser $authUser, Coffre $coffre): bool
    {
        return $authUser->can('Replicate:Coffre');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Coffre');
    }

}