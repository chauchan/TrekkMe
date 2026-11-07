<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Protege el panel web: solo entra quien inició sesión como admin
class ExigirAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('admin')) {
            return redirect('/login');
        }

        return $next($request);
    }
}
