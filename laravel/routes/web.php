<?php

use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\ExcursionController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/usuarios', [UsuarioController::class, 'index']);
Route::get('/usuarios/crear', [UsuarioController::class, 'create']);
Route::post('/usuarios', [UsuarioController::class, 'store']);

Route::get('/excursiones', [ExcursionController::class, 'index']);
Route::get('/excursiones/crear', [ExcursionController::class, 'create']);
Route::post('/excursiones', [ExcursionController::class, 'store']);

Route::get('/bitacoras', [BitacoraController::class, 'index']);
Route::get('/bitacoras/crear', [BitacoraController::class, 'create']);
Route::post('/bitacoras', [BitacoraController::class, 'store']);
