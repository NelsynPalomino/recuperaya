<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Reportes de pérdida (RF-05) y coincidencias calculadas por el motor (RF-07).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes_perdida', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('categoria_id')->constrained('categorias');
            $table->foreignId('lugar_id')->nullable()->constrained('lugares');
            $table->string('descripcion', 500);
            $table->string('color', 40);
            $table->date('fecha_perdida');
            $table->string('estado', 20)->default('Activo');
            $table->timestamps();
        });

        Schema::create('coincidencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporte_perdida_id')->constrained('reportes_perdida')->cascadeOnDelete();
            $table->foreignId('objeto_id')->constrained('objetos')->cascadeOnDelete();
            $table->unsignedTinyInteger('puntaje');
            $table->timestamps();
            $table->unique(['reporte_perdida_id', 'objeto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coincidencias');
        Schema::dropIfExists('reportes_perdida');
    }
};
