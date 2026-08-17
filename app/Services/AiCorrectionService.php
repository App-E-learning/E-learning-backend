<?php

namespace App\Services;

use App\Services\AI\ExplicationProviderInterface;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Propose une correction (et, pour un QCM, des options plausibles) quand
 * l'admin n'a pas encore de corrigé sous la main — typique après un import
 * OCR. Réutilise le même fournisseur IA que AiExplicationService (Anthropic
 * ou Gemini selon AI_PROVIDER dans .env), donc aucune nouvelle clé requise.
 *
 * IMPORTANT : ceci ne fait QUE proposer. Rien n'est enregistré ici — c'est
 * à l'admin de relire, corriger si besoin, puis d'enregistrer via le
 * formulaire habituel (PUT /exercices/{id}). Une IA qui se trompe sur un
 * corrigé "officiel" empoisonnerait ensuite les explications données à
 * tous les élèves sur cet exercice : la relecture humaine reste obligatoire.
 */
class AiCorrectionService
{
    public function __construct(private readonly ExplicationProviderInterface $provider)
    {
    }

    /**
     * @param  string[]  $options  Options déjà saisies (QCM), vide sinon.
     * @return array{reponses_correctes: string[], explication_officielle: string, options_proposees: string[]}
     */
    public function proposerCorrection(string $enonce, string $type, array $options = []): array
    {
        $resultat = $this->provider->genererTexte(
            $this->promptSysteme(),
            $this->promptUtilisateur($enonce, $type, $options),
            900,
        );

        return $this->parserReponse($resultat['texte'], $type);
    }

    private function promptSysteme(): string
    {
        return <<<'PROMPT'
Tu es un professeur de mathématiques expérimenté qui prépare des corrigés
pour des élèves de Terminale C au Cameroun. On te donne un énoncé
d'exercice (parfois extrait par OCR, donc potentiellement imparfait).

Résous l'exercice rigoureusement, étape par étape dans ta réflexion, puis
réponds UNIQUEMENT avec un objet JSON valide — aucun texte avant ou après,
aucun bloc de code markdown — au format EXACT suivant :

{"reponses_correctes": ["..."], "explication_officielle": "...", "options_proposees": ["..."]}

Règles :
- "reponses_correctes" : réponse(s) finale(s) COURTES uniquement (un
  nombre, une formule, un mot — jamais une phrase ni un paragraphe). Le
  raisonnement complet va uniquement dans "explication_officielle".
- "explication_officielle" : la méthode de résolution complète mais
  concise (4 à 8 phrases), rédigée pour un lycéen.
- "options_proposees" : UNIQUEMENT si le type d'exercice est "qcm" ET que
  moins de 4 options utilisables sont fournies dans le message : propose
  alors 4 options plausibles au total (la ou les bonnes réponses incluses,
  plus des distracteurs réalistes basés sur des erreurs de calcul
  classiques). Sinon, renvoie un tableau vide [].
- Si l'énoncé contient PLUSIEURS exercices ou questions numérotées
  distinctes (ex: "Exercice 1", "Exercice 2"...), ne traite QUE le tout
  premier exercice/question, et dis-le en une phrase au début de
  "explication_officielle". Ne tente jamais de tout résoudre à la fois.
- Si tu n'es pas certain à 100%, donne quand même ta meilleure estimation
  rigoureuse plutôt que de refuser de répondre — mais ne réponds jamais
  au hasard : pose le raisonnement mentalement avant de conclure.
- N'utilise JAMAIS de notation LaTeX (\( \), \[ \], \frac, \pm, \sqrt...) :
  écris toutes les expressions mathématiques en texte brut lisible (ex:
  "z = 1 + 2i", "x = (-13 ± 3i√3) / 2", "√3", "x²"). Un backslash suivi
  d'un caractère qui n'est pas un échappement JSON valide (\", \\, \/,
  \n...) rend le JSON invalide et fait échouer l'enregistrement.
- IMPORTANT (format JSON) : n'insère JAMAIS de retour à la ligne littéral
  à l'intérieur d'une valeur texte. Écris tout sur une seule ligne par
  valeur (utilise des espaces ou des points-virgules à la place des
  retours à la ligne). Un retour à la ligne brut dans une chaîne rend le
  JSON invalide.
PROMPT;
    }

