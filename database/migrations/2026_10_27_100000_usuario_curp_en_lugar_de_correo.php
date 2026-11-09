<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Los estudiantes ya no usan correo: su cuenta se identifica con la CURP.
 * Los administradores siguen entrando con su correo.
 *
 * - Agrega `usuario.curp` (única) y la llena con la CURP ya capturada en `estudiante`.
 * - `usuario.correo` pasa a ser opcional (las cuentas de estudiante nuevas no tienen).
 *
 * Los estudiantes que queden sin CURP se listan y se les asigna con:
 * php artisan usuarios:asignar-curp
 */
return new class extends Migration
{
    public function up(): void
    {
        // En una instalación nueva `tabla_usuario` ya crea la tabla con `curp`.
        if (!Schema::hasTable('usuario') || Schema::hasColumn('usuario', 'curp')) {
            return;
        }

        Schema::table('usuario', function (Blueprint $table) {
            $table->string('curp', 18)->nullable()->unique()->after('id');
            $table->string('correo')->nullable()->change();
        });

        $asignadas = [];
        DB::table('estudiante')
            ->whereNotNull('curp')
            ->orderBy('id')
            ->get(['usuario', 'curp'])
            ->each(function ($e) use (&$asignadas) {
                $curp = strtoupper(trim($e->curp));
                if (strlen($curp) !== 18 || isset($asignadas[$curp])) {
                    return;
                }
                $asignadas[$curp] = true;
                DB::table('usuario')->where('id', $e->usuario)->update(['curp' => $curp]);
            });
    }

    public function down(): void
    {
        if (!Schema::hasTable('usuario') || !Schema::hasColumn('usuario', 'curp')) {
            return;
        }

        Schema::table('usuario', function (Blueprint $table) {
            $table->dropUnique(['curp']);
            $table->dropColumn('curp');
        });
    }
};
