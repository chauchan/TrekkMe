<?php

namespace App\Console\Commands;

use App\Services\Firestore\FirestoreClient;
use App\Services\Simulador\Simulador;
use Illuminate\Console\Command;

class SimuladorLimpiarCommand extends Command
{
    protected $signature = 'simulador:limpiar {--force : No pide confirmación}';

    protected $description = 'Borra de Firestore solo lo que creó el simulador (excursiones y avisos sim-*, y sus bitácoras)';

    public function handle(FirestoreClient $f): int
    {
        $esSimulado = fn ($id) => str_starts_with((string) $id, Simulador::PREFIJO);

        $excursiones = collect($f->consultar('excursiones'))->pluck('id')->filter($esSimulado);
        $avisos = collect($f->consultar('avisos'))->pluck('id')->filter($esSimulado);
        $bitacoras = collect($f->consultar('bitacora'))->filter(fn ($b) => $esSimulado($b['excursionId'] ?? ''))->pluck('id');

        $total = $excursiones->count() + $avisos->count() + $bitacoras->count();
        if ($total === 0) {
            $this->info('No hay nada simulado que borrar.');

            return self::SUCCESS;
        }

        $this->line("Se borrarán {$excursiones->count()} excursiones, {$avisos->count()} avisos y {$bitacoras->count()} registros de bitácora simulados.");
        if (! $this->option('force') && ! $this->confirm('¿Continuar?')) {
            return self::SUCCESS;
        }

        $borrados = $excursiones->map(fn ($id) => $f->borrado('excursiones', $id))
            ->merge($avisos->map(fn ($id) => $f->borrado('avisos', $id)))
            ->merge($bitacoras->map(fn ($id) => $f->borrado('bitacora', $id)));

        // Firestore acepta hasta 500 escrituras por operación
        foreach ($borrados->chunk(400) as $lote) {
            $f->confirmar($lote->values()->all());
        }

        $this->info("Listo: {$total} documentos borrados.");

        return self::SUCCESS;
    }
}
