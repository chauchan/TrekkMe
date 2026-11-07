<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Symfony\Component\HttpFoundation\Response;

// Exige "Authorization: Bearer <ID token de Firebase>". El token lo emite Firebase al iniciar sesión
// con el token personalizado de /login, y trae el rol y la jurisdicción como claims.
class AutenticarConFirebase
{
    public function __construct(private FirebaseAuth $auth) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return $this->sinSesion();
        }

        try {
            $verificado = $this->auth->verifyIdToken($token);
        } catch (\Throwable) {
            return $this->sinSesion();
        }

        $claims = $verificado->claims();

        $request->attributes->set('usuario', [
            'id' => (string) $claims->get('sub'),
            'rol' => $claims->get('rol'),
            'jurisdiccionId' => $claims->get('jurisdiccionId') ?: null,
        ]);

        return $next($request);
    }

    private function sinSesion(): Response
    {
        return response()->json(['message' => 'Tu sesión expiró. Vuelve a iniciar sesión.'], 401);
    }
}
