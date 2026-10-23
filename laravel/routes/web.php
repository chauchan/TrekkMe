<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Models\User;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/hola', [Controller::class, 'hola']);
Route::get('/chao', [Controller::class, 'chao']);
Route::get('/index', [Controller::class, 'index']);

Route::get('/usuario/{id}', function ($id) {
    $usuario = User::find($id);
    return $usuario;
});
