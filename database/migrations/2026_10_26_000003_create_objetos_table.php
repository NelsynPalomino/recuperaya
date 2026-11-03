<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Objetos encontrados (RF-03, RF-04). El campo "slug" permite URL amigables (Sesión 09).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('objetos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('slug')->unique();
            $table->foreignId('categoria_id')->constrained('categorias');
            $table->foreignId('lugar_id')->constrained('lugares');
            $table->string('descripcion', 500);
            $table->string('color', 40);
            $table->date('fecha_hallazgo');
            $table->string('foto')->nullable();
            $table->string('pregunta1', 200);
            $table->string('respuesta1_hash');
            $table->string('pregunta2', 200);
            $table->string('respuesta2_hash');
            $table->string('estado', 20)->default('En custodia');
            $table->foreignId('registrado_por')->constrained('users');
            $table->timestamps();
            $table->index(['estado', 'categoria_id', 'lugar_id', 'fecha_hallazgo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('objetos');
    }
};
