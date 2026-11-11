<?php

namespace App\Services\Firestore;

use App\Enums\RolUsuario;
use Illuminate\Support\Facades\Hash;

class UsuarioRepository extends Repositorio
{
    public function todos(): array
    {
        return collect($this->firestore->consultar('usuarios'))
            ->map($this->normalizar(...))
            ->sortBy('nombre')
            ->values()
            ->all();
    }

    public function porId(string $id): ?array
    {
        $doc = $this->firestore->obtener('usuarios', $id);

        return $doc ? $this->normalizar($doc) : null;
    }

    public function porEmail(string $email): ?array
    {
        $doc = $this->firestore->consultar('usuarios', [['email', '==', $email]], null, 1)[0] ?? null;

        return $doc ? $this->normalizar($doc) : null;
    }

    // Devuelve el usuario si el correo y la contraseña son correctos; si no, null
    public function autenticar(string $email, string $password): ?array
    {
        $doc = $this->firestore->consultar('usuarios', [['email', '==', $email]], null, 1)[0] ?? null;

        // Se compara siempre contra un hash para que la respuesta tarde igual exista o no el correo
        $hash = $doc['passwordHash'] ?? Hash::make('relleno');

        if (! Hash::check($password, $hash) || ! $doc || empty($doc['passwordHash'])) {
            return null;
        }

        return $this->normalizar($doc);
    }

    public function rescatistas(?string $jurisdiccionId = null): array
    {
        $filtros = [['rol', '==', RolUsuario::Rescatista->value]];
        if ($jurisdiccionId) {
            $filtros[] = ['jurisdiccionId', '==', $jurisdiccionId];
        }

        return collect($this->firestore->consultar('usuarios', $filtros))
            ->map($this->normalizar(...))
            ->sortBy('nombre')
            ->values()
            ->all();
    }

    // Crea un usuario con ID correlativo (resc-004...) y la contraseña cifrada. Devuelve el ID.
    public function crear(string $nombre, string $email, RolUsuario $rol, ?string $jurisdiccionId, string $password): string
    {
        $id = $this->siguienteId('usuarios', $rol->prefijo());

        $this->firestore->confirmar([$this->firestore->escritura('usuarios', $id, [
            'nombre' => $nombre,
            'email' => $email,
            'rol' => $rol->value,
            'jurisdiccionId' => $jurisdiccionId ?? '',
            'passwordHash' => Hash::make($password),
        ], null, false)]);

        return $id;
    }

    // Reasigna la contraseña de un usuario existente. Solo reescribe passwordHash.
    public function cambiarPassword(string $id, string $password): void
    {
        $this->firestore->confirmar([$this->firestore->actualizacion('usuarios', $id, [
            'passwordHash' => Hash::make($password),
        ])]);
    }

    // El hash de la contraseña no sale de aquí hacia las vistas
    private function normalizar(array $doc): array
    {
        unset($doc['passwordHash']);
        $doc['rol'] = RolUsuario::tryFrom($doc['rol'] ?? '');
        $doc['email'] ??= '';
        $doc['jurisdiccionId'] ??= '';

        return $doc;
    }
}
