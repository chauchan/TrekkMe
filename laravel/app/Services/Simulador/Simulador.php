<?php

namespace App\Services\Simulador;

use App\Enums\EstadoExcursion;
use App\Services\Firestore\ConflictoDeEscritura;
use App\Services\Firestore\ExcursionRepository;
use App\Services\Firestore\FirestoreClient;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\HttpClientException;
use RuntimeException;

// Hace el papel de la app del excursionista (Flutter) para la demo: crea excursiones,
// las pasa de planificada a en curso, envía ubicación y batería, y al llegar la hora de
// retorno las cierra (volvió a tiempo) o las deja en alerta (se perdió).
// Escribe en Firestore y Ionic se actualiza solo. Nunca cambia el estado de un caso que ya
// gestiona una persona (en operativo o cierre pendiente): solo le actualiza la ubicación.
class Simulador
{
    public const PREFIJO = 'sim-';

    private const ACTIVOS = ['planificada', 'en_curso', 'en_alerta', 'en_operativo', 'cierre_pendiente'];

    // Para no gastar la cuota gratuita de Firestore: como máximo estas excursiones se escriben por tick
    private const MAX_ESCRITURAS_POR_TICK = 3;

    // Si ya hay tantas perdidas a la vez, las nuevas vuelven a tiempo (para no llenar la bandeja de alertas)
    private const MAX_PERDIDAS = 3;

    // Punto de partida aproximado de cada sendero del seed
    private const INICIO_SENDERO = [
        'sen-001' => [-36.8795, -72.9731],
        'sen-002' => [-36.8720, -72.9860],
        'sen-003' => [-37.4105, -71.3540],
        'sen-004' => [-37.8235, -73.0312],
    ];

    private const INICIO_POR_DEFECTO = [-36.8270, -73.0500];

    /** @var array<int, array{id: string, nombre: string}> */
    private array $senderos = [];

    private int $siguiente = 1;

    private int $intervalo = 20;

    private int $maxActivas = 4;

    private int $perdidas = 30;

    /** @var array{0: int, 1: int} */
    private array $duracion = [3, 5];

    private string $jurisdiccion = 'jur-001';

    public function __construct(
        private FirestoreClient $firestore,
        private ExcursionRepository $excursiones,
        private GeneradorExcursion $generador,
    ) {}

    /**
     * @param  int  $intervalo  segundos entre ticks
     * @param  int  $maxActivas  máximo de excursiones planificadas o en curso a la vez
     * @param  int  $perdidas  porcentaje de excursiones que se pierden
     * @param  array{0: int, 1: int}  $duracion  minutos que dura una excursión (mín, máx)
     */
    public function configurar(int $intervalo, int $maxActivas, int $perdidas, array $duracion, string $jurisdiccion): void
    {
        $this->intervalo = $intervalo;
        $this->maxActivas = $maxActivas;
        $this->perdidas = $perdidas;
        $this->duracion = $duracion;
        $this->jurisdiccion = $jurisdiccion;

        $this->senderos = collect($this->firestore->consultar('senderos', [['jurisdiccionId', '==', $jurisdiccion]]))
            ->map(fn ($s) => ['id' => $s['id'], 'nombre' => $s['nombre']])
            ->values()
            ->all();

        if (! $this->senderos) {
            throw new RuntimeException("No hay senderos en la jurisdicción {$jurisdiccion}. Corre primero: php artisan firestore:seed");
        }

        // Sigue la numeración de las que ya existen: sim-0001, sim-0002...
        $this->siguiente = 1 + (collect($this->firestore->consultar('excursiones'))
            ->pluck('id')
            ->filter(fn ($id) => str_starts_with($id, self::PREFIJO))
            ->map(fn ($id) => (int) substr($id, strlen(self::PREFIJO)))
            ->max() ?? 0);
    }

