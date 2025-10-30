<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CaisseJournaliere;
use Illuminate\Auth\Access\HandlesAuthorization;

class CaisseJournalierePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CaisseJournaliere');
    }

    public function view(AuthUser $authUser, CaisseJournaliere $caisseJournaliere): bool
    {
        return $authUser->can('View:CaisseJournaliere');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CaisseJournaliere');
    }

    public function update(AuthUser $authUser, CaisseJournaliere $caisseJournaliere): bool
    {
        return $authUser->can('Update:CaisseJournaliere');
    }

    public function delete(AuthUser $authUser, CaisseJournaliere $caisseJournaliere): bool
    {
        return $authUser->can('Delete:CaisseJournaliere');
    }

    public function restore(AuthUser $authUser, CaisseJournaliere $caisseJournaliere): bool
    {
        return $authUser->can('Restore:CaisseJournaliere');
    }

    public function forceDelete(AuthUser $authUser, CaisseJournaliere $caisseJournaliere): bool
    {
        return $authUser->can('ForceDelete:CaisseJournaliere');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CaisseJournaliere');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CaisseJournaliere');
    }

    public function replicate(AuthUser $authUser, CaisseJournaliere $caisseJournaliere): bool
    {
        return $authUser->can('Replicate:CaisseJournaliere');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CaisseJournaliere');
    }

}