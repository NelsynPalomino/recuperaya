<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Lugar;
use App\Models\Objeto;
use App\Models\User;
use App\Services\EstadisticaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** PRUEBAS DEL MÓDULO DE REPORTES (RF-12): indicadores calculados desde los registros y filtros de consulta. */
class ReporteEstadisticoTest extends TestCase
{
    use RefreshDatabase;

    private Categoria $celular;
    private Categoria $llaves;

    protected function setUp(): void
    {
        parent::setUp();
        $this->celular = Categoria::create(['nombre' => 'Celular']);
        $this->llaves = Categoria::create(['nombre' => 'Llaves']);
        Lugar::create(['nombre' => 'Biblioteca']);
        $hoy = now()->toDateString();
        Objeto::factory()->count(3)->create(['categoria_id' => $this->celular->id, 'estado' => Objeto::EN_CUSTODIA, 'fecha_hallazgo' => $hoy]);
        Objeto::factory()->create(['categoria_id' => $this->celular->id, 'estado' => Objeto::DEVUELTO, 'fecha_hallazgo' => $hoy]);
        Objeto::factory()->count(2)->create(['categoria_id' => $this->llaves->id, 'estado' => Objeto::DEVUELTO, 'fecha_hallazgo' => $hoy]);
    }

    private function rango(): array
    {
        return [now()->startOfYear()->toDateString(), now()->toDateString()];
    }

    #[Test] public function cp46_indicadores_se_calculan_desde_los_registros(): void
    {
        $r = EstadisticaService::resumen(...$this->rango());
        $this->assertSame(6, $r['total']);
        $this->assertSame(3, $r['devueltos']);
        $this->assertSame(3, $r['en_custodia']);
        $this->assertSame(50.0, (float) $r['tasa_recuperacion']);
    }

    #[Test] public function cp47_filtro_por_categoria(): void
    {
        $r = EstadisticaService::resumen(...[...$this->rango(), $this->llaves->id]);
        $this->assertSame(2, $r['total']);
        $this->assertSame(100.0, (float) $r['tasa_recuperacion']);
    }

    #[Test] public function cp48_filtro_por_estado(): void
    {
        $r = EstadisticaService::resumen(...[...$this->rango(), null, Objeto::EN_CUSTODIA]);
        $this->assertSame(3, $r['total']);
        $this->assertSame(0, $r['devueltos']);
    }

    #[Test] public function cp49_estado_invalido_es_rechazado_en_la_pantalla(): void
    {
        $this->actingAs(User::factory()->encargado()->create())
            ->get(route('estadisticas.index', ['estado' => 'Inventado']))
            ->assertSessionHasErrors('estado');
    }

    #[Test] public function cp50_pantalla_de_reportes_muestra_indicadores_filtrados(): void
    {
        $this->actingAs(User::factory()->encargado()->create())
            ->get(route('estadisticas.index', ['categoria' => $this->celular->id]))
            ->assertOk()->assertSee('Objetos registrados')->assertSee('Objetos registrados por mes')->assertViewHas('r', fn ($r) => $r['total'] === 4);
    }
}
