<?php

namespace App\Filament\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use UnitEnum;

class PermissionManager extends Page
{
    protected static ?string $navigationLabel = 'Gestion des Permissions';
    // protected static string|int|null $navigationSort = 1;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected  string $view = 'filament.pages.permission-manager';

    // protected string $view = 'resources.pages.permission-manager';
    public Collection $roles;
    public Collection $permissions;
    public array $rolePermissions = [];
    public array $permissionCategories = [];

    public string $search = '';
    public bool $showInfo = false;

    public array $filteredPermissionCategories = [];

    public function mount(): void
    {
        $this->loadData();
        $this->filteredPermissionCategories = $this->getFilteredPermissionCategoriesProperty();
    }

    public function loadData(): void
    {
        $this->roles = Role::with('permissions')->orderBy('name')->get();
        $this->permissions = Permission::orderBy('name')->get();

        $this->organizePermissionsByCategory();

        foreach ($this->roles as $role) {
            $this->rolePermissions[$role->id] = $role->permissions->pluck('id')->toArray();
        }
    }

    protected function organizePermissionsByCategory(): void
    {
        $categories = [
            'Gestion des Membres' => ['creer_compte_membre', 'gerer_remboursement', 'extraire_rapport_membres'],
            'Opérations de Caisse' => ['effectuer_depot', 'effectuer_retrait', 'verifier_solde', 'extraire_rapport_caisse'],
            'Gestion des Crédits' => ['postage_credit', 'debourser_credit', 'autoriser_retrait'],
            'Opérations Comptables' => ['passer_operations_logistique', 'enregistrer_operation_coffre', 'extraire_operations_coffre'],
            'Gestion des Batchs' => ['gestion_batch'],
            'Paiements' => ['paiement_salaire'],
            'Rapports' => ['acces_rapports', 'extraire_rapport_collecte', 'extraire_tous_rapports'],
            'Collecte' => ['dispatching'],
        ];

        $this->permissionCategories = [];

        foreach ($categories as $category => $permissionNames) {
            $categoryPermissions = $this->permissions->whereIn('name', $permissionNames);
            if ($categoryPermissions->isNotEmpty()) {
                $this->permissionCategories[$category] = $categoryPermissions;
            }
        }

        $categorizedPermissionIds = collect($this->permissionCategories)
            ->flatten()
            ->pluck('id')
            ->toArray();

        $uncategorized = $this->permissions->whereNotIn('id', $categorizedPermissionIds);
        if ($uncategorized->isNotEmpty()) {
            $this->permissionCategories['Autres'] = $uncategorized;
        }
    }

    public function updatedSearch(): void
    {
        $this->filteredPermissionCategories = $this->getFilteredPermissionCategoriesProperty();
    }

    public function getFilteredPermissionCategoriesProperty(): array
    {
        if (empty($this->search)) {
            return $this->permissionCategories;
        }

        $search = strtolower($this->search);
        $filteredCategories = [];

        foreach ($this->permissionCategories as $category => $permissions) {
            $filteredPermissions = $permissions->filter(fn($p) => 
                str_contains(strtolower($p->name), $search) ||
                str_contains(strtolower($p->description ?? ''), $search)
            );
            if ($filteredPermissions->isNotEmpty()) {
                $filteredCategories[$category] = $filteredPermissions;
            }
        }

        return $filteredCategories;
    }

    public function updateRolePermissions($roleId, $permissionId, $checked): void
    {
        $role = Role::find($roleId);
        $permission = Permission::find($permissionId);

        if ($role && $permission) {
            $checked ? $role->givePermissionTo($permission) : $role->revokePermissionTo($permission);
            $this->loadData();
            $this->filteredPermissionCategories = $this->getFilteredPermissionCategoriesProperty();

            Notification::make()
                ->title('Permission mise à jour')
                ->success()
                ->send();
        }
    }

    public function deleteRole($roleId): void
    {
        $role = Role::find($roleId);
        if ($role && $role->name !== 'Admin') {
            $userCount = User::role($role->name)->count();
            if ($userCount > 0) {
                Notification::make()
                    ->title('Impossible de supprimer')
                    ->body("{$userCount} utilisateur(s) ont encore ce rôle")
                    ->warning()
                    ->send();
                return;
            }

            $role->delete();
            $this->loadData();
            $this->filteredPermissionCategories = $this->getFilteredPermissionCategoriesProperty();

            Notification::make()
                ->title('Rôle supprimé')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Impossible de supprimer le rôle Admin')
                ->warning()
                ->send();
        }
    }

    public function toggleInfo(): void
    {
        $this->showInfo = !$this->showInfo;
    }

   public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return $user && $user->hasRole('Admin');
    }
}
