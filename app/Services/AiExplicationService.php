<?php

namespace App\Services;

use App\Models\Explication;
use App\Services\AI\ExplicationProviderInterface;

class AiExplicationService
{
    // Le service ne connaît que l'interface, jamais Anthropic ou Gemini
    // directement. Laravel injecte automatiquement la bonne implémentation
    // (voir AppServiceProvider) selon la config active.
    public function __construct(private readonly ExplicationProviderInterface $provider)
    {
    }

    public function genererExplication(
        string $enonce,
        string $corrigeOfficiel,
        ?string $reponseEleve = null,
        ?int $exerciceId = null,
    ): Explication {
        $cleCache = $this->construireCleCache($enonce, $corrigeOfficiel, $reponseEleve);

        $existante = Explication::where('cle_cache', $cleCache)->first();
        if ($existante) {
            return $existante;
        }

        $resultat = $this->provider->genererTexte(
            $this->promptSysteme(),
            $this->construirePromptUtilisateur($enonce, $corrigeOfficiel, $reponseEleve),
            800,
        );

        return Explication::create([
            'exercice_id' => $exerciceId,
            'cle_cache' => $cleCache,
            'enonce' => $enonce,
            'corrige_officiel' => $corrigeOfficiel,
            'reponse_eleve' => $reponseEleve,
            'contenu' => $resultat['texte'],
            'modele_ia' => config('services.ai_explication.provider'),
            'tokens_utilises' => $resultat['tokens'],
        ]);
    }

    private function construireCleCache(string $enonce, string $corrige, ?string $reponse): string
    {
        $reponseNormalisee = $reponse !== null
            ? preg_replace('/\s+/', ' ', mb_strtolower(trim($reponse)))
            : 'aucune_reponse';

        return hash('sha256', $enonce . '|' . $corrige . '|' . $reponseNormalisee);
    }

    private function promptSysteme(): string
    {
        return <<<'PROMPT'
Tu es un assistant pédagogique pour des élèves de Terminale C au Cameroun.
Ton rôle est d'expliquer une correction d'exercice à partir UNIQUEMENT du
corrigé officiel fourni. Règles strictes :
1. Ne contredis JAMAIS le corrigé officiel, même si une autre méthode existe :
   reste fidèle à la méthode et au résultat donnés dans le corrigé.
2. N'invente aucune information (formule, théorème, donnée) absente de
   l'énoncé ou du corrigé fournis.
3. Si une réponse d'élève est fournie et qu'elle diffère du corrigé,
   explique précisément où se situe l'erreur, sans être condescendant.
4. Reste concis : 4 à 8 phrases, adaptées à un lycéen, pas un exposé académique.
5. Ne propose pas de méthode alternative de résolution.
PROMPT;
    }

    private function construirePromptUtilisateur(string $enonce, string $corrige, ?string $reponseEleve): string
    {
        $prompt = "Énoncé de l'exercice :\n{$enonce}\n\nCorrigé officiel :\n{$corrige}\n\n";

        $prompt .= $reponseEleve !== null
            ? "Réponse donnée par l'élève :\n{$reponseEleve}\n\nExplique la correction en tenant compte de l'erreur éventuelle de l'élève."
            : 'Explique la correction de façon claire et pédagogique.';

        return $prompt;
    }
}