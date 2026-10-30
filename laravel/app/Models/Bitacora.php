<?php

namespace App\Models;

use App\Models\Concerns\GeneraIdConPrefijo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bitacora extends Model
{
    use GeneraIdConPrefijo;

    protected $table = 'bitacoras';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'hora_inicio' => 'datetime',
            'hora_fin' => 'datetime',
            'registrado_at' => 'datetime',
        ];
    }

    protected function prefijoId(): string
    {
        return 'bit';
    }

    public function rescatista(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'rescatista_id');
    }

    public function excursion(): BelongsTo
    {
        return $this->belongsTo(Excursion::class);
    }
}
