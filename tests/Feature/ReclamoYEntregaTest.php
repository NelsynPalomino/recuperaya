<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Lugar;
use App\Models\Notificacion;
use App\Models\Objeto;
use App\Models\Reclamo;
use App\Models\ReportePerdida;
use App\Models\User;
use App\Services\Texto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** PRUEBAS DE INTEGRACIÓN del flujo principal: reporte → coincidencia → reclamo → entrega (RF-05 a RF-11). */
class ReclamoYEntregaTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private User $encargado;
    private Objeto $objeto;

    protected function setUp(): void
    {
        parent::setUp();
        Categoria::create(['nombre' => 'Celular']);
        Lugar::create(['nombre' => 'Biblioteca']);
        $this->usuario = User::factory()->create(['codigo_universitario' => '74000001']);
        $this->encargado = User::factory()->encargado()->create();
        $this->objeto = Objeto::factory()->create([
            'categoria_id' => Categoria::first()->id, 'lugar_id' => Lugar::first()->id, 'color' => 'Negro',
            'fecha_hallazgo' => now()->toDateString(),
            'respuesta1_hash' => password_hash(Texto::normalizar('un perro'), PASSWORD_BCRYPT),
            'respuesta2_hash' => password_hash(Texto::normalizar('transparente'), PASSWORD_BCRYPT),
        ]);
    }

    #[Test] public function cp31_reporte_de_perdida_genera_coincidencia_y_aviso(): void
    {
        $this->actingAs($this->usuario)->post(route('reportes.store'), [
            'categoria_id' => Categoria::first()->id, 'lugar_id' => Lugar::first()->id, 'color' => 'negro',
            'descripcion' => 'Celular negro que dejé en la biblioteca', 'fecha_perdida' => now()->subDay()->toDateString(),
        ])->assertRedirect(route('reportes.index'));
        $this->assertDatabaseCount('coincidencias', 1);
        $this->assertSame(1, Notificacion::where('user_id', $this->usuario->id)->count());
    }

    #[Test] public function reporte_con_fecha_futura_es_rechazado(): void
    {
        $this->actingAs($this->usuario)->post(route('reportes.store'), [
            'categoria_id' => Categoria::first()->id, 'color' => 'negro', 'descripcion' => 'Celular negro perdido', 'fecha_perdida' => now()->addDays(3)->toDateString(),
        ])->assertSessionHasErrors('fecha_perdida');
    }

    #[Test] public function cp33_cp34_dos_intentos_fallidos_rechazan_el_reclamo(): void
    {
        $this->actingAs($this->usuario)->post(route('reclamos.store', $this->objeto), ['respuesta1' => 'gato', 'respuesta2' => 'rojo'])->assertSessionHas('aviso');
        $this->actingAs($this->usuario)->post(route('reclamos.store', $this->objeto), ['respuesta1' => 'loro', 'respuesta2' => 'azul'])->assertSessionHas('error');
        $this->assertSame(Reclamo::RECHAZADO, Reclamo::first()->estado);
        $this->assertSame(Objeto::EN_CUSTODIA, $this->objeto->fresh()->estado);
    }

    #[Test] public function cp35_no_se_permite_un_tercer_intento(): void
    {
        foreach (['a', 'b', 'un perro'] as $r) {
            $this->actingAs($this->usuario)->post(route('reclamos.store', $this->objeto), ['respuesta1' => $r, 'respuesta2' => $r === 'un perro' ? 'transparente' : 'x']);
        }
        $this->assertSame(Objeto::EN_CUSTODIA, $this->objeto->fresh()->estado);
    }

    #[Test] public function cp36_respuestas_correctas_aprueban_el_reclamo(): void
    {
        $this->actingAs($this->usuario)->post(route('reclamos.store', $this->objeto), ['respuesta1' => '  Un PERRO ', 'respuesta2' => 'Transparénte'])
            ->assertRedirect(route('reclamos.mis'));
        $this->assertSame(Reclamo::APROBADO, Reclamo::first()->estado);
        $this->assertSame(Objeto::RECLAMADO, $this->objeto->fresh()->estado);
    }

    #[Test] public function reclamo_con_respuestas_vacias_es_rechazado_por_el_form_request(): void
    {
        $this->actingAs($this->usuario)->post(route('reclamos.store', $this->objeto), ['respuesta1' => '', 'respuesta2' => ''])
            ->assertSessionHasErrors(['respuesta1', 'respuesta2']);
    }

    private function aprobar(): Reclamo
    {
        $this->actingAs($this->usuario)->post(route('reclamos.store', $this->objeto), ['respuesta1' => 'un perro', 'respuesta2' => 'transparente']);
        return Reclamo::first();
    }

    #[Test] public function cp38_no_se_entrega_sin_verificar_el_carne(): void
    {
        $r = $this->aprobar();
        $this->actingAs($this->encargado)->post(route('entregas.store'), ['reclamo_id' => $r->id, 'codigo_carne' => '74000001'])
            ->assertSessionHasErrors('carne_verificado');
    }

    #[Test] public function cp39_codigo_de_carne_invalido_es_rechazado(): void
    {
        $r = $this->aprobar();
        $this->actingAs($this->encargado)->post(route('entregas.store'), ['reclamo_id' => $r->id, 'codigo_carne' => '12', 'carne_verificado' => '1'])
            ->assertSessionHasErrors('codigo_carne');
    }

    #[Test] public function cp40_entrega_valida_marca_el_objeto_como_devuelto(): void
    {
        $this->actingAs($this->usuario)->post(route('reportes.store'), [
            'categoria_id' => Categoria::first()->id, 'lugar_id' => Lugar::first()->id, 'color' => 'negro',
            'descripcion' => 'Celular negro que dejé en la biblioteca', 'fecha_perdida' => now()->toDateString(),
        ]);
        $r = $this->aprobar();
        $this->actingAs($this->encargado)->post(route('entregas.store'), ['reclamo_id' => $r->id, 'codigo_carne' => '74000001', 'carne_verificado' => '1'])
            ->assertSessionHas('exito');
        $this->assertSame(Objeto::DEVUELTO, $this->objeto->fresh()->estado);
        $this->assertSame(ReportePerdida::RESUELTO, ReportePerdida::first()->estado);
    }

    #[Test] public function cp41_no_se_entrega_dos_veces(): void
    {
        $r = $this->aprobar();
        $this->actingAs($this->encargado)->post(route('entregas.store'), ['reclamo_id' => $r->id, 'codigo_carne' => '74000001', 'carne_verificado' => '1']);
        $this->actingAs($this->encargado)->post(route('entregas.store'), ['reclamo_id' => $r->id, 'codigo_carne' => '74000001', 'carne_verificado' => '1'])
            ->assertSessionHasErrors('reclamo_id');
        $this->assertDatabaseCount('entregas', 1);
    }
}
