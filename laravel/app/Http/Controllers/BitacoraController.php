<?php

namespace App\Http\Controllers;

use App\Services\Firestore\BitacoraRepository;
use App\Services\Firestore\CatalogoRepository;
use App\Services\Firestore\ExcursionRepository;
use App\Services\Firestore\UsuarioRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BitacoraController extends Controller
{
    public function index(BitacoraRepository $bitacoras, ExcursionRepository $excursiones, UsuarioRepository $usuarios)
    {
        return view('bitacoras.index', [
            'bitacoras' => $bitacoras->todas(),
            'excursionistas' => collect($excursiones->todas())->pluck('excursionista', 'id'),
            'nombres' => collect($usuarios->todos())->pluck('nombre', 'id'),
        ]);
    }

    public function create(ExcursionRepository $excursiones, UsuarioRepository $usuarios, CatalogoRepository $catalogo)
    {
        return view('bitacoras.crear', [
            'excursiones' => $excursiones->todas(),
            'rescatistas' => $usuarios->rescatistas(),
            'senderos' => $catalogo->senderos(),
        ]);
    }

    public function store(Request $request, BitacoraRepository $bitacoras, ExcursionRepository $excursiones, UsuarioRepository $usuarios, CatalogoRepository $catalogo)
    {
        $datos = $request->validate([
            'excursion_id' => ['required', 'string'],
            'rescatista_id' => ['required', 'string'],
            'sendero_id' => ['nullable', 'string'],
            'hora_inicio' => ['required', 'date'],
            'hora_fin' => ['nullable', 'date', 'after:hora_inicio'],
            'texto' => ['required', 'string'],
        ]);

        if (! $excursiones->porId($datos['excursion_id'])) {
            return back()->withInput()->withErrors(['excursion_id' => 'La excursión no existe.']);
        }
        $rescatista = $usuarios->porId($datos['rescatista_id']);
        if (! $rescatista || $rescatista['rol']?->value !== 'rescatista') {
            return back()->withInput()->withErrors(['rescatista_id' => 'El rescatista no existe.']);
        }

        // Sin sendero = "Fuera de sendero"
        $sendero = ! empty($datos['sendero_id']) ? $catalogo->sendero($datos['sendero_id']) : null;
        if (! empty($datos['sendero_id']) && ! $sendero) {
            return back()->withInput()->withErrors(['sendero_id' => 'El sendero no existe.']);
        }

        $id = $bitacoras->crear([
            'excursionId' => $datos['excursion_id'],
            'rescatistaId' => $datos['rescatista_id'],
            'senderoId' => $sendero['id'] ?? null,
            'ruta' => $sendero['nombre'] ?? 'Fuera de sendero',
            'horaInicio' => Carbon::parse($datos['hora_inicio']),
            'horaFin' => ! empty($datos['hora_fin']) ? Carbon::parse($datos['hora_fin']) : null,
            'texto' => $datos['texto'],
        ]);

        return redirect('/bitacoras')->with('ok', "Registro {$id} creado en Firestore.");
    }
}
