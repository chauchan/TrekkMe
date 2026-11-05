<?php

namespace App\Enums;

// Cambios de estado que se piden por la API. Cada acción solo es válida desde un estado.
enum AccionExcursion: string
{
    case Escalar = 'escalar';
    case Cerrar = 'cerrar';
    case ConfirmarCierre = 'confirmar-cierre';
    case RechazarCierre = 'rechazar-cierre';
    case SolicitarCierre = 'solicitar-cierre';

    public function desde(): EstadoExcursion
    {
        return match ($this) {
            self::Escalar, self::Cerrar => EstadoExcursion::EnAlerta,
            self::ConfirmarCierre, self::RechazarCierre => EstadoExcursion::CierrePendiente,
            self::SolicitarCierre => EstadoExcursion::EnOperativo,
        };
    }

    public function hacia(): EstadoExcursion
    {
        return match ($this) {
            self::Escalar, self::RechazarCierre => EstadoExcursion::EnOperativo,
            self::Cerrar, self::ConfirmarCierre => EstadoExcursion::Cerrada,
            self::SolicitarCierre => EstadoExcursion::CierrePendiente,
        };
    }

    // Rol que puede pedir la acción
    public function rol(): RolUsuario
    {
        return $this === self::SolicitarCierre ? RolUsuario::Rescatista : RolUsuario::Carabinero;
    }
}
