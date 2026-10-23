<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jurisdiccion extends Model
{
    protected $table = 'jurisdicciones';

    protected $fillable = ['nombre', 'region', 'comuna'];

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class, 'jurisdiccion_id');
    }
}
