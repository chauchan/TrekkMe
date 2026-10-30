<?php

namespace App\Http\Controllers;

class Controller
{
    public function index()
    {
        $usuarios = Usuario::with(['excursions'])->latest()->paginate(10);
        return view('usuarios.index', compact('usuarios'));
    }

    public function hola()
    {
        return view('hola');
    }

    public function chao()
        {
            return view('chao');
        }
}
