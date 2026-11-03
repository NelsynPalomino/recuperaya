<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Catálogos configurables por el administrador (RF-13).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60)->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('lugares', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80)->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lugares');
        Schema::dropIfExists('categorias');
    }
};
