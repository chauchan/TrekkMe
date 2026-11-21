<?php

namespace Tests\Feature;

use App\Services\Firestore\ConflictoDeEscritura;
use App\Services\Firestore\FirestoreClient;
use Illuminate\Support\Carbon;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Lcobucci\JWT\Token\DataSet;
use Lcobucci\JWT\UnencryptedToken;
use Mockery\MockInterface;
use Tests\TestCase;

// Iniciar, regresar y enviar ubicación. Firebase y Firestore se simulan: el TransicionService es el real.
class TransicionesExcursionistaTest extends TestCase
{
    private const CABECERA = ['Authorization' => 'Bearer cualquiera'];

    /** Escrituras que el servicio mandó a Firestore en el último commit */
    private array $escrituras = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(FirebaseAuth::class, function (MockInterface $m) {
            $token = \Mockery::mock(UnencryptedToken::class);
            $token->shouldReceive('claims')->andReturn(new DataSet(['sub' => 'usr-001', 'rol' => 'excursionista'], ''));
            $m->shouldReceive('verifyIdToken')->andReturn($token);
        });
    }

    /** Simula Firestore con una excursión y su aviso. $dueno: quién la creó. */
    private function firestore(string $estado, string $dueno = 'usr-001', bool $existe = true, ?\Throwable $falla = null): void
    {
        $this->mock(FirestoreClient::class, function (MockInterface $m) use ($estado, $dueno, $existe, $falla) {
            $m->shouldReceive('obtener')->with('excursiones', 'exc-010')->andReturn(
                $existe ? ['id' => 'exc-010', 'estado' => $estado, 'excursionistaId' => $dueno, 'rescatistaId' => '', '_actualizado' => 'v-exc'] : null
            );
            $m->shouldReceive('obtener')->with('avisos', 'exc-010')->andReturn(
                $existe ? ['id' => 'exc-010', 'estado' => $estado, 'excursionistaId' => $dueno, 'jurisdiccionId' => 'jur-001', '_actualizado' => 'v-avi'] : null
            );
            $m->shouldReceive('actualizacion')->andReturnUsing(
                fn ($coleccion, $id, $campos, $version) => compact('coleccion', 'id', 'campos', 'version')
            );

            $espera = $m->shouldReceive('confirmar');
            if ($falla) {
                $espera->andThrow($falla);
            } else {
                $espera->andReturnUsing(function (array $lote) {
                    $this->escrituras = $lote;
                });
            }
        });
    }

    private function sinEscrituras(): void
    {
        $this->assertSame([], $this->escrituras, 'No debía escribir nada en Firestore.');
    }

    // ------------------------------------------------------- iniciar / regresar

    public function test_inicia_una_excursion_planificada(): void
    {
        $this->firestore('planificada');

        $this->postJson('/api/v1/excursionista/excursiones/exc-010/iniciar', ['estado_esperado' => 'planificada'], self::CABECERA)
            ->assertOk()
            ->assertExactJson(['data' => ['id' => 'exc-010', 'estado' => 'en_curso']]);

        // Cambia las dos colecciones juntas, cada una con la versión que se leyó
        $this->assertCount(2, $this->escrituras);
        $this->assertSame(['coleccion' => 'excursiones', 'id' => 'exc-010', 'campos' => ['estado' => 'en_curso'], 'version' => 'v-exc'], $this->escrituras[0]);
        $this->assertSame(['coleccion' => 'avisos', 'id' => 'exc-010', 'campos' => ['estado' => 'en_curso'], 'version' => 'v-avi'], $this->escrituras[1]);
    }

    public function test_confirma_el_regreso_y_cierra_la_excursion(): void
    {
        $this->firestore('en_curso');

        $this->postJson('/api/v1/excursionista/excursiones/exc-010/regresar', ['estado_esperado' => 'en_curso'], self::CABECERA)
            ->assertOk()
            ->assertJsonPath('data.estado', 'cerrada');

        $this->assertSame('cerrada', $this->escrituras[0]['campos']['estado']);
    }

    public function test_si_el_estado_cambio_responde_409_y_no_escribe(): void
    {
        // La app tenía "en curso" en pantalla, pero el servidor ya la pasó a alerta
        $this->firestore('en_alerta');

        $this->postJson('/api/v1/excursionista/excursiones/exc-010/regresar', ['estado_esperado' => 'en_curso'], self::CABECERA)
            ->assertStatus(409)
            ->assertJsonPath('message', 'Tu excursión cambió de estado. Actualiza e intenta de nuevo.');

        $this->sinEscrituras();
    }

    public function test_no_se_puede_regresar_desde_alerta_aunque_la_app_lo_sepa(): void
    {
        $this->firestore('en_alerta');

        $this->postJson('/api/v1/excursionista/excursiones/exc-010/regresar', ['estado_esperado' => 'en_alerta'], self::CABECERA)
            ->assertStatus(409);

        $this->sinEscrituras();
    }

    public function test_no_se_puede_iniciar_una_excursion_ya_en_curso(): void
    {
        $this->firestore('en_curso');

        $this->postJson('/api/v1/excursionista/excursiones/exc-010/iniciar', ['estado_esperado' => 'en_curso'], self::CABECERA)
            ->assertStatus(409);

        $this->sinEscrituras();
    }

    public function test_un_conflicto_de_escritura_responde_409(): void
    {
        $this->firestore('planificada', falla: new ConflictoDeEscritura('cambió'));

        $this->postJson('/api/v1/excursionista/excursiones/exc-010/iniciar', ['estado_esperado' => 'planificada'], self::CABECERA)
            ->assertStatus(409)
            ->assertJsonPath('message', 'Tu excursión cambió de estado. Actualiza e intenta de nuevo.');
    }

    public function test_no_actua_sobre_excursiones_ajenas_ni_sin_dueno(): void
    {
        foreach (['usr-999', ''] as $dueno) {
            $this->firestore('planificada', dueno: $dueno);

            $this->postJson('/api/v1/excursionista/excursiones/exc-010/iniciar', ['estado_esperado' => 'planificada'], self::CABECERA)
                ->assertForbidden()
                ->assertJsonPath('message', 'No tienes acceso a esta excursión.');

            $this->sinEscrituras();
        }
    }

    public function test_excursion_inexistente_da_404(): void
    {
        $this->firestore('planificada', existe: false);

        $this->postJson('/api/v1/excursionista/excursiones/exc-010/iniciar', ['estado_esperado' => 'planificada'], self::CABECERA)
            ->assertNotFound();
    }

    public function test_exige_el_estado_esperado_y_solo_acepta_iniciar_y_regresar(): void
    {
        $this->firestore('planificada');

        $this->postJson('/api/v1/excursionista/excursiones/exc-010/iniciar', [], self::CABECERA)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('estado_esperado');

        // Acciones de carabinero o rescatista no existen para esta ruta
        $this->postJson('/api/v1/excursionista/excursiones/exc-010/escalar', ['estado_esperado' => 'en_alerta'], self::CABECERA)
            ->assertNotFound();
    }

    // --------------------------------------------------------------- ubicación

    public function test_guarda_la_ubicacion_y_la_bateria_en_las_dos_colecciones(): void
    {
        Carbon::setTestNow('2026-11-21T10:00:00Z');
        $this->firestore('en_curso');

        $this->postJson('/api/v1/excursionista/excursiones/exc-010/ubicacion', ['lat' => -37.8235, 'lng' => -73.0312, 'bateria' => 78], self::CABECERA)
            ->assertOk()
            ->assertJsonPath('data.id', 'exc-010')
            ->assertJsonPath('data.ultima_lat', -37.8235)
            ->assertJsonPath('data.ultima_lng', -73.0312)
            ->assertJsonPath('data.bateria', 78)
            ->assertJsonPath('data.ultima_ubicacion_at', '2026-11-21T10:00:00+00:00');

        $this->assertCount(2, $this->escrituras);
        foreach ($this->escrituras as $escritura) {
            $this->assertSame(-37.8235, $escritura['campos']['ultimaLat']);
            $this->assertSame(78, $escritura['campos']['bateria']);
            $this->assertTrue(Carbon::parse('2026-11-21T10:00:00Z')->equalTo($escritura['campos']['ultimaUbicacionAt']));
        }
        $this->assertSame(['v-exc', 'v-avi'], array_column($this->escrituras, 'version'));
    }

    public function test_la_bateria_es_opcional(): void
    {
        $this->firestore('en_alerta');

        $this->postJson('/api/v1/excursionista/excursiones/exc-010/ubicacion', ['lat' => -37.8, 'lng' => -73.0], self::CABECERA)
            ->assertOk()
            ->assertJsonPath('data.bateria', null);
    }

    public function test_no_acepta_ubicacion_si_no_esta_en_marcha(): void
    {
        foreach (['planificada', 'cerrada'] as $estado) {
            $this->firestore($estado);

            $this->postJson('/api/v1/excursionista/excursiones/exc-010/ubicacion', ['lat' => -37.8, 'lng' => -73.0], self::CABECERA)
                ->assertStatus(409);

            $this->sinEscrituras();
        }
    }

    public function test_valida_los_rangos_de_la_ubicacion(): void
    {
        $this->firestore('en_curso');

        $this->postJson('/api/v1/excursionista/excursiones/exc-010/ubicacion', ['lat' => 91, 'lng' => -181, 'bateria' => 101], self::CABECERA)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lat', 'lng', 'bateria']);

        $this->postJson('/api/v1/excursionista/excursiones/exc-010/ubicacion', [], self::CABECERA)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lat', 'lng']);

        $this->sinEscrituras();
    }

    public function test_no_guarda_ubicacion_de_una_excursion_ajena(): void
    {
        $this->firestore('en_curso', dueno: 'usr-999');

        $this->postJson('/api/v1/excursionista/excursiones/exc-010/ubicacion', ['lat' => -37.8, 'lng' => -73.0], self::CABECERA)
            ->assertForbidden();

        $this->sinEscrituras();
    }
}
