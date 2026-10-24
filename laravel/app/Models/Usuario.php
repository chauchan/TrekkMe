<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    protected $table = 'usuarios';

    protected $fillable = ['nombre', 'email', 'password'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'email_verificado_en' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function excursiones(): HasMany
    {
        return $this->hasMany(Excursion::class);
    }

    public function bitacoras(): HasMany
    {
        return $this->hasMany(Bitacora::class);
    }
}