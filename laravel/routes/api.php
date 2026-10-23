<?php

use App\Http\Controllers\Api\TerritorioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Catálogo de territorio: público por ahora, no tiene datos personales
Route::get('/areas', [TerritorioController::class, 'areas']);
Route::get('/areas/{area}/senderos', [TerritorioController::class, 'senderos'])->whereNumber('area');
