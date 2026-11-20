<?php

namespace App\Http\Controllers;

use App\Enums\AccionExcursion;
use App\Enums\EstadoExcursion;
use App\Services\Firestore\CatalogoRepository;
use App\Services\Firestore\ExcursionRepository;
use App\Services\Firestore\UsuarioRepository;
use App\Services\TransicionService;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

// App Flutter: el excursionista avisa su salida y ve el estado de sus excursiones.
// Solo trabaja con excursiones propias (las que tienen su id en excursionistaId).
class ExcursionistaController extends Controller
{
    // Mis excursiones, las más recientes primero
    public function index(Request $request, ExcursionRepository $excursiones): JsonResponse
    {
        $usuario = $request->attributes->get('usuario');

        $lista = array_map($this->aRespuesta(...), $excursiones->avisosDelExcursionista($usuario['id']));

        return response()->json(['data' => $lista]);
    }

    // Avisar una salida. Queda "planificada", sin rescatista y a nombre del usuario del token.
    public function crear(
        Request $request,
        ExcursionRepository $excursiones,
        CatalogoRepository $catalogo,
        UsuarioRepository $usuarios,
    ): JsonResponse {
        $usuario = $request->attributes->get('usuario');

        $datos = $request->validate([
            'sendero_id' => ['required', 'string'],
            'salida_at' => ['required', 'date'],
            'retorno_at' => ['required', 'date', 'after:salida_at'],
            'contacto_nombre' => ['required', 'string', 'max:255'],
            'contacto_telefono' => ['required', 'string', 'max:30'],
            // Datos personales: aún no viven en el perfil del usuario, los envía la app
            'rut' => ['required', 'string', 'max:20'],
            'telefono' => ['required', 'string', 'max:30'],
            'edad' => ['required', 'integer', 'min:1', 'max:120'],
            'experiencia' => ['required', Rule::in(['Principiante', 'Intermedio', 'Avanzado'])],
            'condiciones_medicas' => ['nullable', 'string', 'max:255'],
            'detalle' => ['nullable', 'string', 'max:2000'],
            'lleva_carpa' => ['nullable', 'boolean'],
            'color_carpa' => ['nullable', 'string', 'max:50'],
            'lleva_linterna' => ['nullable', 'boolean'],
            'acompanantes' => ['nullable', 'array', 'max:20'],
            'acompanantes.*.nombre' => ['required', 'string', 'max:255'],
            'acompanantes.*.rut' => ['required', 'string', 'max:20'],
            'acompanantes.*.edad' => ['required', 'integer', 'min:0', 'max:120'],
            'acompanantes.*.condiciones_medicas' => ['nullable', 'string', 'max:255'],
        ]);

        $sendero = $catalogo->sendero($datos['sendero_id']);
        if (! $sendero) {
            throw ValidationException::withMessages(['sendero_id' => 'El sendero no existe.']);
        }

        // El nombre sale de la cuenta, no del cuerpo de la petición
        $perfil = $usuarios->porId($usuario['id']);
        if (! $perfil) {
            throw ValidationException::withMessages(['email' => 'Tu cuenta ya no existe.']);
        }

        // Los acompañantes de esta app no tienen cuenta: solo los datos mínimos
        $acompanantes = collect($datos['acompanantes'] ?? [])->map(fn ($a) => [
            'nombre' => $a['nombre'],
            'rut' => $a['rut'],
            'edad' => (int) $a['edad'],
            'condicionesMedicas' => $a['condiciones_medicas'] ?? 'Ninguna',
            'tieneCuenta' => false,
            'telefono' => '',
            'experiencia' => '',
        ])->values()->all();

        $llevaCarpa = $request->boolean('lleva_carpa');

        $id = $excursiones->crear([
            'excursionistaId' => $usuario['id'],
            'excursionista' => $perfil['nombre'],
            'rut' => $datos['rut'],
            'telefono' => $datos['telefono'],
            'edad' => $datos['edad'],
            'experiencia' => $datos['experiencia'],
            'condicionesMedicas' => $datos['condiciones_medicas'] ?? 'Ninguna',
            'acompanantes' => $acompanantes,
            'ruta' => $sendero['nombre'],
            'contactoNombre' => $datos['contacto_nombre'],
            'contactoTelefono' => $datos['contacto_telefono'],
            'salidaAt' => Carbon::parse($datos['salida_at']),
            'retornoAt' => Carbon::parse($datos['retorno_at']),
            'estado' => EstadoExcursion::Planificada,
            'rescatistaId' => '',
            'detalle' => $datos['detalle'] ?? '',
            'llevaCarpa' => $llevaCarpa,
            'colorCarpa' => $llevaCarpa ? ($datos['color_carpa'] ?? '') : '',
            'llevaLinterna' => $request->boolean('lleva_linterna'),
        ], $sendero['id'], $sendero['jurisdiccionId']);

        return response()->json(['data' => $this->aRespuesta($excursiones->aviso($id))], 201);
    }

