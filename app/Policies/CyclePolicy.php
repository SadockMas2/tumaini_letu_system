<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Cycle;
use Illuminate\Auth\Access\HandlesAuthorization;

class CyclePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Cycle');
    }

    public function view(AuthUser $authUser, Cycle $cycle): bool
    {
        return $authUser->can('View:Cycle');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Cycle');
    }

    public function update(AuthUser $authUser, Cycle $cycle): bool
    {
        return $authUser->can('Update:Cycle');
    }

    public function delete(AuthUser $authUser, Cycle $cycle): bool
    {
        return $authUser->can('Delete:Cycle');
    }

    public function restore(AuthUser $authUser, Cycle $cycle): bool
    {
        return $authUser->can('Restore:Cycle');
    }

    public function forceDelete(AuthUser $authUser, Cycle $cycle): bool
    {
        return $authUser->can('ForceDelete:Cycle');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Cycle');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Cycle');
    }

    public function replicate(AuthUser $authUser, Cycle $cycle): bool
    {
        return $authUser->can('Replicate:Cycle');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Cycle');
    }

}