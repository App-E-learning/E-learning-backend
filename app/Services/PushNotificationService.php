<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;

/**
 * Envoie des notifications push via l'API Expo (https://exp.host), le
 * service utilisé par défaut pour toute app construite avec Expo — pas
 * besoin de configurer directement Firebase (Android) ou APNs (iOS),
 * Expo s'en charge derrière son propre endpoint unique.
 *
 * Ne dépend d'aucune clé API : les tokens Expo ("ExponentPushToken[...]")
 * suffisent, Expo route lui-même vers FCM/APNs.
 */
class PushNotificationService
{
    private const EXPO_PUSH_URL = 'https://exp.host/--/api/v2/push/send';

    /** Expo limite chaque appel à 100 messages. */
    private const TAILLE_LOT = 100;

    /**
     * Envoie le même message à tous les tokens fournis, par lots de 100
     * (limite imposée par Expo). Les tokens que Expo signale comme
     * invalides/désinstallés (`DeviceNotRegistered`) sont supprimés de la
     * base au passage, pour ne pas les re-solliciter indéfiniment.
     *
     * @param  \Illuminate\Support\Collection<int, DeviceToken>  $deviceTokens
     */
    public function envoyerATous($deviceTokens, string $titre, string $corps, array $data = []): void
    {
        $tokensAsupprimer = [];

        foreach ($deviceTokens->chunk(self::TAILLE_LOT) as $lot) {
            $messages = $lot->map(fn (DeviceToken $dt) => [
                'to' => $dt->token,
                'title' => $titre,
                'body' => $corps,
                'data' => $data,
                'sound' => 'default',
            ])->values()->all();

            try {
                $response = Http::timeout(30)
                    ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
                    ->post(self::EXPO_PUSH_URL, $messages);
            } catch (\Throwable $e) {
                Log::warning('Push Expo : appel réseau échoué.', ['erreur' => $e->getMessage()]);
                continue;
            }

            if (! $response->successful()) {
                Log::warning('Push Expo : réponse HTTP en erreur.', ['status' => $response->status(), 'body' => $response->body()]);
                continue;
            }

            $tickets = $response->json('data', []);
            foreach ($tickets as $i => $ticket) {
                if (($ticket['status'] ?? null) === 'error' && ($ticket['details']['error'] ?? null) === 'DeviceNotRegistered') {
                    $tokensAsupprimer[] = $lot->values()->get($i)?->token;
                }
            }
        }

        if (! empty(array_filter($tokensAsupprimer))) {
            DeviceToken::whereIn('token', array_filter($tokensAsupprimer))->delete();
        }
    }
}