    // Iniciar (planificada → en curso) o confirmar el regreso (en curso → cerrada).
    // Solo sobre excursiones propias; si el estado ya cambió responde 409.
    public function transicion(Request $request, string $id, string $accion, TransicionService $transiciones): JsonResponse
    {
        $datos = $request->validate([
            'estado_esperado' => ['required', Rule::enum(EstadoExcursion::class)],
        ]);

        $resultado = $transiciones->aplicar(
            $id,
            AccionExcursion::from($accion),
            EstadoExcursion::from($datos['estado_esperado']),
            $request->attributes->get('usuario'),
        );

        return response()->json(['data' => $resultado]);
    }

    // Última ubicación y batería del teléfono del excursionista
    public function ubicacion(Request $request, string $id, TransicionService $transiciones): JsonResponse
    {
        $datos = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'bateria' => ['nullable', 'integer', 'between:0,100'],
        ]);

        $guardada = $transiciones->registrarUbicacion(
            $id,
            $request->attributes->get('usuario'),
            (float) $datos['lat'],
            (float) $datos['lng'],
            isset($datos['bateria']) ? (int) $datos['bateria'] : null,
        );

        return response()->json(['data' => [
            'id' => $guardada['id'],
            'ultima_lat' => $guardada['ultimaLat'],
            'ultima_lng' => $guardada['ultimaLng'],
            'ultima_ubicacion_at' => $guardada['ultimaUbicacionAt']->toIso8601String(),
            'bateria' => $guardada['bateria'],
        ]]);
    }

    // Aviso de Firestore → JSON de la API (snake_case, fechas ISO 8601, sin datos médicos)
    private function aRespuesta(array $aviso): array
    {
        $fecha = fn ($v) => $v instanceof CarbonInterface ? $v->toIso8601String() : null;

        return [
            'id' => $aviso['id'],
            'sendero_id' => $aviso['senderoId'] ?? null,
            'sendero' => $aviso['ruta'] ?? '',
            'estado' => ($aviso['estado'] ?? EstadoExcursion::Planificada)->value,
            'salida_at' => $fecha($aviso['salidaAt'] ?? null),
            'retorno_at' => $fecha($aviso['retornoAt'] ?? null),
            'contacto_nombre' => $aviso['contactoNombre'] ?? '',
            'contacto_telefono' => $aviso['contactoTelefono'] ?? '',
            'detalle' => ($aviso['detalle'] ?? '') ?: null,
            'lleva_carpa' => (bool) ($aviso['llevaCarpa'] ?? false),
            'color_carpa' => ($aviso['colorCarpa'] ?? '') ?: null,
            'lleva_linterna' => (bool) ($aviso['llevaLinterna'] ?? false),
            'acompanantes' => array_map(
                fn ($a) => ['nombre' => $a['nombre'], 'rut' => $a['rut']],
                $aviso['acompanantes'] ?? []
            ),
            'ultima_lat' => $aviso['ultimaLat'] ?? null,
            'ultima_lng' => $aviso['ultimaLng'] ?? null,
            'ultima_ubicacion_at' => $fecha($aviso['ultimaUbicacionAt'] ?? null),
            'bateria' => $aviso['bateria'] ?? null,
        ];
    }
}
