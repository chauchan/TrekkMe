<?php

namespace App\Console\Commands;

use App\Models\Bitacora;
use App\Models\Excursion;
use App\Models\Usuario;
use App\Services\FirestoreSync;
use Illuminate\Console\Command;

class FirestoreSyncCommand extends Command
{
    protected $signature = 'firestore:sync {--dry-run : Solo muestra lo que se enviaría}';

    protected $description = 'Envía a Firestore los usuarios, excursiones y bitácoras que falten (no pisa los existentes)';

    public function handle(FirestoreSync $firestore): int
    {
        $grupos = [
            'usuarios' => [Usuario::orderBy('id')->get(), fn ($m) => $firestore->usuario($m)],
            'excursiones' => [Excursion::orderBy('id')->get(), fn ($m) => $firestore->excursion($m)],
            'bitacora' => [Bitacora::orderBy('id')->get(), fn ($m) => $firestore->bitacora($m)],
        ];

        foreach ($grupos as $coleccion => [$modelos, $enviar]) {
            foreach ($modelos as $modelo) {
                if ($this->option('dry-run')) {
                    $this->line("[dry-run] {$coleccion}/{$modelo->id}");

                    continue;
                }

                try {
                    $resultado = $enviar($modelo);
                } catch (\Throwable $e) {
                    $this->error($e->getMessage());

                    return self::FAILURE;
                }

                $this->line(sprintf('%s/%s: %s', $coleccion, $modelo->id, $resultado === FirestoreSync::CREADO ? 'creado' : 'ya existía, sin cambios'));
            }
        }

        return self::SUCCESS;
    }
}
