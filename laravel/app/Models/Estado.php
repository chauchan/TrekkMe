<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estado extends Model
{
    protected $table = 'estado';

    public $timestamps = false;

    protected $fillable = ['nombre_estado'];

    public function excursiones(): HasMany
    {
        return $this->hasMany(Excursion::class);
    }
}