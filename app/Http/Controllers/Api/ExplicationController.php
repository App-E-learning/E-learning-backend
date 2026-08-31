<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenererExplicationRequest;
use App\Http\Resources\ExplicationResource;
use App\Services\AiExplicationService;
use RuntimeException;

class ExplicationController extends Controller
{
    // Injection de dépendances : Laravel instancie AiExplicationService
    // automatiquement et l'injecte ici, sans qu'on ait à écrire
    // "new AiExplicationService()" nous-mêmes.
    public function __construct(private readonly AiExplicationService $service)
    {
    }

    public function generer(GenererExplicationRequest $request)
    {
        $valide = $request->validated();

        try {
            $explication = $this->service->genererExplication(
                enonce: $valide['enonce'],
                corrigeOfficiel: $valide['corrige_officiel'],
                reponseEleve: $valide['reponse_eleve'] ?? null,
                exerciceId: $valide['exercice_id'] ?? null,
            );
        } catch (\Throwable $e) {
            // \Throwable et pas seulement RuntimeException : certaines
            // exceptions (ex: Illuminate\Http\Client\ConnectionException en
            // cas de timeout réseau) n'héritent PAS de RuntimeException et
            // passaient au travers de ce filet, laissant fuiter le message
            // technique brut ("cURL error 28...") jusqu'à l'élève — bug
            // observé en prod. Le détail réel part dans les logs, jamais
            // dans la réponse envoyée au client.
            \Illuminate\Support\Facades\Log::error('Explication IA : échec non intercepté par le service.', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            // 502 Bad Gateway : notre serveur va bien, c'est le service
            // externe (Claude) qui a échoué — code HTTP sémantiquement correct
            return response()->json([
                'message' => "Impossible de générer l'explication pour le moment. Veuillez réessayer.",
            ], 502);
        }

        return (new ExplicationResource($explication))
            ->response()
            // wasRecentlyCreated est un booléen Eloquent automatique :
            // true si create() vient d'être appelé dans CETTE requête,
            // false si l'enregistrement provient d'un where()->first()
            // existant. Ça nous donne "201 = généré" vs "200 = depuis le cache"
            // sans code supplémentaire.
            ->setStatusCode($explication->wasRecentlyCreated ? 201 : 200);
    }
}