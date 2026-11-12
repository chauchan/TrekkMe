<?php

namespace App\Console\Commands;

use App\Services\Simulador\Simulador;
use Illuminate\Console\Command;
use Throwable;

class SimuladorCorrerCommand extends Command
{
    protected $signature = 'simulador:correr
        {--intervalo=20 : Segundos entre ticks}
        {--max-activas=4 : Máximo de excursiones planificadas o en curso a la vez}
        {--perdidas=30 : Porcentaje de excursiones que se pierden}
        {--duracion=3-5 : Minutos que dura una excursión (mín-máx)}
        {--jurisdiccion=jur-001 : Jurisdicción de los senderos que se usan}
        {--semilla= : Semilla del azar, para repetir la misma secuencia}
        {--una-vez : Hace un solo tick y termina}';

    protected $description = 'Simula excursionistas para la demo: crea excursiones y las hace avanzar en Firestore. Ctrl+C para detener';

    public function handle(Simulador $simulador): int
    {
        $intervalo = max(5, (int) $this->option('intervalo'));
        // "3-5" → 3 y 5; "4" → 4 y 4
        $partes = array_map('intval', explode('-', (string) $this->option('duracion')));
        [$min, $max] = [$partes[0], $partes[1] ?? $partes[0]];
        if ($min < 1 || $max < $min) {
            $this->error('--duracion debe ser "mín-máx" en minutos, por ejemplo 3-5.');

            return self::FAILURE;
        }

        if ($this->option('semilla') !== null) {
            mt_srand((int) $this->option('semilla'));
        }

        $simulador->configurar(
            $intervalo,
            max(1, (int) $this->option('max-activas')),
            min(100, max(0, (int) $this->option('perdidas'))),
            [$min, $max],
            (string) $this->option('jurisdiccion'),
        );

        $this->info("Simulador de excursionistas: tick cada {$intervalo} s, excursiones de {$min} a {$max} min, jurisdicción {$this->option('jurisdiccion')}.");
        $this->line('Para borrar lo simulado: php artisan simulador:limpiar');

        do {
            $this->line('<fg=gray>['.now()->format('H:i:s').'] tick</>');
            try {
                foreach ($simulador->tick() as $linea) {
                    $this->line("  {$linea}");
                }
            } catch (Throwable $e) {
                // Un error de red no debe botar la demo: se avisa y se sigue en el próximo tick
                $this->warn('  Error en el tick: '.$e->getMessage());
            }

            if ($this->option('una-vez')) {
                break;
            }
            sleep($intervalo);
        } while (true);

        return self::SUCCESS;
    }
}
