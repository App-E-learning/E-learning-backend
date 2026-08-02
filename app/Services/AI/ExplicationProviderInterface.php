<?php

namespace App\Services\AI;

interface ExplicationProviderInterface
{
    /**
     * Envoie les prompts au fournisseur IA et retourne le texte généré
     * ainsi que le nombre de tokens consommés.
     *
     * @return array{texte: string, tokens: int}
     */
    public function genererTexte(string $promptSysteme, string $promptUtilisateur): array;
}