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
        $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->timeout(60)
            ->retry(2, 500, throw: false)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => $maxTokens,
                'system' => $promptSysteme,
                'messages' => [
                    ['role' => 'user', 'content' => $promptUtilisateur],
                ],
            ]);

        if ($response->failed()) {
            Log::error('Echec appel API Anthropic (explication IA)', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException("L'API Anthropic a répondu avec une erreur ({$response->status()}).");
        }

        $donnees = $response->json();

        $texte = collect($donnees['content'] ?? [])
            ->where('type', 'text')
            ->pluck('text')
            ->implode("\n");

        if (trim($texte) === '') {
            throw new RuntimeException("L'API Anthropic a retourné une réponse vide.");
        }

        return [
            'texte' => trim($texte),
            'tokens' => ($donnees['usage']['input_tokens'] ?? 0) + ($donnees['usage']['output_tokens'] ?? 0),
        ];
    }
}