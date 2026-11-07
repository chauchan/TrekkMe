<?php

namespace App\Services\Firestore;

// Jurisdicciones y senderos
class CatalogoRepository extends Repositorio
{
    public function jurisdicciones(): array
    {
        return collect($this->firestore->consultar('jurisdicciones'))->sortBy('id')->values()->all();
    }

    public function senderos(?string $jurisdiccionId = null): array
    {
        $filtros = $jurisdiccionId ? [['jurisdiccionId', '==', $jurisdiccionId]] : [];

        return collect($this->firestore->consultar('senderos', $filtros))->sortBy('nombre')->values()->all();
    }

    public function sendero(string $id): ?array
    {
        return $this->firestore->obtener('senderos', $id);
    }
}
