<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfilType extends Model
{
    use HasFactory;

    protected $table = 'profil_types';

    
    protected $fillable = [
        'nom_profil',
        'description',
        'permissions',
    ];

    
    protected $casts = [
        'permissions' => 'array',
    ];

    // ✅ Relation : un type de profil peut avoir plusieurs utilisateurs
    public function users()
    {
        return $this->hasMany(User::class, 'profil_type_id');
    }
}
