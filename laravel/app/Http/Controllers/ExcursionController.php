<?php

namespace App\Http\Controllers;

use App\Enums\EstadoExcursion;
use App\Services\Firestore\BitacoraRepository;
use App\Services\Firestore\CatalogoRepository;
use App\Services\Firestore\ExcursionRepository;
use App\Services\Firestore\UsuarioRepository;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExcursionController extends Controller
{
    public function index(ExcursionRepository $excursiones, UsuarioRepository $usuarios, BitacoraRepository $bitacoras)
    {
        return view('excursiones.index', [
            'excursiones' => $excursiones->todas(),
            'nombres' => collect($usuarios->todos())->pluck('nombre', 'id'),
            'registros' => collect($bitacoras->todas())->countBy('excursionId'),
        ]);
    }

    public function create(CatalogoRepository $catalogo, UsuarioRepository $usuarios)
    {
        return view('excursiones.crear', [
            'estados' => EstadoExcursion::cases(),
            'senderos' => $catalogo->senderos(),
            'rescatistas' => $usuarios->rescatistas(),
        ]);
    }

    public function store(Request $request, ExcursionRepository $excursiones, CatalogoRepository $catalogo, UsuarioRepository $usuarios)
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
            'sendero_id' => ['required', 'string'],
            'contacto_nombre' => ['required', 'string', 'max:255'],
            'contacto_telefono' => ['required', 'string', 'max:30'],
            'salida_at' => ['required', 'date'],
            'retorno_at' => ['required', 'date', 'after:salida_at'],
            'estado' => ['required', Rule::enum(EstadoExcursion::class)],
            'rescatista_id' => ['nullable', 'string'],
            'detalle' => ['nullable', 'string'],
            'color_carpa' => ['nullable', 'string', 'max:50'],
            'ultima_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'ultima_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'bateria' => ['nullable', 'integer', 'between:0,100'],
        ]);

        $sendero = $catalogo->sendero($datos['sendero_id']);
        if (! $sendero) {
            return back()->withInput()->withErrors(['sendero_id' => 'El sendero no existe.']);
        }

        $rescatistaId = $datos['rescatista_id'] ?? '';
        if ($rescatistaId !== '') {
            $rescatista = $usuarios->porId($rescatistaId);
            if (! $rescatista || $rescatista['rol']?->value !== 'rescatista') {
                return back()->withInput()->withErrors(['rescatista_id' => 'El rescatista no existe.']);
            }
        }

        $acompanantes = collect($datos['acompanantes'] ?? [])->map(fn ($a) => [
            'nombre' => $a['nombre'],
            'rut' => $a['rut'],
            'edad' => (int) $a['edad'],
            'condicionesMedicas' => $a['condicionesMedicas'] ?? 'Ninguna',
            'tieneCuenta' => ! empty($a['telefono']),
            'telefono' => $a['telefono'] ?? '',
            'experiencia' => $a['experiencia'] ?? '',
        ])->values()->all();

        $id = $excursiones->crear([
            'excursionista' => $datos['excursionista'],
            'rut' => $datos['rut'],
            'telefono' => $datos['telefono'],
            'edad' => $datos['edad'],
            'experiencia' => $datos['experiencia'],
            'condicionesMedicas' => $datos['condiciones_medicas'],
            'acompanantes' => $acompanantes,
            'ruta' => $sendero['nombre'],
            'contactoNombre' => $datos['contacto_nombre'],
            'contactoTelefono' => $datos['contacto_telefono'],
            'salidaAt' => \Illuminate\Support\Carbon::parse($datos['salida_at']),
            'retornoAt' => \Illuminate\Support\Carbon::parse($datos['retorno_at']),
            'estado' => $datos['estado'],
            'rescatistaId' => $rescatistaId,
            'detalle' => $datos['detalle'] ?? '',
            'llevaCarpa' => $request->boolean('lleva_carpa'),
            'colorCarpa' => $datos['color_carpa'] ?? '',
            'llevaLinterna' => $request->boolean('lleva_linterna'),
            'ultimaLat' => isset($datos['ultima_lat']) ? (float) $datos['ultima_lat'] : null,
            'ultimaLng' => isset($datos['ultima_lng']) ? (float) $datos['ultima_lng'] : null,
            'ultimaUbicacionAt' => isset($datos['ultima_lat']) ? now() : null,
            'bateria' => isset($datos['bateria']) ? (int) $datos['bateria'] : null,
        ], $sendero['id'], $sendero['jurisdiccionId']);

        return redirect('/excursiones')->with('ok', "Excursión {$id} creada en Firestore (excursiones y avisos).");
    }
}
