<?php

namespace App\Enums;

enum RolUsuario: string
{
    case Excursionista = 'excursionista';
    case Rescatista = 'rescatista';
    case Carabinero = 'carabinero';
    case Admin = 'admin';

    public function etiqueta(): string
    {
        return ucfirst($this->value);
    }

    // Roles que se pueden crear desde el panel (el admin solo se crea por consola)
    public static function asignables(): array
    {
        return array_values(array_filter(self::cases(), fn (self $rol) => $rol !== self::Admin));
    }

    // Prefijo del ID, como los de Firestore (resc-001)
    public function prefijo(): string
    {
        return match ($this) {
            self::Excursionista => 'usr',
            self::Rescatista => 'resc',
            self::Carabinero => 'carab',
            self::Admin => 'adm',
        };
    }
}
