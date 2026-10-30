<?php

namespace App\Http\Controllers;

use App\Models\Usuario;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = Usuario::withCount(['excursiones', 'bitacoras'])
            ->orderBy('nombre')
            ->get();

        return view('usuarios.index', compact('usuarios'));
    }
}
