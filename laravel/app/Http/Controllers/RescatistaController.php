<?php

namespace App\Http\Controllers;

use App\Enums\AccionExcursion;
use App\Enums\EstadoExcursion;
use App\Services\Firestore\BitacoraRepository;
use App\Services\Firestore\CatalogoRepository;
use App\Services\Firestore\ConflictoDeEscritura;
use App\Services\Firestore\ExcursionRepository;
use App\Services\TransicionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RescatistaController extends Controller
{
    private const SIN_ACCESO = 'No tienes acceso a este caso.';

    private const BITACORA_MODIFICADA = 'Alguien modificó este registro. Vuelve a abrirlo e intenta de nuevo.';

    // En operativo → cierre pendiente. Solo el rescatista asignado puede pedirlo.
    public function solicitarCierre(Request $request, string $id, TransicionService $transiciones): JsonResponse
    {
        $datos = $request->validate([
            'estado_esperado' => ['required', Rule::enum(EstadoExcursion::class)],
        ]);

        $resultado = $transiciones->aplicar(
            $id,
            AccionExcursion::SolicitarCierre,
            EstadoExcursion::from($datos['estado_esperado']),
            $request->attributes->get('usuario'),
        );

        return response()->json(['data' => $resultado]);
    }

    public function crearBitacora(Request $request, string $casoId, BitacoraRepository $bitacoras, ExcursionRepository $excursiones, CatalogoRepository $catalogo): JsonResponse
    {
        $usuario = $request->attributes->get('usuario');
        $this->exigirCaso($excursiones, $casoId, $usuario['id']);

        $datos = $this->validarRegistro($request);
        $sendero = $this->sendero($catalogo, $datos['sendero_id'] ?? null);

        // El rescatista sale del token, nunca del cuerpo de la petición
        $id = $bitacoras->crear([
            'excursionId' => $casoId,
            'rescatistaId' => $usuario['id'],
            'senderoId' => $sendero['id'] ?? null,
            'ruta' => $sendero['nombre'] ?? 'Fuera de sendero',
            'horaInicio' => Carbon::parse($datos['hora_inicio']),
            'horaFin' => ! empty($datos['hora_fin']) ? Carbon::parse($datos['hora_fin']) : null,
            'texto' => $datos['texto'],
        ]);

        return response()->json(['data' => ['id' => $id, 'version' => 1]], 201);
    }

    public function editarBitacora(Request $request, string $id, BitacoraRepository $bitacoras, ExcursionRepository $excursiones, CatalogoRepository $catalogo): JsonResponse
    {
        $usuario = $request->attributes->get('usuario');
        $registro = $this->registro($bitacoras, $id);
        $this->exigirCaso($excursiones, $registro['excursionId'], $usuario['id']);

        $datos = $this->validarRegistro($request, conVersion: true);

        if ((int) $datos['version'] !== (int) $registro['version']) {
            throw new HttpException(409, self::BITACORA_MODIFICADA);
        }

        $sendero = $this->sendero($catalogo, $datos['sendero_id'] ?? null);
        $nuevaVersion = (int) $registro['version'] + 1;

        try {
            $bitacoras->actualizar($id, [
                'senderoId' => $sendero['id'] ?? null,
                'ruta' => $sendero['nombre'] ?? 'Fuera de sendero',
                'horaInicio' => Carbon::parse($datos['hora_inicio']),
                'horaFin' => ! empty($datos['hora_fin']) ? Carbon::parse($datos['hora_fin']) : null,
                'texto' => $datos['texto'],
                'version' => $nuevaVersion,
            ], $registro['_actualizado']);
        } catch (ConflictoDeEscritura) {
            // Alguien guardó entre la lectura y la escritura
            throw new HttpException(409, self::BITACORA_MODIFICADA);
        }

        return response()->json(['data' => ['id' => $id, 'version' => $nuevaVersion]]);
    }

    public function eliminarBitacora(Request $request, string $id, BitacoraRepository $bitacoras, ExcursionRepository $excursiones): JsonResponse
    {
        $registro = $this->registro($bitacoras, $id);
        $this->exigirCaso($excursiones, $registro['excursionId'], $request->attributes->get('usuario')['id']);

        $bitacoras->eliminar($id);

        return response()->json(null, 204);
    }

    // Catálogo de senderos para el selector (cualquier usuario con sesión)
    public function senderos(CatalogoRepository $catalogo): JsonResponse
    {
        $lista = array_map(
            fn ($s) => ['id' => $s['id'], 'nombre' => $s['nombre'], 'jurisdiccion_id' => $s['jurisdiccionId']],
            $catalogo->senderos()
        );

        return response()->json(['data' => $lista]);
    }

    // ---------------------------------------------------------------- internos

    // El caso debe existir y estar asignado a este rescatista
    private function exigirCaso(ExcursionRepository $excursiones, string $casoId, string $rescatistaId): void
    {
        $caso = $excursiones->porId($casoId);

        if (! $caso) {
            throw new HttpException(404, 'El caso ya no existe.');
        }
        if (($caso['rescatistaId'] ?? '') !== $rescatistaId) {
            throw new HttpException(403, self::SIN_ACCESO);
        }
    }

    private function registro(BitacoraRepository $bitacoras, string $id): array
    {
        return $bitacoras->porId($id) ?? throw new HttpException(404, 'El registro ya no existe.');
    }

    // Sin sendero = "Fuera de sendero"
    private function sendero(CatalogoRepository $catalogo, ?string $senderoId): ?array
    {
        if (empty($senderoId)) {
            return null;
        }

        return $catalogo->sendero($senderoId)
            ?? throw \Illuminate\Validation\ValidationException::withMessages(['sendero_id' => 'El sendero no existe.']);
    }

    private function validarRegistro(Request $request, bool $conVersion = false): array
    {
        return $request->validate([
            'sendero_id' => ['nullable', 'string'],
            'hora_inicio' => ['required', 'date'],
            'hora_fin' => ['nullable', 'date', 'after:hora_inicio'],
            'texto' => ['required', 'string'],
            'version' => $conVersion ? ['required', 'integer', 'min:1'] : ['prohibited'],
        ]);
    }
}
