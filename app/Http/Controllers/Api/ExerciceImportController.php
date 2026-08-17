<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportAutoRequest;
use App\Http\Requests\ImportExerciceRequest;
use App\Http\Requests\StoreExerciceBrouillonRequest;
use App\Http\Resources\ExerciceResource;
use App\Models\Exercice;
use App\Services\AiCorrectionService;
use App\Services\OcrService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExerciceImportController extends Controller
{
    public function __construct(
        private OcrService $ocrService,
        private AiCorrectionService $aiCorrectionService,
    ) {}

    /**
     * Pipeline complet en une seule requête : OCR -> découpage IA (si la
     * page contient plusieurs exercices) -> proposition de corrigé IA pour
     * CHAQUE exercice détecté -> création en base. C'est la version
     * "vraiment automatique" demandée : l'admin envoie une photo/PDF de
     * page d'examen et récupère directement des fiches d'exercices avec
     * énoncé + options + corrigé + explication déjà remplis, au lieu
     * d'enchaîner manuellement store() -> decouper() -> proposer() ->
     * storeTexte() pour chacun.
     *
     * Reste en statut "brouillon" par défaut (voir ImportAutoRequest) :
     * l'admin doit relire au moins une fois avant publication, car l'IA
     * peut se tromper sur le contenu source (contrairement à la
     * génération pure de QCM où le corrigé vient de la même IA qui a
     * écrit l'énoncé — voir QcmGenerationController).
     */
    public function storeAuto(ImportAutoRequest $request)
    {
        $ocr = $this->ocrService->extraireTexte(
            $request->file('fichier'),
            $request->boolean('forcer_vision_ia')
        );

        $texte = trim($ocr['texte']);
        if ($texte === '') {
            return response()->json([
                'message' => "Aucun texte n'a pu être extrait de ce fichier.",
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Découpage : une page peut contenir un seul exercice ou plusieurs
        // (numérotés "Exercice 1", "Exercice 2"...). On tente toujours le
        // découpage IA ; s'il échoue ou ne détecte rien, on retombe sur le
        // texte entier comme un unique exercice plutôt que d'échouer.
        try {
            $blocs = $this->aiCorrectionService->decouperExercices($texte)['exercices'];
        } catch (\Throwable $e) {
            Log::info('Découpage IA sans résultat exploitable, traitement en un seul bloc.', ['erreur' => $e->getMessage()]);
            $blocs = [['titre' => 'Exercice', 'enonce' => $texte, 'type_suggere' => 'texte_court']];
        }

        $chapitreId = $request->validated('chapitre_id');
        $difficulteDemandee = $request->validated('difficulte');
        $anneeOrigine = $request->validated('annee_origine');
        $autoValider = $request->boolean('auto_valider', false);

        $exercicesCrees = [];
        $echecs = [];

        foreach ($blocs as $bloc) {
            try {
                $proposition = $this->aiCorrectionService->proposerCorrection(
                    $bloc['enonce'],
                    $bloc['type_suggere'],
                );

                $options = $bloc['type_suggere'] === 'qcm'
                    ? (! empty($proposition['options_proposees']) ? $proposition['options_proposees'] : null)
                    : null;

                $exercice = DB::transaction(function () use ($chapitreId, $bloc, $options, $difficulteDemandee, $anneeOrigine, $autoValider, $proposition, $texte) {
                    $exercice = Exercice::create([
                        'chapitre_id' => $chapitreId,
                        'enonce' => $bloc['enonce'],
                        'type' => $bloc['type_suggere'],
                        'options' => $options,
                        'difficulte' => $difficulteDemandee ?? 2,
                        'annee_origine' => $anneeOrigine,
                        'origine' => 'import_ocr',
                        'statut' => $autoValider ? 'valide' : 'brouillon',
                        'texte_ocr_brut' => $texte,
                    ]);

                    $exercice->corrige()->create([
                        'reponses_correctes' => $proposition['reponses_correctes'],
                        'explication_officielle' => $proposition['explication_officielle'],
                    ]);

                    return $exercice;
                });

                $exercicesCrees[] = $exercice;
            } catch (\Throwable $e) {
                // Un bloc qui échoue (proposition IA inexploitable) ne doit
                // pas faire perdre les autres blocs déjà traités.
                Log::warning('Bloc ignoré lors de l\'import auto.', ['erreur' => $e->getMessage(), 'enonce' => mb_substr($bloc['enonce'], 0, 200)]);
                $echecs[] = $bloc['enonce'];
            }
        }

        if (empty($exercicesCrees)) {
            return response()->json([
                'message' => "Aucun exercice n'a pu être créé automatiquement à partir de ce fichier.",
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'message' => count($exercicesCrees).' exercice(s) créé(s)'.($autoValider ? ' et publié(s)' : ' en brouillon, à relire avant publication').'.'.
                (! empty($echecs) ? ' '.count($echecs)." bloc(s) n'ont pas pu être traités automatiquement." : ''),
            'ocr_methode' => $ocr['methode'],
            // Le corrigé est toujours inclus ici (contrairement à
            // ExerciceResource utilisé ailleurs, qui le masque par défaut) :
            // l'admin doit pouvoir relire immédiatement ce qui vient d'être
            // généré, sans requête supplémentaire.
            'data' => collect($exercicesCrees)->map(fn (Exercice $e) => [
                'id' => $e->id,
                'chapitre_id' => $e->chapitre_id,
                'enonce' => $e->enonce,
                'type' => $e->type,
                'options' => $e->options,
                'difficulte' => $e->difficulte,
                'statut' => $e->statut,
                'reponses_correctes' => $e->corrige->reponses_correctes,
                'explication_officielle' => $e->corrige->explication_officielle,
            ]),
        ], Response::HTTP_CREATED);
    }

    /**
     * Importe un exercice digitalisé (image ou PDF scanné) via OCR.
     * L'exercice est créé en statut "brouillon" : il n'est jamais visible
     * côté élève tant qu'un admin ne l'a pas relu et validé (voir
     * Exercice::scopeDisponibles). Le corrigé n'est PAS créé automatiquement
     * — il doit être complété manuellement après relecture, l'OCR ne pouvant
     * pas fiablement distinguer énoncé et réponse correcte.
     *
     * Conservée pour l'import assisté pas-à-pas ; pour le flux automatique
     * en une requête, voir storeAuto() ci-dessus.
     */
    public function store(ImportExerciceRequest $request)
    {
        $resultat = $this->ocrService->extraireTexte(
            $request->file('fichier'),
            $request->boolean('forcer_vision_ia')
        );

        $exercice = Exercice::create([
            'chapitre_id' => $request->validated('chapitre_id'),
            'enonce' => $resultat['texte'],
            'type' => $request->validated('type'),
            'difficulte' => $request->validated('difficulte'),
            'annee_origine' => $request->validated('annee_origine'),
            'origine' => 'import_ocr',
            'statut' => 'brouillon',
            'texte_ocr_brut' => $resultat['texte'],
        ]);

        return (new ExerciceResource($exercice->load('chapitre')))
            ->additional(['ocr_methode' => $resultat['methode']])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Crée un brouillon à partir d'un texte déjà disponible (ex: un bloc
     * issu du découpage IA d'une page à plusieurs exercices), sans passer
     * par l'OCR. Même logique que store() ci-dessus : statut "brouillon",
     * pas de corrigé exigé à la création — à compléter avant validation.
     */
    public function storeTexte(StoreExerciceBrouillonRequest $request)
    {
        $exercice = Exercice::create([
            'chapitre_id' => $request->validated('chapitre_id'),
            'enonce' => $request->validated('enonce'),
            'type' => $request->validated('type'),
            'options' => $request->validated('options', []) ?: null,
            'difficulte' => $request->validated('difficulte'),
            'annee_origine' => $request->validated('annee_origine'),
            'origine' => 'import_ocr',
            'statut' => 'brouillon',
            'texte_ocr_brut' => $request->validated('enonce'),
        ]);

        return (new ExerciceResource($exercice->load('chapitre')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Liste les exercices en attente de validation admin (brouillons OCR).
     */
    public function brouillons()
    {
        $exercices = Exercice::with('chapitre')->brouillons()->orderByDesc('id')->paginate(20);

        return ExerciceResource::collection($exercices);
    }

    /**
     * Valide un brouillon : le rend visible côté élève. À appeler une fois
     * que l'admin a relu/corrigé l'énoncé (via PUT /exercices/{id}) et
     * complété le corrigé.
     */
    public function valider(Exercice $exercice)
    {
        if (! $exercice->corrige) {
            return response()->json([
                'message' => 'Impossible de valider : cet exercice n\'a pas encore de corrigé associé.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $exercice->update(['statut' => 'valide']);

        return new ExerciceResource($exercice->load('chapitre', 'corrige'));
    }
}