    /** Un paso de la simulación. Devuelve lo que pasó, una línea por cambio. */
    public function tick(): array
    {
        $ahora = now();
        $log = [];

        $activas = collect($this->firestore->consultar('excursiones', [['estado', 'in', self::ACTIVOS]]))
            ->filter(fn ($e) => str_starts_with($e['id'], self::PREFIJO));

        // 1) Cambios de estado primero; después las ubicaciones más antiguas
        $pendientes = $activas
            ->map(fn ($e) => [$e, $this->siguientePaso($e, $ahora)])
            ->filter(fn ($p) => $p[1] !== null)
            ->sortBy(fn ($p) => [$p[1] === 'ubicacion' ? 1 : 0, $p[0]['ultimaUbicacionAt']?->getTimestamp() ?? 0])
            ->take(self::MAX_ESCRITURAS_POR_TICK);

        foreach ($pendientes as [$excursion, $paso]) {
            $linea = $this->aplicar($excursion, $paso, $ahora);
            if ($linea) {
                $log[] = $linea;
            }
        }

        // 2) Una excursión nueva por tick, si hay cupo
        $enMarcha = $activas->filter(fn ($e) => in_array($e['estado'], ['planificada', 'en_curso'], true))->count();
        if ($enMarcha < $this->maxActivas) {
            $perdidasAhora = $activas->filter(fn ($e) => ($e['simGuion'] ?? '') === 'se_pierde')->count();
            $log[] = $this->crear($ahora, $perdidasAhora < self::MAX_PERDIDAS);
        }

        return $log;
    }

    // Qué le toca a esta excursión en este tick, o null si nada
    private function siguientePaso(array $e, CarbonInterface $ahora): ?string
    {
        $sePierde = ($e['simGuion'] ?? '') === 'se_pierde';

        if ($e['estado'] === 'planificada') {
            return $ahora->gte($e['salidaAt']) ? 'salir' : null;
        }
        if ($e['estado'] === 'en_curso' && ! $sePierde && $ahora->gte($e['simVueltaAt'] ?? $e['retornoAt'])) {
            return 'volver';
        }
        if ($e['estado'] === 'en_curso' && $sePierde && $ahora->gte($e['retornoAt'])) {
            return 'alerta';
        }

        // Ubicación: cada 2 o 3 ticks, hasta que el teléfono se apaga
        if (! empty($e['simApagado']) || ! $e['ultimaUbicacionAt']) {
            return null;
        }
        $cada = $this->intervalo * (($e['simCadaTicks'] ?? 2));
        if ($ahora->diffInSeconds($e['ultimaUbicacionAt'], true) < $cada - 2) {
            return null;
        }
        // Perdido y ya en alerta: la señal falla, a veces no llega nada
        if ($sePierde && $e['estado'] !== 'en_curso' && mt_rand(0, 1) === 0) {
            return null;
        }

        return 'ubicacion';
    }

    private function aplicar(array $e, string $paso, CarbonInterface $ahora): ?string
    {
        $nombre = "{$e['id']} ({$e['excursionista']})";

        return match ($paso) {
            'salir' => $this->escribir($e, ['estado' => EstadoExcursion::EnCurso->value] + $this->primeraUbicacion($e, $ahora))
                ? "{$nombre}: planificada → en curso" : null,
            'volver' => $this->escribir($e, ['estado' => EstadoExcursion::Cerrada->value])
                ? "{$nombre}: volvió a tiempo → cerrada" : null,
            'alerta' => $this->escribir($e, ['estado' => EstadoExcursion::EnAlerta->value])
                ? "{$nombre}: no confirmó el retorno → EN ALERTA" : null,
            'ubicacion' => $this->moverse($e, $ahora, $nombre),
        };
    }

    private function primeraUbicacion(array $e, CarbonInterface $ahora): array
    {
        [$lat, $lng] = self::INICIO_SENDERO[$e['simSenderoId'] ?? ''] ?? self::INICIO_POR_DEFECTO;

        return [
            'ultimaLat' => round($lat + $this->azar(-0.0008, 0.0008), 6),
            'ultimaLng' => round($lng + $this->azar(-0.0008, 0.0008), 6),
            'ultimaUbicacionAt' => $ahora,
            'bateria' => mt_rand(55, 100),
        ];
    }

