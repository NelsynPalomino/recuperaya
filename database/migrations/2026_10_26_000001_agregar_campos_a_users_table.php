<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Agrega a la tabla "users" de Laravel los campos propios de RecuperaYa (RF-01, RF-02, RNF-04).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('codigo_universitario', 20)->nullable()->unique()->after('email');
            $table->string('rol', 20)->default('usuario')->after('password');
            $table->boolean('activo')->default(true)->after('rol');
            $table->unsignedTinyInteger('intentos_fallidos')->default(0)->after('activo');
            $table->dateTime('bloqueado_hasta')->nullable()->after('intentos_fallidos');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['codigo_universitario']);
            $table->dropColumn(['codigo_universitario', 'rol', 'activo', 'intentos_fallidos', 'bloqueado_hasta']);
        });
    }
};
