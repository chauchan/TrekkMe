<?php

namespace App\Domain\Shared\Exceptions;

use DomainException;

// El recurso pedido no existe (la API responde 404)
class RecursoNoEncontrado extends DomainException
{
}
