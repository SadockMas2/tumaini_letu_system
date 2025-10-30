<?php

namespace App\Livewire;

use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Collection;

class RolePermissionManager extends Component
{
    public Collection $roles;
    public Collection $permissions;
    public array $permissionCategories = [];
    
    public string $newRoleName = '';
    public string $search = '';
    public array $selectedPermissions = [];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->roles = Role::with('permissions')->orderBy('name')->get();
        $this->permissions = Permission::orderBy('name')->get();
        $this->organizePermissionsByCategory();
    }

    protected function organizePermissionsByCategory()
    {
        $categories = [
            '👥 Gestion des Clients' => ['view_client', 'create_client', 'edit_client', 'delete_client', 'force_delete_client'],
            '💰 Gestion des Comptes' => ['view_compte', 'create_compte', 'edit_compte', 'delete_compte'],
            '💳 Gestion des Crédits' => ['view_credit', 'create_credit', 'edit_credit', 'delete_credit', 'approve_credit'],
            '📊 Opérations Comptables' => ['view_operation', 'create_operation', 'edit_operation', 'delete_operation'],
            '📈 Rapports & Analytics' => ['view_report', 'export_report', 'view_analytics'],
            '⚙️ Administration Système' => ['manage_users', 'manage_roles', 'system_settings'],
        ];

        $this->permissionCategories = [];

        foreach ($categories as $category => $permissionNames) {
            $categoryPermissions = $this->permissions->whereIn('name', $permissionNames);
            if ($categoryPermissions->isNotEmpty()) {
                $this->permissionCategories[$category] = $categoryPermissions;
            }
        }

        // Permissions non catégorisées
        $uncategorized = $this->permissions->whereNotIn('name', array_merge(...array_values($categories)));
        if ($uncategorized->isNotEmpty()) {
            $this->permissionCategories['📁 Autres Permissions'] = $uncategorized;
        }
    }

    public function getFilteredCategories()
    {
        if (empty($this->search)) {
            return $this->permissionCategories;
        }

        $search = strtolower($this->search);
        $filtered = [];

        foreach ($this->permissionCategories as $category => $permissions) {
            $filteredPermissions = $permissions->filter(function ($permission) use ($search) {
                return str_contains(strtolower($permission->name), $search) ||
                       str_contains(strtolower($permission->description ?? ''), $search) ||
                       str_contains(strtolower($this->formatPermissionName($permission->name)), $search);
            });

            if ($filteredPermissions->isNotEmpty()) {
                $filtered[$category] = $filteredPermissions;
            }
        }

        return $filtered;
    }

    public function formatPermissionName($permissionName)
    {
        // Convertit "create_client" en "Create Client"
        return ucwords(str_replace(['_', '-'], ' ', $permissionName));
    }

    public function createRole()
    {
        $this->validate([
            'newRoleName' => 'required|min:2|unique:roles,name',
            'selectedPermissions' => 'array'
        ]);

        try {
            $role = Role::create(['name' => $this->newRoleName, 'guard_name' => 'web']);
            
            if (!empty($this->selectedPermissions)) {
                $role->syncPermissions($this->selectedPermissions);
            }

            $this->reset(['newRoleName', 'selectedPermissions']);
            $this->loadData();
            
            // Remplacement de $this->success()
            session()->flash('success', "Rôle créé avec succès !");
            
        } catch (\Exception $e) {
            // Remplacement de $this->error()
            session()->flash('error', "Erreur lors de la création du rôle : " . $e->getMessage());
        }
    }

    public function deleteRole($roleId)
    {
        $role = Role::find($roleId);
        
        if ($role && $role->name !== 'Admin') {
            $role->delete();
            $this->loadData();
            // Remplacement de $this->success()
            session()->flash('success', 'Rôle supprimé avec succès');
        } else {
            // Remplacement de $this->warning()
            session()->flash('warning', 'Impossible de supprimer le rôle Admin');
        }
    }

    public function render()
    {
        return view('livewire.role-permission-manager', [
            'filteredCategories' => $this->getFilteredCategories()
        ]);
    }
}