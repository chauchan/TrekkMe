<?php

namespace App\Services\Firestore;

use Carbon\Carbon;

// Base de los repositorios: acceso al cliente y ayudas comunes
abstract class Repositorio
{
    public function __construct(protected FirestoreClient $firestore) {}

    // Siguiente ID correlativo con prefijo: exc-001, exc-002...
    protected function siguienteId(string $coleccion, string $prefijo): string
    {
        $ultimo = collect($this->firestore->consultar($coleccion))
            ->pluck('id')
            ->filter(fn ($id) => str_starts_with($id, $prefijo.'-'))
            ->map(fn ($id) => (int) substr($id, strlen($prefijo) + 1))
            ->max() ?? 0;

        return sprintf('%s-%03d', $prefijo, $ultimo + 1);
    }

    protected function fecha(mixed $valor): ?Carbon
    {
        return $valor instanceof Carbon ? $valor : null;
    }
}
