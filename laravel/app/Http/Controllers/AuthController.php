<?php

namespace App\Http\Controllers;

use App\Enums\RolUsuario;
use App\Services\Firestore\UsuarioRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;

class AuthController extends Controller
{
    // Valida correo y contraseña y entrega un token de Firebase. La app lo canjea con signInWithCustomToken
    // y desde entonces usa el ID token de Firebase tanto para Firestore como para esta API.
    public function login(Request $request, UsuarioRepository $usuarios, FirebaseAuth $firebase): JsonResponse
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $usuario = $usuarios->autenticar($datos['email'], $datos['password']);

        if (! $usuario) {
            // 422 y no 401: un 401 haría que la app crea que expiró una sesión
            throw ValidationException::withMessages(['email' => 'Correo o contraseña incorrectos.']);
        }

        // Las apps móviles son para carabineros y rescatistas (Ionic) y excursionistas (Flutter)
        if (! in_array($usuario['rol'], [RolUsuario::Carabinero, RolUsuario::Rescatista, RolUsuario::Excursionista], true)) {
            return response()->json(['message' => 'Este perfil no tiene acceso a la aplicación.'], 403);
        }

        $token = $firebase->createCustomToken($usuario['id'], [
            'rol' => $usuario['rol']->value,
            'jurisdiccionId' => $usuario['jurisdiccionId'],
        ]);

        return response()->json(['data' => [
            'firebase_token' => $token->toString(),
            'perfil' => $usuario['rol']->value,
            'usuario_id' => $usuario['id'],
            'nombre' => $usuario['nombre'],
            'jurisdiccion_id' => $usuario['jurisdiccionId'] ?: null,
        ]]);
    }

    // No hay sesión en el servidor que cerrar: la app cierra su sesión de Firebase. Existe para que el contrato esté completo.
    public function logout(): JsonResponse
    {
        return response()->json(null, 204);
    }

    // Quién soy según el token (sirve para probar que la sesión funciona)
    public function yo(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->attributes->get('usuario')]);
    }
}
