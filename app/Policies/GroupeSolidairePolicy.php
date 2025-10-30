<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\GroupeSolidaire;
use Illuminate\Auth\Access\HandlesAuthorization;

class GroupeSolidairePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:GroupeSolidaire');
    }

    public function view(AuthUser $authUser, GroupeSolidaire $groupeSolidaire): bool
    {
        return $authUser->can('View:GroupeSolidaire');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:GroupeSolidaire');
    }

    public function update(AuthUser $authUser, GroupeSolidaire $groupeSolidaire): bool
    {
        return $authUser->can('Update:GroupeSolidaire');
    }

    public function delete(AuthUser $authUser, GroupeSolidaire $groupeSolidaire): bool
    {
        return $authUser->can('Delete:GroupeSolidaire');
    }

    public function restore(AuthUser $authUser, GroupeSolidaire $groupeSolidaire): bool
    {
        return $authUser->can('Restore:GroupeSolidaire');
    }

    public function forceDelete(AuthUser $authUser, GroupeSolidaire $groupeSolidaire): bool
    {
        return $authUser->can('ForceDelete:GroupeSolidaire');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:GroupeSolidaire');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:GroupeSolidaire');
    }

    public function replicate(AuthUser $authUser, GroupeSolidaire $groupeSolidaire): bool
    {
        return $authUser->can('Replicate:GroupeSolidaire');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:GroupeSolidaire');
    }

}