<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentos que sube el estudiante (acta, CURP, certificado, INE).
 * En la BD MySQL original esta tabla se creó a mano; esta migración la crea
 * en instalaciones nuevas (p. ej. SQLite) y no hace nada si ya existe.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('documento_estudiante')) {
            return;
        }

        Schema::create('documento_estudiante', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('estudiante_id');
            $table->string('tipo', 40);              // acta_nacimiento | curp | certificado_secundaria | ine
            $table->string('archivo');               // ruta relativa del PDF
            $table->string('nombre_original')->nullable();
            $table->string('mime_type', 80)->default('application/pdf');
            $table->unsignedInteger('peso')->default(0); // bytes
            $table->enum('estatus', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['estudiante_id', 'tipo'], 'uq_doc_estudiante_tipo');
            $table->index('tipo', 'idx_doc_tipo');
            $table->index('estatus', 'idx_doc_estatus');
            $table->foreign('estudiante_id', 'fk_doc_estudiante')
                ->references('id')->on('estudiante')
                ->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_estudiante');
    }
};
