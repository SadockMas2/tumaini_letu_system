<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Dispatch;
use Illuminate\Auth\Access\HandlesAuthorization;

class DispatchPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Dispatch');
    }

    public function view(AuthUser $authUser, Dispatch $dispatch): bool
    {
        return $authUser->can('View:Dispatch');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Dispatch');
    }

    public function update(AuthUser $authUser, Dispatch $dispatch): bool
    {
        return $authUser->can('Update:Dispatch');
    }

    public function delete(AuthUser $authUser, Dispatch $dispatch): bool
    {
        return $authUser->can('Delete:Dispatch');
    }

    public function restore(AuthUser $authUser, Dispatch $dispatch): bool
    {
        return $authUser->can('Restore:Dispatch');
    }

    public function forceDelete(AuthUser $authUser, Dispatch $dispatch): bool
    {
        return $authUser->can('ForceDelete:Dispatch');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Dispatch');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Dispatch');
    }

    public function replicate(AuthUser $authUser, Dispatch $dispatch): bool
    {
        return $authUser->can('Replicate:Dispatch');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Dispatch');
    }

}