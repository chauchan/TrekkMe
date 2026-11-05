<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Uso: ->middleware('rol:carabinero') o 'rol:carabinero,rescatista'
class ExigirRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->attributes->get('usuario');

        if (! $usuario || ! in_array($usuario['rol'], $roles, true)) {
            return response()->json(['message' => 'No tienes acceso a este recurso.'], 403);
        }

        return $next($request);
    }
}
