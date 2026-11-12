<?php

namespace App\Console\Commands;

use App\Enums\RolUsuario;
use App\Services\Firestore\ConflictoDeEscritura;
use App\Services\Firestore\ExcursionRepository;
use App\Services\Firestore\FirestoreClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class FirestoreSeedCommand extends Command
{
    protected $signature = 'firestore:seed {--dry-run : Solo muestra lo que haría}';

    protected $description = 'Carga datos de ejemplo en Firestore. Solo crea lo que falta: nunca pisa documentos existentes';

    // Jurisdicciones y senderos de ejemplo (los nombres de jurisdicción son provisorios)
    private const JURISDICCIONES = [
        'jur-001' => 'Concepción',
        'jur-002' => 'Biobío interior',
    ];

    private const SENDEROS = [
        'sen-001' => ['PN Nonguén - Sendero Los Canelos', 'jur-001'],
        'sen-002' => ['PN Nonguén - Sendero Los Copihues', 'jur-001'],
        'sen-003' => ['Laguna del Laja - Sierra Velluda', 'jur-001'],
        'sen-004' => ['PN Nahuelbuta - Piedra del Águila', 'jur-002'],
    ];

    // id => [nombre, correo, rol, jurisdicción]
    private const USUARIOS = [
        'adm-001' => ['Admin de Prueba', 'sysadmin@trekkme.cl', RolUsuario::Admin, ''],
        'carab-001' => ['Carabinero de Prueba', 'carabinero@trekkme.test', RolUsuario::Carabinero, 'jur-001'],
        'carab-002' => ['Carabinero Biobío', 'carabinero2@trekkme.test', RolUsuario::Carabinero, 'jur-002'],
        'resc-001' => ['Andrés Poblete', 'andres.poblete@trekkme.test', RolUsuario::Rescatista, 'jur-001'],
        'resc-002' => ['Carolina Henríquez', 'carolina.henriquez@trekkme.test', RolUsuario::Rescatista, 'jur-002'],
        'resc-003' => ['Tomás Riquelme', 'tomas.riquelme@trekkme.test', RolUsuario::Rescatista, 'jur-001'],
    ];

    private bool $simular = false;

    public function handle(FirestoreClient $f, ExcursionRepository $excursiones): int
    {
        $this->simular = (bool) $this->option('dry-run');
        $datos = require database_path('datos/ejemplo.php');

        foreach (self::JURISDICCIONES as $id => $nombre) {
            $this->crearSiFalta($f, 'jurisdicciones', $id, ['nombre' => $nombre]);
        }

        $jurisdiccionPorSendero = [];
        foreach (self::SENDEROS as $id => [$nombre, $jurisdiccion]) {
            $this->crearSiFalta($f, 'senderos', $id, ['nombre' => $nombre, 'jurisdiccionId' => $jurisdiccion]);
            $jurisdiccionPorSendero[$nombre] = [$id, $jurisdiccion];
        }

        $this->usuarios($f);

        foreach ($datos['excursiones'] as $id => $campos) {
            $this->crearSiFalta($f, 'excursiones', $id, $excursiones->completar($campos));
        }

        // Un aviso por cada excursión que exista en Firestore (también las que ya estaban)
        foreach ($excursiones->todas() as $e) {
            [$senderoId, $jurisdiccionId] = $jurisdiccionPorSendero[$e['ruta']] ?? [null, null];
            if (! $senderoId) {
                $this->warn("avisos/{$e['id']}: la ruta \"{$e['ruta']}\" no coincide con ningún sendero, se omite.");

                continue;
            }
            $this->crearSiFalta($f, 'avisos', $e['id'], $excursiones->avisoDe($e, $senderoId, $jurisdiccionId));
        }

        foreach ($datos['bitacoras'] as $id => [$excursionId, $ruta, $inicio, $fin, $texto, $registrado]) {
            [$senderoId] = $jurisdiccionPorSendero[$ruta] ?? [null];
            $this->crearSiFalta($f, 'bitacora', $id, [
                'excursionId' => $excursionId,
                'rescatistaId' => 'resc-001',
                'senderoId' => $senderoId,
                'ruta' => $ruta,
                'horaInicio' => now()->subHours($inicio),
                'horaFin' => $fin === null ? null : now()->subHours($fin),
                'texto' => $texto,
                'registradoAt' => now()->subHours($registrado),
                'version' => 1,
            ]);
        }

        return self::SUCCESS;
    }

    // Usuarios: si no existe se crea completo; si existe, solo se agregan los campos que le faltan
    private function usuarios(FirestoreClient $f): void
    {
        foreach (self::USUARIOS as $id => [$nombre, $email, $rol, $jurisdiccion]) {
            $actual = $f->obtener('usuarios', $id);

            if (! $actual) {
                $this->crearSiFalta($f, 'usuarios', $id, [
                    'nombre' => $nombre, 'email' => $email, 'rol' => $rol->value,
                    'jurisdiccionId' => $jurisdiccion, 'passwordHash' => Hash::make('password'),
                ]);

                continue;
            }

            $faltan = array_filter([
                'email' => $email, 'rol' => $rol->value, 'jurisdiccionId' => $jurisdiccion,
                'passwordHash' => Hash::make('password'),
            ], fn ($v, $campo) => $v !== '' && (! isset($actual[$campo]) || $actual[$campo] === ''), ARRAY_FILTER_USE_BOTH);

            if (! $faltan) {
                $this->line("usuarios/{$id}: ya completo");

                continue;
            }
            $this->line("usuarios/{$id}: agrega ".implode(', ', array_keys($faltan)));
            if (! $this->simular) {
                $f->confirmar([$f->actualizacion('usuarios', $id, $faltan, $actual['_actualizado'])]);
            }
        }
    }

    private function crearSiFalta(FirestoreClient $f, string $coleccion, string $id, array $campos): void
    {
        if ($this->simular) {
            $existe = $f->obtener($coleccion, $id) !== null;
            $this->line(sprintf('[dry-run] %s/%s: %s', $coleccion, $id, $existe ? 'ya existe' : 'se crearía'));

            return;
        }

        try {
            $f->confirmar([$f->escritura($coleccion, $id, $campos, null, false)]);
            $this->line("{$coleccion}/{$id}: creado");
        } catch (ConflictoDeEscritura) {
            $this->line("{$coleccion}/{$id}: ya existía, sin cambios");
        }
    }
}
