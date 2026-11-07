<?php

namespace App\Services\Firestore;

use App\Enums\EstadoExcursion;

// Escribe cada excursión en dos colecciones: "excursiones" (completa, para el rescatista asignado)
// y "avisos" (sin datos médicos, para el carabinero de la jurisdicción).
class ExcursionRepository extends Repositorio
{
    public function todas(): array
    {
        return collect($this->firestore->consultar('excursiones'))
            ->map($this->normalizar(...))
            ->sortBy('id')
            ->values()
            ->all();
    }

    public function porId(string $id): ?array
    {
        $doc = $this->firestore->obtener('excursiones', $id);

        return $doc ? $this->normalizar($doc) : null;
    }

    public function aviso(string $id): ?array
    {
        $doc = $this->firestore->obtener('avisos', $id);

        return $doc ? $this->normalizar($doc) : null;
    }

    // Crea la excursión y su aviso en una sola operación. Devuelve el ID.
    public function crear(array $datos, string $senderoId, string $jurisdiccionId): string
    {
        $id = $this->siguienteId('excursiones', 'exc');
        $datos['estado'] = ($datos['estado'] instanceof EstadoExcursion ? $datos['estado'] : EstadoExcursion::from($datos['estado']))->value;

        $this->firestore->confirmar([
            $this->firestore->escritura('excursiones', $id, $this->completar($datos), null, false),
            $this->firestore->escritura('avisos', $id, $this->avisoDe($datos, $senderoId, $jurisdiccionId), null, false),
        ]);

        return $id;
    }

    // Aviso = excursión sin datos médicos; de los acompañantes solo nombre y RUT
    public function avisoDe(array $excursion, string $senderoId, string $jurisdiccionId): array
    {
        $excursion = $this->completar($excursion);
        unset($excursion['condicionesMedicas']);

        $excursion['acompanantes'] = array_map(
            fn ($a) => ['nombre' => $a['nombre'], 'rut' => $a['rut']],
            $excursion['acompanantes']
        );
        $excursion['estado'] = $this->codigoEstado($excursion['estado']);
        $excursion['senderoId'] = $senderoId;
        $excursion['jurisdiccionId'] = $jurisdiccionId;

        return $excursion;
    }

    // Deja todos los campos presentes y con el tipo que espera Ionic ('' en vez de null para los textos)
    public function completar(array $d): array
    {
        $texto = fn ($v) => $v ?? '';

        return [
            'excursionista' => $d['excursionista'],
            'rut' => $d['rut'],
            'telefono' => $d['telefono'],
            'edad' => (int) $d['edad'],
            'experiencia' => $d['experiencia'],
            'condicionesMedicas' => $texto($d['condicionesMedicas'] ?? 'Ninguna'),
            'acompanantes' => $d['acompanantes'] ?? [],
            'ruta' => $d['ruta'],
            'contactoNombre' => $d['contactoNombre'],
            'contactoTelefono' => $d['contactoTelefono'],
            'salidaAt' => $d['salidaAt'],
            'retornoAt' => $d['retornoAt'],
            'estado' => $d['estado'] instanceof EstadoExcursion ? $d['estado']->value : $d['estado'],
            'rescatistaId' => $texto($d['rescatistaId'] ?? ''),
            'fotoUrl' => $texto($d['fotoUrl'] ?? ''),
            'detalle' => $texto($d['detalle'] ?? ''),
            'llevaCarpa' => (bool) ($d['llevaCarpa'] ?? false),
            'colorCarpa' => $texto($d['colorCarpa'] ?? ''),
            'llevaLinterna' => (bool) ($d['llevaLinterna'] ?? false),
            'ultimaLat' => $d['ultimaLat'] ?? null,
            'ultimaLng' => $d['ultimaLng'] ?? null,
            'ultimaUbicacionAt' => $d['ultimaUbicacionAt'] ?? null,
            'bateria' => $d['bateria'] ?? null,
        ];
    }

    // Convierte el texto de estado en el enum (acepta código y texto antiguo)
    private function normalizar(array $doc): array
    {
        $doc['estadoTexto'] = $doc['estado'] ?? '';
        $doc['estado'] = EstadoExcursion::desdeTexto($doc['estadoTexto']);

        return $doc;
    }

    private function codigoEstado(mixed $estado): string
    {
        return $estado instanceof EstadoExcursion
            ? $estado->value
            : (EstadoExcursion::desdeTexto((string) $estado)?->value ?? (string) $estado);
    }
}
