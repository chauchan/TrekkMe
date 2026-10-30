<?php

namespace App\Http\Controllers;

use App\Enums\EstadoExcursion;
use App\Enums\RolUsuario;
use App\Models\Excursion;
use App\Models\Usuario;
use App\Services\FirestoreSync;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExcursionController extends Controller
{
    public function index()
    {
        $excursiones = Excursion::with('rescatista')->withCount('bitacoras')->orderBy('id')->get();

        return view('excursiones.index', compact('excursiones'));
    }

    public function create()
    {
        return view('excursiones.crear', [
            'estados' => EstadoExcursion::cases(),
            'rescatistas' => Usuario::where('rol', RolUsuario::Rescatista)->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request, FirestoreSync $firestore)
    {
        $datos = $request->validate([
            'excursionista' => ['required', 'string', 'max:255'],
            'rut' => ['required', 'string', 'max:20'],
            'telefono' => ['required', 'string', 'max:30'],
            'edad' => ['required', 'integer', 'min:1', 'max:120'],
            'experiencia' => ['required', Rule::in(['Principiante', 'Intermedio', 'Avanzado'])],
            'condiciones_medicas' => ['required', 'string', 'max:255'],
            'acompanantes' => ['nullable', 'array'],
            'acompanantes.*.nombre' => ['required', 'string', 'max:255'],
            'acompanantes.*.rut' => ['required', 'string', 'max:20'],
            'acompanantes.*.edad' => ['required', 'integer', 'min:0', 'max:120'],
            'acompanantes.*.condicionesMedicas' => ['nullable', 'string', 'max:255'],
            'acompanantes.*.telefono' => ['nullable', 'string', 'max:30'],
            'acompanantes.*.experiencia' => ['nullable', 'string', 'max:30'],
            'ruta' => ['required', 'string', 'max:255'],
            'contacto_nombre' => ['required', 'string', 'max:255'],
            'contacto_telefono' => ['required', 'string', 'max:30'],
            'salida_at' => ['required', 'date'],
            'retorno_at' => ['required', 'date', 'after:salida_at'],
            'estado' => ['required', Rule::enum(EstadoExcursion::class)],
            'rescatista_id' => ['nullable', Rule::exists('usuarios', 'id')->where('rol', RolUsuario::Rescatista->value)],
            'detalle' => ['nullable', 'string'],
            'color_carpa' => ['nullable', 'string', 'max:50'],
            'ultima_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'ultima_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'bateria' => ['nullable', 'integer', 'between:0,100'],
        ]);

        // Mismo formato que en Firestore: cada acompañante guarda si tiene cuenta y los vacíos quedan como texto vacío
        $acompanantes = collect($datos['acompanantes'] ?? [])->map(fn ($a) => [
            'nombre' => $a['nombre'],
            'rut' => $a['rut'],
            'edad' => (int) $a['edad'],
            'condicionesMedicas' => $a['condicionesMedicas'] ?? 'Ninguna',
            'tieneCuenta' => ! empty($a['telefono']),
            'telefono' => $a['telefono'] ?? '',
            'experiencia' => $a['experiencia'] ?? '',
        ])->values()->all();

        $excursion = Excursion::create([
            ...$datos,
            'acompanantes' => $acompanantes,
            'lleva_carpa' => $request->boolean('lleva_carpa'),
            'lleva_linterna' => $request->boolean('lleva_linterna'),
            'ultima_ubicacion_at' => isset($datos['ultima_lat']) ? now() : null,
        ]);

        [$aviso] = $this->sincronizar(fn () => $firestore->excursion($excursion));

        return redirect('/excursiones')->with('ok', "Excursión {$excursion->id} creada.".$aviso);
    }
}
