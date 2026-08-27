<?php

namespace App\Console\Commands;

use App\Models\NotificationLog;
use App\Models\PushToken;
use App\Models\Sequence;
use App\Models\User;
use App\Services\ExpoPushService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class NotifySequenceEndingCommand extends Command
{
    protected $signature = 'notify:sequence-ending';

    protected $description = "Notifie les élèves quand une séquence de leur niveau approche de sa date de fin (J-3 et J-1)";

    private const MILESTONES = [3, 1];

    public function handle(ExpoPushService $expoPush): int
    {
        foreach (self::MILESTONES as $daysBefore) {
            $this->notifyForMilestone($daysBefore, $expoPush);
        }

        return self::SUCCESS;
    }

    private function notifyForMilestone(int $daysBefore, ExpoPushService $expoPush): void
    {
        $targetDate = Carbon::today()->addDays($daysBefore)->toDateString();
        $type = "sequence_ending_j{$daysBefore}";

        $sequences = Sequence::query()
            ->whereDate('date_fin', $targetDate)
            ->with('niveau')
            ->get();

        foreach ($sequences as $sequence) {
            $alreadySent = NotificationLog::where('type', $type)
                ->where('notifiable_type', Sequence::class)
                ->where('notifiable_id', $sequence->id)
                ->exists();

            if ($alreadySent) {
                continue;
            }

            $niveauCode = $sequence->niveau?->nom;

            if (!$niveauCode) {
                continue;
            }

            $userIds = User::query()
                ->where('role', 'eleve')
                ->where('niveau', $niveauCode)
                ->pluck('id');

            if ($userIds->isEmpty()) {
                continue;
            }

            $tokens = PushToken::whereIn('user_id', $userIds)->pluck('token')->all();

            $title = $daysBefore === 1
                ? 'Dernier jour !'
                : "Plus que {$daysBefore} jours !";

            $body = "La séquence « {$sequence->nom} » se termine bientôt. Termine tes exercices avant la fin !";

            $expoPush->send($tokens, $title, $body, [
                'type' => $type,
                'sequence_id' => $sequence->id,
            ]);

            NotificationLog::create([
                'type' => $type,
                'notifiable_type' => Sequence::class,
                'notifiable_id' => $sequence->id,
                'sent_at' => now(),
            ]);

            $this->info("Notif envoyée pour la séquence #{$sequence->id} (" . count($tokens) . " appareils)");
        }
    }
}