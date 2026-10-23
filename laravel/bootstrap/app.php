<?php

use App\Domain\Shared\Exceptions\ConflictoDeDominio;
use App\Domain\Shared\Exceptions\RecursoNoEncontrado;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Excepciones del dominio → códigos HTTP, para que los casos de uso no conozcan HTTP
        $exceptions->render(fn (RecursoNoEncontrado $e) => response()->json(['message' => $e->getMessage()], 404));
        $exceptions->render(fn (ConflictoDeDominio $e) => response()->json(['message' => $e->getMessage()], 409));
    })->create();
