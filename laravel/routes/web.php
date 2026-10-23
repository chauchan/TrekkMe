<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/hola', [Controller::class, 'hola']);
Route::get('/chao', [Controller::class, 'chao']);