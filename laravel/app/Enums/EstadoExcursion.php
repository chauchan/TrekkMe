<?php

namespace App\Enums;

use App\Exceptions\TransicionDeEstadoInvalida;

// Estados de una excursión y las transiciones permitidas entre ellos.
// Ningún cliente escribe el estado directo: todo cambio pasa por transicionarA().
enum EstadoExcursion: string
{
    case Programada = 'programada';
    case EnCurso = 'en_curso';
    case Vencida = 'vencida';                  // pasó la hora comprometida de retorno
    case EnAlerta = 'en_alerta';               // el escalamiento llegó a la unidad (o hubo SOS)
    case EnOperativo = 'en_operativo';         // el carabinero la escaló a búsqueda
    case CierrePendiente = 'cierre_pendiente'; // el rescatista pidió cerrar; falta Carabineros
    case Finalizada = 'finalizada';
    case Cancelada = 'cancelada';

    // Estados a los que se puede pasar desde este
    public function siguientesPermitidos(): array
    {
        return match ($this) {
            self::Programada => [self::EnCurso, self::Cancelada],
            self::EnCurso => [self::Finalizada, self::Vencida, self::EnAlerta],
            self::Vencida => [self::EnCurso, self::EnAlerta],
            self::EnAlerta => [self::Finalizada, self::EnOperativo],
            self::EnOperativo => [self::CierrePendiente],
            self::CierrePendiente => [self::Finalizada, self::EnOperativo],
            self::Finalizada, self::Cancelada => [],
        };
    }

    public function puedePasarA(self $nuevo): bool
    {
        return in_array($nuevo, $this->siguientesPermitidos(), true);
    }

    // Devuelve el nuevo estado o lanza excepción (la API responde 409)
    public function transicionarA(self $nuevo): self
    {
        if (! $this->puedePasarA($nuevo)) {
            throw new TransicionDeEstadoInvalida($this, $nuevo);
        }

        return $nuevo;
    }

    public function esTerminal(): bool
    {
        return $this->siguientesPermitidos() === [];
    }

    // Estados que el rescatista ve en "Casos asignados"
    public static function visiblesParaRescatista(): array
    {
        return [self::EnOperativo, self::CierrePendiente];
    }

    // El rescatista puede escribir en la bitácora mientras el caso no esté cerrado
    public function permiteEscribirBitacora(): bool
    {
        return in_array($this, self::visiblesParaRescatista(), true);
    }
}
