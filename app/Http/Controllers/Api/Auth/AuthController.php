<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'telephone' => $data['telephone'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => 'eleve',
            'niveau' => $data['niveau'],
            'matiere_id' => $data['matiere_id'],
        ]);

        $token = $user->createToken('flutter-app')->plainTextToken;

        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'telephone', 'role', 'niveau', 'matiere_id']),
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $identifiant = $request->validated()['identifiant'];
        $password = $request->validated()['password'];

        $field = filter_var($identifiant, FILTER_VALIDATE_EMAIL) ? 'email' : 'telephone';

        $user = User::where($field, $identifiant)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'identifiant' => ['Identifiants invalides.'],
            ]);
        }

        $token = $user->createToken('flutter-app')->plainTextToken;

        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'telephone', 'role', 'niveau', 'matiere_id']),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    public function logoutAll(Request $request)
    {
        // Révoque tous les tokens de l'utilisateur (ex: perte du téléphone)
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Déconnexion de tous les appareils réussie.']);
    }

    public function refresh(Request $request)
    {
        $user = $request->user();

        // Révoque l'ancien token puis en émet un nouveau
        $request->user()->currentAccessToken()->delete();
        $token = $user->createToken('flutter-app')->plainTextToken;

        return response()->json(['token' => $token]);
    }

    public function me(Request $request)
    {
        return response()->json(
            $request->user()->only(['id', 'name', 'email', 'telephone', 'role', 'niveau', 'matiere_id'])
        );
    }
}