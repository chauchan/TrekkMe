<?php

namespace App\Services;

use App\Enums\EstadoExcursion;
use App\Services\Firestore\ConflictoDeEscritura;
use App\Services\Firestore\ExcursionRepository;
use App\Services\Firestore\FirestoreClient;
use Carbon\CarbonInterface;

// Pasa a "en alerta" las excursiones en curso cuya hora de retorno ya venció. Es el paso que dispara
// el aviso al carabinero: ningún cliente lo hace, lo ejecuta el comando programado.
class AlertaService
{
    public function __construct(
        private FirestoreClient $firestore,
        private ExcursionRepository $excursiones,
    ) {}

    /** @return string[] IDs de las excursiones que pasaron a "en alerta" */
    public function marcarVencidas(?CarbonInterface $ahora = null): array
    {
        $ahora ??= now();
        $marcadas = [];

        foreach ($this->excursiones->todas() as $excursion) {
            $retorno = $excursion['retornoAt'] ?? null;

            if ($excursion['estado'] !== EstadoExcursion::EnCurso || ! $retorno instanceof CarbonInterface || $retorno->gte($ahora)) {
                continue;
            }

            $aviso = $this->firestore->obtener('avisos', $excursion['id']);
            if (! $aviso) {
                continue;
            }

            $cambios = ['estado' => EstadoExcursion::EnAlerta->value];

            try {
                $this->firestore->confirmar([
                    $this->firestore->actualizacion('excursiones', $excursion['id'], $cambios, $excursion['_actualizado']),
                    $this->firestore->actualizacion('avisos', $excursion['id'], $cambios, $aviso['_actualizado']),
                ]);
                $marcadas[] = $excursion['id'];
            } catch (ConflictoDeEscritura) {
                // Cambió mientras tanto (por ejemplo el excursionista confirmó su regreso): se deja como está
            }
        }

        return $marcadas;
    }
}
