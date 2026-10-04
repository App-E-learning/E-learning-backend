<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GeminiProvider implements ExplicationProviderInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
    ) {}

    public function genererTexte(string $promptSysteme, string $promptUtilisateur, int $maxTokens = 600): array
    {
        $tentativesMax = 3;
        $derniereErreur = null;
        $response = null;

        for ($tentative = 1; $tentative <= $tentativesMax; $tentative++) {
            try {
                $response = Http::withHeaders([
                        'x-goog-api-key' => $this->apiKey,
                        'content-type' => 'application/json',
                        'Api-Revision' => '2026-05-20',
                    ])
                    ->timeout(60)
                    ->post('https://generativelanguage.googleapis.com/v1beta/interactions', [
                        'model' => $this->model,
                        'system_instruction' => $promptSysteme,
                        'input' => $promptUtilisateur,
                        'generation_config' => [
                            'max_output_tokens' => $maxTokens,
                        ],
                    ]);

                // 503 (surcharge) et 429 (quota) sont explicitement présentés
                // par Google comme temporaires — on les retente comme une
                // ConnectionException, contrairement aux autres erreurs 4xx
                // (ex. mauvais format de requête) qui ne se résoudront pas
                // en réessayant.
                if (in_array($response->status(), [429, 503], true)) {
                    $derniereErreur = new RuntimeException("Gemini a répondu {$response->status()}.");
                    Log::warning("Gemini surchargé/quota atteint, tentative {$tentative}/{$tentativesMax}.", [
                        'status' => $response->status(),
                    ]);
                    if ($tentative < $tentativesMax) {
                        usleep(800_000 * $tentative);
                        continue;
                    }
                    break;
                }

                $derniereErreur = null;
                break;
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $derniereErreur = $e;
                Log::warning("Gemini injoignable, tentative {$tentative}/{$tentativesMax}.", ['erreur' => $e->getMessage()]);
                if ($tentative < $tentativesMax) {
                    usleep(500_000);
                }
            }
        }

        if ($derniereErreur !== null) {
            Log::error('Gemini injoignable/surchargé après plusieurs tentatives — explication IA', ['erreur' => $derniereErreur->getMessage()]);
            throw new RuntimeException("Le service IA ne répond pas pour le moment. Réessaie dans quelques instants.");
        }

        if ($response->failed()) {
            Log::error('Echec appel API Gemini (explication IA)', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException("Le service IA a rencontré une erreur. Réessaie dans quelques instants.");
        }

        $donnees = $response->json();

        // La structure "Interactions" range sa réponse dans "steps" : on
        // cherche l'étape de type "model_output" et on concatène ses blocs
        // de texte.
        $texte = collect($donnees['steps'] ?? [])
            ->where('type', 'model_output')
            ->flatMap(fn ($step) => collect($step['content'] ?? [])->where('type', 'text')->pluck('text'))
            ->implode("\n");

        if (trim($texte) === '') {
            throw new RuntimeException("Le service IA a retourné une réponse vide. Réessaie.");
        }

        return [
            'texte' => trim($texte),
            'tokens' => $donnees['usage']['total_tokens'] ?? 0,
        ];
    }
}