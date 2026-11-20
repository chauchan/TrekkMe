<?php

namespace Tests\Feature;

use App\Enums\EstadoExcursion;
use App\Services\AlertaService;
use App\Services\Firestore\ConflictoDeEscritura;
use App\Services\Firestore\ExcursionRepository;
use App\Services\Firestore\FirestoreClient;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use Tests\TestCase;

// El paso automático de "en curso" a "en alerta" cuando vence la hora de retorno.
class AlertaServiceTest extends TestCase
{
    private const AHORA = '2026-11-21T20:00:00Z';

    private function excursion(string $id, EstadoExcursion $estado, string $retorno): array
    {
        return ['id' => $id, 'estado' => $estado, 'retornoAt' => Carbon::parse($retorno), '_actualizado' => "v-exc-{$id}"];
    }

    /** @return array{0: AlertaService, 1: \ArrayObject} servicio y lotes que se enviaron a Firestore */
    private function servicio(array $excursiones, ?\Throwable $falla = null): array
    {
        $lotes = new \ArrayObject;

        $firestore = $this->mock(FirestoreClient::class, function (MockInterface $m) use ($lotes, $falla) {
            $m->shouldReceive('obtener')->with('avisos', \Mockery::any())->andReturnUsing(
                fn ($c, $id) => ['id' => $id, '_actualizado' => "v-avi-{$id}"]
            );
            $m->shouldReceive('actualizacion')->andReturnUsing(
                fn ($coleccion, $id, $campos, $version) => compact('coleccion', 'id', 'campos', 'version')
            );
            $espera = $m->shouldReceive('confirmar');
            $falla ? $espera->andThrow($falla) : $espera->andReturnUsing(fn (array $lote) => $lotes->append($lote));
        });

        $repo = $this->mock(ExcursionRepository::class, fn (MockInterface $m) => $m->shouldReceive('todas')->andReturn($excursiones));

        return [new AlertaService($firestore, $repo), $lotes];
    }

    public function test_marca_solo_las_en_curso_con_el_retorno_vencido(): void
    {
        [$servicio, $lotes] = $this->servicio([
            $this->excursion('exc-001', EstadoExcursion::EnCurso, '2026-11-21T18:00:00Z'),      // vencida
            $this->excursion('exc-002', EstadoExcursion::EnCurso, '2026-11-21T23:00:00Z'),      // aún a tiempo
            $this->excursion('exc-003', EstadoExcursion::EnCurso, self::AHORA),                  // justo ahora: todavía no
            $this->excursion('exc-004', EstadoExcursion::Planificada, '2026-11-20T10:00:00Z'),  // no salió
            $this->excursion('exc-005', EstadoExcursion::EnAlerta, '2026-11-20T10:00:00Z'),     // ya en alerta
            $this->excursion('exc-006', EstadoExcursion::Cerrada, '2026-11-20T10:00:00Z'),      // terminada
            $this->excursion('exc-007', EstadoExcursion::EnCurso, '2026-11-20T10:00:00Z'),      // vencida
        ]);

        $marcadas = $servicio->marcarVencidas(Carbon::parse(self::AHORA));

        $this->assertSame(['exc-001', 'exc-007'], $marcadas);
        $this->assertCount(2, $lotes);

        // Cada una cambia "excursiones" y "avisos" juntas, con la versión leída
        $this->assertSame([
            ['coleccion' => 'excursiones', 'id' => 'exc-001', 'campos' => ['estado' => 'en_alerta'], 'version' => 'v-exc-exc-001'],
            ['coleccion' => 'avisos', 'id' => 'exc-001', 'campos' => ['estado' => 'en_alerta'], 'version' => 'v-avi-exc-001'],
        ], $lotes[0]);
    }

    public function test_sin_vencidas_no_escribe_nada(): void
    {
        [$servicio, $lotes] = $this->servicio([
            $this->excursion('exc-001', EstadoExcursion::EnCurso, '2026-11-21T23:00:00Z'),
        ]);

        $this->assertSame([], $servicio->marcarVencidas(Carbon::parse(self::AHORA)));
        $this->assertCount(0, $lotes);
    }

    public function test_si_cambio_mientras_tanto_se_deja_como_esta(): void
    {
        // Por ejemplo, el excursionista confirmó su regreso justo antes
        [$servicio] = $this->servicio(
            [$this->excursion('exc-001', EstadoExcursion::EnCurso, '2026-11-21T18:00:00Z')],
            new ConflictoDeEscritura('cambió'),
        );

        $this->assertSame([], $servicio->marcarVencidas(Carbon::parse(self::AHORA)));
    }

    public function test_el_comando_informa_lo_que_marco(): void
    {
        $this->mock(AlertaService::class, fn (MockInterface $m) => $m->shouldReceive('marcarVencidas')->once()->andReturn(['exc-001', 'exc-007']));

        $this->artisan('excursiones:marcar-alertas')
            ->expectsOutputToContain('2 excursión(es) pasaron a "en alerta".')
            ->expectsOutputToContain('exc-007')
            ->assertSuccessful();
    }

    public function test_el_comando_esta_programado_cada_minuto(): void
    {
        $eventos = collect($this->app->make(Schedule::class)->events())
            ->filter(fn ($e) => str_contains($e->command, 'excursiones:marcar-alertas'));

        $this->assertCount(1, $eventos);
        $this->assertSame('* * * * *', $eventos->first()->expression);
    }
}
