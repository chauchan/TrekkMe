<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sendero extends Model
{
    protected $table = 'senderos';

    protected $fillable = [
        'area_id', 'nombre', 'dificultad', 'distancia_km',
        'desnivel_m', 'duracion_estimada_min', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'distancia_km' => 'float',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }
}
