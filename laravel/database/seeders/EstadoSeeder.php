<?php

namespace Database\Seeders;

use App\Models\Estado;
use Illuminate\Database\Seeder;

class EstadoSeeder extends Seeder
{
    public function run(): void
    {
        Estado::create(['nombre_estado' => 'programada']);
        Estado::create(['nombre_estado' => 'en_curso']);
        Estado::create(['nombre_estado' => 'vencida']);
        Estado::create(['nombre_estado' => 'en_alerta']);
        Estado::create(['nombre_estado' => 'en_operativo']);
        Estado::create(['nombre_estado' => 'cierre_pendiente']);
        Estado::create(['nombre_estado' => 'finalizada']);
        Estado::create(['nombre_estado' => 'cancelada']);
    }
}