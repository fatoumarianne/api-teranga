<?php

/*
 * Autorisations d'origine croisee.
 *
 * Le frontend Angular (http://localhost:4200 en developpement) et l'API
 * (http://localhost:8000) sont sur des origines differentes : sans cette
 * configuration, le navigateur bloquerait les requetes du frontend.
 */
return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:4200'),
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
