<?php

namespace App\Http\Controllers;

use App\Enums\RolUsuario;
use App\Services\Firestore\BitacoraRepository;
use App\Services\Firestore\CatalogoRepository;
use App\Services\Firestore\ExcursionRepository;
use App\Services\Firestore\UsuarioRepository;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    public function index(UsuarioRepository $usuarios, ExcursionRepository $excursiones, BitacoraRepository $bitacoras, CatalogoRepository $catalogo)
    {
        $casos = collect($excursiones->todas())->countBy('rescatistaId');
        $registros = collect($bitacoras->todas())->countBy('rescatistaId');
        $jurisdicciones = collect($catalogo->jurisdicciones())->pluck('nombre', 'id');

        return view('usuarios.index', [
            'usuarios' => $usuarios->todos(),
            'casos' => $casos,
            'registros' => $registros,
            'jurisdicciones' => $jurisdicciones,
        ]);
    }

    public function create(CatalogoRepository $catalogo)
    {
        return view('usuarios.crear', [
            'roles' => RolUsuario::asignables(),
            'jurisdicciones' => $catalogo->jurisdicciones(),
        ]);
    }

    public function store(Request $request, UsuarioRepository $usuarios, CatalogoRepository $catalogo)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'rol' => ['required', Rule::in(array_column(RolUsuario::asignables(), 'value'))],
            'jurisdiccion_id' => ['nullable', Rule::in(collect($catalogo->jurisdicciones())->pluck('id')->all())],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $rol = RolUsuario::from($datos['rol']);

        if ($rol !== RolUsuario::Excursionista && empty($datos['jurisdiccion_id'])) {
            return back()->withInput()->withErrors(['jurisdiccion_id' => 'Los carabineros y rescatistas necesitan una jurisdicción.']);
        }
        if ($usuarios->porEmail($datos['email'])) {
            return back()->withInput()->withErrors(['email' => 'Ya existe un usuario con ese correo.']);
        }

        $id = $usuarios->crear($datos['nombre'], $datos['email'], $rol, $datos['jurisdiccion_id'] ?? null, $datos['password']);

        return redirect('/usuarios')->with('ok', "Usuario {$id} creado en Firestore.");
    }
}
