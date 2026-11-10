<?php

namespace Database\Seeders;

use App\Models\Administrador;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Cuenta de administrador para entrar al sistema.
 *
 *   php artisan db:seed --class=AdministradorAccesoSeeder --force
 *
 * Correo: ADMIN_CORREO en .env (por defecto admin@ceec.mx).
 * Contraseña: ADMIN_PASSWORD en .env; si no está definida se genera una al azar
 * y se muestra en la terminal. Volver a ejecutarlo restablece la contraseña.
 */
class AdministradorAccesoSeeder extends Seeder
{
    public function run(): void
    {
        $correo = mb_strtolower(trim((string) env('ADMIN_CORREO', 'admin@ceec.mx')));
        $password = (string) env('ADMIN_PASSWORD', '');
        $generada = $password === '';
        if ($generada) {
            $password = Str::password(12, symbols: false);
        }

        DB::transaction(function () use ($correo, $password) {
            $usuario = User::whereRaw('LOWER(correo) = ?', [$correo])->first() ?? new User(['correo' => $correo]);
            $usuario->rol = 'Administrador';
            $usuario->contraseña = Hash::make($password);
            $usuario->save();

            Administrador::firstOrCreate(
                ['usuario_id' => $usuario->id],
                ['nombre' => 'Administrador', 'apellido_paterno' => 'CEEC'],
            );
        });

        $this->command?->info("Administrador listo: {$correo}");
        if ($generada) {
            $this->command?->warn("Contraseña generada: {$password}");
            $this->command?->line('Guárdala; para fijar una propia define ADMIN_PASSWORD en .env y vuelve a ejecutar el seeder.');
        } else {
            $this->command?->line('Contraseña: la definida en ADMIN_PASSWORD (.env).');
        }
    }
}
