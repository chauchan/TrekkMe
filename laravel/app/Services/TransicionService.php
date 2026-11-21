<?php

namespace App\Services;

use App\Enums\AccionExcursion;
use App\Enums\EstadoExcursion;
use App\Enums\RolUsuario;
use App\Services\Firestore\ConflictoDeEscritura;
use App\Services\Firestore\FirestoreClient;
use Symfony\Component\HttpKernel\Exception\HttpException;

// Cambia el estado de una excursión. Escribe "excursiones" y "avisos" juntas y solo si ninguna
// cambió desde que se leyeron, así dos personas no pueden gestionar el mismo aviso a la vez.
class TransicionService
{
    private const YA_GESTIONADO = 'Otra persona ya gestionó este aviso. Vuelve a abrirlo.';

    private const YA_CAMBIO = 'Tu excursión cambió de estado. Actualiza e intenta de nuevo.';

    // Estados en los que el excursionista puede seguir enviando su ubicación
    private const CON_UBICACION = [
        EstadoExcursion::EnCurso,
        EstadoExcursion::EnAlerta,
        EstadoExcursion::EnOperativo,
        EstadoExcursion::CierrePendiente,
    ];

    public function __construct(private FirestoreClient $firestore) {}

    /**
     * @param  array  $usuario  ['id', 'rol', 'jurisdiccionId'] del token
     * @return array ['id', 'estado'] con el estado nuevo
     */
    public function aplicar(string $id, AccionExcursion $accion, EstadoExcursion $estadoEsperado, array $usuario, ?string $rescatistaId = null): array
    {
        [$excursion, $aviso] = $this->cargar($id);

        $this->autorizar($accion->rol(), $excursion, $aviso, $usuario);

        $actual = EstadoExcursion::desdeTexto((string) $excursion['estado']);

        // Quien pide el cambio vio un estado y la excursión ya es otra: alguien se le adelantó
        if ($actual !== $estadoEsperado) {
            throw new HttpException(409, $this->mensajeConflicto($accion->rol()));
        }
        if ($actual !== $accion->desde()) {
            throw new HttpException(409, "No se puede aplicar \"{$accion->value}\" a una excursión en estado \"{$actual?->etiqueta()}\".");
        }

        $cambios = ['estado' => $accion->hacia()->value];

        if ($accion === AccionExcursion::Escalar) {
            $cambios['rescatistaId'] = $rescatistaId;
        }

        $this->guardar($id, $cambios, $excursion, $aviso, $accion->rol());

        return ['id' => $id, 'estado' => $accion->hacia()->value];
    }

    /**
     * El excursionista informa dónde está. Solo mientras la excursión está en marcha o en búsqueda.
     *
     * @param  array  $usuario  ['id', 'rol', 'jurisdiccionId'] del token
     * @return array con la ubicación guardada
     */
    public function registrarUbicacion(string $id, array $usuario, float $lat, float $lng, ?int $bateria): array
    {
        [$excursion, $aviso] = $this->cargar($id);

        $this->autorizar(RolUsuario::Excursionista, $excursion, $aviso, $usuario);

        $actual = EstadoExcursion::desdeTexto((string) $excursion['estado']);

        if (! in_array($actual, self::CON_UBICACION, true)) {
            throw new HttpException(409, 'Solo puedes enviar tu ubicación mientras tu excursión está en curso.');
        }

        $cambios = [
            'ultimaLat' => $lat,
            'ultimaLng' => $lng,
            'ultimaUbicacionAt' => now(),
            'bateria' => $bateria,
        ];

        $this->guardar($id, $cambios, $excursion, $aviso, RolUsuario::Excursionista);

        return ['id' => $id] + $cambios;
    }

    // Excursión y aviso del mismo ID; si falta alguno, la excursión no existe
    private function cargar(string $id): array
    {
        $excursion = $this->firestore->obtener('excursiones', $id);
        $aviso = $this->firestore->obtener('avisos', $id);

        if (! $excursion || ! $aviso) {
            throw new HttpException(404, 'El aviso ya no existe.');
        }

        return [$excursion, $aviso];
    }

    // Escribe los mismos cambios en "excursiones" y "avisos", o ninguno si alguna cambió mientras tanto
    private function guardar(string $id, array $cambios, array $excursion, array $aviso, RolUsuario $rol): void
    {
        try {
            $this->firestore->confirmar([
                $this->firestore->actualizacion('excursiones', $id, $cambios, $excursion['_actualizado']),
                $this->firestore->actualizacion('avisos', $id, $cambios, $aviso['_actualizado']),
            ]);
        } catch (ConflictoDeEscritura) {
            throw new HttpException(409, $this->mensajeConflicto($rol));
        }
    }

    private function autorizar(RolUsuario $rol, array $excursion, array $aviso, array $usuario): void
    {
        $permitido = match ($rol) {
            // El carabinero solo gestiona avisos de su jurisdicción
            RolUsuario::Carabinero => ($usuario['jurisdiccionId'] ?? null) !== null
                && ($aviso['jurisdiccionId'] ?? null) === $usuario['jurisdiccionId'],
            // El rescatista solo cierra casos que tiene asignados
            RolUsuario::Rescatista => ($excursion['rescatistaId'] ?? '') === $usuario['id'],
            // El excursionista solo actúa sobre las excursiones que creó
            RolUsuario::Excursionista => ($excursion['excursionistaId'] ?? '') !== ''
                && $excursion['excursionistaId'] === $usuario['id'],
            default => false,
        };

        if (! $permitido) {
            throw new HttpException(403, $rol === RolUsuario::Excursionista
                ? 'No tienes acceso a esta excursión.'
                : 'No tienes acceso a este caso.');
        }
    }

    private function mensajeConflicto(RolUsuario $rol): string
    {
        return $rol === RolUsuario::Excursionista ? self::YA_CAMBIO : self::YA_GESTIONADO;
    }
}
