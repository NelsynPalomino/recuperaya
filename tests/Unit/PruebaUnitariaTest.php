<?php

namespace Tests\Unit;

use App\Services\MotorCoincidencias;
use App\Services\Texto;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * PRUEBAS UNITARIAS (examen final – consigna).
 * Cada prueba verifica una sola unidad del código, aislada, sin base de datos ni navegador.
 */
class PruebaUnitariaTest extends TestCase
{
    // ---------- PU-01: MotorCoincidencias::puntaje() ----------

    /** [objeto, reporte, puntaje esperado] — partición de equivalencia y valores límite. */
    public static function casosPuntaje(): array
    {
        $o = ['categoria_id' => 1, 'lugar_id' => 1, 'fecha_hallazgo' => '2026-11-10', 'color' => 'Negro'];
        $r = ['categoria_id' => 1, 'lugar_id' => 1, 'fecha_perdida' => '2026-11-09', 'color' => 'negro'];
        return [
            'PU-01.1 coincidencia total (20+40+30+10)' => [$o, $r, 100],
            'PU-01.2 categoría diferente' => [['categoria_id' => 2] + $o, $r, 0],
            'PU-01.3 lugar distinto: justo en el umbral 60' => [['lugar_id' => 9] + $o, $r, 60],
            'PU-01.4 encontrado 30 días después (límite)' => [['fecha_hallazgo' => '2026-12-09'] + $o, $r, 80],
            'PU-01.5 encontrado 31 días después (límite + 1)' => [['fecha_hallazgo' => '2026-12-10'] + $o, $r, 0],
            'PU-01.6 encontrado 2 días antes de perderse' => [['fecha_hallazgo' => '2026-11-07'] + $o, $r, 0],
            'PU-01.7 encontrado 1 día antes (límite -1)' => [['fecha_hallazgo' => '2026-11-08'] + $o, $r, 100],
            'PU-01.8 encontrado 7 días después (4 a 10)' => [['fecha_hallazgo' => '2026-11-16'] + $o, $r, 90],
            'PU-01.9 reporte sin lugar (15 pts)' => [$o, ['lugar_id' => null] + $r, 75],
            'PU-01.10 color distinto' => [['color' => 'Rojo'] + $o, $r, 90],
        ];
    }

    #[Test] #[DataProvider('casosPuntaje')]
    public function pu01_puntaje_de_coincidencia(array $objeto, array $reporte, int $esperado): void
    {
        $this->assertSame($esperado, MotorCoincidencias::puntaje($objeto, $reporte));
    }

    // ---------- PU-02: Texto::normalizar() ----------

    /** [texto escrito por el estudiante, texto normalizado esperado] */
    public static function casosNormalizar(): array
    {
        return [
            'PU-02.1 mayúsculas y espacios extra' => ['  Un   PERRO ', 'un perro'],
            'PU-02.2 tildes' => ['Transparénte', 'transparente'],
            'PU-02.3 eñe y signos' => ['¡Niño!', 'nino'],
            'PU-02.4 números se conservan' => ['Casio FX-570', 'casio fx 570'],
            'PU-02.5 texto vacío' => ['', ''],
        ];
    }

    #[Test] #[DataProvider('casosNormalizar')]
    public function pu02_normalizacion_de_respuestas(string $entrada, string $esperado): void
    {
        $this->assertSame($esperado, Texto::normalizar($entrada));
    }
}
