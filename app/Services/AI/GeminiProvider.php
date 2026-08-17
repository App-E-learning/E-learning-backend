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
        $response = Http::withHeaders([
                'x-goog-api-key' => $this->apiKey,
                'content-type' => 'application/json',
            ])
            ->timeout(60)
            ->retry(2, 500, throw: false)
            ->post('https://generativelanguage.googleapis.com/v1beta/interactions', [
                'model' => $this->model,
                'system_instruction' => $promptSysteme,
                'input' => $promptUtilisateur,
                'max_output_tokens' => $maxTokens,
            ]);

        if ($response->failed()) {
            Log::error('Echec appel API Gemini (explication IA)', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException("L'API Gemini a répondu avec une erreur ({$response->status()}).");
        }

        $donnees = $response->json();

        // La nouvelle Interactions API structure sa réponse en "steps" :
        // on cherche l'étape de type "model_output" et on concatène
        // ses blocs de texte, comme pour Anthropic.
        $texte = collect($donnees['steps'] ?? [])
            ->where('type', 'model_output')
            ->flatMap(fn ($step) => collect($step['content'] ?? [])->where('type', 'text')->pluck('text'))
            ->implode("\n");

        if (trim($texte) === '') {
            throw new RuntimeException("L'API Gemini a retourné une réponse vide.");
        }

        return [
            'texte' => trim($texte),
            // Cette nouvelle API n'expose pas encore le détail des tokens
            // dans la réponse de base ; on met 0 par défaut plutôt que
            // de deviner une structure qui n'existe pas.
            'tokens' => $donnees['usage']['total_tokens'] ?? 0,
        ];
    }
}