<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    protected $table = 'areas';

    protected $fillable = ['jurisdiccion_id', 'nombre', 'tipo', 'cobertura_celular', 'activa'];

    protected $casts = ['activa' => 'boolean'];

    public function jurisdiccion(): BelongsTo
    {
        return $this->belongsTo(Jurisdiccion::class, 'jurisdiccion_id');
    }

    public function senderos(): HasMany
    {
        return $this->hasMany(Sendero::class, 'area_id');
    }
}
