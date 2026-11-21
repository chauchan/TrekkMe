<?php

namespace Tests\Feature;

use App\Enums\EstadoExcursion;
use App\Enums\RolUsuario;
use App\Services\Firestore\CatalogoRepository;
use App\Services\Firestore\ExcursionRepository;
use App\Services\Firestore\UsuarioRepository;
use Illuminate\Support\Carbon;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Lcobucci\JWT\Token\DataSet;
use Lcobucci\JWT\UnencryptedToken;
use Mockery\MockInterface;
use Tests\TestCase;

// Endpoints /excursionista/excursiones. Firebase y Firestore se simulan: no hace falta red ni credenciales.
class ExcursionesExcursionistaTest extends TestCase
{
    private const CABECERA = ['Authorization' => 'Bearer cualquiera'];

    private function iniciarSesion(string $rol = 'excursionista'): void
    {
        $this->mock(FirebaseAuth::class, function (MockInterface $m) use ($rol) {
            $token = \Mockery::mock(UnencryptedToken::class);
            $token->shouldReceive('claims')->andReturn(new DataSet(['sub' => 'usr-001', 'rol' => $rol], ''));
            $m->shouldReceive('verifyIdToken')->andReturn($token);
        });
    }

    private function datosValidos(array $cambios = []): array
    {
        return array_merge([
            'sendero_id' => 'sen-001',
            'salida_at' => '2026-11-21T08:00:00-03:00',
            'retorno_at' => '2026-11-21T16:00:00-03:00',
            'contacto_nombre' => 'Paula Díaz',
            'contacto_telefono' => '+56 9 5555 0108',
            'rut' => '20.000.004-4',
            'telefono' => '+56 9 5555 0107',
            'edad' => 23,
            'experiencia' => 'Principiante',
            'condiciones_medicas' => 'Alergia a picaduras',
            'detalle' => 'Polerón amarillo.',
            'lleva_linterna' => true,
            'acompanantes' => [
                ['nombre' => 'Catalina Ríos', 'rut' => '20.000.016-6', 'edad' => 22, 'condiciones_medicas' => 'Epilepsia'],
            ],
        ], $cambios);
    }

    private function avisoEjemplo(): array
    {
        return [
            'id' => 'exc-010',
            'senderoId' => 'sen-001',
            'ruta' => 'PN Nahuelbuta - Piedra del Águila',
            'estado' => EstadoExcursion::Planificada,
            'salidaAt' => Carbon::parse('2026-11-21T08:00:00-03:00'),
            'retornoAt' => Carbon::parse('2026-11-21T16:00:00-03:00'),
            'contactoNombre' => 'Paula Díaz',
            'contactoTelefono' => '+56 9 5555 0108',
            'detalle' => '',
            'llevaCarpa' => false,
            'colorCarpa' => '',
            'llevaLinterna' => true,
            'acompanantes' => [['nombre' => 'Catalina Ríos', 'rut' => '20.000.016-6']],
            'ultimaLat' => null,
            'ultimaLng' => null,
            'ultimaUbicacionAt' => null,
            'bateria' => null,
        ];
    }

    // ------------------------------------------------------------------ listar

