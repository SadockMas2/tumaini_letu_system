<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $table = 'users'; // ← on utilise la table users
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'email',
        'password',
        'image',
    ];
    public $timestamps = true;

    // Optionnel : relation inverse si tu veux
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
