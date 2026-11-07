<?php

namespace App\Http\Controllers;

use App\Enums\AccionExcursion;
use App\Enums\EstadoExcursion;
use App\Services\Firestore\UsuarioRepository;
use App\Services\TransicionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CarabineroController extends Controller
{
    // Rescatistas de la jurisdicción del carabinero, para el selector al escalar
    public function rescatistas(Request $request, UsuarioRepository $usuarios): JsonResponse
    {
        $jurisdiccion = $request->attributes->get('usuario')['jurisdiccionId'];

        $lista = array_map(
            fn ($r) => ['id' => $r['id'], 'nombre' => $r['nombre']],
            $usuarios->rescatistas($jurisdiccion)
        );

        return response()->json(['data' => $lista]);
    }

    public function transicion(Request $request, string $id, string $accion, TransicionService $transiciones, UsuarioRepository $usuarios): JsonResponse
    {
        $accion = AccionExcursion::from($accion);
        $usuario = $request->attributes->get('usuario');

        $datos = $request->validate([
            'estado_esperado' => ['required', Rule::enum(EstadoExcursion::class)],
            'rescatista_id' => [Rule::requiredIf($accion === AccionExcursion::Escalar), 'string'],
        ]);

        $rescatistaId = null;
        if ($accion === AccionExcursion::Escalar) {
            $rescatista = $usuarios->porId($datos['rescatista_id']);

            // Debe existir, ser rescatista y ser de la misma jurisdicción que el carabinero
            if (! $rescatista || $rescatista['rol']?->value !== 'rescatista' || $rescatista['jurisdiccionId'] !== $usuario['jurisdiccionId']) {
                throw ValidationException::withMessages(['rescatista_id' => 'Elige un rescatista de tu jurisdicción.']);
            }
            $rescatistaId = $rescatista['id'];
        }

        $resultado = $transiciones->aplicar($id, $accion, EstadoExcursion::from($datos['estado_esperado']), $usuario, $rescatistaId);

        return response()->json(['data' => $resultado]);
    }
}
