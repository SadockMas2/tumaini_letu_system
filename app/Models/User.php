<?php

namespace App\Models;

use Filament\Panel;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Models\ProfilType;



class User extends Authenticatable 
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable ,  HasRoles;

       protected $guard_name = 'filament';


    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'image',
        'profil_type_id',
        'password',
        
        
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function profilType()
    {
        return $this->belongsTo(ProfilType::class, 'profil_type_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true; // ou votre logique d'accès
    }

    // public function roles()
    // {
    //     return $this->belongsToMany(\Spatie\Permission\Models\Role::class);
    // }

    // public function permissions()
    // {
    //     return $this->belongsToMany(\Spatie\Permission\Models\Permission::class);
    // }

    // Dans app/Models/User.php
/// Dans app/Models/User.php
public function compteTransitoires()
    {
        return $this->hasMany(\App\Models\CompteTransitoire::class);
    }

    public function epargne()
    {
        return $this->hasMany(\App\Models\Epargne::class);
    }
}
