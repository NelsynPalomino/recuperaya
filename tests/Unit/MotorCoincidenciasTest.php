<?php

namespace Tests\Unit;

use App\Services\MotorCoincidencias;
use App\Services\Texto;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * PRUEBAS UNITARIAS: verifican el motor de coincidencias (RF-07) de forma aislada,
 * sin base de datos ni navegador.
 */
class MotorCoincidenciasTest extends TestCase
{
    private array $objeto = ['categoria_id' => 1, 'lugar_id' => 1, 'fecha_hallazgo' => '2026-10-15', 'color' => 'Negro'];
    private array $reporte = ['categoria_id' => 1, 'lugar_id' => 1, 'fecha_perdida' => '2026-10-14', 'color' => 'negro'];

    #[Test] public function cp23_coincidencia_total_suma_100_puntos(): void
    {
        $this->assertSame(100, MotorCoincidencias::puntaje($this->objeto, $this->reporte));
    }

    #[Test] public function cp24_categoria_diferente_da_cero(): void
    {
        $this->assertSame(0, MotorCoincidencias::puntaje(['categoria_id' => 2] + $this->objeto, $this->reporte));
    }

    #[Test] public function cp25_objeto_encontrado_dos_dias_antes_de_la_perdida_da_cero(): void
    {
        $this->assertSame(0, MotorCoincidencias::puntaje(['fecha_hallazgo' => '2026-10-12'] + $this->objeto, $this->reporte));
    }

    #[Test] public function cp26_valor_limite_31_dias_despues_da_cero(): void
    {
        $this->assertSame(0, MotorCoincidencias::puntaje(['fecha_hallazgo' => '2026-11-14'] + $this->objeto, $this->reporte));
    }

    #[Test] public function cp27_valor_limite_30_dias_despues_si_puntua(): void
    {
        $this->assertSame(80, MotorCoincidencias::puntaje(['fecha_hallazgo' => '2026-11-13'] + $this->objeto, $this->reporte));
    }

    #[Test] public function cp28_lugar_distinto_queda_justo_en_el_umbral(): void
    {
        $this->assertSame(60, MotorCoincidencias::puntaje(['lugar_id' => 5] + $this->objeto, $this->reporte));
    }

    #[Test] public function cp29_reporte_sin_lugar_puede_coincidir(): void
    {
        $this->assertGreaterThanOrEqual(60, MotorCoincidencias::puntaje($this->objeto, ['lugar_id' => null] + $this->reporte));
    }

    #[Test] public function cp30_color_parecido_ignora_tildes_y_mayusculas(): void
    {
        $this->assertTrue(MotorCoincidencias::colorParecido('Azul marino', 'AZUL'));
        $this->assertFalse(MotorCoincidencias::colorParecido('Rojo', 'Verde'));
    }

    #[Test] public function cp32_normaliza_respuestas_sin_tildes_ni_espacios(): void
    {
        $this->assertSame('un perro', Texto::normalizar('  Un   PERRO '));
        $this->assertSame('transparente', Texto::normalizar('Transparénte'));
    }
}
