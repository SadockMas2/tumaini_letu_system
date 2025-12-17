<?php

namespace App\Console\Commands;

use App\Models\SmsLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyserCoutSms extends Command
{
    protected $signature = 'sms:analyser-cout';
    protected $description = 'Analyser le coût des SMS envoyés';

    public function handle()
    {
        $this->info('📊 Analyse des coûts SMS...');
        
        // Analyser les derniers SMS
        $logs = SmsLog::where('created_at', '>', now()->subDays(3))
            ->orderBy('created_at', 'desc')
            ->get();
        
        $this->table(
            ['Date', 'Type', 'Téléphone', 'Longueur', 'Parties', 'Statut'],
            $logs->map(function ($log) {
                $message = $log->message ?? '';
                $longueur = strlen($message);
                $parties = ceil($longueur / 160);
                
                // Extraire un extrait du message
                $extrait = substr($message, 0, 30) . (strlen($message) > 30 ? '...' : '');
                
                return [
                    $log->created_at->format('d/m H:i'),
                    $log->type ?? 'N/A',
                    substr($log->phone_number ?? '', -4),
                    $longueur,
                    $parties,
                    $log->status,
                    'Message' => $extrait
                ];
            })
        );
        
        // Statistiques
        $totalSms = $logs->count();
        $totalLongueur = $logs->sum(function($log) {
            return strlen($log->message ?? '');
        });
        $moyenneLongueur = $totalSms > 0 ? $totalLongueur / $totalSms : 0;
        $partiesEstimees = $logs->sum(function($log) {
            return ceil(strlen($log->message ?? '') / 160);
        });
        
        $this->info("\n📈 Statistiques :");
        $this->line("Total SMS : {$totalSms}");
        $this->line("Longueur moyenne : " . round($moyenneLongueur) . " caractères");
        $this->line("Parties estimées : {$partiesEstimees}");
        $this->line("Coût estimé (0.06 USD/partie) : " . number_format($partiesEstimees * 0.06, 2) . " USD");
        
        // Messages les plus longs
        $this->info("\n⚠️ Messages les plus longs (> 160 chars) :");
        $longs = $logs->filter(function($log) {
            return strlen($log->message ?? '') > 160;
        })->take(5);
        
        foreach ($longs as $log) {
            $this->line("- ID {$log->id}: " . strlen($log->message) . " caractères");
            $this->line("  " . substr($log->message, 0, 50) . "...");
        }
        
        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}