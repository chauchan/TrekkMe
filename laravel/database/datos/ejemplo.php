<?php

use App\Enums\EstadoExcursion;

// Datos de ejemplo: los mismos de cargarEjemplos() en Ionic. Las fechas son relativas a "ahora".
$h = fn (int $horas) => now()->subHours($horas);
$ninguna = 'Ninguna';
$foto = 'assets/ejemplos/foto-ejemplo.svg';

return [
    'excursiones' => [
            'exc-001' => [
                'excursionista' => 'Camila Rojas Soto', 'rut' => '20.000.001-1', 'telefono' => '+56 9 5555 0101',
                'edad' => 29, 'experiencia' => 'Intermedio', 'condicionesMedicas' => 'Asma',
                'acompanantes' => [
                    ['nombre' => 'Diego Rojas Soto', 'rut' => '20.000.011-1', 'edad' => 26, 'condicionesMedicas' => $ninguna, 'tieneCuenta' => true, 'telefono' => '+56 9 5555 0201', 'experiencia' => 'Principiante'],
                ],
                'ruta' => 'PN Nonguén - Sendero Los Canelos',
                'contactoNombre' => 'Jorge Rojas (padre)', 'contactoTelefono' => '+56 9 5555 0102',
                'salidaAt' => $h(30), 'retornoAt' => $h(22),
                'estado' => EstadoExcursion::EnOperativo, 'rescatistaId' => 'resc-001',
                'fotoUrl' => $foto,
                'detalle' => 'Chaqueta azul y mochila gris de 30 litros. Pelo castaño largo, usa lentes. Va con su perro, un labrador negro.',
                'llevaCarpa' => false, 'colorCarpa' => null, 'llevaLinterna' => true,
                'ultimaLat' => -36.8795, 'ultimaLng' => -72.9731, 'ultimaUbicacionAt' => $h(25), 'bateria' => 12,
            ],
            'exc-002' => [
                'excursionista' => 'Matías Fuentes Vera', 'rut' => '20.000.002-2', 'telefono' => '+56 9 5555 0103',
                'edad' => 41, 'experiencia' => 'Avanzado', 'condicionesMedicas' => $ninguna,
                'acompanantes' => [],
                'ruta' => 'Laguna del Laja - Sierra Velluda',
                'contactoNombre' => 'Daniela Vera (hermana)', 'contactoTelefono' => '+56 9 5555 0104',
                'salidaAt' => $h(11), 'retornoAt' => $h(2),
                'estado' => EstadoExcursion::EnAlerta, 'rescatistaId' => null,
                'fotoUrl' => $foto,
                'detalle' => 'Chaqueta roja, casco blanco y piolet. Barba y gorro de lana negro. Llevo comida para dos días.',
                'llevaCarpa' => true, 'colorCarpa' => 'Naranja', 'llevaLinterna' => true,
                'ultimaLat' => -37.4702, 'ultimaLng' => -71.4188, 'ultimaUbicacionAt' => $h(5), 'bateria' => 34,
            ],
            'exc-003' => [
                'excursionista' => 'Valentina Muñoz Paredes', 'rut' => '20.000.003-3', 'telefono' => '+56 9 5555 0105',
                'edad' => 35, 'experiencia' => 'Principiante', 'condicionesMedicas' => $ninguna,
                'acompanantes' => [
                    ['nombre' => 'Luis Muñoz Tapia', 'rut' => '20.000.012-2', 'edad' => 37, 'condicionesMedicas' => $ninguna, 'tieneCuenta' => true, 'telefono' => '+56 9 5555 0202', 'experiencia' => 'Intermedio'],
                    ['nombre' => 'Sofía Muñoz Muñoz', 'rut' => '20.000.013-3', 'edad' => 12, 'condicionesMedicas' => 'Alergia al maní', 'tieneCuenta' => false, 'telefono' => '', 'experiencia' => ''],
                    ['nombre' => 'Benjamín Muñoz Muñoz', 'rut' => '20.000.014-4', 'edad' => 9, 'condicionesMedicas' => $ninguna, 'tieneCuenta' => false, 'telefono' => '', 'experiencia' => ''],
                ],
                'ruta' => 'PN Nahuelbuta - Piedra del Águila',
                'contactoNombre' => 'Luis Muñoz (esposo)', 'contactoTelefono' => '+56 9 5555 0106',
                'salidaAt' => $h(3), 'retornoAt' => $h(-4),
                'estado' => EstadoExcursion::EnCurso, 'rescatistaId' => null,
                'fotoUrl' => null, 'detalle' => null,
                'llevaCarpa' => false, 'colorCarpa' => null, 'llevaLinterna' => false,
                'ultimaLat' => -37.8235, 'ultimaLng' => -73.0312, 'ultimaUbicacionAt' => $h(1), 'bateria' => 78,
            ],
            'exc-004' => [
                'excursionista' => 'Ignacio Salazar Díaz', 'rut' => '20.000.004-4', 'telefono' => '+56 9 5555 0107',
                'edad' => 23, 'experiencia' => 'Principiante', 'condicionesMedicas' => 'Alergia a picaduras de abeja',
                'acompanantes' => [
                    ['nombre' => 'Tomás Garrido Lagos', 'rut' => '20.000.015-5', 'edad' => 23, 'condicionesMedicas' => $ninguna, 'tieneCuenta' => true, 'telefono' => '+56 9 5555 0203', 'experiencia' => 'Principiante'],
                    ['nombre' => 'Catalina Ríos Peña', 'rut' => '20.000.016-6', 'edad' => 22, 'condicionesMedicas' => 'Epilepsia controlada', 'tieneCuenta' => false, 'telefono' => '', 'experiencia' => ''],
                ],
                'ruta' => 'PN Nonguén - Sendero Los Copihues',
                'contactoNombre' => 'Paula Díaz (madre)', 'contactoTelefono' => '+56 9 5555 0108',
                'salidaAt' => $h(-2), 'retornoAt' => $h(-8),
                'estado' => EstadoExcursion::Planificada, 'rescatistaId' => null,
                'fotoUrl' => null,
                'detalle' => 'Polerón amarillo. Vamos tres amigos de la universidad, todos con mochilas chicas.',
                'llevaCarpa' => false, 'colorCarpa' => null, 'llevaLinterna' => true,
                'ultimaLat' => null, 'ultimaLng' => null, 'ultimaUbicacionAt' => null, 'bateria' => null,
            ],
            'exc-005' => [
                'excursionista' => 'Fernanda Castro Leal', 'rut' => '20.000.005-5', 'telefono' => '+56 9 5555 0109',
                'edad' => 52, 'experiencia' => 'Avanzado', 'condicionesMedicas' => 'Hipertensión',
                'acompanantes' => [
                    ['nombre' => 'Pedro Castro Leal', 'rut' => '20.000.017-7', 'edad' => 55, 'condicionesMedicas' => $ninguna, 'tieneCuenta' => false, 'telefono' => '', 'experiencia' => ''],
                ],
                'ruta' => 'PN Nahuelbuta - Piedra del Águila',
                'contactoNombre' => 'Pedro Castro (hermano)', 'contactoTelefono' => '+56 9 5555 0110',
                'salidaAt' => $h(52), 'retornoAt' => $h(45),
                'estado' => EstadoExcursion::Cerrada, 'rescatistaId' => null,
                'fotoUrl' => $foto, 'detalle' => 'Chaqueta negra y bastones de trekking.',
                'llevaCarpa' => true, 'colorCarpa' => 'Verde', 'llevaLinterna' => true,
                'ultimaLat' => -37.8201, 'ultimaLng' => -73.0297, 'ultimaUbicacionAt' => $h(46), 'bateria' => 55,
            ],
            'exc-006' => [
                'excursionista' => 'Roberto Sepúlveda Araya', 'rut' => '20.000.006-6', 'telefono' => '+56 9 5555 0111',
                'edad' => 67, 'experiencia' => 'Intermedio', 'condicionesMedicas' => 'Diabetes tipo 2',
                'acompanantes' => [],
                'ruta' => 'Laguna del Laja - Sierra Velluda',
                'contactoNombre' => 'Marcela Araya (hija)', 'contactoTelefono' => '+56 9 5555 0112',
                'salidaAt' => $h(80), 'retornoAt' => $h(72),
                'estado' => EstadoExcursion::EnOperativo, 'rescatistaId' => 'resc-001',
                'fotoUrl' => $foto,
                'detalle' => 'Parka gris y sombrero de ala ancha. Camino lento por una lesión en la rodilla. Llevo insulina en la mochila.',
                'llevaCarpa' => true, 'colorCarpa' => 'Azul', 'llevaLinterna' => true,
                'ultimaLat' => null, 'ultimaLng' => null, 'ultimaUbicacionAt' => null, 'bateria' => null,
            ],
            'exc-007' => [
                'excursionista' => 'Javiera Contreras Mella', 'rut' => '20.000.007-7', 'telefono' => '+56 9 5555 0113',
                'edad' => 31, 'experiencia' => 'Avanzado', 'condicionesMedicas' => $ninguna,
                'acompanantes' => [
                    ['nombre' => 'Cristóbal Mella Jara', 'rut' => '20.000.018-8', 'edad' => 33, 'condicionesMedicas' => $ninguna, 'tieneCuenta' => true, 'telefono' => '+56 9 5555 0204', 'experiencia' => 'Avanzado'],
                ],
                'ruta' => 'PN Nonguén - Sendero Los Copihues',
                'contactoNombre' => 'Cristóbal Mella (pareja)', 'contactoTelefono' => '+56 9 5555 0114',
                'salidaAt' => $h(40), 'retornoAt' => $h(33),
                'estado' => EstadoExcursion::CierrePendiente, 'rescatistaId' => 'resc-001',
                'fotoUrl' => $foto, 'detalle' => null,
                'llevaCarpa' => false, 'colorCarpa' => null, 'llevaLinterna' => true,
                'ultimaLat' => -36.8842, 'ultimaLng' => -72.9655, 'ultimaUbicacionAt' => $h(30), 'bateria' => 41,
            ],
            'exc-008' => [
                'excursionista' => 'Sebastián Vidal Ortiz', 'rut' => '20.000.008-8', 'telefono' => '+56 9 5555 0115',
                'edad' => 19, 'experiencia' => 'Principiante', 'condicionesMedicas' => $ninguna,
                'acompanantes' => [
                    ['nombre' => 'Martín Vidal Ortiz', 'rut' => '20.000.019-9', 'edad' => 17, 'condicionesMedicas' => $ninguna, 'tieneCuenta' => false, 'telefono' => '', 'experiencia' => ''],
                    ['nombre' => 'Agustín Pérez Soto', 'rut' => '20.000.020-K', 'edad' => 19, 'condicionesMedicas' => 'Asma', 'tieneCuenta' => true, 'telefono' => '+56 9 5555 0205', 'experiencia' => 'Principiante'],
                ],
                'ruta' => 'PN Nahuelbuta - Piedra del Águila',
                'contactoNombre' => 'Rosa Ortiz (madre)', 'contactoTelefono' => '+56 9 5555 0116',
                'salidaAt' => $h(20), 'retornoAt' => $h(14),
                'estado' => EstadoExcursion::EnOperativo, 'rescatistaId' => 'resc-002',
                'fotoUrl' => null,
                'detalle' => 'Cortaviento blanco y jeans. Sin mochila grande, solo una botella de agua.',
                'llevaCarpa' => false, 'colorCarpa' => null, 'llevaLinterna' => false,
                'ultimaLat' => -37.8179, 'ultimaLng' => -73.0344, 'ultimaUbicacionAt' => $h(16), 'bateria' => 3,
            ],
            'exc-009' => [
                'excursionista' => 'Antonia Herrera Pino', 'rut' => '20.000.009-9', 'telefono' => '+56 9 5555 0117',
                'edad' => 26, 'experiencia' => 'Intermedio', 'condicionesMedicas' => $ninguna,
                'acompanantes' => [
                    ['nombre' => 'Felipe Herrera Pino', 'rut' => '20.000.021-1', 'edad' => 30, 'condicionesMedicas' => $ninguna, 'tieneCuenta' => true, 'telefono' => '+56 9 5555 0206', 'experiencia' => 'Intermedio'],
                ],
                'ruta' => 'PN Nonguén - Sendero Los Canelos',
                'contactoNombre' => 'Felipe Herrera (hermano)', 'contactoTelefono' => '+56 9 5555 0118',
                'salidaAt' => $h(7), 'retornoAt' => $h(1),
                'estado' => EstadoExcursion::EnAlerta, 'rescatistaId' => null,
                'fotoUrl' => null, 'detalle' => null,
                'llevaCarpa' => false, 'colorCarpa' => null, 'llevaLinterna' => true,
                'ultimaLat' => -36.8811, 'ultimaLng' => -72.9702, 'ultimaUbicacionAt' => $h(2), 'bateria' => 60,
            ],
        ],

    // id => [excursionId, ruta, horas atrás de inicio, horas atrás de fin|null, texto, horas atrás de registro]
    'bitacoras' => [
            'bit-001' => ['exc-001', 'PN Nonguén - Sendero Los Canelos', 20, 16, 'Primera salida. Recorrimos el sendero completo hasta el mirador sin encontrar rastros. Se entrevistó a dos visitantes que no la vieron.', 16],
            'bit-002' => ['exc-001', 'Fuera de sendero', 12, 8, 'Búsqueda por la quebrada al norte del mirador. Se encontró un envoltorio de barra de cereal reciente. Sin contacto visual ni respuesta a los llamados.', 8],
            'bit-003' => ['exc-001', 'PN Nonguén - Sendero Los Copihues', 2, null, 'Tercera salida con apoyo de un guardaparque. Revisando el cruce con el sendero Los Copihues.', 2],
            'bit-004' => ['exc-006', 'Laguna del Laja - Sierra Velluda', 66, 58, 'Día 1. Ascenso por la ruta declarada hasta el primer campamento. No hay carpa ni señales de pernocte.', 58],
            'bit-005' => ['exc-006', 'Laguna del Laja - Sierra Velluda', 42, 33, 'Día 2. Se amplía la búsqueda hacia el sector del glaciar. Mala visibilidad por neblina, se suspende a las 18:00.', 33],
            'bit-006' => ['exc-007', 'PN Nonguén - Sendero Los Copihues', 31, 29, 'Se encuentra a las dos personas en buen estado a 400 metros del sendero. Se habían desorientado al anochecer. Se acompaña su descenso.', 29],
        ],
];
