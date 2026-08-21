<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterDeviceTokenRequest;
use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DeviceTokenController extends Controller
{
    /**
     * Enregistre (ou réattribue) un token push pour l'utilisateur connecté.
     * `token` est UNIQUE en base : si ce token existait déjà sous un autre
     * compte (déconnexion/reconnexion avec un compte différent sur le même
     * appareil), on le réattribue au lieu d'échouer sur la contrainte
     * unique — l'ancien propriétaire ne doit plus recevoir de push sur cet
     * appareil précis.
     */
    public function store(RegisterDeviceTokenRequest $request)
    {
        $token = DeviceToken::updateOrCreate(
            ['token' => $request->validated('token')],
            ['user_id' => $request->user()->id, 'platform' => $request->validated('platform')]
        );

        return response()->json(['data' => $token], Response::HTTP_CREATED);
    }

    /**
     * Désenregistre un token (à appeler à la déconnexion) pour que cet
     * appareil arrête de recevoir des notifications tant que personne n'y
     * est reconnecté.
     */
    public function destroy(Request $request)
    {
        $request->validate(['token' => ['required', 'string']]);

        DeviceToken::where('user_id', $request->user()->id)
            ->where('token', $request->input('token'))
            ->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}