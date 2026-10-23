<?php

namespace App\Http\Controllers\Api;

use App\Application\Territorio\ListarAreas;
use App\Application\Territorio\ListarSenderosDeArea;
use App\Http\Controllers\Controller;
use App\Http\Resources\AreaResource;
use App\Http\Resources\SenderoResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

// Catálogo de áreas y senderos. Lo consumen Ionic (bitácora) y Flutter (aviso de salida).
class TerritorioController extends Controller
{
    public function areas(ListarAreas $listarAreas): AnonymousResourceCollection
    {
        return AreaResource::collection($listarAreas->ejecutar());
    }

    public function senderos(int $area, ListarSenderosDeArea $listarSenderos): AnonymousResourceCollection
    {
        return SenderoResource::collection($listarSenderos->ejecutar($area));
    }
}
