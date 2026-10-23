<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JurisdiccionModel extends Model
{
    protected $table = 'jurisdicciones';

    protected $fillable = ['nombre', 'region', 'comuna'];

    public function areas(): HasMany
    {
        return $this->hasMany(AreaModel::class, 'jurisdiccion_id');
    }
}
