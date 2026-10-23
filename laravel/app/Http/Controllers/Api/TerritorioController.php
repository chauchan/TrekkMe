<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AreaResource;
use App\Http\Resources\SenderoResource;
use App\Models\Area;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

// Catálogo de áreas y senderos. Lo consumen Ionic (bitácora) y Flutter (aviso de salida).
class TerritorioController extends Controller
{
    public function areas(): AnonymousResourceCollection
    {
        $areas = Area::where('activa', true)->orderBy('nombre')->get();

        return AreaResource::collection($areas);
    }

    // Si el área no existe, el route model binding responde 404
    public function senderos(Area $area): AnonymousResourceCollection
    {
        $senderos = $area->senderos()->where('activo', true)->orderBy('nombre')->get();

        return SenderoResource::collection($senderos);
    }
}
