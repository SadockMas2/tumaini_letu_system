<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeCompte extends Model
{
    protected $table = 'type_comptes';

    protected $fillable = [
        'designation',
        'description',
    ];
}
    