    private function promptUtilisateur(string $enonce, string $type, array $options): string
    {
        $prompt = "Type d'exercice : {$type}\n\nÉnoncé :\n{$enonce}\n";

        if (! empty($options)) {
            $prompt .= "\nOptions déjà saisies :\n";
            foreach (array_values($options) as $i => $option) {
                $prompt .= chr(65 + $i).") {$option}\n";
            }
        }

        return $prompt;
    }

    /**
     * Découpe le texte brut d'une page scannée contenant plusieurs
     * exercices en blocs distincts, sans rien résoudre — c'est une étape
     * de mise en forme, pas de correction. Chaque bloc est ensuite créé
     * comme une fiche exercice séparée (brouillon), pour que la correction
     * IA et la relecture humaine restent un exercice à la fois.
     *
     * @return array{exercices: array<int, array{titre: string, enonce: string, type_suggere: string}>}
     */
    public function decouperExercices(string $texteComplet): array
    {
        $resultat = $this->provider->genererTexte(
            $this->promptSystemeDecoupage(),
            "Texte brut à découper :\n\n{$texteComplet}",
            2000,
        );

        $donnees = $this->extraireJson($resultat['texte']);

        if (! is_array($donnees) || ! isset($donnees['exercices']) || ! is_array($donnees['exercices'])) {
            Log::warning('IA découpage : JSON inexploitable.', [
                'texte_brut' => mb_substr($resultat['texte'], 0, 2000),
            ]);

            throw new RuntimeException("L'IA n'a pas réussi à découper ce texte. Réessaie, ou découpe-le manuellement.");
        }

        $exercices = collect($donnees['exercices'])
            ->map(fn ($ex) => [
                'titre' => (string) ($ex['titre'] ?? 'Exercice'),
                'enonce' => trim((string) ($ex['enonce'] ?? '')),
                'type_suggere' => in_array($ex['type_suggere'] ?? null, ['qcm', 'numerique', 'texte_court'], true)
                    ? $ex['type_suggere']
                    : 'texte_court',
            ])
            ->filter(fn ($ex) => $ex['enonce'] !== '')
            ->values()
            ->all();

        if (empty($exercices)) {
            throw new RuntimeException("Aucun exercice distinct n'a pu être détecté dans ce texte.");
        }

        return ['exercices' => $exercices];
    }

    private function promptSystemeDecoupage(): string
    {
        return <<<'PROMPT'
Tu reçois le texte brut d'une page d'examen scannée par OCR, contenant
PLUSIEURS exercices ou questions numérotées distinctes (ex: "Exercice 1",
"Exercice 2", "I.", "II."...). Ta tâche est UNIQUEMENT de les séparer,
PAS de les résoudre ni de les corriger.

Réponds UNIQUEMENT avec un objet JSON valide — aucun texte avant ou
après, aucun bloc de code markdown — au format EXACT suivant :

{"exercices": [{"titre": "Exercice 1", "enonce": "...", "type_suggere": "qcm"}]}

Règles :
- Un élément du tableau par exercice détecté, dans l'ordre du document.
- "enonce" : le texte complet de cet exercice (toutes ses sous-questions
  a/b/c incluses), nettoyé des artefacts OCR évidents (mots coupés en fin
  de ligne, espaces en trop) — mais sans reformuler ni résumer le contenu
  mathématique.
- "type_suggere" : "qcm" si l'exercice propose déjà des choix (A/B/C/D),
  "numerique" si la réponse attendue est un nombre ou une expression,
  "texte_court" sinon.
- N'insère jamais de retour à la ligne littéral à l'intérieur d'une
  valeur JSON — utilise des espaces à la place.
- N'utilise JAMAIS de notation LaTeX (\( \), \frac, \pm, \sqrt...) dans
  "enonce" : recopie les expressions mathématiques en texte brut (ex:
  "x²", "√3", "±"), jamais entourées de \( \). Un backslash suivi d'un
  caractère qui n'est pas un échappement JSON valide rend le JSON
  invalide et fait tout échouer.
- S'il n'y a en réalité qu'un seul exercice dans le texte, renvoie un
  tableau avec un seul élément.
PROMPT;
    }

