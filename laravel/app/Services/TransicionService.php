<?php

namespace App\Services;

use App\Enums\AccionExcursion;
use App\Enums\EstadoExcursion;
use App\Services\Firestore\ConflictoDeEscritura;
use App\Services\Firestore\FirestoreClient;
use Symfony\Component\HttpKernel\Exception\HttpException;

// Cambia el estado de una excursión. Escribe "excursiones" y "avisos" juntas y solo si ninguna
// cambió desde que se leyeron, así dos personas no pueden gestionar el mismo aviso a la vez.
class TransicionService
{
    private const YA_GESTIONADO = 'Otra persona ya gestionó este aviso. Vuelve a abrirlo.';

    public function __construct(private FirestoreClient $firestore) {}

    /**
     * @param  array  $usuario  ['id', 'rol', 'jurisdiccionId'] del token
     * @return array ['id', 'estado'] con el estado nuevo
     */
    public function aplicar(string $id, AccionExcursion $accion, EstadoExcursion $estadoEsperado, array $usuario, ?string $rescatistaId = null): array
    {
        $excursion = $this->firestore->obtener('excursiones', $id);
        $aviso = $this->firestore->obtener('avisos', $id);

        if (! $excursion || ! $aviso) {
            throw new HttpException(404, 'El aviso ya no existe.');
        }

        $this->autorizar($accion, $excursion, $aviso, $usuario);

        $actual = EstadoExcursion::desdeTexto((string) $excursion['estado']);

        // El carabinero vio un estado y el aviso ya es otro: alguien se le adelantó
        if ($actual !== $estadoEsperado) {
            throw new HttpException(409, self::YA_GESTIONADO);
        }
        if ($actual !== $accion->desde()) {
            throw new HttpException(409, "No se puede aplicar \"{$accion->value}\" a una excursión en estado \"{$actual?->etiqueta()}\".");
        }

        $cambios = ['estado' => $accion->hacia()->value];

        if ($accion === AccionExcursion::Escalar) {
            $cambios['rescatistaId'] = $rescatistaId;
        }

        try {
            $this->firestore->confirmar([
                $this->firestore->actualizacion('excursiones', $id, $cambios, $excursion['_actualizado']),
                $this->firestore->actualizacion('avisos', $id, $cambios, $aviso['_actualizado']),
            ]);
        } catch (ConflictoDeEscritura) {
            throw new HttpException(409, self::YA_GESTIONADO);
        }

        return ['id' => $id, 'estado' => $accion->hacia()->value];
    }

    private function autorizar(AccionExcursion $accion, array $excursion, array $aviso, array $usuario): void
    {
        $permitido = match ($accion->rol()) {
            // El carabinero solo gestiona avisos de su jurisdicción
            \App\Enums\RolUsuario::Carabinero => ($usuario['jurisdiccionId'] ?? null) !== null
                && ($aviso['jurisdiccionId'] ?? null) === $usuario['jurisdiccionId'],
            // El rescatista solo cierra casos que tiene asignados
            \App\Enums\RolUsuario::Rescatista => ($excursion['rescatistaId'] ?? '') === $usuario['id'],
            default => false,
        };

        if (! $permitido) {
            throw new HttpException(403, 'No tienes acceso a este caso.');
        }
    }
}
