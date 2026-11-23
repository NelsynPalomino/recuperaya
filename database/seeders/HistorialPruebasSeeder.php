<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Entrega;
use App\Models\Lugar;
use App\Models\Objeto;
use App\Models\Reclamo;
use App\Models\ReportePerdida;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Historial de PRUEBA para el ambiente de Cypress/demostración (no se ejecuta en producción).
 * Genera objetos, reclamos, entregas y reportes de pérdida de los últimos meses para que
 * el módulo de reportes calcule sus indicadores a partir de registros guardados en la base de datos.
 *
 * Uso: APP_ENV=cypress php artisan db:seed --class=HistorialPruebasSeeder
 */
class HistorialPruebasSeeder extends Seeder
{
    public function run(): void
    {
        fake()->seed(2026); // datos reproducibles
        $encargado = User::where('rol', User::ENCARGADO)->firstOrFail();
        $estudiantes = User::factory()->count(12)->create();
        $categorias = Categoria::pluck('id')->all();
        $lugares = Lugar::pluck('id')->all();

        foreach (range(0, 59) as $i) {
            $fecha = now()->startOfYear()->addDays(fake()->numberBetween(0, (int) now()->startOfYear()->diffInDays(now())));
            $estado = fake()->randomElement([Objeto::DEVUELTO, Objeto::DEVUELTO, Objeto::DEVUELTO, Objeto::EN_CUSTODIA, Objeto::EN_CUSTODIA, Objeto::RECLAMADO, Objeto::NO_RECLAMADO]);
            $objeto = Objeto::factory()->create([
                'codigo' => sprintf('HIS-%04d', $i + 1), 'slug' => sprintf('historial-his-%04d', $i + 1),
                'categoria_id' => fake()->randomElement($categorias), 'lugar_id' => fake()->randomElement($lugares),
                'fecha_hallazgo' => $fecha->toDateString(), 'estado' => $estado, 'registrado_por' => $encargado->id,
            ]);
            $dueno = fake()->randomElement($estudiantes);

            if (fake()->boolean(25)) {
                Reclamo::forceCreate(['objeto_id' => $objeto->id, 'user_id' => fake()->randomElement($estudiantes)->id, 'intentos' => 3, 'estado' => Reclamo::RECHAZADO]);
            }
            if (in_array($estado, [Objeto::RECLAMADO, Objeto::DEVUELTO], true)) {
                $reclamo = Reclamo::forceCreate(['objeto_id' => $objeto->id, 'user_id' => $dueno->id, 'intentos' => 1,
                    'estado' => $estado === Objeto::DEVUELTO ? Reclamo::ENTREGADO : Reclamo::APROBADO]);
                if ($estado === Objeto::DEVUELTO) {
                    Entrega::create(['objeto_id' => $objeto->id, 'reclamo_id' => $reclamo->id, 'receptor_id' => $dueno->id,
                        'encargado_id' => $encargado->id, 'codigo_carne' => (string) fake()->numerify('7#######'),
                        'fecha_entrega' => min(now(), $fecha->copy()->addDays(fake()->numberBetween(1, 9))->setTime(fake()->numberBetween(8, 18), 0))]);
                }
            }
            if (fake()->boolean(60)) {
                ReportePerdida::forceCreate(['user_id' => $dueno->id, 'categoria_id' => $objeto->categoria_id, 'lugar_id' => $objeto->lugar_id,
                    'descripcion' => 'Reporte de pérdida de prueba relacionado con ' . $objeto->codigo, 'color' => $objeto->color,
                    'fecha_perdida' => $fecha->copy()->subDays(fake()->numberBetween(0, 2))->toDateString(), 'estado' => ReportePerdida::CERRADO]);
            }
        }
    }
}
