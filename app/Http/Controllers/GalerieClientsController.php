<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GalerieClientsController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        
        $query = Client::query();
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nom', 'like', '%' . $search . '%')
                  ->orWhere('prenom', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('telephone', 'like', '%' . $search . '%')
                  ->orWhere('numero_membre', 'like', '%' . $search . '%');
            });
        }

        $clients = $query->paginate(12);
        
        // DEBUG AVANCÉ: Vérifiez si les fichiers existent vraiment
        foreach($clients as $client) {
            if ($client->image) {
                $storagePath = 'public/' . $client->image;
                $publicPath = 'storage/' . $client->image;
                $fileExists = Storage::exists($storagePath);
                
                Log::info("=== DEBUG CLIENT {$client->id} ===");
                Log::info("Nom: {$client->nom} {$client->prenom}");
                Log::info("Image DB: {$client->image}");
                Log::info("Storage path: {$storagePath}");
                Log::info("Public URL: " . asset($publicPath));
                Log::info("Fichier existe: " . ($fileExists ? 'OUI' : 'NON'));
                
                if (!$fileExists) {
                    Log::warning("⚠️ FICHIER MANQUANT: {$storagePath}");
                }
            }
        }
        
        // Calcul des statistiques
        $stats = [
            'total' => $clients->total(),
            'avec_email' => (clone $query)->whereNotNull('email')->count(),
            'avec_telephone' => (clone $query)->whereNotNull('telephone')->count(),
            'avec_signature' => (clone $query)->whereNotNull('signature')->count(),
        ];

        return view('galerie-clients', compact('clients', 'stats', 'search'));

        // Dans GalerieClientsController - méthode index
foreach($clients as $client) {
    if ($client->image) {
        $exists = Storage::disk('public')->exists($client->image);
        $url = Storage::disk('public')->url($client->image);
        
        Log::info("Image {$client->image} - Exists: " . ($exists ? 'Yes' : 'No'));
        Log::info("URL: {$url}");
    }
}
    }

    public function show($id)
    {
        $client = Client::findOrFail($id);
        return response()->json($client);
    }


    
}