<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protège les pages Blade du back-office (/admin/*) : distinct du
 * middleware 'role' (qui protège l'API pour les clients Sanctum).
 * Utilise le guard de session 'web' classique, adapté à des pages
 * servies et rechargées par le navigateur (pas un client API pur).
 */
class EnsureAdminWeb
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('web')->check()) {
            return redirect()->route('admin.login');
        }

        if (Auth::user()->role !== 'admin') {
            Auth::guard('web')->logout();
            $request->session()->invalidate();

            return redirect()->route('admin.login')
                ->withErrors(['identifiant' => 'Accès réservé aux administrateurs.']);
        }

        return $next($request);
    }
}