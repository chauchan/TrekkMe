<?php

namespace App\Services\Firestore;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

// Cliente mínimo de Firestore por REST, con la cuenta de servicio (salta las reglas de seguridad).
class FirestoreClient
{
    private const ALCANCE = 'https://www.googleapis.com/auth/datastore';

    /** Devuelve el documento como arreglo (con "id" y "_actualizado"), o null si no existe. */
    public function obtener(string $coleccion, string $id): ?array
    {
        $respuesta = $this->http()->get($this->urlDocumento($coleccion, $id));

        if ($respuesta->status() === 404) {
            return null;
        }
        $respuesta->throw();

        return $this->aDocumento($respuesta->json());
    }

    /**
     * Consulta una colección. $filtros: [['campo', '==', valor], ...]; operadores: == in > >= < <=
     * $orden: ['campo', 'asc'|'desc'] o null.
     */
    public function consultar(string $coleccion, array $filtros = [], ?array $orden = null, ?int $limite = null): array
    {
        $consulta = ['from' => [['collectionId' => $coleccion]]];

        if ($filtros) {
            $condiciones = array_map(fn ($f) => [
                'fieldFilter' => [
                    'field' => ['fieldPath' => $f[0]],
                    'op' => $this->operador($f[1]),
                    'value' => $this->valor($f[2]),
                ],
            ], $filtros);

            $consulta['where'] = count($condiciones) === 1
                ? $condiciones[0]
                : ['compositeFilter' => ['op' => 'AND', 'filters' => $condiciones]];
        }
        if ($orden) {
            $consulta['orderBy'] = [[
                'field' => ['fieldPath' => $orden[0]],
                'direction' => strtolower($orden[1] ?? 'asc') === 'desc' ? 'DESCENDING' : 'ASCENDING',
            ]];
        }
        if ($limite) {
            $consulta['limit'] = $limite;
        }

        $respuesta = $this->http()->post($this->base().'/documents:runQuery', ['structuredQuery' => $consulta])->throw();

        return collect($respuesta->json())
            ->filter(fn ($fila) => isset($fila['document']))
            ->map(fn ($fila) => $this->aDocumento($fila['document']))
            ->values()
            ->all();
    }

    /** Crea o reemplaza un documento completo. */
    public function guardar(string $coleccion, string $id, array $datos): void
    {
        $this->confirmar([$this->escritura($coleccion, $id, $datos)]);
    }

    /**
     * Aplica varias escrituras de una sola vez (todas o ninguna).
     * Cada escritura se arma con escritura() o actualizacion(); con $existeActualizadoEn la operación falla (409)
     * si el documento cambió desde que se leyó.
     */
    public function confirmar(array $escrituras): void
    {
        $respuesta = $this->http()->post($this->base().'/documents:commit', ['writes' => $escrituras]);

        if (in_array($respuesta->status(), [409, 400], true)
            && in_array($respuesta->json('error.status'), ['ABORTED', 'FAILED_PRECONDITION', 'ALREADY_EXISTS'], true)) {
            throw new ConflictoDeEscritura($respuesta->json('error.message') ?? 'Conflicto de escritura.');
        }
        $respuesta->throw();
    }

    /** Escritura que reemplaza el documento completo. */
    public function escritura(string $coleccion, string $id, array $datos, ?string $actualizadoEn = null, ?bool $debeExistir = null): array
    {
        $escritura = ['update' => [
            'name' => $this->nombreDocumento($coleccion, $id),
            'fields' => array_map($this->valor(...), $datos),
        ]];

        return $this->conPrecondicion($escritura, $actualizadoEn, $debeExistir);
    }

    /** Escritura que cambia solo algunos campos, sin tocar el resto. */
    public function actualizacion(string $coleccion, string $id, array $campos, ?string $actualizadoEn = null): array
    {
        $escritura = [
            'update' => [
                'name' => $this->nombreDocumento($coleccion, $id),
                'fields' => array_map($this->valor(...), $campos),
            ],
            'updateMask' => ['fieldPaths' => array_keys($campos)],
        ];

        return $this->conPrecondicion($escritura, $actualizadoEn, true);
    }

    public function borrado(string $coleccion, string $id): array
    {
        return ['delete' => $this->nombreDocumento($coleccion, $id)];
    }

    // ---------------------------------------------------------------- internos

    private function conPrecondicion(array $escritura, ?string $actualizadoEn, ?bool $debeExistir): array
    {
        if ($actualizadoEn !== null) {
            $escritura['currentDocument'] = ['updateTime' => $actualizadoEn];
        } elseif ($debeExistir !== null) {
            $escritura['currentDocument'] = ['exists' => $debeExistir];
        }

        return $escritura;
    }

    private function http(): PendingRequest
    {
        return Http::withToken($this->token())->acceptJson()->timeout(20);
    }

    private function token(): string
    {
        return Cache::remember('firestore.token', 3000, function () {
            $credenciales = new ServiceAccountCredentials(self::ALCANCE, config('firebase.credentials'));

            return $credenciales->fetchAuthToken()['access_token'];
        });
    }

    private function base(): string
    {
        return 'https://firestore.googleapis.com/v1/projects/'.config('firebase.project_id').'/databases/(default)';
    }

    private function nombreDocumento(string $coleccion, string $id): string
    {
        return 'projects/'.config('firebase.project_id')."/databases/(default)/documents/{$coleccion}/{$id}";
    }

    private function urlDocumento(string $coleccion, string $id): string
    {
        return $this->base()."/documents/{$coleccion}/{$id}";
    }

    private function operador(string $op): string
    {
        return match ($op) {
            '==' => 'EQUAL', 'in' => 'IN', '>' => 'GREATER_THAN', '>=' => 'GREATER_THAN_OR_EQUAL',
            '<' => 'LESS_THAN', '<=' => 'LESS_THAN_OR_EQUAL',
        };
    }

    private function aDocumento(array $doc): array
    {
        return ['id' => basename($doc['name'])]
            + array_map($this->decodificar(...), $doc['fields'] ?? [])
            + ['_actualizado' => $doc['updateTime'] ?? null];
    }

    // Valor PHP → formato de la API REST de Firestore
    public function valor(mixed $v): array
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

    // Formato de la API REST de Firestore → valor PHP
    private function decodificar(array $v): mixed
    {
        $tipo = array_key_first($v);

        return match ($tipo) {
            'nullValue' => null,
            'booleanValue' => $v[$tipo],
            'integerValue' => (int) $v[$tipo],
            'doubleValue' => (float) $v[$tipo],
            'timestampValue' => Carbon::parse($v[$tipo]),
            'arrayValue' => array_map($this->decodificar(...), $v[$tipo]['values'] ?? []),
            'mapValue' => array_map($this->decodificar(...), $v[$tipo]['fields'] ?? []),
            default => $v[$tipo],
        };
    }
}
