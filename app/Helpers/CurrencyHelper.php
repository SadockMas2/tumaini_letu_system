<?php
// app/Helpers/CurrencyHelper.php

namespace App\Helpers;

class CurrencyHelper
{
    public static function format($amount, $currency = 'USD')
    {
        if (!is_numeric($amount)) {
            $amount = 0;
        }

        $symbol = $currency === 'CDF' ? 'CDF' : 'USD';
        $formatted = number_format(floatval($amount), 2);
        
        return "{$formatted} {$symbol}";
    }

    public static function convert($amount, $fromCurrency, $toCurrency)
    {
        if (!is_numeric($amount)) {
            return 0;
        }

        if ($fromCurrency === $toCurrency) {
            return floatval($amount);
        }

        $exchangeRate = 2400; // 1 USD = 2400 CDF
        
        if ($fromCurrency === 'USD' && $toCurrency === 'CDF') {
            return floatval($amount) * $exchangeRate;
        } elseif ($fromCurrency === 'CDF' && $toCurrency === 'USD') {
            return floatval($amount) / $exchangeRate;
        }
        
        return floatval($amount);
    }

    // Nouvelle méthode pour calculer les totaux par devise
    public static function getTotalsSeparated()
    {
        $creditsUSD = \App\Models\Credit::where('statut_demande', 'approuve')->where('devise', 'USD')->sum('montant_total');
        $creditsCDF = \App\Models\Credit::where('statut_demande', 'approuve')->where('devise', 'CDF')->sum('montant_total');
        
        $groupesUSD = \App\Models\CreditGroupe::where('statut_demande', 'approuve')->where('devise', 'USD')->sum('montant_total');
        $groupesCDF = \App\Models\CreditGroupe::where('statut_demande', 'approuve')->where('devise', 'CDF')->sum('montant_total');

        return [
            'USD' => [
                'credits' => $creditsUSD + $groupesUSD,
                'formatted' => self::format($creditsUSD + $groupesUSD, 'USD')
            ],
            'CDF' => [
                'credits' => $creditsCDF + $groupesCDF,
                'formatted' => self::format($creditsCDF + $groupesCDF, 'CDF')
            ],
            'total_converti_usd' => ($creditsUSD + $groupesUSD) + self::convert($creditsCDF + $groupesCDF, 'CDF', 'USD')
        ];
    }
}