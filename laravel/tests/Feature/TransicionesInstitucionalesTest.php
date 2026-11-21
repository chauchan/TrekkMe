<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Services\Firestore\FirestoreClient;
use App\Services\Firestore\UsuarioRepository;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Lcobucci\JWT\Token\DataSet;
use Lcobucci\JWT\UnencryptedToken;
use Mockery\MockInterface;
use Tests\TestCase;

// No regresión: carabinero y rescatista (app Ionic) siguen usando el mismo TransicionService que el excursionista.
class TransicionesInstitucionalesTest extends TestCase
{
    private const CABECERA = ['Authorization' => 'Bearer cualquiera'];

    private array $escrituras = [];

    private function sesion(string $id, string $rol, ?string $jurisdiccion = null): void
    {
        $this->mock(FirebaseAuth::class, function (MockInterface $m) use ($id, $rol, $jurisdiccion) {
            $token = \Mockery::mock(UnencryptedToken::class);
            $token->shouldReceive('claims')->andReturn(new DataSet(['sub' => $id, 'rol' => $rol, 'jurisdiccionId' => $jurisdiccion], ''));
            $m->shouldReceive('verifyIdToken')->andReturn($token);
        });
    }

    private function firestore(string $estado, string $rescatistaId = ''): void
    {
        $this->mock(FirestoreClient::class, function (MockInterface $m) use ($estado, $rescatistaId) {
            $m->shouldReceive('obtener')->with('excursiones', 'exc-010')->andReturn(
                ['id' => 'exc-010', 'estado' => $estado, 'excursionistaId' => 'usr-001', 'rescatistaId' => $rescatistaId, '_actualizado' => 'v-exc']
            );
            $m->shouldReceive('obtener')->with('avisos', 'exc-010')->andReturn(
                ['id' => 'exc-010', 'estado' => $estado, 'jurisdiccionId' => 'jur-001', '_actualizado' => 'v-avi']
            );
            $m->shouldReceive('actualizacion')->andReturnUsing(
                fn ($coleccion, $id, $campos, $version) => compact('coleccion', 'id', 'campos', 'version')
            );
            $m->shouldReceive('confirmar')->andReturnUsing(function (array $lote) {
                $this->escrituras = $lote;
            });
        });
    }

    public function test_el_carabinero_escala_y_asigna_rescatista(): void
    {
        $this->sesion('carab-001', 'carabinero', 'jur-001');
        $this->firestore('en_alerta');
        $this->mock(UsuarioRepository::class, fn (MockInterface $m) => $m->shouldReceive('porId')->with('resc-001')->andReturn([
            'id' => 'resc-001', 'rol' => RolUsuario::Rescatista, 'jurisdiccionId' => 'jur-001',
        ]));

        $this->postJson('/api/v1/carabinero/excursiones/exc-010/escalar', ['estado_esperado' => 'en_alerta', 'rescatista_id' => 'resc-001'], self::CABECERA)
            ->assertOk()
            ->assertExactJson(['data' => ['id' => 'exc-010', 'estado' => 'en_operativo']]);

        $this->assertCount(2, $this->escrituras);
        $this->assertSame(['estado' => 'en_operativo', 'rescatistaId' => 'resc-001'], $this->escrituras[0]['campos']);
    }

    public function test_el_carabinero_de_otra_jurisdiccion_recibe_403_con_el_mensaje_de_siempre(): void
    {
        $this->sesion('carab-002', 'carabinero', 'jur-999');
        $this->firestore('en_alerta');

        $this->postJson('/api/v1/carabinero/excursiones/exc-010/cerrar', ['estado_esperado' => 'en_alerta'], self::CABECERA)
            ->assertForbidden()
            ->assertJsonPath('message', 'No tienes acceso a este caso.');

        $this->assertSame([], $this->escrituras);
    }

    public function test_el_carabinero_recibe_el_409_de_siempre_si_otro_se_adelanto(): void
    {
        $this->sesion('carab-001', 'carabinero', 'jur-001');
        $this->firestore('en_operativo');

        $this->postJson('/api/v1/carabinero/excursiones/exc-010/cerrar', ['estado_esperado' => 'en_alerta'], self::CABECERA)
            ->assertStatus(409)
            ->assertJsonPath('message', 'Otra persona ya gestionó este aviso. Vuelve a abrirlo.');
    }

    public function test_el_rescatista_asignado_solicita_el_cierre(): void
    {
        $this->sesion('resc-001', 'rescatista', 'jur-001');
        $this->firestore('en_operativo', rescatistaId: 'resc-001');

        $this->postJson('/api/v1/rescatista/casos/exc-010/solicitar-cierre', ['estado_esperado' => 'en_operativo'], self::CABECERA)
            ->assertOk()
            ->assertJsonPath('data.estado', 'cierre_pendiente');
    }

    public function test_un_rescatista_no_asignado_recibe_403(): void
    {
        $this->sesion('resc-002', 'rescatista', 'jur-001');
        $this->firestore('en_operativo', rescatistaId: 'resc-001');

        $this->postJson('/api/v1/rescatista/casos/exc-010/solicitar-cierre', ['estado_esperado' => 'en_operativo'], self::CABECERA)
            ->assertForbidden()
            ->assertJsonPath('message', 'No tienes acceso a este caso.');
    }

    public function test_el_excursionista_no_puede_usar_las_acciones_de_carabinero(): void
    {
        $this->sesion('usr-001', 'excursionista');
        $this->firestore('en_alerta');

        $this->postJson('/api/v1/carabinero/excursiones/exc-010/cerrar', ['estado_esperado' => 'en_alerta'], self::CABECERA)
            ->assertForbidden();

        $this->assertSame([], $this->escrituras);
    }
}
