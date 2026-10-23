<?php

namespace App\Http\Resources;

use App\Domain\Territorio\Entities\Area;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Area */
class AreaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'jurisdiccion_id' => $this->jurisdiccionId,
            'nombre' => $this->nombre,
            'tipo' => $this->tipo,
            'cobertura_celular' => $this->coberturaCelular,
        ];
    }
}
