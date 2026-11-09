<?php

namespace App\Console\Commands;

use App\Models\Estudiante;
use App\Models\User;
use App\Support\Curp;
use Illuminate\Console\Command;

/**
 * Los estudiantes inician sesión con su CURP (los administradores, con su correo).
 * Los estudiantes registrados antes del cambio que no tenían CURP no pueden
 * iniciar sesión hasta tener una:
 *
 *   php artisan usuarios:asignar-curp              → lista las cuentas sin CURP
 *   php artisan usuarios:asignar-curp 15 CURP...   → asigna la CURP a la cuenta 15
 */
class AsignarCurpUsuario extends Command
{
    protected $signature = 'usuarios:asignar-curp {id? : ID de la cuenta (tabla usuario)} {curp? : CURP a asignar}';

    protected $description = 'Lista los estudiantes sin CURP o asigna la CURP con la que iniciarán sesión';

    public function handle(): int
    {
        if (!$this->argument('id')) {
            $sinCurp = User::with('estudiante')->where('rol', 'estudiante')->whereNull('curp')->orderBy('id')->get();

            if ($sinCurp->isEmpty()) {
                $this->info('Todos los estudiantes tienen CURP.');
                return self::SUCCESS;
            }

            $this->table(['ID', 'Rol', 'Nombre'], $sinCurp->map(fn (User $u) => [
                $u->id,
                $u->rol,
                $u->estudiante?->nombre_completo ?? '(sin perfil)',
            ]));
            $this->line('Asigna una CURP con: php artisan usuarios:asignar-curp {id} {curp}');

            return self::SUCCESS;
        }

        $user = User::find($this->argument('id'));
        if (!$user) {
            $this->error('No existe la cuenta ' . $this->argument('id') . '.');
            return self::FAILURE;
        }

        if ($user->isAdmin()) {
            $this->error('Los administradores inician sesión con su correo, no con CURP.');
            return self::FAILURE;
        }

        $curp = Curp::normalizar($this->argument('curp'));
        if ($error = Curp::errorFormato($curp)) {
            $this->error($error);
            return self::FAILURE;
        }
        if (User::where('curp', $curp)->where('id', '!=', $user->id)->exists()) {
            $this->error('Esa CURP ya pertenece a otra cuenta.');
            return self::FAILURE;
        }

        $user->update(['curp' => $curp]);
        Estudiante::where('usuario', $user->id)->update(['curp' => $curp]);

        $this->info("CURP {$curp} asignada a la cuenta {$user->id}.");

        return self::SUCCESS;
    }
}
