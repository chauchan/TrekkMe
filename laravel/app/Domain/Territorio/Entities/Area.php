<?php

namespace App\Domain\Territorio\Entities;

// Parque, reserva u otra zona donde hay senderos
final class Area
{
    public function __construct(
        public readonly int $id,
        public readonly int $jurisdiccionId,
        public readonly string $nombre,
        public readonly string $tipo,              // parque_nacional, reserva, otro
        public readonly string $coberturaCelular,  // nula, parcial, buena
        public readonly bool $activa,
    ) {
    }
}