    // Avanza la posición por el rumbo y baja la batería. El que se pierde gasta más y se desvía
    private function moverse(array $e, CarbonInterface $ahora, string $nombre): ?string
    {
        $sePierde = ($e['simGuion'] ?? '') === 'se_pierde';
        $rumbo = (float) ($e['simRumbo'] ?? 0);
        $extra = [];

        if ($sePierde) {
            $rumbo += $this->azar(-60, 60);
        } elseif (empty($e['simRegresando']) && $ahora->gte($e['salidaAt']->copy()->addSeconds(
            (int) ($e['salidaAt']->diffInSeconds($e['simVueltaAt'] ?? $e['retornoAt'], true) / 2)
        ))) {
            // A mitad de camino da la vuelta hacia el inicio
            $rumbo += 180;
            $extra['simRegresando'] = true;
        } else {
            $rumbo += $this->azar(-20, 20);
        }

        $paso = $this->azar(0.0012, 0.0028);
        $bateria = (int) $e['bateria'] - ($sePierde ? mt_rand(3, 7) : mt_rand(1, 3));
        $apagado = $bateria <= 2;
        if ($apagado) {
            $bateria = mt_rand(1, 3);
        }

        $ok = $this->escribir($e, [
            'ultimaLat' => round($e['ultimaLat'] + $paso * cos(deg2rad($rumbo)), 6),
            'ultimaLng' => round($e['ultimaLng'] + $paso * sin(deg2rad($rumbo)), 6),
            'ultimaUbicacionAt' => $ahora,
            'bateria' => $bateria,
        ], $extra + [
            'simRumbo' => fmod($rumbo + 360, 360),
            'simCadaTicks' => mt_rand(2, 3),
            'simApagado' => $apagado,
        ]);

        if (! $ok) {
            return null;
        }

        return $apagado ? "{$nombre}: última ubicación, batería {$bateria}% → se apagó el teléfono" : "{$nombre}: ubicación nueva, batería {$bateria}%";
    }

    private function crear(CarbonInterface $ahora, bool $puedePerderse): string
    {
        $sendero = $this->senderos[mt_rand(0, count($this->senderos) - 1)];
        $sePierde = $puedePerderse && mt_rand(1, 100) <= $this->perdidas;

        $salida = $ahora->copy()->addSeconds($this->intervalo * mt_rand(1, 2));
        $duracion = mt_rand($this->duracion[0] * 60, $this->duracion[1] * 60);
        $retorno = $salida->copy()->addSeconds($duracion);

        $datos = $this->generador->generar($sendero['nombre'], $salida, $retorno);
        $id = sprintf('%s%04d', self::PREFIJO, $this->siguiente++);

        $simulacion = [
            'simulado' => true,
            'simGuion' => $sePierde ? 'se_pierde' : 'vuelve',
            'simSenderoId' => $sendero['id'],
            // El que vuelve llega un poco antes de la hora comprometida
            'simVueltaAt' => $salida->copy()->addSeconds((int) ($duracion * $this->azar(0.8, 0.97))),
            'simRumbo' => (float) mt_rand(0, 359),
            'simCadaTicks' => mt_rand(2, 3),
            'simApagado' => false,
        ];

        try {
            $this->firestore->confirmar([
                $this->firestore->escritura('excursiones', $id, $this->excursiones->completar($datos) + $simulacion, null, false),
                $this->firestore->escritura('avisos', $id, $this->excursiones->avisoDe($datos, $sendero['id'], $this->jurisdiccion), null, false),
            ]);
        } catch (ConflictoDeEscritura) {
            return "{$id}: ya existía, se intenta con el siguiente número en el próximo tick";
        }

        $guion = $sePierde ? 'se va a perder' : 'va a volver a tiempo';

        return "{$id} ({$datos['excursionista']}): excursión nueva en {$sendero['nombre']}, sale {$salida->format('H:i:s')}, retorno {$retorno->format('H:i:s')} ({$guion})";
    }

    /**
     * Escribe los mismos cambios en "excursiones" y "avisos" a la vez. $soloExcursion son campos
     * internos del simulador que el carabinero no necesita. Si una persona cambió la excursión
     * desde que se leyó, no se escribe nada y se reintenta en el próximo tick.
     */
    private function escribir(array $e, array $cambios, array $soloExcursion = []): bool
    {
        try {
            $this->firestore->confirmar([
                $this->firestore->actualizacion('excursiones', $e['id'], $cambios + $soloExcursion, $e['_actualizado']),
                $this->firestore->actualizacion('avisos', $e['id'], $cambios),
            ]);

            return true;
        } catch (ConflictoDeEscritura|HttpClientException) {
            return false;
        }
    }

    private function azar(float $min, float $max): float
    {
        return $min + mt_rand() / mt_getrandmax() * ($max - $min);
    }
}
