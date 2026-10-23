<?php

namespace App\Domain\Territorio\Repositories;

use App\Domain\Territorio\Entities\Area;
use App\Domain\Territorio\Entities\Sendero;

// Contrato de acceso al catálogo de territorio.
// La implementación vive en Infrastructure; el dominio no sabe que existe Eloquent.
interface TerritorioRepository
{
    /** @return Area[] */
    public function areasActivas(): array;

    public function buscarArea(int $areaId): ?Area;

    /** @return Sendero[] */
    public function senderosActivosDeArea(int $areaId): array;
}
