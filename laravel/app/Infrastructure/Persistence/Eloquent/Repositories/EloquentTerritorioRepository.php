<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Territorio\Entities\Area;
use App\Domain\Territorio\Entities\Sendero;
use App\Domain\Territorio\Repositories\TerritorioRepository;
use App\Infrastructure\Persistence\Eloquent\Models\AreaModel;
use App\Infrastructure\Persistence\Eloquent\Models\SenderoModel;

// Implementación del catálogo de territorio con Eloquent.
// Traduce modelos de BD a entidades del dominio para que no se filtre Eloquent hacia arriba.
class EloquentTerritorioRepository implements TerritorioRepository
{
    public function areasActivas(): array
    {
        return AreaModel::where('activa', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn (AreaModel $m) => $this->aArea($m))
            ->all();
    }

    public function buscarArea(int $areaId): ?Area
    {
        $modelo = AreaModel::find($areaId);

        return $modelo ? $this->aArea($modelo) : null;
    }

    public function senderosActivosDeArea(int $areaId): array
    {
        return SenderoModel::where('area_id', $areaId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn (SenderoModel $m) => $this->aSendero($m))
            ->all();
    }

    private function aArea(AreaModel $m): Area
    {
        return new Area(
            id: $m->id,
            jurisdiccionId: $m->jurisdiccion_id,
            nombre: $m->nombre,
            tipo: $m->tipo,
            coberturaCelular: $m->cobertura_celular,
            activa: $m->activa,
        );
    }

    private function aSendero(SenderoModel $m): Sendero
    {
        return new Sendero(
            id: $m->id,
            areaId: $m->area_id,
            nombre: $m->nombre,
            dificultad: $m->dificultad,
            distanciaKm: $m->distancia_km,
            desnivelM: $m->desnivel_m,
            duracionEstimadaMin: $m->duracion_estimada_min,
            activo: $m->activo,
        );
    }
}
