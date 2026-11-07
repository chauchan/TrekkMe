<?php

namespace App\Console\Commands;

use App\Enums\EstadoExcursion;
use App\Services\Firestore\FirestoreClient;
use Illuminate\Console\Command;

class FirestoreNormalizarEstadosCommand extends Command
{
    protected $signature = 'firestore:normalizar-estados {--aplicar : Escribe los cambios (sin esta opción solo los muestra)}';

    protected $description = 'Cambia el estado de las excursiones de texto ("En operativo") a código ("en_operativo"), sin alterar su valor';

    public function handle(FirestoreClient $f): int
    {
        foreach (['excursiones', 'avisos'] as $coleccion) {
            foreach ($f->consultar($coleccion) as $doc) {
                $actual = (string) ($doc['estado'] ?? '');
                $estado = EstadoExcursion::desdeTexto($actual);

                if (! $estado) {
                    $this->warn("{$coleccion}/{$doc['id']}: estado desconocido \"{$actual}\", no se toca.");

                    continue;
                }
                if ($actual === $estado->value) {
                    continue;
                }

                $this->line("{$coleccion}/{$doc['id']}: \"{$actual}\" → \"{$estado->value}\"");

                if ($this->option('aplicar')) {
                    // Con la fecha de actualización leída: si alguien lo cambió mientras tanto, no se pisa
                    $f->confirmar([$f->actualizacion($coleccion, $doc['id'], ['estado' => $estado->value], $doc['_actualizado'])]);
                }
            }
        }

        $this->info($this->option('aplicar') ? 'Listo.' : 'Simulación: agrega --aplicar para escribir.');

        return self::SUCCESS;
    }
}
