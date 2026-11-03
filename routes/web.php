<?php

use App\Http\Controllers\Admin\CategoriaController;
use App\Http\Controllers\Admin\LugarController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\EntregaController;
use App\Http\Controllers\EstadisticaController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\ObjetoController;
use App\Http\Controllers\PaginaController;
use App\Http\Controllers\ReclamoController;
use App\Http\Controllers\ReportePerdidaController;
use Illuminate\Support\Facades\Route;

// ---------- Visitantes (sin sesión) ----------
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/registro', [AuthController::class, 'showRegistro'])->name('registro');
    Route::post('/registro', [AuthController::class, 'registro'])->name('registro.store');
});
Route::get('/privacidad', [PaginaController::class, 'privacidad'])->name('privacidad');

// ---------- Usuarios con sesión ----------
Route::middleware('auth')->group(function () {
    Route::get('/', InicioController::class)->name('inicio');
    Route::post('/salir', [AuthController::class, 'logout'])->name('logout');
    Route::get('/avisos', [NotificacionController::class, 'index'])->name('notificaciones.index');

    // Catálogo con URL amigable por slug: /catalogo/celular-negro-obj-0001
    Route::get('/catalogo', [CatalogoController::class, 'index'])->name('catalogo.index');
    Route::get('/catalogo/{objeto}', [CatalogoController::class, 'show'])->name('catalogo.show');

    // Estudiante / docente / personal
    Route::middleware('can:reportar')->group(function () {
        Route::resource('reportes', ReportePerdidaController::class)->only(['index', 'create', 'store', 'destroy']);
        Route::post('/catalogo/{objeto}/reclamar', [ReclamoController::class, 'store'])->name('reclamos.store');
        Route::get('/mis-reclamos', [ReclamoController::class, 'mis'])->name('reclamos.mis');
    });

    // Encargado de custodia y administrador
    Route::middleware('can:gestionar')->group(function () {
        Route::resource('objetos', ObjetoController::class);   // 7 rutas: index, create, store, show, edit, update, destroy
        Route::get('/entregas', [EntregaController::class, 'index'])->name('entregas.index');
        Route::post('/entregas', [EntregaController::class, 'store'])->name('entregas.store');
        Route::get('/reportes-estadisticos', [EstadisticaController::class, 'index'])->name('estadisticas.index');
        Route::get('/reportes-estadisticos/imprimir', [EstadisticaController::class, 'imprimir'])->name('estadisticas.imprimir');
        Route::get('/reportes-estadisticos/excel', [EstadisticaController::class, 'csv'])->name('estadisticas.csv');
    });

    // Solo administrador
    Route::middleware('can:administrar')->prefix('admin')->group(function () {
        Route::get('/reportes-estadisticos/datos-ml', [EstadisticaController::class, 'ml'])->name('estadisticas.ml');
        Route::resource('usuarios', UsuarioController::class)->only(['index', 'edit', 'update']);
        Route::resource('categorias', CategoriaController::class)->except(['show']);
        Route::resource('lugares', LugarController::class)->except(['show'])->parameters(['lugares' => 'lugar']);
    });
});