    private function parserReponse(string $texte, string $type): array
    {
        $donnees = $this->extraireJson($texte);

        if (! is_array($donnees) || ! array_key_exists('reponses_correctes', $donnees)) {
            Log::warning('IA correction : JSON inexploitable.', [
                'texte_brut' => mb_substr($texte, 0, 2000),
            ]);

            throw new RuntimeException("L'IA n'a pas renvoyé un JSON exploitable. Réessaie, ou saisis le corrigé manuellement.");
        }

        return [
            'reponses_correctes' => array_values(array_filter(array_map('strval', (array) $donnees['reponses_correctes']))),
            'explication_officielle' => (string) ($donnees['explication_officielle'] ?? ''),
            'options_proposees' => $type === 'qcm'
                ? array_values(array_filter(array_map('strval', (array) ($donnees['options_proposees'] ?? []))))
                : [],
        ];
    }

    /**
     * Essaie plusieurs stratégies pour extraire un objet JSON valide d'une
     * réponse de modèle de langage, qui respecte rarement à 100% une
     * consigne "réponds uniquement en JSON" (préambule, ```json ... ```,
     * phrase de politesse après, etc.).
     */
    private function extraireJson(string $texte): ?array
    {
        $texte = trim($texte);

        // Un modèle de langage insère parfois un vrai saut de ligne à
        // l'intérieur d'une valeur texte, ce qui est invalide en JSON
        // (les chaînes doivent utiliser \n, pas un saut de ligne brut).
        // On neutralise ces caractères de contrôle AVANT chaque tentative
        // de décodage plutôt que de les interdire seulement dans le prompt.
        // On répare aussi les backslashes "orphelins" (notation LaTeX du
        // type \( \), \frac... que le modèle insère parfois malgré la
        // consigne) : \( n'est pas un échappement JSON valide et fait
        // échouer tout le décodage, alors qu'un simple \\( (backslash
        // littéral) est parfaitement valide.
        $assainir = fn (string $s) => $this->repererEchappementsInvalides(preg_replace('/[\x00-\x1F]+/', ' ', $s));

        // 1. Tel quel.
        $donnees = json_decode($assainir($texte), true);
        if (is_array($donnees)) {
            return $donnees;
        }

        // 2. Sans un éventuel entourage ```json ... ``` ou ``` ... ```.
        $sansMarkdown = trim(preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $texte));
        $donnees = json_decode($assainir($sansMarkdown), true);
        if (is_array($donnees)) {
            return $donnees;
        }

        // 3. Extrait le premier bloc { ... } jusqu'au dernier } du texte
        //    (au cas où le modèle a ajouté une phrase avant ou après).
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
     * Double tout backslash qui n'est PAS suivi d'un caractère d'échappement
     * JSON valide (" \ / b f n r t u). Sert de filet de sécurité si le
     * modèle insère quand même de la notation LaTeX (\( \), \frac...)
     * malgré la consigne du prompt : \( devient \\( (backslash littéral
     * suivi d'une parenthèse), ce qui est un JSON valide, au lieu de
     * planter tout le décodage sur un seul caractère mal échappé.
     * Un backslash déjà suivi d'un caractère valide est laissé intact.
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