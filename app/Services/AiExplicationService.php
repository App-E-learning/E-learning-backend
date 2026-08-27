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
            // Filet de sécurité rétroactif : une explication mise en cache
            // AVANT ce correctif peut encore contenir du LaTeX/Markdown brut
            // (voir nettoyerTexte). On la nettoie au passage plutôt que
            // d'exiger une purge manuelle du cache.
            $texteNettoye = $this->nettoyerTexte($existante->contenu);
            if ($texteNettoye !== $existante->contenu) {
                $existante->update(['contenu' => $texteNettoye]);
            }

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
            'contenu' => $this->nettoyerTexte($resultat['texte']),
            'modele_ia' => config('services.ai_explication.provider'),
            'tokens_utilises' => $resultat['tokens'],
        ]);
    }

    /**
     * Filet de sécurité si l'IA ignore quand même la consigne du prompt :
     * retire la mise en forme Markdown (illisible sans moteur de rendu côté
     * app) et convertit la notation LaTeX en texte mathématique lisible.
     * Volontairement best-effort (regex simples) plutôt qu'un vrai parseur
     * LaTeX — suffisant pour les cas usuels (\frac, \pm, \sqrt, indices...).
     */
    private function nettoyerTexte(string $texte): string
    {
        // Markdown : gras/italique -> texte brut ; les étoiles isolées
        // restantes (souvent une emphase mal fermée) sont juste retirées.
        $texte = preg_replace('/\*\*(.+?)\*\*/s', '$1', $texte);
        $texte = preg_replace('/__(.+?)__/s', '$1', $texte);
        $texte = preg_replace('/\*(.+?)\*/s', '$1', $texte);
        $texte = str_replace('*', '', $texte);

        // Délimiteurs LaTeX \( \) et \[ \] : juste des marqueurs, on les
        // retire en gardant le contenu mathématique.
        $texte = preg_replace('/\\\\[\(\)\[\]]/', '', $texte);

        // \frac{a}{b} -> (a)/(b)
        $texte = preg_replace('/\\\\frac\{([^{}]*)\}\{([^{}]*)\}/', '($1)/($2)', $texte);

        // Symboles courants -> équivalent texte lisible
        $texte = str_replace(
            ['\\pm', '\\times', '\\cdot', '\\sqrt', '\\leq', '\\geq', '\\neq', '\\infty', '\\in'],
            ['±', '×', '×', '√', '≤', '≥', '≠', '∞', '∈'],
            $texte
        );

        // Toute commande LaTeX résiduelle (\alpha, \sum...) : on retire le
        // backslash plutôt que de laisser un symbole illisible pour l'élève.
        $texte = preg_replace('/\\\\([a-zA-Z]+)/', '$1', $texte);

        // Indices/exposants -> vrais caractères Unicode en indice/exposant
        // (uₙ, uₙ₊₁, u₀, x²...) plutôt que la notation "u_n" ou "u_(n+1)" :
        // ça reste du texte brut affichable sans moteur de rendu, mais ça a
        // enfin l'air d'une vraie notation mathématique de suite numérique.
        $texte = preg_replace_callback('/_\{([^{}]+)\}/', fn ($m) => $this->versIndiceOuExposant($m[1], true), $texte);
        $texte = preg_replace_callback('/_([a-zA-Z0-9])(?![a-zA-Z0-9])/', fn ($m) => $this->versIndiceOuExposant($m[1], true), $texte);
        $texte = preg_replace_callback('/\^\{([^{}]+)\}/', fn ($m) => $this->versIndiceOuExposant($m[1], false), $texte);
        $texte = preg_replace_callback('/\^([a-zA-Z0-9])(?![a-zA-Z0-9])/', fn ($m) => $this->versIndiceOuExposant($m[1], false), $texte);

        // Filet supplémentaire : l'IA écrit parfois l'indice collé, sans
        // underscore ("Un+1", "u0"), malgré la consigne du prompt. On ne
        // convertit QUE si un chiffre suit collé (offset "n+1"/"n-1", ou
        // indice numérique direct "u0") — jamais "un"/"Un" tout seul, qui
        // est aussi le mot français "un" ("Un problème...", "un exercice").
        $texte = preg_replace_callback(
            '/\b([uvwUVW])(n[+-]\d+|\d+)\b/',
            fn ($m) => $m[1] . $this->versIndiceOuExposant($m[2], true),
            $texte
        );

        return trim(preg_replace('/[ \t]{2,}/', ' ', $texte));
    }

    /**
     * Convertit chaque caractère en son équivalent Unicode indice/exposant
     * (u_{n+1} -> uₙ₊₁, x^{2} -> x²). Seul un sous-ensemble de lettres a un
     * équivalent Unicode officiel (chiffres, +-=(), et les lettres
     * a,e,h,i,j,k,l,m,n,o,p,r,s,t,u,v,x pour l'indice ; un alphabet quasi
     * complet pour l'exposant) — un caractère hors de cet ensemble (rare :
     * b,c,d,f,g,q,w,y,z en indice) est laissé tel quel plutôt que de casser
     * l'affichage.
     */
    private function versIndiceOuExposant(string $contenu, bool $indice): string
    {
        $indices = [
            '0' => '₀', '1' => '₁', '2' => '₂', '3' => '₃', '4' => '₄', '5' => '₅', '6' => '₆', '7' => '₇', '8' => '₈', '9' => '₉',
            '+' => '₊', '-' => '₋', '=' => '₌', '(' => '₍', ')' => '₎',
            'a' => 'ₐ', 'e' => 'ₑ', 'h' => 'ₕ', 'i' => 'ᵢ', 'j' => 'ⱼ', 'k' => 'ₖ', 'l' => 'ₗ', 'm' => 'ₘ',
            'n' => 'ₙ', 'o' => 'ₒ', 'p' => 'ₚ', 'r' => 'ᵣ', 's' => 'ₛ', 't' => 'ₜ', 'u' => 'ᵤ', 'v' => 'ᵥ', 'x' => 'ₓ',
        ];
        $exposants = [
            '0' => '⁰', '1' => '¹', '2' => '²', '3' => '³', '4' => '⁴', '5' => '⁵', '6' => '⁶', '7' => '⁷', '8' => '⁸', '9' => '⁹',
            '+' => '⁺', '-' => '⁻', '=' => '⁼', '(' => '⁽', ')' => '⁾',
            'a' => 'ᵃ', 'b' => 'ᵇ', 'c' => 'ᶜ', 'd' => 'ᵈ', 'e' => 'ᵉ', 'f' => 'ᶠ', 'g' => 'ᵍ', 'h' => 'ʰ',
            'i' => 'ⁱ', 'j' => 'ʲ', 'k' => 'ᵏ', 'l' => 'ˡ', 'm' => 'ᵐ', 'n' => 'ⁿ', 'o' => 'ᵒ', 'p' => 'ᵖ',
            'r' => 'ʳ', 's' => 'ˢ', 't' => 'ᵗ', 'u' => 'ᵘ', 'v' => 'ᵛ', 'w' => 'ʷ', 'x' => 'ˣ', 'y' => 'ʸ', 'z' => 'ᶻ',
        ];

        $table = $indice ? $indices : $exposants;
        $resultat = '';
        foreach (mb_str_split($contenu) as $car) {
            $resultat .= $table[$car] ?? $car;
        }

        return $resultat;
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
6. Ce texte s'affiche tel quel, SANS aucun moteur de rendu (ni LaTeX, ni
   Markdown) : écris uniquement du texte brut lisible.
   - JAMAIS de notation LaTeX : pas de \( \), \[ \], \frac{}{}, \pm, \sqrt...
     Écris les maths en clair : "u_1 = (2×1 + 3)/5 = 1", "x²", "√3", "±".
   - Pour l'indice d'une suite, utilise la notation avec underscore
     (ex: "u_n", "u_{n+1}", "u_0") — jamais "u_n" collé sans underscore ni
     du LaTeX \(u_n\). Cette notation est automatiquement convertie en
     vrai indice (uₙ) à l'affichage.
   - JAMAIS de mise en forme Markdown : pas d'astérisques **gras** ni
     _italique_, pas de titres avec #, pas de listes à puces avec -.
     Un simple retour à la ligne suffit pour séparer les idées.
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