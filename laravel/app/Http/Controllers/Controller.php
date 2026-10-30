<?php

namespace App\Http\Controllers;

use App\Services\FirestoreSync;

abstract class Controller
{
    // Envía el registro a Firestore. Si falla, el registro igual queda guardado en Laravel y se avisa.
    protected function sincronizar(callable $enviar): array
    {
        try {
            $resultado = $enviar();
        } catch (\Throwable $e) {
            report($e);

            return [' Guardado en Laravel, pero NO se pudo enviar a Firestore.', 'error'];
        }

        return $resultado === FirestoreSync::YA_EXISTE
            ? [' Guardado en Laravel; ese ID ya existía en Firestore y no se tocó.', 'aviso']
            : [' Enviado a Firestore.', 'ok'];
    }
}
