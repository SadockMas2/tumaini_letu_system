<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'numero_membre',
        'nom',
        'postnom',
        'prenom',
        'date_naissance',
        'email',
        'telephone',
        'adresse',
        'ville',
        'pays',
        'code_postal',
        'id_createur',
        'status',
        'identifiant_national',
        'type_client',
        'type_compte',
        'activites',
        'etat_civil',
        'image',
        'signature'
        
        
    ];

            public function typeCompte()
        {
            return $this->belongsTo(TypeCompte::class, 'type_compte_id');
        }
        protected static function boot()
        {
            parent::boot();

            static::creating(function ($client) {
                // Si aucun numero_membre n’est fourni, on le génère automatiquement
                if (empty($client->numero_membre)) {
                    $lastNumber = static::max('numero_membre') ?? 100000;
                    $client->numero_membre = $lastNumber + 1;
                }
            });
        }

        // Dans votre modèle Client, ajoutez cette méthode
       public function getImageUrl()
{
    if (!$this->image) {
        return $this->getDefaultAvatar();
    }
    
    // Extraire juste le nom du fichier du chemin
    $filename = basename($this->image);
    
    return route('client.image', ['filename' => $filename]);
}

public function getSignatureUrl()
{
    if (!$this->signature) {
        return null;
    }
    
    // Extraire juste le nom du fichier du chemin
    $filename = basename($this->signature);
    
    return route('client.image', ['filename' => $filename]);
}

    /**
     * Get default avatar URL
     */
    private function getDefaultAvatar()
    {
        return 'https://ui-avatars.com/api/?name=' . 
               urlencode($this->nom . '+' . $this->prenom) . 
               '&background=667eea&color=fff&size=300';
    }        public function getCompteEpargneParDevise(string $devise)
        {
            return CompteEpargne::where('client_id', $this->id)
                ->where('devise', $devise)
                ->first();
        }

        public function groupesSolidaires()
        {
            return $this->belongsToMany(GroupeSolidaire::class, 'groupes_membres', 'client_id', 'groupe_solidaire_id');
        }

        public function comptes()
        {
            return $this->hasMany(Compte::class);
        }

        public function transactions()
        {
            return $this->hasMany(Transaction::class);
        }

        public function credits()
        {
            return $this->hasMany(Credit::class);
        }

        // Ajouter cette méthode dans le modèle Client
        public function getNomCompletAttribute()
        {
            return trim($this->nom . ' ' . $this->postnom . ' ' . $this->prenom);
        }

        

        
}
