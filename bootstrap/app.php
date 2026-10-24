<?php

use App\Http\Middleware\EstAdministrateur;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // alias utilise dans routes/api.php
        $middleware->alias([
            'admin' => EstAdministrateur::class,
        ]);

        // L'API est volontairement SANS ETAT : l'authentification passe par un
        // jeton Bearer lu dans l'en-tete Authorization, pas par un cookie de
        // session. On n'active donc pas statefulApi(), qui activerait le mode
        // par cookie et exigerait un jeton CSRF a chaque requete.

        // En production, l'API est derriere le proxy de l'hebergeur. Sans
        // cette ligne, Laravel croirait recevoir des requetes en HTTP et
        // construirait des liens de pagination en http:// que le navigateur
        // refuserait de suivre depuis une page servie en https://.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
