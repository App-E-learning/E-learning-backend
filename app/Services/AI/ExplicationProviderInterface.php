<?php

namespace App\Services\AI;

interface ExplicationProviderInterface
{
    /**
     * Envoie les prompts au fournisseur IA et retourne le texte généré
     * ainsi que le nombre de tokens consommés.
     *
     * $maxTokens : budget de sortie. Une explication courte tient dans le
     * défaut (600), mais générer plusieurs QCM (énoncé + 4 options +
     * explication CHACUN) ou découper une page à plusieurs exercices en
     * demande bien plus — un budget trop court tronque le JSON en plein
     * milieu et le rend inexploitable (voir AiQcmGenerationService).
     *
     * @return array{texte: string, tokens: int}
     */
    public function genererTexte(string $promptSysteme, string $promptUtilisateur, int $maxTokens = 600): array;
}