<?php

namespace App\Models;

use App\Enums\RolUsuario;
use App\Models\Concerns\GeneraIdConPrefijo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    use GeneraIdConPrefijo;

    protected $table = 'usuarios';

    protected $fillable = ['id', 'nombre', 'email', 'rol', 'password'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'rol' => RolUsuario::class,
            'email_verificado_en' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected function prefijoId(): string
    {
        $rol = $this->rol instanceof RolUsuario ? $this->rol : RolUsuario::from($this->rol);

        return $rol->prefijo();
    }

    // Casos que tiene asignados (solo rescatistas)
    public function excursiones(): HasMany
    {
        return $this->hasMany(Excursion::class, 'rescatista_id');
    }

    public function bitacoras(): HasMany
    {
        return $this->hasMany(Bitacora::class, 'rescatista_id');
    }
}
