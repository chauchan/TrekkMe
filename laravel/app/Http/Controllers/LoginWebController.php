<?php

namespace App\Http\Controllers;

use App\Enums\RolUsuario;
use App\Services\Firestore\UsuarioRepository;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoginWebController extends Controller
{
    public function create(Request $request)
    {
        if ($request->session()->has('admin')) {
            return redirect('/usuarios');
        }

        return view('login');
    }

    public function store(Request $request, UsuarioRepository $usuarios)
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $usuario = $usuarios->autenticar($datos['email'], $datos['password']);

        // Mismo mensaje para correo inexistente, clave mala o rol distinto de admin
        if (! $usuario || $usuario['rol'] !== RolUsuario::Admin) {
            throw ValidationException::withMessages(['email' => 'Correo o contraseña incorrectos.']);
        }

        $request->session()->regenerate();
        $request->session()->put('admin', ['id' => $usuario['id'], 'nombre' => $usuario['nombre']]);

        return redirect()->intended('/usuarios');
    }

    public function destroy(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
