<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompteSpecial extends Model
{
    use HasFactory;

    protected $table = 'compte_special';

    protected $fillable = [
    'nom', 
    'solde',
    'devise'];
}
