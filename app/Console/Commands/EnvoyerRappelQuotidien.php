<?php

namespace App\Console\Commands;

use App\Models\DeviceToken;
use App\Models\Soumission;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;

/**
 * Rappel quotidien envoyé à 20h (voir la planification dans
 * routes/console.php) : "N'oublie pas ta séance du jour !"
 *
 * N'envoie PAS aux élèves déjà actifs aujourd'hui (au moins une soumission
 * d'exercice) — un rappel n'a de sens que pour ceux qui n'ont encore rien
 * fait, sinon c'est juste une notification agaçante pour ceux qui bossent déjà.
 */
class EnvoyerRappelQuotidien extends Command
{
    protected $signature = 'app:rappel-quotidien';

    protected $description = "Envoie une notification push de rappel aux élèves inactifs aujourd'hui";

    public function handle(PushNotificationService $push): int
    {
        $idsActifsAujourdhui = Soumission::whereDate('created_at', now()->toDateString())
            ->distinct()
            ->pluck('user_id');

        $tokens = DeviceToken::whereNotIn('user_id', $idsActifsAujourdhui)
            ->whereHas('user', fn ($q) => $q->where('role', 'eleve'))
            ->get();

        if ($tokens->isEmpty()) {
            $this->info('Aucun appareil à notifier (tout le monde est déjà actif aujourd\'hui, ou aucun token enregistré).');
            return self::SUCCESS;
        }

        $push->envoyerATous(
            $tokens,
            'C\'est l\'heure de réviser 📚',
            'Une petite séance de 10 minutes avant demain ? Tes chapitres t\'attendent.',
            ['type' => 'rappel_quotidien'],
        );

        $this->info("Rappel envoyé à {$tokens->count()} appareil(s).");
        return self::SUCCESS;
    }
}