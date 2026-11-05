<?php

use App\Enums\AccionExcursion;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CarabineroController;
use App\Http\Controllers\RescatistaController;
use Illuminate\Support\Facades\Route;

// Todas las rutas quedan bajo /api/v1
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware('firebase')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/yo', [AuthController::class, 'yo']);

    // Catálogo para los selectores (carabineros y rescatistas)
    Route::get('/senderos', [RescatistaController::class, 'senderos'])->middleware('rol:carabinero,rescatista');

    Route::middleware('rol:rescatista')->prefix('rescatista')->group(function () {
        Route::post('/casos/{id}/solicitar-cierre', [RescatistaController::class, 'solicitarCierre']);

        Route::post('/casos/{casoId}/bitacora', [RescatistaController::class, 'crearBitacora']);
        Route::put('/bitacora/{id}', [RescatistaController::class, 'editarBitacora']);
        Route::delete('/bitacora/{id}', [RescatistaController::class, 'eliminarBitacora']);
    });

    Route::middleware('rol:carabinero')->prefix('carabinero')->group(function () {
        Route::get('/rescatistas', [CarabineroController::class, 'rescatistas']);

        Route::post('/excursiones/{id}/{accion}', [CarabineroController::class, 'transicion'])
            ->whereIn('accion', [
                AccionExcursion::Escalar->value,
                AccionExcursion::Cerrar->value,
                AccionExcursion::ConfirmarCierre->value,
                AccionExcursion::RechazarCierre->value,
            ]);
    });
});
