<?php

namespace App\Console\Commands;

use App\Services\Firestore\UsuarioRepository;
use Illuminate\Console\Command;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class UsuarioResetPasswordCommand extends Command
{
    protected $signature = 'usuario:reset-password {email?} {--password=}';

    protected $description = 'Reasigna la contraseña de un usuario de Firestore (no la recupera: el hash es de una vía)';

    public function handle(UsuarioRepository $usuarios): int
    {
        $email = $this->argument('email') ?? text('Correo del usuario', required: true);

        $usuario = $usuarios->porEmail($email);
        if (! $usuario) {
            $this->error("No existe ningún usuario con el correo {$email}.");

            return self::FAILURE;
        }

        $clave = $this->option('password') ?: password(
            'Nueva contraseña (mínimo 8 caracteres)',
            required: true,
            validate: fn ($v) => strlen($v) < 8 ? 'Mínimo 8 caracteres.' : null,
        );

        if (strlen($clave) < 8) {
            $this->error('La contraseña debe tener al menos 8 caracteres.');

            return self::FAILURE;
        }

        $usuarios->cambiarPassword($usuario['id'], $clave);

        $this->info("Contraseña reasignada para {$usuario['nombre']} ({$email}).");

        return self::SUCCESS;
    }
}
