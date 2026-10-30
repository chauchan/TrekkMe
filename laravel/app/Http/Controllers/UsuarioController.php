<?php

namespace App\Http\Controllers;

use App\Enums\RolUsuario;
use App\Models\Usuario;
use App\Services\FirestoreSync;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = Usuario::withCount(['excursiones', 'bitacoras'])
            ->orderBy('nombre')
            ->get();

        return view('usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        return view('usuarios.crear', ['roles' => RolUsuario::cases()]);
    }

    public function store(Request $request, FirestoreSync $firestore)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:usuarios,email'],
            'rol' => ['required', Rule::enum(RolUsuario::class)],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $usuario = Usuario::create($datos);

        [$aviso] = $this->sincronizar(fn () => $firestore->usuario($usuario));

        return redirect('/usuarios')->with('ok', "Usuario {$usuario->id} creado.".$aviso);
    }
}
