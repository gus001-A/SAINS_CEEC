<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CEEC: al crear una cuenta se piden, además de la CURP, el correo electrónico
 * y el teléfono celular (ambos obligatorios). El correo ya existe en `usuario`;
 * aquí se agrega el teléfono.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('usuario') || Schema::hasColumn('usuario', 'telefono')) {
            return;
        }

        Schema::table('usuario', function (Blueprint $table) {
            $table->string('telefono', 10)->nullable()->after('correo');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('usuario') && Schema::hasColumn('usuario', 'telefono')) {
            Schema::table('usuario', function (Blueprint $table) {
                $table->dropColumn('telefono');
            });
        }
    }
};
