<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;

class Handler extends ExceptionHandler
{
    /**
     * Les types d'exceptions qui ne doivent pas être rapportés.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * Les champs sensibles qui ne doivent jamais être flashés pour la session lors de la validation d'erreurs.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Signaler ou enregistrer une exception.
     */
    public function report(Throwable $exception): void
    {
        parent::report($exception);
    }

    /**
     * Rendre une exception en réponse HTTP.
     */
    public function render($request, Throwable $exception)
    {
        // Gestion des erreurs de validation
        if ($exception instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $exception->errors(),
            ], 422);
        }

        return parent::render($request, $exception);
    }

    /**
     * Gérer les utilisateurs non authentifiés.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // Si c'est une requête API ou que le client attend du JSON
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }

        // Sinon comportement web standard
        return redirect()->guest(route('login'));
    }
}
