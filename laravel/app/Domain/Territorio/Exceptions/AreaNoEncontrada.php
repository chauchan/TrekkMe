<?php

namespace App\Domain\Territorio\Exceptions;

use App\Domain\Shared\Exceptions\RecursoNoEncontrado;

class AreaNoEncontrada extends RecursoNoEncontrado
{
    public function __construct(int $areaId)
    {
        parent::__construct("No existe el área {$areaId}.");
    }
}
