<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Lugar;
use App\Models\Objeto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pruebas complementarias agregadas tras medir la cobertura con PCOV:
 * acceso con cuenta bloqueada/desactivada (RNF-04), CRUD de catálogos (RF-13),
 * exportaciones del módulo de reportes (RF-12: PDF, CSV y dataset de ML) y usuarios y roles (RF-02).
 */
class AdministracionYExportacionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    // ---------- Acceso (AuthController::motivoDeRechazo) ----------

    #[Test] public function cp51_cuenta_bloqueada_no_puede_ingresar(): void
    {
        $u = User::factory()->create(['password' => Hash::make('Clave2026*')]);
        $u->forceFill(['bloqueado_hasta' => now()->addMinutes(10)])->save();
        $this->post(route('login.store'), ['email' => $u->email, 'password' => 'Clave2026*'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Cuenta bloqueada temporalmente'));
        $this->assertGuest();
    }

    #[Test] public function cp52_cuenta_desactivada_no_puede_ingresar(): void
    {
        $u = User::factory()->create(['password' => Hash::make('Clave2026*')]);
        $u->forceFill(['activo' => false])->save();
        $this->post(route('login.store'), ['email' => $u->email, 'password' => 'Clave2026*'])
            ->assertSessionHas('error', 'Tu cuenta está desactivada. Comunícate con el administrador.');
        $this->assertGuest();
    }

    // ---------- CRUD de categorías y lugares ----------

    #[Test] public function cp53_crud_de_categorias(): void
    {
        $this->actingAs($this->admin)->post(route('categorias.store'), ['nombre' => 'Paraguas', 'activo' => 1])
            ->assertSessionHas('exito', 'Categoría "Paraguas" creado(a).');
        $c = Categoria::where('nombre', 'Paraguas')->firstOrFail();
        $this->put(route('categorias.update', $c), ['nombre' => 'Paraguas y sombrillas', 'activo' => 1])
            ->assertSessionHas('exito', 'Categoría "Paraguas y sombrillas" actualizado(a).');
        $this->delete(route('categorias.destroy', $c))
            ->assertSessionHas('exito', 'Categoría "Paraguas y sombrillas" eliminado(a).');
        $this->assertModelMissing($c);
    }

    #[Test] public function cp54_crud_de_lugares(): void
    {
        $this->actingAs($this->admin)->post(route('lugares.store'), ['nombre' => 'Cafetería central', 'activo' => 1])
            ->assertSessionHas('exito', 'Lugar "Cafetería central" creado(a).');
        $l = Lugar::where('nombre', 'Cafetería central')->firstOrFail();
        $this->put(route('lugares.update', $l), ['nombre' => 'Cafetería del pabellón A', 'activo' => 1])
            ->assertSessionHas('exito', 'Lugar "Cafetería del pabellón A" actualizado(a).');
        $this->delete(route('lugares.destroy', $l))
            ->assertSessionHas('exito', 'Lugar "Cafetería del pabellón A" eliminado(a).');
        $this->assertModelMissing($l);
    }

    #[Test] public function cp55_categoria_en_uso_no_se_elimina(): void
    {
        $o = Objeto::factory()->create();
        $this->actingAs($this->admin)->delete(route('categorias.destroy', $o->categoria))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'ya se usa en objetos o reportes'));
        $this->assertModelExists($o->categoria);
    }

    // ---------- Exportaciones del módulo de reportes ----------

    private function conObjetos(): Categoria
    {
        $c = Categoria::create(['nombre' => 'Celular']);
        Lugar::create(['nombre' => 'Biblioteca']);
        Objeto::factory()->count(2)->create(['categoria_id' => $c->id, 'fecha_hallazgo' => now()->toDateString()]);
        return $c;
    }

    #[Test] public function cp56_vista_imprimible_respeta_los_filtros(): void
    {
        $c = $this->conObjetos();
        $this->actingAs($this->admin)->get(route('estadisticas.imprimir', ['categoria' => $c->id, 'estado' => Objeto::EN_CUSTODIA]))
            ->assertOk()->assertViewHas('filas', fn ($f) => count($f) === 3); // encabezado + 2 objetos
    }

    #[Test] public function cp57_exportacion_csv_con_filtros(): void
    {
        $c = $this->conObjetos();
        $r = $this->actingAs($this->admin)->get(route('estadisticas.csv', ['categoria' => $c->id]));
        $r->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $contenido = $r->streamedContent();
        $this->assertStringContainsString('Código;Categoría;Lugar', $contenido);
        $this->assertSame(3, substr_count(trim($contenido), "\n") + 1);
    }

    #[Test] public function cp58_dataset_de_machine_learning(): void
    {
        $this->conObjetos();
        $contenido = $this->actingAs($this->admin)->get(route('estadisticas.ml'))->assertOk()->streamedContent();
        $this->assertStringContainsString('franja_horaria', $contenido);
        $this->assertMatchesRegularExpression('/;(manana|tarde|noche);/', $contenido);
    }

    // ---------- Usuarios y roles (Admin\UsuarioController) ----------

    #[Test] public function cp59_administrador_cambia_rol_y_desactiva_cuenta(): void
    {
        $u = User::factory()->create();
        $this->actingAs($this->admin)->put(route('usuarios.update', $u), ['rol' => User::ENCARGADO, 'activo' => 0])
            ->assertRedirect(route('usuarios.index'))->assertSessionHas('exito');
        $u->refresh();
        $this->assertSame(User::ENCARGADO, $u->rol);
        $this->assertFalse((bool) $u->activo);
        $this->assertDatabaseHas('auditorias', ['accion' => 'cambio_rol']);
    }

    #[Test] public function cp60_administrador_no_puede_quitarse_su_propio_rol(): void
    {
        $this->actingAs($this->admin)->put(route('usuarios.update', $this->admin), ['rol' => User::USUARIO, 'activo' => 1])
            ->assertSessionHas('error', 'No puedes quitarte el rol de administrador ni desactivar tu propia cuenta.');
        $this->assertSame(User::ADMIN, $this->admin->refresh()->rol);
    }

    #[Test] public function cp61_encargado_no_puede_gestionar_usuarios(): void
    {
        $encargado = User::factory()->encargado()->create();
        $this->actingAs($encargado)->get(route('usuarios.index'))->assertForbidden();
        $this->actingAs($encargado)->put(route('usuarios.update', $encargado), ['rol' => User::ADMIN, 'activo' => 1])->assertForbidden();
        $this->assertSame(User::ENCARGADO, $encargado->refresh()->rol);
    }
}
