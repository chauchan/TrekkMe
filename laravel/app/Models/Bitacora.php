<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bitacora extends Model
{
    protected $table = 'bitacoras';

    public $timestamps = false;

    protected $fillable = ['usuario_id', 'excursion_id', 'hora_inicio', 'hora_fin'];

    protected function casts(): array
    {
        return [
            'hora_inicio' => 'datetime',
            'hora_fin' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function excursion(): BelongsTo
    {
        return $this->belongsTo(Excursion::class);
    }
}
