<?php

namespace Database\Seeders;

use App\Enums\EstadoExcursion;
use App\Models\Excursion;
use Illuminate\Database\Seeder;

// Mismas 9 excursiones de ejemplo que carga ExcursionService.cargarEjemplos() en Ionic
class ExcursionSeeder extends Seeder
{
    public function run(): void
    {
        $ninguna = 'Ninguna';
        $foto = 'assets/ejemplos/foto-ejemplo.svg';

        $excursiones = [
            'exc-001' => [
                'excursionista' => 'Camila Rojas Soto', 'rut' => '20.000.001-1', 'telefono' => '+56 9 5555 0101',
                'edad' => 29, 'experiencia' => 'Intermedio', 'condiciones_medicas' => 'Asma',
                'acompanantes' => [
                    ['nombre' => 'Diego Rojas Soto', 'rut' => '20.000.011-1', 'edad' => 26, 'condicionesMedicas' => $ninguna, 'tieneCuenta' => true, 'telefono' => '+56 9 5555 0201', 'experiencia' => 'Principiante'],
                ],
                'ruta' => 'PN Nonguén - Sendero Los Canelos',
                'contacto_nombre' => 'Jorge Rojas (padre)', 'contacto_telefono' => '+56 9 5555 0102',
                'salida_at' => $this->haceHoras(30), 'retorno_at' => $this->haceHoras(22),
                'estado' => EstadoExcursion::EnOperativo, 'rescatista_id' => 'resc-001',
                'foto_url' => $foto,
                'detalle' => 'Chaqueta azul y mochila gris de 30 litros. Pelo castaño largo, usa lentes. Va con su perro, un labrador negro.',
                'lleva_carpa' => false, 'color_carpa' => null, 'lleva_linterna' => true,
                'ultima_lat' => -36.8795, 'ultima_lng' => -72.9731, 'ultima_ubicacion_at' => $this->haceHoras(25), 'bateria' => 12,
            ],
            'exc-002' => [
                'excursionista' => 'Matías Fuentes Vera', 'rut' => '20.000.002-2', 'telefono' => '+56 9 5555 0103',
                'edad' => 41, 'experiencia' => 'Avanzado', 'condiciones_medicas' => $ninguna,
                'acompanantes' => [],
                'ruta' => 'Laguna del Laja - Sierra Velluda',
                'contacto_nombre' => 'Daniela Vera (hermana)', 'contacto_telefono' => '+56 9 5555 0104',
                'salida_at' => $this->haceHoras(11), 'retorno_at' => $this->haceHoras(2),
                'estado' => EstadoExcursion::EnAlerta, 'rescatista_id' => null,
                'foto_url' => $foto,
                'detalle' => 'Chaqueta roja, casco blanco y piolet. Barba y gorro de lana negro. Llevo comida para dos días.',
                'lleva_carpa' => true, 'color_carpa' => 'Naranja', 'lleva_linterna' => true,
                'ultima_lat' => -37.4702, 'ultima_lng' => -71.4188, 'ultima_ubicacion_at' => $this->haceHoras(5), 'bateria' => 34,
            ],
            'exc-003' => [
                'excursionista' => 'Valentina Muñoz Paredes', 'rut' => '20.000.003-3', 'telefono' => '+56 9 5555 0105',
                'edad' => 35, 'experiencia' => 'Principiante', 'condiciones_medicas' => $ninguna,
                'acompanantes' => [
                    ['nombre' => 'Luis Muñoz Tapia', 'rut' => '20.000.012-2', 'edad' => 37, 'condicionesMedicas' => $ninguna, 'tieneCuenta' => true, 'telefono' => '+56 9 5555 0202', 'experiencia' => 'Intermedio'],
                    ['nombre' => 'Sofía Muñoz Muñoz', 'rut' => '20.000.013-3', 'edad' => 12, 'condicionesMedicas' => 'Alergia al maní', 'tieneCuenta' => false, 'telefono' => '', 'experiencia' => ''],
                    ['nombre' => 'Benjamín Muñoz Muñoz', 'rut' => '20.000.014-4', 'edad' => 9, 'condicionesMedicas' => $ninguna, 'tieneCuenta' => false, 'telefono' => '', 'experiencia' => ''],
                ],
                'ruta' => 'PN Nahuelbuta - Piedra del Águila',
                'contacto_nombre' => 'Luis Muñoz (esposo)', 'contacto_telefono' => '+56 9 5555 0106',
                'salida_at' => $this->haceHoras(3), 'retorno_at' => $this->haceHoras(-4),
                'estado' => EstadoExcursion::EnCurso, 'rescatista_id' => null,
                'foto_url' => null, 'detalle' => null,
                'lleva_carpa' => false, 'color_carpa' => null, 'lleva_linterna' => false,
                'ultima_lat' => -37.8235, 'ultima_lng' => -73.0312, 'ultima_ubicacion_at' => $this->haceHoras(1), 'bateria' => 78,
            ],
            'exc-004' => [
                'excursionista' => 'Ignacio Salazar Díaz', 'rut' => '20.000.004-4', 'telefono' => '+56 9 5555 0107',
                'edad' => 23, 'experiencia' => 'Principiante', 'condiciones_medicas' => 'Alergia a picaduras de abeja',
                'acompanantes' => [
                    ['nombre' => 'Tomás Garrido Lagos', 'rut' => '20.000.015-5', 'edad' => 23, 'condicionesMedicas' => $ninguna, 'tieneCuenta' => true, 'telefono' => '+56 9 5555 0203', 'experiencia' => 'Principiante'],
                    ['nombre' => 'Catalina Ríos Peña', 'rut' => '20.000.016-6', 'edad' => 22, 'condicionesMedicas' => 'Epilepsia controlada', 'tieneCuenta' => false, 'telefono' => '', 'experiencia' => ''],
                ],
                'ruta' => 'PN Nonguén - Sendero Los Copihues',
                'contacto_nombre' => 'Paula Díaz (madre)', 'contacto_telefono' => '+56 9 5555 0108',
                'salida_at' => $this->haceHoras(-2), 'retorno_at' => $this->haceHoras(-8),
                'estado' => EstadoExcursion::Planificada, 'rescatista_id' => null,
                'foto_url' => null,
                'detalle' => 'Polerón amarillo. Vamos tres amigos de la universidad, todos con mochilas chicas.',
                'lleva_carpa' => false, 'color_carpa' => null, 'lleva_linterna' => true,
                'ultima_lat' => null, 'ultima_lng' => null, 'ultima_ubicacion_at' => null, 'bateria' => null,
            ],
            'exc-005' => [
                'excursionista' => 'Fernanda Castro Leal', 'rut' => '20.000.005-5', 'telefono' => '+56 9 5555 0109',
                'edad' => 52, 'experiencia' => 'Avanzado', 'condiciones_medicas' => 'Hipertensión',
                'acompanantes' => [
                    ['nombre' => 'Pedro Castro Leal', 'rut' => '20.000.017-7', 'edad' => 55, 'condicionesMedicas' => $ninguna, 'tieneCuenta' => false, 'telefono' => '', 'experiencia' => ''],
                ],
                'ruta' => 'PN Nahuelbuta - Piedra del Águila',
                'contacto_nombre' => 'Pedro Castro (hermano)', 'contacto_telefono' => '+56 9 5555 0110',
                'salida_at' => $this->haceHoras(52), 'retorno_at' => $this->haceHoras(45),
                'estado' => EstadoExcursion::Cerrada, 'rescatista_id' => null,
                'foto_url' => $foto, 'detalle' => 'Chaqueta negra y bastones de trekking.',
                'lleva_carpa' => true, 'color_carpa' => 'Verde', 'lleva_linterna' => true,
                'ultima_lat' => -37.8201, 'ultima_lng' => -73.0297, 'ultima_ubicacion_at' => $this->haceHoras(46), 'bateria' => 55,
            ],
            'exc-006' => [
                'excursionista' => 'Roberto Sepúlveda Araya', 'rut' => '20.000.006-6', 'telefono' => '+56 9 5555 0111',
                'edad' => 67, 'experiencia' => 'Intermedio', 'condiciones_medicas' => 'Diabetes tipo 2',
                'acompanantes' => [],
                'ruta' => 'Laguna del Laja - Sierra Velluda',
                'contacto_nombre' => 'Marcela Araya (hija)', 'contacto_telefono' => '+56 9 5555 0112',
                'salida_at' => $this->haceHoras(80), 'retorno_at' => $this->haceHoras(72),
                'estado' => EstadoExcursion::EnOperativo, 'rescatista_id' => 'resc-001',
                'foto_url' => $foto,
                'detalle' => 'Parka gris y sombrero de ala ancha. Camino lento por una lesión en la rodilla. Llevo insulina en la mochila.',
                'lleva_carpa' => true, 'color_carpa' => 'Azul', 'lleva_linterna' => true,
                'ultima_lat' => null, 'ultima_lng' => null, 'ultima_ubicacion_at' => null, 'bateria' => null,
            ],
            'exc-007' => [
                'excursionista' => 'Javiera Contreras Mella', 'rut' => '20.000.007-7', 'telefono' => '+56 9 5555 0113',
                'edad' => 31, 'experiencia' => 'Avanzado', 'condiciones_medicas' => $ninguna,
                'acompanantes' => [
                    ['nombre' => 'Cristóbal Mella Jara', 'rut' => '20.000.018-8', 'edad' => 33, 'condicionesMedicas' => $ninguna, 'tieneCuenta' => true, 'telefono' => '+56 9 5555 0204', 'experiencia' => 'Avanzado'],
                ],
                'ruta' => 'PN Nonguén - Sendero Los Copihues',
                'contacto_nombre' => 'Cristóbal Mella (pareja)', 'contacto_telefono' => '+56 9 5555 0114',
                'salida_at' => $this->haceHoras(40), 'retorno_at' => $this->haceHoras(33),
                'estado' => EstadoExcursion::CierrePendiente, 'rescatista_id' => 'resc-001',
                'foto_url' => $foto, 'detalle' => null,
                'lleva_carpa' => false, 'color_carpa' => null, 'lleva_linterna' => true,
                'ultima_lat' => -36.8842, 'ultima_lng' => -72.9655, 'ultima_ubicacion_at' => $this->haceHoras(30), 'bateria' => 41,
            ],
            'exc-008' => [
                'excursionista' => 'Sebastián Vidal Ortiz', 'rut' => '20.000.008-8', 'telefono' => '+56 9 5555 0115',
                'edad' => 19, 'experiencia' => 'Principiante', 'condiciones_medicas' => $ninguna,
                'acompanantes' => [
                    ['nombre' => 'Martín Vidal Ortiz', 'rut' => '20.000.019-9', 'edad' => 17, 'condicionesMedicas' => $ninguna, 'tieneCuenta' => false, 'telefono' => '', 'experiencia' => ''],
                    ['nombre' => 'Agustín Pérez Soto', 'rut' => '20.000.020-K', 'edad' => 19, 'condicionesMedicas' => 'Asma', 'tieneCuenta' => true, 'telefono' => '+56 9 5555 0205', 'experiencia' => 'Principiante'],
                ],
                'ruta' => 'PN Nahuelbuta - Piedra del Águila',
                'contacto_nombre' => 'Rosa Ortiz (madre)', 'contacto_telefono' => '+56 9 5555 0116',
                'salida_at' => $this->haceHoras(20), 'retorno_at' => $this->haceHoras(14),
                'estado' => EstadoExcursion::EnOperativo, 'rescatista_id' => 'resc-002',
                'foto_url' => null,
                'detalle' => 'Cortaviento blanco y jeans. Sin mochila grande, solo una botella de agua.',
                'lleva_carpa' => false, 'color_carpa' => null, 'lleva_linterna' => false,
                'ultima_lat' => -37.8179, 'ultima_lng' => -73.0344, 'ultima_ubicacion_at' => $this->haceHoras(16), 'bateria' => 3,
            ],
            'exc-009' => [
                'excursionista' => 'Antonia Herrera Pino', 'rut' => '20.000.009-9', 'telefono' => '+56 9 5555 0117',
                'edad' => 26, 'experiencia' => 'Intermedio', 'condiciones_medicas' => $ninguna,
                'acompanantes' => [
                    ['nombre' => 'Felipe Herrera Pino', 'rut' => '20.000.021-1', 'edad' => 30, 'condicionesMedicas' => $ninguna, 'tieneCuenta' => true, 'telefono' => '+56 9 5555 0206', 'experiencia' => 'Intermedio'],
                ],
                'ruta' => 'PN Nonguén - Sendero Los Canelos',
                'contacto_nombre' => 'Felipe Herrera (hermano)', 'contacto_telefono' => '+56 9 5555 0118',
                'salida_at' => $this->haceHoras(7), 'retorno_at' => $this->haceHoras(1),
                'estado' => EstadoExcursion::EnAlerta, 'rescatista_id' => null,
                'foto_url' => null, 'detalle' => null,
                'lleva_carpa' => false, 'color_carpa' => null, 'lleva_linterna' => true,
                'ultima_lat' => -36.8811, 'ultima_lng' => -72.9702, 'ultima_ubicacion_at' => $this->haceHoras(2), 'bateria' => 60,
            ],
        ];

        foreach ($excursiones as $id => $datos) {
            Excursion::updateOrCreate(['id' => $id], $datos);
        }
    }

    private function haceHoras(int $horas)
    {
        return now()->subHours($horas);
    }
}
