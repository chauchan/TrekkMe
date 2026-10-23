<?php

namespace App\Providers;

use App\Domain\Territorio\Repositories\TerritorioRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentTerritorioRepository;
use Illuminate\Support\ServiceProvider;

// Conecta cada interfaz del dominio con su implementación de infraestructura
class DomainServiceProvider extends ServiceProvider
{
    public array $bindings = [
        TerritorioRepository::class => EloquentTerritorioRepository::class,
    ];
}
