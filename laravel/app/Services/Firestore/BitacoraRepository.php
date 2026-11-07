<?php

namespace App\Services\Firestore;

use Illuminate\Support\Str;

class BitacoraRepository extends Repositorio
{
    public function todas(): array
    {
        return collect($this->firestore->consultar('bitacora'))
            ->sortByDesc(fn ($b) => $b['registradoAt'] ?? null)
            ->values()
            ->all();
    }

    public function porId(string $id): ?array
    {
        return $this->firestore->obtener('bitacora', $id);
    }

    // Cambia solo los campos indicados. Falla (ConflictoDeEscritura) si el documento cambió desde que se leyó.
    public function actualizar(string $id, array $cambios, string $actualizadoEn): void
    {
        $this->firestore->confirmar([$this->firestore->actualizacion('bitacora', $id, $cambios, $actualizadoEn)]);
    }

    public function eliminar(string $id): void
    {
        $this->firestore->confirmar([$this->firestore->borrado('bitacora', $id)]);
    }

    // Crea un registro nuevo con ID automático (como hace Ionic). Devuelve el ID.
    public function crear(array $datos): string
    {
        $id = Str::random(20);

        $this->firestore->confirmar([$this->firestore->escritura('bitacora', $id, [
            'excursionId' => $datos['excursionId'],
            'rescatistaId' => $datos['rescatistaId'],
            'senderoId' => $datos['senderoId'] ?? null,
            'ruta' => $datos['ruta'],
            'horaInicio' => $datos['horaInicio'],
            'horaFin' => $datos['horaFin'] ?? null,
            'texto' => $datos['texto'],
            'registradoAt' => now(),
            'version' => 1,
        ], null, false)]);

        return $id;
    }
}
