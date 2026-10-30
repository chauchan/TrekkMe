<?php

namespace App\Enums;

enum RolUsuario: string
{
    case Excursionista = 'excursionista';
    case Rescatista = 'rescatista';
    case Carabinero = 'carabinero';

    public function etiqueta(): string
    {
        return ucfirst($this->value);
    }

    // Prefijo del ID, como los de Firestore (resc-001)
    public function prefijo(): string
    {
        return match ($this) {
            self::Excursionista => 'usr',
            self::Rescatista => 'resc',
            self::Carabinero => 'carab',
        };
    }
}
