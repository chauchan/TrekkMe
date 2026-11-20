<?php

namespace Tests\Feature;

use App\Enums\EstadoExcursion;
use App\Services\Firestore\ExcursionRepository;
use App\Services\Firestore\FirestoreClient;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use Tests\TestCase;

// Qué se escribe en Firestore y cómo se consultan las excursiones de un excursionista.
class ExcursionRepositoryTest extends TestCase
{
    public function test_crear_guarda_el_dueno_en_excursiones_y_en_avisos(): void
    {
        $escrituras = [];
        $firestore = $this->mock(FirestoreClient::class, function (MockInterface $m) use (&$escrituras) {
            $m->shouldReceive('consultar')->with('excursiones')->andReturn([['id' => 'exc-009']]);
            $m->shouldReceive('escritura')->andReturnUsing(fn ($coleccion, $id, $datos) => compact('coleccion', 'id', 'datos'));
            $m->shouldReceive('confirmar')->once()->andReturnUsing(function (array $lote) use (&$escrituras) {
                $escrituras = $lote;
            });
        });

        $id = (new ExcursionRepository($firestore))->crear([
            'excursionistaId' => 'usr-001',
            'excursionista' => 'Trekker de Prueba',
            'rut' => '20.000.004-4',
            'telefono' => '+56 9 5555 0107',
            'edad' => 23,
            'experiencia' => 'Principiante',
            'condicionesMedicas' => 'Alergia',
            'acompanantes' => [['nombre' => 'Ana', 'rut' => '1-9', 'edad' => 20, 'condicionesMedicas' => 'Asma']],
            'ruta' => 'Sendero',
            'contactoNombre' => 'Paula',
            'contactoTelefono' => '+56 9 1',
            'salidaAt' => Carbon::parse('2026-11-21T08:00:00Z'),
            'retornoAt' => Carbon::parse('2026-11-21T16:00:00Z'),
            'estado' => EstadoExcursion::Planificada,
        ], 'sen-001', 'jur-001');

        $this->assertSame('exc-010', $id);
        $this->assertCount(2, $escrituras);

        [$excursion, $aviso] = $escrituras;
        $this->assertSame('excursiones', $excursion['coleccion']);
        $this->assertSame('avisos', $aviso['coleccion']);
        $this->assertSame('usr-001', $excursion['datos']['excursionistaId']);
        $this->assertSame('usr-001', $aviso['datos']['excursionistaId']);

        // El aviso no lleva datos médicos; la excursión completa sí
        $this->assertSame('Alergia', $excursion['datos']['condicionesMedicas']);
        $this->assertArrayNotHasKey('condicionesMedicas', $aviso['datos']);
        $this->assertSame(['nombre' => 'Ana', 'rut' => '1-9'], $aviso['datos']['acompanantes'][0]);
        $this->assertSame('jur-001', $aviso['datos']['jurisdiccionId']);
        $this->assertSame('planificada', $aviso['datos']['estado']);
    }

    public function test_las_excursiones_sin_dueno_quedan_con_texto_vacio(): void
    {
        $repo = new ExcursionRepository($this->mock(FirestoreClient::class));

        $this->assertSame('', $repo->completar([
            'excursionista' => 'A', 'rut' => '1', 'telefono' => '1', 'edad' => 1, 'experiencia' => 'Avanzado',
            'ruta' => 'R', 'contactoNombre' => 'C', 'contactoTelefono' => '1',
            'salidaAt' => now(), 'retornoAt' => now(), 'estado' => 'planificada',
        ])['excursionistaId']);
    }

    public function test_los_avisos_del_excursionista_se_filtran_y_vienen_del_mas_reciente(): void
    {
        $firestore = $this->mock(FirestoreClient::class, function (MockInterface $m) {
            $m->shouldReceive('consultar')
                ->once()
                ->with('avisos', [['excursionistaId', '==', 'usr-001']])
                ->andReturn([
                    ['id' => 'exc-001', 'estado' => 'cerrada', 'salidaAt' => Carbon::parse('2026-11-01T08:00:00Z')],
                    ['id' => 'exc-003', 'estado' => 'en_curso', 'salidaAt' => Carbon::parse('2026-11-20T08:00:00Z')],
                    ['id' => 'exc-002', 'estado' => 'planificada', 'salidaAt' => Carbon::parse('2026-11-10T08:00:00Z')],
                ]);
        });

        $lista = (new ExcursionRepository($firestore))->avisosDelExcursionista('usr-001');

        $this->assertSame(['exc-003', 'exc-002', 'exc-001'], array_column($lista, 'id'));
        $this->assertSame(EstadoExcursion::EnCurso, $lista[0]['estado']);
    }
}
