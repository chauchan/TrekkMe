<?php

namespace App\Exceptions;

use App\Enums\EstadoExcursion;
use Exception;
use Illuminate\Http\JsonResponse;

// Se intentó un cambio de estado no permitido, o el estado ya cambió (otra persona lo gestionó)
class TransicionDeEstadoInvalida extends Exception
{
    public function __construct(
        public readonly EstadoExcursion $desde,
        public readonly EstadoExcursion $hacia,
    ) {
        parent::__construct("No se puede pasar de '{$desde->value}' a '{$hacia->value}'.");
    }

    // Laravel llama a este método al convertir la excepción en respuesta HTTP
    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 409);
    }
}
