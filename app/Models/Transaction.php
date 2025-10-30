<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'source_type',
        'source_id',
        'destination_type',
        'destination_id',
        'membre_id',
        'montant',
        'devise',
        'description'
    ];

    public function source()
    {
        return $this->morphTo();
    }

    public function destination()
    {
        return $this->morphTo();
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
