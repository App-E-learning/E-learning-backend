<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return response()->json(
            $request->user()->load('matiere')
                ->only(['id', 'name', 'email', 'telephone', 'role', 'niveau', 'matiere_id', 'matiere'])
        );
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json(
            $user->fresh('matiere')->only(['id', 'name', 'email', 'telephone', 'role', 'niveau', 'matiere_id', 'matiere'])
        );
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $user = $request->user();

        if (! Hash::check($request->validated()['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Mot de passe actuel incorrect.'],
            ]);
        }

        $user->update(['password' => Hash::make($request->validated()['password'])]);

        // Sécurité : on révoque les autres tokens après changement de mot de passe
        $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'Mot de passe mis à jour.']);
    }
}