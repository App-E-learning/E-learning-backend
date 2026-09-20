<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StartGoogleAuthRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Flux Google 100% backend, pensé pour marcher même en Expo Go (pas de
 * deep link, pas de scheme personnalisé) :
 *
 * 1. Le mobile appelle POST /auth/google/start -> reçoit {session_id, auth_url}
 * 2. Le mobile ouvre auth_url dans le navigateur système (juste un onglet,
 *    rien de spécial à l'app)
 * 3. Google redirige vers GET /auth/google/callback (ICI, sur ce serveur)
 *    -> on échange le code contre un jeton, on vérifie l'identité, on
 *    connecte/crée le compte, et on stocke le résultat en cache sous
 *    session_id
 * 4. Le mobile SONDE GET /auth/google/session/{session_id} toutes les ~1,5s
 *    jusqu'à obtenir le résultat, puis ferme l'onglet lui-même
 *
 * Le session_id est généré ici, jamais par le client — évite qu'un attaquant
 * puisse deviner/forcer une valeur.
 */
class GoogleAuthController extends Controller
{
    private const CACHE_PREFIX = 'google_auth_session:';
    private const TTL_MINUTES = 10;

    public function start(StartGoogleAuthRequest $request)
    {
        $sessionId = Str::random(40);

        Cache::put(self::CACHE_PREFIX.$sessionId, [
            'status' => 'pending',
            'matiere_id' => $request->validated('matiere_id'),
        ], now()->addMinutes(self::TTL_MINUTES));

        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $sessionId,
            'prompt' => 'select_account',
        ]);

        return response()->json(['session_id' => $sessionId, 'auth_url' => $authUrl]);
    }

    public function callback(Request $request)
    {
        $sessionId = $request->query('state');
        $cacheKey = self::CACHE_PREFIX.$sessionId;
        $session = Cache::get($cacheKey);

        if (! $session) {
            return $this->pageResultat('Session expirée', "Cette tentative de connexion a expiré. Retourne dans l'application et réessaie.", false);
        }

        if ($request->query('error')) {
            Cache::put($cacheKey, ['status' => 'failed', 'message' => 'Connexion annulée.'], now()->addMinutes(self::TTL_MINUTES));
            return $this->pageResultat('Connexion annulée', 'Tu peux fermer cette fenêtre et retourner dans l\'application.', false);
        }

        try {
            $payload = $this->echangerCodeContreIdentite($request->query('code'));
        } catch (\Throwable $e) {
            Log::error('Google OAuth : échange du code échoué.', ['erreur' => $e->getMessage()]);
            Cache::put($cacheKey, ['status' => 'failed', 'message' => 'La connexion avec Google a échoué.'], now()->addMinutes(self::TTL_MINUTES));
            return $this->pageResultat('Erreur', 'La connexion avec Google a échoué. Retourne dans l\'application et réessaie.', false);
        }

        $user = User::where('google_id', $payload['sub'])->first()
            ?? User::where('email', $payload['email'])->first();

        if ($user) {
            if (! $user->google_id) {
                $user->update(['google_id' => $payload['sub']]);
            }
        } else {
            $matiereId = $session['matiere_id'] ?? null;
            if (! $matiereId) {
                Cache::put($cacheKey, [
                    'status' => 'failed',
                    'message' => 'Aucun compte existant avec ce compte Google. Inscris-toi en choisissant une matière.',
                    'code' => 'matiere_requise',
                ], now()->addMinutes(self::TTL_MINUTES));
                return $this->pageResultat('Inscription requise', "Aucun compte n'existe encore avec ce compte Google. Retourne dans l'application pour terminer ton inscription.", false);
            }

            $user = User::create([
                'name' => $payload['name'] ?? explode('@', $payload['email'])[0],
                'email' => $payload['email'],
                'google_id' => $payload['sub'],
                'role' => 'eleve',
                'niveau' => 'Terminale C',
                'matiere_id' => $matiereId,
                'password' => null,
            ]);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        Cache::put($cacheKey, [
            'status' => 'completed',
            'token' => $token,
            'user' => $user->fresh('matiere')->only([
                'id', 'name', 'email', 'telephone', 'role', 'niveau', 'matiere_id', 'matiere', 'photo_url',
            ]),
        ], now()->addMinutes(self::TTL_MINUTES));

        return $this->pageResultat('Connexion réussie ✓', 'Tu peux fermer cette fenêtre et retourner dans l\'application.', true);
    }

    public function session(string $sessionId)
    {
        $cacheKey = self::CACHE_PREFIX.$sessionId;
        $session = Cache::get($cacheKey);

        if (! $session) {
            return response()->json(['status' => 'pending']);
        }

        // Une fois le résultat consommé (succès ou échec), on l'efface :
        // usage unique, pas de rejeu possible avec le même session_id.
        if ($session['status'] !== 'pending') {
            Cache::forget($cacheKey);
        }

        return response()->json($session);
    }

    /**
     * Échange le code d'autorisation contre les jetons Google, puis décode
     * le id_token reçu. Pas besoin de vérifier sa signature nous-mêmes :
     * on le reçoit directement du endpoint token de Google, authentifié
     * par notre client_secret sur un canal HTTPS — c'est cet échange
     * authentifié qui constitue la vérification.
     */
    private function echangerCodeContreIdentite(?string $code): array
    {
        if (! $code) {
            throw new \RuntimeException("Code d'autorisation manquant.");
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Echange de jeton refusé par Google: '.$response->body());
        }

        $idToken = $response->json('id_token');
        if (! $idToken) {
            throw new \RuntimeException("Pas de id_token dans la réponse Google.");
        }

        // Décodage du payload JWT (2e segment) — base64url -> base64.
        $segments = explode('.', $idToken);
        if (count($segments) !== 3) {
            throw new \RuntimeException('id_token malformé.');
        }

        $payloadJson = base64_decode(strtr($segments[1], '-_', '+/'));
        $payload = json_decode($payloadJson, true);

        if (! $payload || empty($payload['email'])) {
            throw new \RuntimeException('id_token illisible.');
        }

        if (($payload['email_verified'] ?? false) !== true && ($payload['email_verified'] ?? 'false') !== 'true') {
            throw new \RuntimeException('Adresse email Google non vérifiée.');
        }

        return $payload;
    }

    private function pageResultat(string $titre, string $message, bool $succes)
    {
        $couleur = $succes ? '#16a34a' : '#dc2626';

        return response("
            <!DOCTYPE html>
            <html><head><meta name='viewport' content='width=device-width, initial-scale=1'></head>
            <body style='font-family: -apple-system, sans-serif; display:flex; align-items:center; justify-content:center; height:100vh; margin:0; background:#0f172a; color:white; text-align:center; padding:24px;'>
                <div>
                    <h1 style='color:{$couleur}'>{$titre}</h1>
                    <p>{$message}</p>
                </div>
            </body></html>
        ", Response::HTTP_OK)->header('Content-Type', 'text/html');
    }
}