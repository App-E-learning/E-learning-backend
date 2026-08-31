<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AnthropicProvider implements ExplicationProviderInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
    ) {}

    public function genererTexte(string $promptSysteme, string $promptUtilisateur, int $maxTokens = 600): array
    {
        // Boucle de retry explicite pour les échecs de CONNEXION (timeout,
        // DNS...) — voir MistralProvider pour le détail du raisonnement.
        $tentativesMax = 2;
        $derniereErreur = null;

        for ($tentative = 1; $tentative <= $tentativesMax; $tentative++) {
            try {
                $response = Http::withHeaders([
                        'x-api-key' => $this->apiKey,
                        'anthropic-version' => '2023-06-01',
                        'content-type' => 'application/json',
                    ])
                    ->timeout(60)
                    ->post('https://api.anthropic.com/v1/messages', [
                        'model' => $this->model,
                        'max_tokens' => $maxTokens,
                        'system' => $promptSysteme,
                        'messages' => [
                            ['role' => 'user', 'content' => $promptUtilisateur],
                        ],
                    ]);
                $derniereErreur = null;
                break;
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $derniereErreur = $e;
                Log::warning("Anthropic injoignable, tentative {$tentative}/{$tentativesMax}.", ['erreur' => $e->getMessage()]);
                if ($tentative < $tentativesMax) {
                    usleep(500_000);
                }
            }
        }

        if ($derniereErreur !== null) {
            Log::error('Anthropic injoignable après plusieurs tentatives — explication IA', ['erreur' => $derniereErreur->getMessage()]);
            throw new RuntimeException("Le service IA ne répond pas pour le moment. Réessaie dans quelques instants.");
        }

        if ($response->failed()) {
            Log::error('Echec appel API Anthropic (explication IA)', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException("Le service IA a rencontré une erreur. Réessaie dans quelques instants.");
        }

        $donnees = $response->json();

        $texte = collect($donnees['content'] ?? [])
            ->where('type', 'text')
            ->pluck('text')
            ->implode("\n");

        if (trim($texte) === '') {
            throw new RuntimeException("Le service IA a retourné une réponse vide. Réessaie.");
        }

        return [
            'texte' => trim($texte),
            'tokens' => ($donnees['usage']['input_tokens'] ?? 0) + ($donnees['usage']['output_tokens'] ?? 0),
        ];
    }
}