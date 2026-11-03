<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Reclamos (RF-09, RF-10) y entregas (RF-11).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reclamos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objeto_id')->constrained('objetos');
            $table->foreignId('user_id')->constrained('users');
            $table->unsignedTinyInteger('intentos')->default(0);
            $table->string('estado', 20)->default('Pendiente');
            $table->timestamps();
        });

        Schema::create('entregas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objeto_id')->constrained('objetos');
            $table->foreignId('reclamo_id')->constrained('reclamos');
            $table->foreignId('receptor_id')->constrained('users');
            $table->foreignId('encargado_id')->constrained('users');
            $table->string('codigo_carne', 20);
            $table->string('observacion')->nullable();
            $table->dateTime('fecha_entrega');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entregas');
        Schema::dropIfExists('reclamos');
    }
};
