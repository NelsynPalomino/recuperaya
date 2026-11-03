<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Lugar;
use App\Models\Objeto;
use App\Models\Reclamo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** PRUEBAS DE INTEGRACIÓN del CRUD de objetos (RF-03): Form Request, asignación masiva, slug y eliminación. */
class ObjetoTest extends TestCase
{
    use RefreshDatabase;

    private User $encargado;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Categoria::create(['nombre' => 'Celular']);
        Lugar::create(['nombre' => 'Biblioteca']);
        $this->encargado = User::factory()->encargado()->create();
    }

    private function datos(array $cambios = []): array
    {
        return $cambios + [
            'categoria_id' => Categoria::first()->id, 'lugar_id' => Lugar::first()->id,
            'descripcion' => 'Celular negro con case transparente', 'color' => 'Negro',
            'fecha_hallazgo' => now()->toDateString(), 'foto' => UploadedFile::fake()->image('celular.jpg', 800, 600),
            'pregunta1' => '¿Qué fondo tiene?', 'respuesta1' => 'un perro',
            'pregunta2' => '¿De qué color es el case?', 'respuesta2' => 'transparente',
        ];
    }

    #[Test] public function cp12_registro_valido_crea_objeto_con_codigo_slug_y_foto(): void
    {
        $this->actingAs($this->encargado)->post(route('objetos.store'), $this->datos())->assertRedirect(route('objetos.index'));
        $o = Objeto::first();
        $this->assertSame('OBJ-0001', $o->codigo);
        $this->assertSame('celular-negro-obj-0001', $o->slug);
        $this->assertSame(Objeto::EN_CUSTODIA, $o->estado);
        Storage::disk('public')->assertExists($o->foto);
    }

    #[Test] public function cp13_fecha_futura_es_rechazada(): void
    {
        $this->actingAs($this->encargado)->post(route('objetos.store'), $this->datos(['fecha_hallazgo' => now()->addDay()->toDateString()]))
            ->assertSessionHasErrors('fecha_hallazgo');
    }

    #[Test] public function cp14_descripcion_de_9_caracteres_es_rechazada(): void
    {
        $this->actingAs($this->encargado)->post(route('objetos.store'), $this->datos(['descripcion' => '123456789']))->assertSessionHasErrors('descripcion');
    }

    #[Test] public function cp17_preguntas_iguales_son_rechazadas(): void
    {
        $this->actingAs($this->encargado)->post(route('objetos.store'), $this->datos(['pregunta2' => '¿Qué fondo tiene?']))->assertSessionHasErrors('pregunta2');
    }

    #[Test] public function cp19_foto_obligatoria(): void
    {
        $d = $this->datos();
        unset($d['foto']);
        $this->actingAs($this->encargado)->post(route('objetos.store'), $d)->assertSessionHasErrors('foto');
    }

    #[Test] public function cp20_archivo_que_no_es_imagen_es_rechazado(): void
    {
        $this->actingAs($this->encargado)->post(route('objetos.store'), $this->datos(['foto' => UploadedFile::fake()->create('virus.php', 10, 'text/x-php')]))
            ->assertSessionHasErrors('foto');
    }

    #[Test] public function cp21_foto_mayor_a_5_mb_es_rechazada(): void
    {
        $this->actingAs($this->encargado)->post(route('objetos.store'), $this->datos(['foto' => UploadedFile::fake()->image('grande.jpg', 800, 600)->size(5121)]))
            ->assertSessionHasErrors('foto');
    }

    #[Test] public function asignacion_masiva_ignora_el_estado_enviado_en_el_formulario(): void
    {
        $this->actingAs($this->encargado)->post(route('objetos.store'), $this->datos(['estado' => Objeto::DEVUELTO]));
        $this->assertSame(Objeto::EN_CUSTODIA, Objeto::first()->estado);
    }

    #[Test] public function url_amigable_muestra_el_objeto_por_su_slug(): void
    {
        $this->actingAs($this->encargado)->post(route('objetos.store'), $this->datos());
        $this->get('/catalogo/celular-negro-obj-0001')->assertOk()->assertSee('OBJ-0001');
    }

    #[Test] public function actualizar_objeto_con_asignacion_masiva(): void
    {
        $o = Objeto::factory()->create();
        $this->actingAs($this->encargado)->put(route('objetos.update', $o), $this->datos(['color' => 'Azul', 'foto' => null, 'respuesta1' => '', 'respuesta2' => '']))
            ->assertRedirect(route('objetos.index'));
        $this->assertSame('Azul', $o->fresh()->color);
    }

    #[Test] public function eliminar_objeto_sin_reclamos(): void
    {
        $o = Objeto::factory()->create();
        $this->actingAs($this->encargado)->delete(route('objetos.destroy', $o))->assertRedirect(route('objetos.index'));
        $this->assertModelMissing($o);
    }

    #[Test] public function no_se_elimina_un_objeto_con_reclamos(): void
    {
        $o = Objeto::factory()->create();
        Reclamo::create(['objeto_id' => $o->id, 'user_id' => User::factory()->create()->id]);
        $this->actingAs($this->encargado)->delete(route('objetos.destroy', $o))->assertSessionHas('error');
        $this->assertModelExists($o);
    }
}
