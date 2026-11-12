<?php

namespace App\Services\Simulador;

use App\Enums\EstadoExcursion;
use Carbon\CarbonInterface;

// Arma excursiones ficticias al azar, con el mismo formato que los datos de ejemplo.
// Usa mt_rand para que, con la misma semilla, salga la misma secuencia.
class GeneradorExcursion
{
    private const NOMBRES = [
        'Camila', 'Valentina', 'Javiera', 'Fernanda', 'Constanza', 'Antonia', 'Francisca', 'Catalina',
        'Matías', 'Benjamín', 'Vicente', 'Joaquín', 'Martín', 'Sebastián', 'Felipe', 'Cristóbal',
    ];

    private const APELLIDOS = [
        'González', 'Muñoz', 'Rojas', 'Díaz', 'Pérez', 'Soto', 'Contreras', 'Silva', 'Martínez',
        'Sepúlveda', 'Morales', 'Rodríguez', 'López', 'Fuentes', 'Hernández', 'Torres', 'Araya', 'Vergara',
    ];

    private const PARENTESCOS = ['madre', 'padre', 'hermana', 'hermano', 'pareja', 'amiga', 'amigo'];

    private const EXPERIENCIAS = ['Principiante', 'Intermedio', 'Avanzado'];

    // "Ninguna" se repite para que sea lo más común
    private const CONDICIONES = [
        'Ninguna', 'Ninguna', 'Ninguna', 'Ninguna', 'Ninguna', 'Asma', 'Diabetes tipo 1',
        'Hipertensión', 'Alergia a picaduras de abeja', 'Epilepsia controlada',
    ];

    private const ROPA = [
        'Chaqueta roja', 'Chaqueta azul', 'Cortaviento verde', 'Polerón amarillo', 'Parka negra', 'Chaqueta naranja',
    ];

    private const EXTRAS = [
        'mochila gris de 30 litros', 'gorro de lana', 'bastones de trekking', 'jockey blanco',
        'mochila roja grande', 'lentes de sol', 'pantalón café',
    ];

    private const COLORES_CARPA = ['Naranja', 'Verde', 'Azul', 'Amarilla', 'Roja'];

    private const FOTO = 'assets/ejemplos/foto-ejemplo.svg';

    /**
     * @param  string  $ruta  nombre del sendero (debe coincidir con la colección "senderos")
     */
    public function generar(string $ruta, CarbonInterface $salidaAt, CarbonInterface $retornoAt): array
    {
        [$nombre, $apellido1, $apellido2] = [$this->uno(self::NOMBRES), $this->uno(self::APELLIDOS), $this->uno(self::APELLIDOS)];
        $llevaCarpa = mt_rand(0, 2) === 0;

        return [
            'excursionista' => "{$nombre} {$apellido1} {$apellido2}",
            'rut' => $this->rut(),
            'telefono' => $this->telefono(),
            'edad' => mt_rand(18, 65),
            'experiencia' => $this->uno(self::EXPERIENCIAS),
            'condicionesMedicas' => $this->uno(self::CONDICIONES),
            'acompanantes' => $this->acompanantes($apellido1),
            'ruta' => $ruta,
            'contactoNombre' => $this->uno(self::NOMBRES).' '.$apellido1.' ('.$this->uno(self::PARENTESCOS).')',
            'contactoTelefono' => $this->telefono(),
            'salidaAt' => $salidaAt,
            'retornoAt' => $retornoAt,
            'estado' => EstadoExcursion::Planificada,
            'rescatistaId' => null,
            'fotoUrl' => mt_rand(0, 1) ? self::FOTO : null,
            'detalle' => mt_rand(0, 3) ? $this->uno(self::ROPA).' y '.$this->uno(self::EXTRAS).'.' : null,
            'llevaCarpa' => $llevaCarpa,
            'colorCarpa' => $llevaCarpa ? $this->uno(self::COLORES_CARPA) : null,
            'llevaLinterna' => (bool) mt_rand(0, 1),
            'ultimaLat' => null,
            'ultimaLng' => null,
            'ultimaUbicacionAt' => null,
            'bateria' => null,
        ];
    }

    // Entre 0 y 3 acompañantes, con y sin cuenta en la app
    private function acompanantes(string $apellido): array
    {
        $lista = [];
        for ($i = mt_rand(0, 3); $i > 0; $i--) {
            $tieneCuenta = (bool) mt_rand(0, 1);
            $lista[] = [
                'nombre' => $this->uno(self::NOMBRES).' '.(mt_rand(0, 1) ? $apellido : $this->uno(self::APELLIDOS)).' '.$this->uno(self::APELLIDOS),
                'rut' => $this->rut(),
                'edad' => mt_rand(8, 70),
                'condicionesMedicas' => $this->uno(self::CONDICIONES),
                'tieneCuenta' => $tieneCuenta,
                'telefono' => $tieneCuenta ? $this->telefono() : '',
                'experiencia' => $tieneCuenta ? $this->uno(self::EXPERIENCIAS) : '',
            ];
        }

        return $lista;
    }

    // RUT con dígito verificador válido (módulo 11), formato 12.345.678-9
    public function rut(): string
    {
        $numero = mt_rand(10_000_000, 25_999_999);

        $suma = 0;
        $factor = 2;
        foreach (array_reverse(str_split((string) $numero)) as $digito) {
            $suma += (int) $digito * $factor;
            $factor = $factor === 7 ? 2 : $factor + 1;
        }
        $resto = 11 - ($suma % 11);
        $dv = match ($resto) {
            11 => '0',
            10 => 'K',
            default => (string) $resto,
        };

        return number_format($numero, 0, ',', '.').'-'.$dv;
    }

    private function telefono(): string
    {
        return sprintf('+56 9 %04d %04d', mt_rand(1000, 9999), mt_rand(0, 9999));
    }

    private function uno(array $opciones): string
    {
        return $opciones[mt_rand(0, count($opciones) - 1)];
    }
}
