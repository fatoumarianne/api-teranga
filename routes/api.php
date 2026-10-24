<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MembreController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RessourceController;
use App\Http\Controllers\StatistiqueController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes de l'API — Club Sportif Teranga
|--------------------------------------------------------------------------
|
| Trois niveaux d'acces :
|   - publiques            : inscription et connexion
|   - auth:sanctum         : tout utilisateur authentifie (membre ou admin)
|   - auth:sanctum + admin : administrateur uniquement
|
*/

// ------------------------------------------------------------- publiques

Route::post('/inscription', [AuthController::class, 'inscription']);
Route::post('/connexion', [AuthController::class, 'connexion']);

// ------------------------------------------- utilisateur authentifie

Route::middleware('auth:sanctum')->group(function () {

    // compte
    Route::get('/profil', [AuthController::class, 'profil']);
    Route::put('/profil', [AuthController::class, 'majProfil']);
    Route::post('/deconnexion', [AuthController::class, 'deconnexion']);

    // consultation des ressources
    Route::get('/ressources', [RessourceController::class, 'index']);
    Route::get('/ressources/{ressource}', [RessourceController::class, 'show']);

    // reservations
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::get('/reservations/calendrier', [ReservationController::class, 'calendrier']);
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::patch('/reservations/{reservation}/annuler', [ReservationController::class, 'annuler']);

    // notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/non-lues', [NotificationController::class, 'nonLues']);
    Route::patch('/notifications/{notification}/lue', [NotificationController::class, 'marquerLue']);
    Route::patch('/notifications/tout-lire', [NotificationController::class, 'toutMarquerLues']);

    // ------------------------------------------------ administrateur

    Route::middleware('admin')->group(function () {

        // gestion des ressources
        Route::post('/ressources', [RessourceController::class, 'store']);
        Route::put('/ressources/{ressource}', [RessourceController::class, 'update']);
        Route::delete('/ressources/{ressource}', [RessourceController::class, 'destroy']);

        // traitement des demandes
        Route::patch('/reservations/{reservation}/valider', [ReservationController::class, 'valider']);
        Route::patch('/reservations/{reservation}/refuser', [ReservationController::class, 'refuser']);

        // comptes des membres
        Route::get('/membres', [MembreController::class, 'index']);
        Route::get('/membres/{membre}', [MembreController::class, 'show']);
        Route::post('/membres', [MembreController::class, 'store']);
        Route::put('/membres/{membre}', [MembreController::class, 'update']);
        Route::patch('/membres/{membre}/statut', [MembreController::class, 'basculerStatut']);

        // tableau de bord
        Route::get('/statistiques', [StatistiqueController::class, 'tableauDeBord']);
    });
});
