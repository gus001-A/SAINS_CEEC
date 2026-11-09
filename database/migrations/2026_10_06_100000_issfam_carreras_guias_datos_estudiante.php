<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ISSFAM: CURP y domicilio del estudiante.
 * (En SAINS ISSFAM ya no hay carreras, guías ni candado de ediciones.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estudiante', function (Blueprint $table) {
            $table->string('curp', 18)->nullable();
            $table->string('calle_numero', 150)->nullable();
            $table->string('colonia', 120)->nullable();
            $table->string('codigo_postal', 5)->nullable();
            $table->string('municipio', 120)->nullable();
            $table->string('entidad_federativa', 60)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('estudiante', function (Blueprint $table) {
            $table->dropColumn(['curp', 'calle_numero', 'colonia', 'codigo_postal', 'municipio', 'entidad_federativa']);
        });
    }
};
