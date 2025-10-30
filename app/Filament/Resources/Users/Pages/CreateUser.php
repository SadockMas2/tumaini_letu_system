<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

      

     protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

     protected function handleRecordCreation(array $data): Model
    {
        $user = User::create($data);
        
        // Assigner les rôles
        if (isset($data['roles'])) {
            $user->roles()->sync($data['roles']);
        }
        
        // Assigner les permissions directes
        if (isset($data['permissions'])) {
            $user->permissions()->sync($data['permissions']);
        }
        
        return $user;
    }
    
}

