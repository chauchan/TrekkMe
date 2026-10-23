<?php

namespace App\Application\Territorio;

use App\Domain\Territorio\Entities\Sendero;
use App\Domain\Territorio\Exceptions\AreaNoEncontrada;
use App\Domain\Territorio\Repositories\TerritorioRepository;

// Caso de uso: senderos activos de un área
class ListarSenderosDeArea
{
    public function __construct(private TerritorioRepository $territorio)
    {
    }

    /** @return Sendero[] */
    public function ejecutar(int $areaId): array
    {
        if ($this->territorio->buscarArea($areaId) === null) {
            throw new AreaNoEncontrada($areaId);
        }

        return $this->territorio->senderosActivosDeArea($areaId);
    }
}
