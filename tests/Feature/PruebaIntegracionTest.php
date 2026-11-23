<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Categoria;
use App\Models\Lugar;
use App\Models\Notificacion;
use App\Models\Objeto;
use App\Models\Reclamo;
use App\Models\User;
use App\Services\Texto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * PRUEBA DE INTEGRACIÓN (examen final – consigna).
 * PI-01: el reclamo de un objeto recorre juntos StoreReclamoRequest -> ReclamoController ->
 * ReclamoService -> Texto -> modelos Reclamo y Objeto -> Notificador -> Auditoria -> base de datos.
 */
class PruebaIntegracionTest extends TestCase
{
    use RefreshDatabase;

    #[Test] public function pi01_reclamo_con_respuestas_correctas_integra_todas_las_capas(): void
    {
        // Datos de prueba
        Categoria::create(['nombre' => 'Celular']);
        Lugar::create(['nombre' => 'Biblioteca']);
        $estudiante = User::factory()->create();
        $encargado = User::factory()->encargado()->create();
        $objeto = Objeto::factory()->create([
            'respuesta1_hash' => password_hash(Texto::normalizar('un perro'), PASSWORD_BCRYPT),
            'respuesta2_hash' => password_hash(Texto::normalizar('transparente'), PASSWORD_BCRYPT),
        ]);

        // Acción: el estudiante envía el formulario de reclamo (con mayúsculas y tildes)
        $respuesta = $this->actingAs($estudiante)->post(route('reclamos.store', $objeto),
            ['respuesta1' => '  Un PERRO ', 'respuesta2' => 'Transparénte']);

        // Verificaciones en cada capa
        $respuesta->assertRedirect(route('reclamos.mis'));                                  // controlador
        $respuesta->assertSessionHas('exito');                                               // mensaje al usuario
        $reclamo = Reclamo::where('user_id', $estudiante->id)->firstOrFail();
        $this->assertSame(Reclamo::APROBADO, $reclamo->estado);                              // servicio
        $this->assertSame(1, $reclamo->intentos);
        $this->assertSame(Objeto::RECLAMADO, $objeto->fresh()->estado);                      // modelo Objeto
        $this->assertTrue(Notificacion::where('user_id', $estudiante->id)->where('titulo', 'Reclamo aprobado')->exists());  // aviso al estudiante
        $this->assertTrue(Notificacion::where('user_id', $encargado->id)->exists());        // aviso al encargado
        $this->assertTrue(Auditoria::where('accion', 'reclamo_aprobado')->exists());         // bitácora
    }
}
