<?php

namespace App\Enums;

// Mismos estados que usa Ionic en Firestore, en minúscula y con guion bajo
enum EstadoExcursion: string
{
    case Planificada = 'planificada';
    case EnCurso = 'en_curso';
    case EnAlerta = 'en_alerta';
    case EnOperativo = 'en_operativo';
    case CierrePendiente = 'cierre_pendiente';
    case Cerrada = 'cerrada';

    public function etiqueta(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }
}
