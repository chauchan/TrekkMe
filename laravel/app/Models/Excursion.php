<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Excursion extends Model
{
    protected $table = 'excursions';

    public $timestamps = false;

    protected $fillable = ['usuario_id', 'estado_id', 'duracion_horas', 'salida'];

    protected function casts(): array
    {
        return [
            'salida' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class);
    }

    public function bitacoras(): HasMany
    {
        return $this->hasMany(Bitacora::class);
    }
}