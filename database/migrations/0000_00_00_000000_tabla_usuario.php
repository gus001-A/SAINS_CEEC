<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario', function (Blueprint $table) {
            $table->id();
            $table->string('curp', 18)->nullable()->unique();   // estudiantes
            $table->string('correo')->nullable()->unique();      // obligatorio al registrarse (CEEC)
            $table->string('telefono', 10)->nullable();          // celular, obligatorio al registrarse (CEEC)
            $table->string('contraseña');
            $table->string('rol');
            $table->timestamps(false); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario');
    }
};