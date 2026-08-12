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

    public function genererTexte(string $promptSysteme, string $promptUtilisateur): array
    {
        if (! $this->apiKey) {
            throw new RuntimeException('MISTRAL_API_KEY absente du fichier .env.');
        }

        $response = Http::withToken($this->apiKey)
            ->timeout(30)
            ->retry(2, 500, throw: false)
            ->post('https://api.mistral.ai/v1/chat/completions', [
                'model' => $this->model,
                'max_tokens' => 1200,
                'messages' => [
                    ['role' => 'system', 'content' => $promptSysteme],
                    ['role' => 'user', 'content' => $promptUtilisateur],
                ],
            ]);

        if ($response->failed()) {
            Log::error('Echec appel API Mistral (explication/correction IA)', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException("L'API Mistral a répondu avec une erreur ({$response->status()}).");
        }

        $donnees = $response->json();
        $texte = trim(data_get($donnees, 'choices.0.message.content', ''));

        if ($texte === '') {
            throw new RuntimeException("L'API Mistral a retourné une réponse vide.");
        }

        return [
            'texte' => $texte,
            'tokens' => (data_get($donnees, 'usage.prompt_tokens', 0)) + (data_get($donnees, 'usage.completion_tokens', 0)),
        ];
    }
}