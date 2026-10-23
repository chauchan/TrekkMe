<?php

namespace App\Http\Resources;

use App\Domain\Territorio\Entities\Sendero;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Sendero */
class SenderoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'area_id' => $this->areaId,
            'nombre' => $this->nombre,
            'dificultad' => $this->dificultad,
            'distancia_km' => $this->distanciaKm,
            'desnivel_m' => $this->desnivelM,
            'duracion_estimada_min' => $this->duracionEstimadaMin,
        ];
    }
}
