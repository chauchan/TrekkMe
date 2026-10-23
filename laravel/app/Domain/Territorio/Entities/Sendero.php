<?php

namespace App\Domain\Territorio\Entities;

// Sendero del catálogo. Lo usan el excursionista (ruta declarada)
// y el rescatista (ruta recorrida en la bitácora), así ambas son comparables.
final class Sendero
{
    public function __construct(
        public readonly int $id,
        public readonly int $areaId,
        public readonly string $nombre,
        public readonly string $dificultad,       // baja, media, alta, experta
        public readonly ?float $distanciaKm,
        public readonly ?int $desnivelM,
        public readonly ?int $duracionEstimadaMin,
        public readonly bool $activo,
    ) {
    }
}
