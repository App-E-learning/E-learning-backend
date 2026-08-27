<?php

namespace App\Console\Commands;

use App\Models\NotificationLog;
use App\Models\PushToken;
use App\Models\User;
use App\Services\ExpoPushService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

// Prévue pour tourner une fois par jour. Rappelle aux élèves inactifs de
// reprendre l'entraînement, à J+2 (rappel doux) et J+5 (rappel plus fort)
// depuis leur dernière soumission d'exercice.
class NotifyInactiveStudentsCommand extends Command
{
    protected $signature = 'notify:inactive-students';

    protected $description = "Relance les élèves qui n'ont pas pratiqué depuis 2 ou 5 jours";

    private const MILESTONES = [2, 5];

    public function handle(ExpoPushService $expoPush): int
    {
        // withMax ajoute une colonne virtuelle soumissions_max_created_at
        // à chaque utilisateur, sans faire une requête par élève.
        $eleves = User::query()
            ->where('role', 'eleve')
            ->withMax('soumissions', 'created_at')
            ->get();

        foreach ($eleves as $eleve) {
            // Si l'élève n'a jamais rien soumis, on se base sur sa date
            // d'inscription comme point de départ de l'inactivité.
            $lastActivity = $eleve->soumissions_max_created_at
                ? Carbon::parse($eleve->soumissions_max_created_at)
                : Carbon::parse($eleve->created_at);

            $daysSinceActivity = (int) $lastActivity->startOfDay()->diffInDays(Carbon::today());

            if (!in_array($daysSinceActivity, self::MILESTONES, true)) {
                continue;
            }

            $type = "inactivity_j{$daysSinceActivity}";

            // On compare la date du dernier envoi à la date de dernière
            // activité : si un rappel a déjà été envoyé APRÈS sa dernière
            // activité, on ne renvoie pas. S'il est redevenu actif entre
            // temps puis re-inactif, sa dernière activité est plus récente
            // que l'ancien envoi, donc le rappel repart normalement.
            $existingLog = NotificationLog::where('type', $type)
                ->where('notifiable_type', User::class)
                ->where('notifiable_id', $eleve->id)
                ->first();

            if ($existingLog && $existingLog->sent_at->greaterThanOrEqualTo($lastActivity)) {
                continue;
            }

            $tokens = PushToken::where('user_id', $eleve->id)->pluck('token')->all();

            if (empty($tokens)) {
                continue;
            }

            $title = $daysSinceActivity === 2
                ? 'On continue ?'
                : 'Tu nous manques !';

            $body = $daysSinceActivity === 2
                ? "Ça fait 2 jours que tu n'as pas pratiqué. Reprends 5 minutes pour garder ton rythme !"
                : "Ça fait 5 jours... tes progrès t'attendent. Reviens t'entraîner avant d'oublier ce que tu as appris !";

            $expoPush->send($tokens, $title, $body, [
                'type' => $type,
            ]);

            NotificationLog::updateOrCreate(
                [
                    'type' => $type,
                    'notifiable_type' => User::class,
                    'notifiable_id' => $eleve->id,
                ],
                ['sent_at' => now()]
            );

            $this->info("Rappel J{$daysSinceActivity} envoyé à l'élève #{$eleve->id}");
        }

        return self::SUCCESS;
    }
}
