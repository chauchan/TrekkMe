<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SenderoModel extends Model
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
        return $this->belongsTo(AreaModel::class, 'area_id');
    }
}
