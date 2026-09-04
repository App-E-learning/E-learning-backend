<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadProfilePhotoRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Séparé de ProfileController (non fourni lors de cette modification) pour
 * rester une simple ADDITION au projet, sans risquer de casser un fichier
 * existant qu'on n'a pas pu relire avant d'éditer. Peut être fusionné dans
 * ProfileController plus tard si souhaité.
 */
class ProfilePhotoController extends Controller
{
    public function store(UploadProfilePhotoRequest $request)
    {
        $user = $request->user();

        // Remplace l'ancienne photo si elle existe, pour ne pas accumuler
        // des fichiers orphelins à chaque changement de photo.
        if ($user->photo_path) {
            Storage::disk('public')->delete($user->photo_path);
        }

        $chemin = $request->file('photo')->store('photos', 'public');
        $user->update(['photo_path' => $chemin]);

        return response()->json(['photo_url' => $user->fresh()->photo_url], Response::HTTP_CREATED);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        if ($user->photo_path) {
            Storage::disk('public')->delete($user->photo_path);
            $user->update(['photo_path' => null]);
        }

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}