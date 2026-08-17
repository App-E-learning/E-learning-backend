<?php

namespace App\Services;

use App\Services\AI\ExplicationProviderInterface;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Génère un lot de QCM inédits pour un chapitre donné (pas d'énoncé source
 * — contrairement à AiCorrectionService qui corrige un énoncé déjà écrit).
 * La difficulté monte progressivement sur le lot (ex: 5 questions => 1,1,2,3,4
 * en gros), pour constituer une vraie séance d'entraînement progressive.
 *
 * Réutilise le même fournisseur IA que les autres services (AI_PROVIDER),
 * et le même schéma de sortie que AiCorrectionService pour rester cohérent
 * avec le reste du back-office.
 */
class AiQcmGenerationService
{
    public function __construct(private readonly ExplicationProviderInterface $provider)
    {
    }

    /**
     * @return array<int, array{
     *   enonce: string, options: string[], reponses_correctes: string[],
     *   explication_officielle: string, difficulte: int
     * }>
     */
    public function genererPourChapitre(
        string $titreChapitre,
        ?string $descriptionChapitre,
        int $nombre,
        int $difficulteDepart,
    ): array {
        $resultat = $this->provider->genererTexte(
            $this->promptSysteme(),
            $this->promptUtilisateur($titreChapitre, $descriptionChapitre, $nombre, $difficulteDepart),
            $this->budgetTokens($nombre),
        );

        return $this->parserReponse($resultat['texte'], $nombre, $difficulteDepart);
    }

    /**
     * ~350 tokens couvrent confortablement un énoncé + 4 options + une
     * explication de 3-6 phrases pour UNE question ; on prévoit une marge
     * (400) plus un forfait fixe pour l'enrobage JSON, borné à 8192 (limite
     * de sortie usuelle des modèles utilisés ici). Un budget insuffisant
     * tronque le JSON en plein milieu — voir le bug corrigé en prod où 600
     * tokens (valeur historique pensée pour une explication unique)
     * coupaient toute génération au-delà de ~2 questions.
     */
    private function budgetTokens(int $nombre): int
    {
        return min(8192, 500 + $nombre * 400);
    }

