<?php

namespace Database\Seeders;

use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class UsuarioSeeder extends Seeder
{
    // Mismos rescatistas que la lista fija de Ionic (models/rescatista.ts). Contraseña de prueba: "password"
    public function run(): void
    {
        $usuarios = [
            ['resc-001', 'Andrés Poblete', 'andres.poblete@trekkme.test', RolUsuario::Rescatista],
            ['resc-002', 'Carolina Henríquez', 'carolina.henriquez@trekkme.test', RolUsuario::Rescatista],
            ['resc-003', 'Tomás Riquelme', 'tomas.riquelme@trekkme.test', RolUsuario::Rescatista],
            ['carab-001', 'Carabinero de Prueba', 'carabinero@trekkme.test', RolUsuario::Carabinero],
        ];

        foreach ($usuarios as [$id, $nombre, $email, $rol]) {
            Usuario::updateOrCreate(['id' => $id], [
                'nombre' => $nombre,
                'email' => $email,
                'rol' => $rol,
                'password' => 'password',
            ]);
        }
    }
}
