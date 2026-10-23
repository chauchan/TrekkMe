<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Area */
class AreaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'jurisdiccion_id' => $this->jurisdiccion_id,
            'nombre' => $this->nombre,
            'tipo' => $this->tipo,
            'cobertura_celular' => $this->cobertura_celular,
        ];
    }
}
