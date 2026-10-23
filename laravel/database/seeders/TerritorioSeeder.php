<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Models\JurisdiccionModel;
use Illuminate\Database\Seeder;

// Mismos senderos que la lista fija RUTAS de Ionic (ionic/src/app/models/bitacora.ts),
// para que las tres apps prueben contra los mismos nombres.
// "Fuera de sendero" no es un sendero: se representa con sendero_id = null.
class TerritorioSeeder extends Seeder
{
    public function run(): void
    {
        $biobio = JurisdiccionModel::create([
            'nombre' => 'Prefectura Biobío',
            'region' => 'Biobío',
            'comuna' => 'Concepción',
        ]);

        $nonguen = $biobio->areas()->create([
            'nombre' => 'PN Nonguén',
            'tipo' => 'parque_nacional',
            'cobertura_celular' => 'parcial',
        ]);
        $nonguen->senderos()->createMany([
            ['nombre' => 'Sendero Los Canelos', 'dificultad' => 'baja', 'distancia_km' => 4.5, 'desnivel_m' => 150, 'duracion_estimada_min' => 120],
            ['nombre' => 'Sendero Los Copihues', 'dificultad' => 'media', 'distancia_km' => 7.2, 'desnivel_m' => 320, 'duracion_estimada_min' => 210],
        ]);

        $nahuelbuta = $biobio->areas()->create([
            'nombre' => 'PN Nahuelbuta',
            'tipo' => 'parque_nacional',
            'cobertura_celular' => 'nula',
        ]);
        $nahuelbuta->senderos()->create(
            ['nombre' => 'Piedra del Águila', 'dificultad' => 'media', 'distancia_km' => 9.0, 'desnivel_m' => 400, 'duracion_estimada_min' => 240],
        );

        $laja = $biobio->areas()->create([
            'nombre' => 'Laguna del Laja',
            'tipo' => 'parque_nacional',
            'cobertura_celular' => 'nula',
        ]);
        $laja->senderos()->create(
            ['nombre' => 'Sierra Velluda', 'dificultad' => 'experta', 'distancia_km' => 22.0, 'desnivel_m' => 1500, 'duracion_estimada_min' => 720],
        );
    }
}
