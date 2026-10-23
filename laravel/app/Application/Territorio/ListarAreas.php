<?php

namespace App\Application\Territorio;

use App\Domain\Territorio\Entities\Area;
use App\Domain\Territorio\Repositories\TerritorioRepository;

// Caso de uso: catálogo de áreas activas (selector de ruta en Ionic y Flutter)
class ListarAreas
{
    public function __construct(private TerritorioRepository $territorio)
    {
    }

    /** @return Area[] */
    public function ejecutar(): array
    {
        return $this->territorio->areasActivas();
    }
}
