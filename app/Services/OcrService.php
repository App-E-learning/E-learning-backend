<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use thiagoalessio\TesseractOCR\TesseractOCR;

class OcrService
{
    /**
     * Seuil en dessous duquel le texte extrait par Tesseract est jugé trop
     * pauvre / probablement raté (page mal scannée, écriture manuscrite,
     * schéma sans texte...) et on bascule sur la vision IA (Mistral) en secours.
     */
    private const SEUIL_LONGUEUR_MINIMALE = 15;

    /**
     * Extrait le texte d'un fichier uploadé (image ou PDF).
     * Retourne le texte brut ainsi que la méthode utilisée, pour traçabilité.
     */
    public function extraireTexte(UploadedFile $fichier, bool $forcerVisionIa = false): array
    {
        $cheminsImages = $this->convertirEnImages($fichier);

        if ($forcerVisionIa) {
            $texteVision = $this->extraireAvecMistral($cheminsImages);
            $this->nettoyerFichiersTemporaires($cheminsImages);

            return ['texte' => $texteVision, 'methode' => 'vision_ia_mistral'];
        }

        $texteTesseract = collect($cheminsImages)
            ->map(fn ($chemin) => $this->extraireAvecTesseract($chemin))
            ->implode("\n\n");

        if (mb_strlen(trim($texteTesseract)) >= self::SEUIL_LONGUEUR_MINIMALE) {
            $this->nettoyerFichiersTemporaires($cheminsImages);

            return ['texte' => $texteTesseract, 'methode' => 'tesseract'];
        }

        Log::info('OCR Tesseract insuffisant, bascule vers la vision IA (Mistral).', [
            'fichier' => $fichier->getClientOriginalName(),
            'longueur_tesseract' => mb_strlen(trim($texteTesseract)),
        ]);

        $texteVision = $this->extraireAvecMistral($cheminsImages);
        $this->nettoyerFichiersTemporaires($cheminsImages);

        return ['texte' => $texteVision, 'methode' => 'vision_ia_mistral'];
    }

    /**
     * Convertit le fichier source en une liste de chemins d'images PNG.
     * Un PDF multi-pages devient plusieurs images ; une image reste telle quelle.
     */
    private function convertirEnImages(UploadedFile $fichier): array
    {
        $cheminTemporaire = $fichier->store('ocr-temp');
        $cheminAbsolu = Storage::path($cheminTemporaire);

        if ($fichier->getClientOriginalExtension() !== 'pdf') {
            return [$cheminAbsolu];
        }

        $prefixeSortie = Storage::path('ocr-temp') . '/' . Str::uuid() . '-page';

        // pdftoppm (poppler-utils) convertit chaque page du PDF en PNG
        exec("pdftoppm -png -r 300 " . escapeshellarg($cheminAbsolu) . ' ' . escapeshellarg($prefixeSortie));

        $images = glob($prefixeSortie . '*.png');

        if (empty($images)) {
            throw new \RuntimeException('Échec de la conversion du PDF en images. Vérifie que poppler-utils est installé.');
        }

        sort($images); // garantit l'ordre des pages

        return $images;
    }

    private function extraireAvecTesseract(string $cheminImage): string
    {
        try {
            return (new TesseractOCR($cheminImage))
                ->lang('fra')
                ->run();
        } catch (\Throwable $e) {
            Log::warning('Échec Tesseract sur une page.', ['erreur' => $e->getMessage()]);

            return '';
        }
    }

    /**
     * Fallback vision IA via l'API Mistral (modèle Pixtral) — utilisé
     * uniquement quand Tesseract échoue ou renvoie un texte trop pauvre
     * (ex: écriture manuscrite, mauvaise qualité de scan). Nécessite
     * MISTRAL_API_KEY dans .env (clé gratuite sur https://console.mistral.ai/).
     */
    private function extraireAvecMistral(array $cheminsImages): string
    {
        $apiKey = config('services.mistral.key');

        if (! $apiKey) {
            Log::error('MISTRAL_API_KEY absente — impossible d\'utiliser le fallback vision IA.');

            return '';
        }

        $textes = [];

        foreach ($cheminsImages as $chemin) {
            $imageBase64 = base64_encode(file_get_contents($chemin));

            $reponse = Http::withToken($apiKey)->post('https://api.mistral.ai/v1/chat/completions', [
                'model' => 'pixtral-12b-2409',
                'max_tokens' => 2000,
                'messages' => [[
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => "Transcris fidèlement tout le texte visible sur cette image d'exercice scolaire (énoncé, éventuellement barème). Ne reformule rien, ne résume rien. Renvoie uniquement le texte transcrit, sans commentaire.",
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => "data:image/png;base64,{$imageBase64}",
                        ],
                    ],
                ]],
            ]);

            if ($reponse->failed()) {
                Log::error('Échec de l\'appel vision IA (Mistral).', ['status' => $reponse->status(), 'body' => $reponse->body()]);
                continue;
            }

            $textes[] = data_get($reponse->json(), 'choices.0.message.content', '');
        }

        return implode("\n\n", $textes);
    }

    private function nettoyerFichiersTemporaires(array $chemins): void
    {
        foreach ($chemins as $chemin) {
            @unlink($chemin);
        }
    }
}