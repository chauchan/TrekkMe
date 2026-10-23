<?php

namespace App\Domain\Shared\Exceptions;

use DomainException;

// Una regla de negocio impide la operación (la API responde 409)
class ConflictoDeDominio extends DomainException
{
}
