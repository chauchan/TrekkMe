<?php

namespace App\Models;

use App\Enums\EstadoExcursion;
use App\Models\Concerns\GeneraIdConPrefijo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Excursion extends Model
{
    use GeneraIdConPrefijo;

    protected $table = 'excursions';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'estado' => EstadoExcursion::class,
            'acompanantes' => 'array',
            'salida_at' => 'datetime',
            'retorno_at' => 'datetime',
            'ultima_ubicacion_at' => 'datetime',
            'lleva_carpa' => 'boolean',
            'lleva_linterna' => 'boolean',
        ];
    }

    protected function prefijoId(): string
    {
        return 'exc';
    }

    public function rescatista(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'rescatista_id');
    }

    public function bitacoras(): HasMany
    {
        return $this->hasMany(Bitacora::class);
    }
}
