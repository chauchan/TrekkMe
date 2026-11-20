<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Services\Firestore\CatalogoRepository;
use App\Services\Firestore\UsuarioRepository;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Lcobucci\JWT\Token\DataSet;
use Lcobucci\JWT\UnencryptedToken;
use Mockery\MockInterface;
use Tests\TestCase;

// El excursionista (app Flutter) entra por el mismo login que Ionic y puede leer el catálogo de senderos.
// Firebase y Firestore se simulan: estas pruebas no necesitan red ni credenciales.
class AccesoExcursionistaTest extends TestCase
{
    private function usuario(RolUsuario $rol): array
    {
        return [
            'id' => 'usr-001',
            'nombre' => 'Trekker de Prueba',
            'email' => 'trekker@trekkme.test',
            'rol' => $rol,
            'jurisdiccionId' => '',
        ];
    }

    private function simularLogin(?array $usuario): void
    {
        $this->mock(UsuarioRepository::class, function (MockInterface $m) use ($usuario) {
            $m->shouldReceive('autenticar')->andReturn($usuario);
        });

        $this->mock(FirebaseAuth::class, function (MockInterface $m) {
            $token = \Mockery::mock(UnencryptedToken::class);
            $token->shouldReceive('toString')->andReturn('token-de-prueba');
            $m->shouldReceive('createCustomToken')->andReturn($token);
        });
    }

    private function simularSesion(string $rol): void
    {
        $this->mock(FirebaseAuth::class, function (MockInterface $m) use ($rol) {
            $token = \Mockery::mock(UnencryptedToken::class);
            $token->shouldReceive('claims')->andReturn(new DataSet(['sub' => 'usr-001', 'rol' => $rol], ''));
            $m->shouldReceive('verifyIdToken')->andReturn($token);
        });

        $this->mock(CatalogoRepository::class, function (MockInterface $m) {
            $m->shouldReceive('senderos')->andReturn([
                ['id' => 'sen-001', 'nombre' => 'PN Nahuelbuta - Piedra del Águila', 'jurisdiccionId' => 'jur-001'],
            ]);
        });
    }

    public function test_el_excursionista_puede_iniciar_sesion(): void
    {
        $this->simularLogin($this->usuario(RolUsuario::Excursionista));

        $this->postJson('/api/v1/login', ['email' => 'trekker@trekkme.test', 'password' => 'clave'])
            ->assertOk()
            ->assertJsonPath('data.perfil', 'excursionista')
            ->assertJsonPath('data.usuario_id', 'usr-001')
            ->assertJsonPath('data.firebase_token', 'token-de-prueba');
    }

    public function test_el_admin_sigue_sin_acceso_a_la_app(): void
    {
        $this->simularLogin($this->usuario(RolUsuario::Admin));

        $this->postJson('/api/v1/login', ['email' => 'admin@trekkme.test', 'password' => 'clave'])
            ->assertForbidden()
            ->assertJsonPath('message', 'Este perfil no tiene acceso a la aplicación.');
    }

    public function test_credenciales_malas_responden_422(): void
    {
        $this->simularLogin(null);

        $this->postJson('/api/v1/login', ['email' => 'trekker@trekkme.test', 'password' => 'mala'])
            ->assertUnprocessable();
    }

    public function test_el_excursionista_lee_el_catalogo_de_senderos(): void
    {
        $this->simularSesion('excursionista');

        $this->getJson('/api/v1/senderos', ['Authorization' => 'Bearer cualquiera'])
            ->assertOk()
            ->assertJsonPath('data.0.id', 'sen-001')
            ->assertJsonPath('data.0.nombre', 'PN Nahuelbuta - Piedra del Águila');
    }

    public function test_un_rol_ajeno_no_lee_el_catalogo(): void
    {
        $this->simularSesion('admin');

        $this->getJson('/api/v1/senderos', ['Authorization' => 'Bearer cualquiera'])
            ->assertForbidden();
    }
}
