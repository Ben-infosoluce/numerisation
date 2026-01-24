<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ManageInertiaMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Si c'est l'application Desktop (NativePHP) qui demande le cookie CSRF
        // ou une route API, on saute Inertia pour avoir une réponse "pure" (JSON/Cookie)
        if ($request->is('sanctum/csrf-cookie') || $request->is('api/*')) {
            return $next($request);
        }

        // 2. Sinon (Navigateur Web classique), on applique Inertia normalement
        return app(\App\Http\Middleware\HandleInertiaRequests::class)->handle($request, $next);
    }
}
