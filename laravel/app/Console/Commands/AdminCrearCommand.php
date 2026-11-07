<?php

namespace App\Console\Commands;

use App\Enums\RolUsuario;
use App\Services\Firestore\UsuarioRepository;
use Illuminate\Console\Command;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class AdminCrearCommand extends Command
{
    protected $signature = 'admin:crear {email?} {--nombre=}';

    protected $description = 'Crea un usuario SysAdmin en Firestore (único rol que entra al panel web)';

    public function handle(UsuarioRepository $usuarios): int
    {
        $email = $this->argument('email') ?? text('Correo', required: true);
        $nombre = $this->option('nombre') ?: text('Nombre', required: true);

        if ($usuarios->porEmail($email)) {
            $this->error('Ya existe un usuario con ese correo.');

            return self::FAILURE;
        }

        $clave = password('Contraseña (mínimo 8 caracteres)', required: true, validate: fn ($v) => strlen($v) < 8 ? 'Mínimo 8 caracteres.' : null);

        $id = $usuarios->crear($nombre, $email, RolUsuario::Admin, null, $clave);
        $this->info("Admin {$id} creado en Firestore.");

        return self::SUCCESS;
    }
}