    private function promptSysteme(): string
    {
        return <<<'PROMPT'
Tu es un professeur de mathématiques expérimenté qui conçoit des QCM
d'entraînement inédits pour des élèves de Terminale C au Cameroun,
conformes au programme officiel camerounais.

Réponds UNIQUEMENT avec un objet JSON valide — aucun texte avant ou après,
aucun bloc de code markdown — au format EXACT suivant :

{"questions": [{"enonce": "...", "options": ["...", "...", "...", "..."], "reponses_correctes": ["..."], "explication_officielle": "..."}]}

Règles :
- Exactement le nombre de questions demandé, dans l'ordre du plus facile
  au plus difficile (difficulté strictement croissante ou constante d'une
  question à l'autre, jamais décroissante).
- "options" : toujours exactement 4 propositions plausibles, dont une
  seule correcte (QCM à réponse unique). Les distracteurs doivent
  correspondre à des erreurs de raisonnement classiques (erreur de signe,
  formule confondue, étourderie de calcul...), jamais des propositions
  absurdes faciles à écarter sans réfléchir.
- "reponses_correctes" : tableau contenant l'unique bonne option, recopiée
  EXACTEMENT comme dans "options" (même texte, même casse).
- "explication_officielle" : méthode de résolution complète mais concise
  (3 à 6 phrases), rédigée pour un lycéen.
- Chaque question doit être autonome (pas de référence à une question
  précédente) et strictement dans le thème du chapitre donné.
- N'invente aucune donnée numérique aberrante ; les calculs doivent être
  exacts et vérifiables.
- N'insère JAMAIS de retour à la ligne littéral à l'intérieur d'une valeur
  texte JSON — utilise des espaces à la place.
- N'utilise JAMAIS de notation LaTeX (\( \), \[ \], \frac, \pm, \sqrt...) :
  écris toutes les expressions mathématiques en texte brut lisible (ex:
  "x²", "√3", "±", "(3x-2)/5"). Un backslash suivi d'un caractère qui
  n'est pas un échappement JSON valide (\", \\, \/, \n...) rend le JSON
  invalide et fait échouer toute la génération.
PROMPT;
    }

    private function promptUtilisateur(
        string $titreChapitre,
        ?string $descriptionChapitre,
        int $nombre,
        int $difficulteDepart,
    ): string {
        $prompt = "Chapitre : {$titreChapitre}\n";

        if (! empty($descriptionChapitre)) {
            $prompt .= "Description du chapitre : {$descriptionChapitre}\n";
        }

        $prompt .= "\nGénère {$nombre} question(s) QCM sur ce chapitre.\n";
        $prompt .= "Niveau de difficulté de départ (échelle 1 à 5) : {$difficulteDepart}. ";
        $prompt .= "Fais monter la difficulté progressivement jusqu'à la dernière question, sans dépasser 5.";

        return $prompt;
    }

    private function parserReponse(string $texte, int $nombreAttendu, int $difficulteDepart): array
    {
        $donnees = $this->extraireJson($texte);

        if (! is_array($donnees) || ! isset($donnees['questions']) || ! is_array($donnees['questions'])) {
            // Dernier recours avant d'abandonner : la réponse peut être un
            // JSON tronqué en plein milieu (budget de tokens insuffisant,
            // coupure réseau...). On essaie de récupérer les objets
            // question COMPLETS déjà écrits avant la coupure plutôt que de
            // perdre toute la génération pour une seule question inachevée.
            $questionsRecuperees = $this->recupererQuestionsPartielles($texte);
            if (! empty($questionsRecuperees)) {
                Log::warning('IA génération QCM : JSON tronqué, récupération partielle.', [
                    'questions_recuperees' => count($questionsRecuperees),
                    'questions_demandees' => $nombreAttendu,
                ]);
                $donnees = ['questions' => $questionsRecuperees];
            } else {
                Log::warning('IA génération QCM : JSON inexploitable.', [
                    'texte_brut' => mb_substr($texte, 0, 2000),
                ]);

                throw new RuntimeException("L'IA n'a pas renvoyé un JSON exploitable. Réessaie, ou demande moins de questions à la fois.");
            }
        }

        $questions = collect($donnees['questions'])
            ->map(fn ($q) => [
                'enonce' => trim((string) ($q['enonce'] ?? '')),
                'options' => array_values(array_filter(array_map('strval', (array) ($q['options'] ?? [])))),
                'reponses_correctes' => array_values(array_filter(array_map('strval', (array) ($q['reponses_correctes'] ?? [])))),
                'explication_officielle' => (string) ($q['explication_officielle'] ?? ''),
            ])
            // Un QCM inutilisable (énoncé vide, pas 4 options, ou bonne
            // réponse qui ne correspond à aucune option) est écarté plutôt
            // que créé en base à moitié cassé.
            ->filter(fn ($q) => $q['enonce'] !== ''
                && count($q['options']) === 4
                && count($q['reponses_correctes']) >= 1
                && collect($q['reponses_correctes'])->every(fn ($r) => in_array($r, $q['options'], true))
            )
            ->values();

        if ($questions->isEmpty()) {
            throw new RuntimeException("Aucun QCM exploitable n'a été généré. Réessaie.");
        }

        // Difficulté croissante répartie sur les questions effectivement
        // exploitables (bornée à 5, l'échelle utilisée partout ailleurs).
        $total = $questions->count();
        return $questions->map(function ($q, $i) use ($total, $difficulteDepart) {
            $progression = $total > 1 ? (int) round($i * (5 - $difficulteDepart) / ($total - 1)) : 0;
            $q['difficulte'] = min(5, max(1, $difficulteDepart + $progression));

            return $q;
        })->all();
    }

    /**
     * Extrait, un par un, chaque objet JSON complet `{...}` trouvé dans le
     * texte brut (en comptant les accolades pour respecter l'imbrication),
     * et ne garde que ceux qui parsent individuellement en JSON valide.
     * Utilisé uniquement quand le document entier ne parse pas (coupure
     * en plein milieu d'une question) — les questions déjà terminées avant
     * la coupure restent exploitables.
     */
    private function recupererQuestionsPartielles(string $texte): array
    {
        $objets = [];
        $profondeur = 0;
        $debut = null;

        for ($i = 0; $i < mb_strlen($texte); $i++) {
            $car = mb_substr($texte, $i, 1);

            if ($car === '{') {
                if ($profondeur === 0) {
                    $debut = $i;
                }
                $profondeur++;
            } elseif ($car === '}') {
                $profondeur--;
                if ($profondeur === 0 && $debut !== null) {
                    $bloc = mb_substr($texte, $debut, $i - $debut + 1);
                    $decode = json_decode($this->repererEchappementsInvalides(preg_replace('/[\x00-\x1F]+/', ' ', $bloc)), true);
                    // On ne garde que des objets qui ressemblent à une
                    // question (pas l'objet englobant {"questions": [...]})
                    if (is_array($decode) && isset($decode['enonce'])) {
                        $objets[] = $decode;
                    }
                    $debut = null;
                }
            }
        }

        return $objets;
    }

    /**
     * Même stratégie d'extraction tolérante que AiCorrectionService
     * (préambule, ```json ... ```, etc.) — dupliquée volontairement pour
     * garder chaque service indépendant plutôt que de créer un couplage
     * pour quelques lignes partagées.
     */
    private function extraireJson(string $texte): ?array
    {
        $texte = trim($texte);
        // Neutralise les sauts de ligne bruts ET répare les backslashes
        // "orphelins" (notation LaTeX \( \), \frac... invalide en JSON) —
        // voir AiCorrectionService::repererEchappementsInvalides pour le détail.
        $assainir = fn (string $s) => $this->repererEchappementsInvalides(preg_replace('/[\x00-\x1F]+/', ' ', $s));

        $donnees = json_decode($assainir($texte), true);
        if (is_array($donnees)) {
            return $donnees;
        }

        $sansMarkdown = trim(preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $texte));
        $donnees = json_decode($assainir($sansMarkdown), true);
        if (is_array($donnees)) {
            return $donnees;
        }

        $debut = strpos($texte, '{');
        $fin = strrpos($texte, '}');
        if ($debut !== false && $fin !== false && $fin > $debut) {
            $donnees = json_decode($assainir(substr($texte, $debut, $fin - $debut + 1)), true);
            if (is_array($donnees)) {
                return $donnees;
            }
        }

        return null;
    }

    /**
     * Voir AiCorrectionService::repererEchappementsInvalides — même logique,
     * dupliquée volontairement pour garder chaque service indépendant.
     */
    private function repererEchappementsInvalides(string $texte): string
    {
        $valides = ['"', '\\', '/', 'b', 'f', 'n', 'r', 't', 'u'];
        $longueur = mb_strlen($texte);
        $resultat = '';

        for ($i = 0; $i < $longueur; $i++) {
            $car = mb_substr($texte, $i, 1);

            if ($car !== '\\') {
                $resultat .= $car;
                continue;
            }

            $suivant = $i + 1 < $longueur ? mb_substr($texte, $i + 1, 1) : '';
            $resultat .= in_array($suivant, $valides, true) ? '\\' : '\\\\';
        }

        return $resultat;
    }
}