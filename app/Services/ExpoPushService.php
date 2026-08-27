<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoPushService
{
    private const ENDPOINT = 'https://exp.host/--/api/v2/push/send';
    private const CHUNK_SIZE = 100;

    public function send(array $expoTokens, string $title, string $body, array $data = []): void
    {
        $validTokens = array_values(array_filter(
            $expoTokens,
            fn (string $token) => str_starts_with($token, 'ExponentPushToken')
        ));

        if (empty($validTokens)) {
            return;
        }

        foreach (array_chunk($validTokens, self::CHUNK_SIZE) as $chunk) {
            $messages = array_map(fn (string $token) => [
                'to' => $token,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'sound' => 'default',
                'priority' => 'high',
            ], $chunk);

            $response = Http::post(self::ENDPOINT, $messages);

            if ($response->failed()) {
                Log::warning('ExpoPushService: échec envoi notifications', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        }
    }
}