<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\BossMiddleware;
use App\Http\Middleware\CaisseMiddleware;
use App\Http\Middleware\CaisseControllerMiddleware;
use App\Http\Middleware\ManageInertiaMiddleware; // On importe le nouveau middleware
// use App\Http\Middleware\HandleInertiaRequests; // Plus besoin de l'importer ici directement
use App\Http\Middleware\Mt1Middleware;
use App\Http\Middleware\PoolControleMiddleware;
use App\Http\Middleware\NumerisationMiddleware;
use App\Http\Middleware\RafMiddleware;
use App\Http\Middleware\ArchivesMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        api: __DIR__ . '/../routes/api.php',
        health: '/up',
    )
    // ->withCors() 
    ->withMiddleware(function (Middleware $middleware) {
        // Groupe WEB
        $middleware->web(append: [
            ManageInertiaMiddleware::class, // <--- ON REMPLACE ICI
            \App\Http\Middleware\ValidatePostSize::class,
        ]);

        // Groupe API (Sanctum)
        // statefulApi permet de gérer les sessions pour l'app Desktop
        $middleware->statefulApi();

        // Alias de métier
        $middleware->alias([
            'PoolControle' => PoolControleMiddleware::class,
            'Numerisation' => NumerisationMiddleware::class,
            'Admin' => AdminMiddleware::class,
            'Caisse' => CaisseMiddleware::class,
            'MT1' => Mt1Middleware::class,
            'Boss' => BossMiddleware::class,
            'CaisseController' => CaisseControllerMiddleware::class,
            'Raf' => RafMiddleware::class,
            'Archives' => ArchivesMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
