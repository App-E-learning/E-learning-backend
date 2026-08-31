<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Fournisseur Mistral pour les explications/corrections IA (texte seul,
 * chat completions — distinct de l'appel vision utilisé dans OcrService
 * pour lire les images scannées, même si c'est la même clé MISTRAL_API_KEY).
 */
class MistralProvider implements ExplicationProviderInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
    ) {}

    public function genererTexte(string $promptSysteme, string $promptUtilisateur, int $maxTokens = 600): array
    {
        if (! $this->apiKey) {
            throw new RuntimeException('MISTRAL_API_KEY absente du fichier .env.');
        }

        // Boucle de retry explicite pour les échecs de CONNEXION (timeout,
        // DNS...) — le ->retry() intégré de Laravel gère surtout les
        // réponses HTTP en erreur (4xx/5xx) ; son comportement pour une
        // ConnectionException (aucune réponse reçue du tout) est ambigu
        // selon les versions. On préfère une boucle simple et prévisible :
        // 1 nouvelle tentative après une courte pause avant d'abandonner.
        $tentativesMax = 2;
        $derniereErreur = null;

        for ($tentative = 1; $tentative <= $tentativesMax; $tentative++) {
            try {
                $response = Http::withToken($this->apiKey)
                    ->timeout(60)
                    ->post('https://api.mistral.ai/v1/chat/completions', [
                        'model' => $this->model,
                        'max_tokens' => max($maxTokens, 1200),
                        'messages' => [
                            ['role' => 'system', 'content' => $promptSysteme],
                            ['role' => 'user', 'content' => $promptUtilisateur],
                        ],
                    ]);
                $derniereErreur = null;
                break;
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $derniereErreur = $e;
                Log::warning("Mistral injoignable, tentative {$tentative}/{$tentativesMax}.", ['erreur' => $e->getMessage()]);
                if ($tentative < $tentativesMax) {
                    usleep(500_000); // 500ms avant de réessayer — absorbe une micro-coupure réseau
                }
            }
        }

        if ($derniereErreur !== null) {
            Log::error('Mistral injoignable après plusieurs tentatives — explication/correction IA', ['erreur' => $derniereErreur->getMessage()]);
            throw new RuntimeException("Le service IA ne répond pas pour le moment. Réessaie dans quelques instants.");
        }

        if ($response->failed()) {
            Log::error('Echec appel API Mistral (explication/correction IA)', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException("Le service IA a rencontré une erreur. Réessaie dans quelques instants.");
        }

        $donnees = $response->json();
        $texte = trim(data_get($donnees, 'choices.0.message.content', ''));

        if ($texte === '') {
            throw new RuntimeException("Le service IA a retourné une réponse vide. Réessaie.");
        }

        return [
            'texte' => $texte,
            'tokens' => (data_get($donnees, 'usage.prompt_tokens', 0)) + (data_get($donnees, 'usage.completion_tokens', 0)),
        ];
    }
}