<?php

namespace App\Http\Controllers;

use App\Enums\RolUsuario;
use App\Models\Bitacora;
use App\Models\Excursion;
use App\Models\Usuario;
use App\Services\FirestoreSync;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BitacoraController extends Controller
{
    public function index()
    {
        $bitacoras = Bitacora::with(['excursion', 'rescatista'])->orderByDesc('registrado_at')->get();

        return view('bitacoras.index', compact('bitacoras'));
    }

    public function create()
    {
        return view('bitacoras.crear', [
            'excursiones' => Excursion::orderBy('id')->get(),
            'rescatistas' => Usuario::where('rol', RolUsuario::Rescatista)->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request, FirestoreSync $firestore)
    {
        $datos = $request->validate([
            'excursion_id' => ['required', 'exists:excursions,id'],
            'rescatista_id' => ['required', Rule::exists('usuarios', 'id')->where('rol', RolUsuario::Rescatista->value)],
            'ruta' => ['required', 'string', 'max:255'],
            'hora_inicio' => ['required', 'date'],
            'hora_fin' => ['nullable', 'date', 'after:hora_inicio'],
            'texto' => ['required', 'string'],
        ]);

        $bitacora = Bitacora::create([
            ...$datos,
            'registrado_at' => now(),
            'version' => 1,
        ]);

        [$aviso] = $this->sincronizar(fn () => $firestore->bitacora($bitacora));

        return redirect('/bitacoras')->with('ok', "Registro {$bitacora->id} creado.".$aviso);
    }
}
