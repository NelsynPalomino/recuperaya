<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Lugar;
use App\Models\Objeto;
use App\Models\Reclamo;
use App\Models\User;
use App\Services\ReclamoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * CASO PRÁCTICO DE CAJA BLANCA: ReclamoService::reclamar() (RF-09, RF-10, RN-06).
 *
 * Grafo de flujo: 6 nodos predicado (P) →  V(G) = P + 1 = 7 caminos independientes.
 *   P1 ¿estado ≠ En custodia?           P2 ¿reclamo previo rechazado?
 *   P3 ¿reclamo previo aprobado/entregado? P4 ¿no existe reclamo previo? (operador ??=)
 *   P5 ¿respuestas correctas?           P6 ¿intentos ≥ máximo (2)?
 * Cada prueba recorre uno de los 7 caminos (CB-01 … CB-07).
 */
class CajaBlancaReclamoTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private Objeto $objeto;

    protected function setUp(): void
    {
        parent::setUp();
        Categoria::create(['nombre' => 'Celular']);
        Lugar::create(['nombre' => 'Biblioteca']);
        User::factory()->encargado()->create();
        $this->usuario = User::factory()->create();
        // Respuestas correctas del factory: "marca" y "nada"
        $this->objeto = Objeto::factory()->create(['estado' => Objeto::EN_CUSTODIA]);
    }

    private function reclamoPrevio(string $estado, int $intentos = 1): Reclamo
    {
        return Reclamo::forceCreate(['user_id' => $this->usuario->id, 'objeto_id' => $this->objeto->id, 'estado' => $estado, 'intentos' => $intentos]);
    }

    #[Test] public function cb01_camino_1_objeto_no_disponible(): void
    {
        $this->objeto->update(['estado' => Objeto::DEVUELTO]);
        $this->objeto->estado = Objeto::DEVUELTO;
        $r = ReclamoService::reclamar($this->usuario->id, $this->objeto, 'marca', 'nada');
        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('ya no está disponible', $r['mensaje']);
    }

    #[Test] public function cb02_camino_2_reclamo_previo_rechazado(): void
    {
        $this->reclamoPrevio(Reclamo::RECHAZADO, 2);
        $r = ReclamoService::reclamar($this->usuario->id, $this->objeto, 'marca', 'nada');
        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('Ya usaste tus 2 intentos', $r['mensaje']);
    }

    #[Test] public function cb03_camino_3_reclamo_previo_aprobado(): void
    {
        $this->reclamoPrevio(Reclamo::APROBADO);
        $r = ReclamoService::reclamar($this->usuario->id, $this->objeto, 'marca', 'nada');
        $this->assertSame('aviso', $r['tipo']);
        $this->assertStringContainsString('Ya tienes un reclamo aprobado', $r['mensaje']);
    }

    #[Test] public function cb04_camino_4_sin_reclamo_previo_y_respuestas_correctas(): void
    {
        $r = ReclamoService::reclamar($this->usuario->id, $this->objeto, ' MARCA ', 'Nada');
        $this->assertTrue($r['ok']);
        $this->assertSame(Objeto::RECLAMADO, $this->objeto->fresh()->estado);
        $this->assertSame(1, Reclamo::first()->intentos);
    }

    #[Test] public function cb05_camino_5_respuesta_incorrecta_con_intentos_restantes(): void
    {
        $r = ReclamoService::reclamar($this->usuario->id, $this->objeto, 'otra', 'nada');
        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('Te queda 1 intento', $r['mensaje']);
        $this->assertSame(Reclamo::PENDIENTE, Reclamo::first()->estado);
    }

    #[Test] public function cb06_camino_6_respuesta_incorrecta_agota_los_intentos(): void
    {
        $this->reclamoPrevio(Reclamo::PENDIENTE, 1);
        $r = ReclamoService::reclamar($this->usuario->id, $this->objeto, 'otra', 'otra');
        $this->assertFalse($r['ok']);
        $this->assertSame(Reclamo::RECHAZADO, Reclamo::first()->estado);
        $this->assertSame(Objeto::EN_CUSTODIA, $this->objeto->fresh()->estado);
    }

    #[Test] public function cb07_camino_7_reclamo_pendiente_existente_y_respuestas_correctas(): void
    {
        $this->reclamoPrevio(Reclamo::PENDIENTE, 1);
        $r = ReclamoService::reclamar($this->usuario->id, $this->objeto, 'marca', 'nada');
        $this->assertTrue($r['ok']);
        $this->assertSame(1, Reclamo::count(), 'No debe crear un segundo reclamo');
        $this->assertSame(2, Reclamo::first()->intentos);
    }
}
