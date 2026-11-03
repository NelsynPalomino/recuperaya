<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Route resource en español (Sesión 09): /objetos/crear y /objetos/{objeto}/editar
        Route::resourceVerbs([
            'create' => 'crear',
            'edit' => 'editar',
        ]);

        // Permisos por rol (RNF-05). Se usan en las rutas con el middleware "can".
        Gate::define('gestionar', fn (User $u) => $u->puedeGestionar());   // encargado y administrador
        Gate::define('administrar', fn (User $u) => $u->esAdmin());        // solo administrador
        Gate::define('reportar', fn (User $u) => $u->esUsuario());         // estudiantes, docentes y personal

        Paginator::useBootstrapFive();
    }
}
