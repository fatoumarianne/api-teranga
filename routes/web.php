<?php

use Illuminate\Support\Facades\Route;

/*
 * L'application est une API consommee par le frontend Angular :
 * il n'y a pas de page web servie par Laravel.
 */
Route::get('/', fn () => response()->json([
    'application' => 'API de réservation — Club Sportif Teranga',
    'version' => '1.0',
]));
