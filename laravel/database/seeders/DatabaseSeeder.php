<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    // Se puede correr varias veces: actualiza por ID en vez de duplicar
    public function run(): void
    {
        $this->call([
            UsuarioSeeder::class,
            ExcursionSeeder::class,
            BitacoraSeeder::class,
        ]);
    }
}
