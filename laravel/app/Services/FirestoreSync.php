<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\Excursion;
use App\Models\Usuario;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;

// Envía los datos de Laravel a Firestore (REST) con el mismo formato que usa Ionic.
// Solo CREA documentos: si el ID ya existe en Firestore no lo toca.
class FirestoreSync
{
    public const CREADO = 'creado';

    public const YA_EXISTE = 'ya_existe';

    public function usuario(Usuario $u): string
    {
        // Nunca se envía la contraseña
        return $this->crear('usuarios', $u->id, [
            'nombre' => $u->nombre,
            'email' => $u->email,
            'rol' => $u->rol->value,
        ]);
    }

    public function excursion(Excursion $e): string
    {
        return $this->crear('excursiones', $e->id, [
            'excursionista' => $e->excursionista,
            'rut' => $e->rut,
            'telefono' => $e->telefono,
            'edad' => $e->edad,
            'experiencia' => $e->experiencia,
            'condicionesMedicas' => $e->condiciones_medicas,
            'acompanantes' => $e->acompanantes ?? [],
            'ruta' => $e->ruta,
            'contactoNombre' => $e->contacto_nombre,
            'contactoTelefono' => $e->contacto_telefono,
            'salidaAt' => $e->salida_at,
            'retornoAt' => $e->retorno_at,
            // Ionic guarda el texto con mayúscula y espacios ("En operativo")
            'estado' => $e->estado->etiqueta(),
            'rescatistaId' => $e->rescatista_id ?? '',
            'fotoUrl' => $e->foto_url ?? '',
            'detalle' => $e->detalle ?? '',
            'llevaCarpa' => $e->lleva_carpa,
            'colorCarpa' => $e->color_carpa ?? '',
            'llevaLinterna' => $e->lleva_linterna,
            'ultimaLat' => $e->ultima_lat,
            'ultimaLng' => $e->ultima_lng,
            'ultimaUbicacionAt' => $e->ultima_ubicacion_at,
            'bateria' => $e->bateria,
        ]);
    }

    public function bitacora(Bitacora $b): string
    {
        return $this->crear('bitacora', $b->id, [
            'excursionId' => $b->excursion_id,
            'rescatistaId' => $b->rescatista_id,
            'ruta' => $b->ruta,
            'horaInicio' => $b->hora_inicio,
            'horaFin' => $b->hora_fin,
            'texto' => $b->texto,
            'registradoAt' => $b->registrado_at,
            'version' => $b->version,
        ]);
    }

    private function crear(string $coleccion, string $id, array $campos): string
    {
        $url = sprintf(
            'https://firestore.googleapis.com/v1/projects/%s/databases/(default)/documents/%s',
            config('firebase.project_id'),
            $coleccion
        );

        $respuesta = Http::timeout(15)->post($url.'?'.http_build_query([
            'documentId' => $id,
            'key' => config('firebase.api_key'),
        ]), [
            'fields' => array_map($this->valor(...), $campos),
        ]);

        if ($respuesta->status() === 409) {
            return self::YA_EXISTE;
        }

        if ($respuesta->failed()) {
            throw new \RuntimeException(
                "Firestore rechazó {$coleccion}/{$id}: ".($respuesta->json('error.message') ?? $respuesta->status())
            );
        }

        return self::CREADO;
    }

    // Convierte un valor PHP al formato de la API REST de Firestore
    private function valor(mixed $v): array
    {
        return match (true) {
            $v === null => ['nullValue' => null],
            is_bool($v) => ['booleanValue' => $v],
            is_int($v) => ['integerValue' => (string) $v],
            is_float($v) => ['doubleValue' => $v],
            $v instanceof CarbonInterface => ['timestampValue' => $v->copy()->utc()->format('Y-m-d\TH:i:s.v\Z')],
            is_array($v) && array_is_list($v) => ['arrayValue' => ['values' => array_map($this->valor(...), $v)]],
            is_array($v) => ['mapValue' => ['fields' => array_map($this->valor(...), $v)]],
            default => ['stringValue' => (string) $v],
        };
    }
}
