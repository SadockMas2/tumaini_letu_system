<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Epargne;
use Illuminate\Auth\Access\HandlesAuthorization;

class EpargnePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Epargne');
    }

    public function view(AuthUser $authUser, Epargne $epargne): bool
    {
        return $authUser->can('View:Epargne');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Epargne');
    }

    public function update(AuthUser $authUser, Epargne $epargne): bool
    {
        return $authUser->can('Update:Epargne');
    }

    public function delete(AuthUser $authUser, Epargne $epargne): bool
    {
        return $authUser->can('Delete:Epargne');
    }

    public function restore(AuthUser $authUser, Epargne $epargne): bool
    {
        return $authUser->can('Restore:Epargne');
    }

    public function forceDelete(AuthUser $authUser, Epargne $epargne): bool
    {
        return $authUser->can('ForceDelete:Epargne');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Epargne');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Epargne');
    }

    public function replicate(AuthUser $authUser, Epargne $epargne): bool
    {
        return $authUser->can('Replicate:Epargne');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Epargne');
    }

}