<?php

namespace Database\Seeders;

use App\Models\Bitacora;
use Illuminate\Database\Seeder;

// Mismos 6 registros de ejemplo que carga BitacoraService.cargarEjemplos() en Ionic
class BitacoraSeeder extends Seeder
{
    public function run(): void
    {
        $registros = [
            'bit-001' => ['exc-001', 'PN Nonguén - Sendero Los Canelos', 20, 16, 'Primera salida. Recorrimos el sendero completo hasta el mirador sin encontrar rastros. Se entrevistó a dos visitantes que no la vieron.', 16],
            'bit-002' => ['exc-001', 'Fuera de sendero', 12, 8, 'Búsqueda por la quebrada al norte del mirador. Se encontró un envoltorio de barra de cereal reciente. Sin contacto visual ni respuesta a los llamados.', 8],
            'bit-003' => ['exc-001', 'PN Nonguén - Sendero Los Copihues', 2, null, 'Tercera salida con apoyo de un guardaparque. Revisando el cruce con el sendero Los Copihues.', 2],
            'bit-004' => ['exc-006', 'Laguna del Laja - Sierra Velluda', 66, 58, 'Día 1. Ascenso por la ruta declarada hasta el primer campamento. No hay carpa ni señales de pernocte.', 58],
            'bit-005' => ['exc-006', 'Laguna del Laja - Sierra Velluda', 42, 33, 'Día 2. Se amplía la búsqueda hacia el sector del glaciar. Mala visibilidad por neblina, se suspende a las 18:00.', 33],
            'bit-006' => ['exc-007', 'PN Nonguén - Sendero Los Copihues', 31, 29, 'Se encuentra a las dos personas en buen estado a 400 metros del sendero. Se habían desorientado al anochecer. Se acompaña su descenso.', 29],
        ];

        foreach ($registros as $id => [$excursionId, $ruta, $inicio, $fin, $texto, $registrado]) {
            Bitacora::updateOrCreate(['id' => $id], [
                'excursion_id' => $excursionId,
                'rescatista_id' => 'resc-001',
                'ruta' => $ruta,
                'hora_inicio' => now()->subHours($inicio),
                'hora_fin' => $fin === null ? null : now()->subHours($fin),
                'texto' => $texto,
                'registrado_at' => now()->subHours($registrado),
                'version' => 1,
            ]);
        }
    }
}
