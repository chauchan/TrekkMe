<?php

namespace App\Domain\Excursion\Exceptions;

use App\Domain\Excursion\EstadoExcursion;
use App\Domain\Shared\Exceptions\ConflictoDeDominio;

class TransicionDeEstadoInvalida extends ConflictoDeDominio
{
    public function __construct(
        public readonly EstadoExcursion $desde,
        public readonly EstadoExcursion $hacia,
    ) {
        parent::__construct("No se puede pasar de '{$desde->value}' a '{$hacia->value}'.");
    }
}