    public function test_lista_solo_las_excursiones_del_usuario_del_token(): void
    {
        $this->iniciarSesion();
        $this->mock(ExcursionRepository::class, function (MockInterface $m) {
            $m->shouldReceive('avisosDelExcursionista')->once()->with('usr-001')->andReturn([$this->avisoEjemplo()]);
        });

        $this->getJson('/api/v1/excursionista/excursiones', self::CABECERA)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 'exc-010')
            ->assertJsonPath('data.0.sendero_id', 'sen-001')
            ->assertJsonPath('data.0.sendero', 'PN Nahuelbuta - Piedra del Águila')
            ->assertJsonPath('data.0.estado', 'planificada')
            ->assertJsonPath('data.0.salida_at', '2026-11-21T08:00:00-03:00')
            ->assertJsonPath('data.0.detalle', null)
            ->assertJsonPath('data.0.color_carpa', null)
            ->assertJsonPath('data.0.lleva_linterna', true)
            ->assertJsonPath('data.0.acompanantes.0.nombre', 'Catalina Ríos')
            ->assertJsonMissingPath('data.0.condicionesMedicas')
            ->assertJsonMissingPath('data.0.acompanantes.0.condiciones_medicas');
    }

    public function test_sin_excursiones_devuelve_lista_vacia(): void
    {
        $this->iniciarSesion();
        $this->mock(ExcursionRepository::class, function (MockInterface $m) {
            $m->shouldReceive('avisosDelExcursionista')->andReturn([]);
        });

        $this->getJson('/api/v1/excursionista/excursiones', self::CABECERA)
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }

    // ------------------------------------------------------------------- crear

    public function test_crea_la_excursion_planificada_a_nombre_del_token(): void
    {
        $this->iniciarSesion();
        $guardado = null;

        $this->mock(CatalogoRepository::class, function (MockInterface $m) {
            $m->shouldReceive('sendero')->with('sen-001')->andReturn([
                'id' => 'sen-001', 'nombre' => 'PN Nahuelbuta - Piedra del Águila', 'jurisdiccionId' => 'jur-001',
            ]);
        });
        $this->mock(UsuarioRepository::class, function (MockInterface $m) {
            $m->shouldReceive('porId')->with('usr-001')->andReturn([
                'id' => 'usr-001', 'nombre' => 'Trekker de Prueba', 'rol' => RolUsuario::Excursionista,
            ]);
        });
        $this->mock(ExcursionRepository::class, function (MockInterface $m) use (&$guardado) {
            $m->shouldReceive('crear')
                ->once()
                ->andReturnUsing(function (array $datos, string $senderoId, string $jurisdiccionId) use (&$guardado) {
                    $guardado = compact('datos', 'senderoId', 'jurisdiccionId');

                    return 'exc-010';
                });
            $m->shouldReceive('aviso')->with('exc-010')->andReturn($this->avisoEjemplo());
        });

        $this->postJson('/api/v1/excursionista/excursiones', $this->datosValidos(), self::CABECERA)
            ->assertCreated()
            ->assertJsonPath('data.id', 'exc-010')
            ->assertJsonPath('data.estado', 'planificada');

        $d = $guardado['datos'];
        $this->assertSame('usr-001', $d['excursionistaId']);
        $this->assertSame('Trekker de Prueba', $d['excursionista']);        // sale de la cuenta
        $this->assertSame(EstadoExcursion::Planificada, $d['estado']);
        $this->assertSame('', $d['rescatistaId']);
        $this->assertSame('PN Nahuelbuta - Piedra del Águila', $d['ruta']);
        $this->assertSame('Alergia a picaduras', $d['condicionesMedicas']);
        $this->assertTrue($d['llevaLinterna']);
        $this->assertFalse($d['llevaCarpa']);
        $this->assertFalse($d['acompanantes'][0]['tieneCuenta']);
        $this->assertSame('Epilepsia', $d['acompanantes'][0]['condicionesMedicas']);
        $this->assertSame('sen-001', $guardado['senderoId']);
        $this->assertSame('jur-001', $guardado['jurisdiccionId']);
    }

    public function test_no_se_puede_elegir_el_dueno_ni_el_estado_desde_el_cuerpo(): void
    {
        $this->iniciarSesion();
        $guardado = null;

        $this->mock(CatalogoRepository::class, fn (MockInterface $m) => $m->shouldReceive('sendero')->andReturn([
            'id' => 'sen-001', 'nombre' => 'Sendero', 'jurisdiccionId' => 'jur-001',
        ]));
        $this->mock(UsuarioRepository::class, fn (MockInterface $m) => $m->shouldReceive('porId')->andReturn([
            'id' => 'usr-001', 'nombre' => 'Trekker de Prueba', 'rol' => RolUsuario::Excursionista,
        ]));
        $this->mock(ExcursionRepository::class, function (MockInterface $m) use (&$guardado) {
            $m->shouldReceive('crear')->andReturnUsing(function (array $datos) use (&$guardado) {
                $guardado = $datos;

                return 'exc-010';
            });
            $m->shouldReceive('aviso')->andReturn($this->avisoEjemplo());
        });

        $this->postJson('/api/v1/excursionista/excursiones', $this->datosValidos([
            'excursionista_id' => 'otro-usuario',
            'estado' => 'en_operativo',
            'rescatista_id' => 'resc-001',
            'excursionista' => 'Otro Nombre',
        ]), self::CABECERA)->assertCreated();

        $this->assertSame('usr-001', $guardado['excursionistaId']);
        $this->assertSame(EstadoExcursion::Planificada, $guardado['estado']);
        $this->assertSame('', $guardado['rescatistaId']);
        $this->assertSame('Trekker de Prueba', $guardado['excursionista']);
    }

    public function test_sendero_inexistente_da_422(): void
    {
        $this->iniciarSesion();
        $this->mock(CatalogoRepository::class, fn (MockInterface $m) => $m->shouldReceive('sendero')->andReturn(null));
        $this->mock(ExcursionRepository::class, fn (MockInterface $m) => $m->shouldNotReceive('crear'));

        $this->postJson('/api/v1/excursionista/excursiones', $this->datosValidos(), self::CABECERA)
            ->assertUnprocessable()
            ->assertJsonPath('errors.sendero_id.0', 'El sendero no existe.');
    }

    public function test_el_retorno_debe_ser_posterior_a_la_salida(): void
    {
        $this->iniciarSesion();
        $this->mock(ExcursionRepository::class, fn (MockInterface $m) => $m->shouldNotReceive('crear'));

        $this->postJson('/api/v1/excursionista/excursiones', $this->datosValidos([
            'retorno_at' => '2026-11-21T07:00:00-03:00',
        ]), self::CABECERA)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('retorno_at');
    }

    public function test_faltan_datos_obligatorios(): void
    {
        $this->iniciarSesion();
        $this->mock(ExcursionRepository::class, fn (MockInterface $m) => $m->shouldNotReceive('crear'));

        $this->postJson('/api/v1/excursionista/excursiones', [], self::CABECERA)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sendero_id', 'salida_at', 'retorno_at', 'contacto_nombre', 'rut', 'edad', 'experiencia']);
    }

    // ----------------------------------------------------------------- permisos

    public function test_otros_roles_y_sin_token_no_acceden(): void
    {
        $this->iniciarSesion('carabinero');
        $this->getJson('/api/v1/excursionista/excursiones', self::CABECERA)->assertForbidden();
        $this->postJson('/api/v1/excursionista/excursiones', $this->datosValidos(), self::CABECERA)->assertForbidden();

        $this->getJson('/api/v1/excursionista/excursiones')->assertUnauthorized();
    }
}
