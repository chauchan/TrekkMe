<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Sendero */
class SenderoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'area_id' => $this->area_id,
            'nombre' => $this->nombre,
            'dificultad' => $this->dificultad,
            'distancia_km' => $this->distancia_km,
            'desnivel_m' => $this->desnivel_m,
            'duracion_estimada_min' => $this->duracion_estimada_min,
        ];
    }
}
