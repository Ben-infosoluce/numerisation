<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NumerisationController;
use App\Http\Controllers\PdcController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



// Route publique pour la connexion
Route::post('/login', [AuthController::class, 'login']);

// Routes protégées par Sanctum
Route::middleware('auth:sanctum')->group(function () {
    // Récupérer l'utilisateur connecté
    Route::get('/user', [AuthController::class, 'user']);

    // Déconnexion
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/numerisation/get/data', [NumerisationController::class, "getNumerisationData"]);
    Route::get('/numerisation/documents/dossier/{id_dossier}', [NumerisationController::class, "listDocumentsforApi"]);
    Route::get('/numerisation/get/dossier/data/{vin}', [NumerisationController::class, "showNumerisationGetDataForApi"]);
    Route::post('/numerisation/ops/save', [NumerisationController::class, "saveOpsNumerisationApi"]);

    // Route::get('/numerisation/documents/dossier/{id_dossier}', [NumerisationController::class, "listDocumentsWithForApi"]);


    Route::post('/fds/ops', [PdcController::class, 'SaveFdsOps']);
    Route::post('/fds/ops/update', [PdcController::class, 'updateFdsOps']);
    Route::post('/fds/ops/update/site', [PdcController::class, 'updateFdsOpsSite']);
});
