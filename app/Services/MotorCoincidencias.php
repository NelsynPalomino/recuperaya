<?php

namespace App\Services;

use App\Models\Coincidencia;
use App\Models\Objeto;
use App\Models\ReportePerdida;

/**
 * Motor de coincidencias (RF-07). Puntaje de 0 a 100:
 *  - Categoría igual: obligatoria (20)        - Lugar igual: 40 (reporte sin lugar: 15)
 *  - Días entre pérdida y hallazgo (-1 a 30): 0-3 días: 30 | 4-10: 20 | 11-30: 10
 *  - Color parecido: 10                        Coincide si el puntaje >= umbral (60).
 */
class MotorCoincidencias
{
    /** Función pura (sin base de datos): ideal para pruebas unitarias. */
    public static function puntaje(array $objeto, array $reporte): int
    {
        if ((int) $objeto['categoria_id'] !== (int) $reporte['categoria_id']) {
            return 0;
        }
        $dias = (int) floor((strtotime((string) $objeto['fecha_hallazgo']) - strtotime((string) $reporte['fecha_perdida'])) / 86400);
        if ($dias < -1 || $dias > 30) {
            return 0;
        }
        $p = 20;
        if (empty($reporte['lugar_id'])) {
            $p += 15;
        } elseif ((int) $reporte['lugar_id'] === (int) $objeto['lugar_id']) {
            $p += 40;
        }
        $d = abs($dias);
        $p += $d <= 3 ? 30 : ($d <= 10 ? 20 : 10);
        if (self::colorParecido($objeto['color'] ?? '', $reporte['color'] ?? '')) {
            $p += 10;
        }
        return min(100, $p);
    }

    public static function colorParecido(string $a, string $b): bool
    {
        $a = Texto::normalizar($a);
        $b = Texto::normalizar($b);
        return $a !== '' && $b !== '' && (str_contains($a, $b) || str_contains($b, $a));
    }

    private static function datos(Objeto|ReportePerdida $m): array
    {
        $d = $m->only(['categoria_id', 'lugar_id', 'color']);
        if ($m instanceof Objeto) {
            $d['fecha_hallazgo'] = $m->fecha_hallazgo->toDateString();
        } else {
            $d['fecha_perdida'] = $m->fecha_perdida->toDateString();
        }
        return $d;
    }

    /** Evento "nuevo objeto registrado". */
    public static function procesarObjeto(Objeto $objeto): int
    {
        $n = 0;
        foreach (ReportePerdida::where('estado', ReportePerdida::ACTIVO)->get() as $reporte) {
            $n += self::guardarSiCoincide($objeto, $reporte) ? 1 : 0;
        }
        return $n;
    }

    /** Evento "nuevo reporte de pérdida". */
    public static function procesarReporte(ReportePerdida $reporte): int
    {
        $n = 0;
        foreach (Objeto::enCustodia()->get() as $objeto) {
            $n += self::guardarSiCoincide($objeto, $reporte) ? 1 : 0;
        }
        return $n;
    }

    private static function guardarSiCoincide(Objeto $objeto, ReportePerdida $reporte): bool
    {
        $p = self::puntaje(self::datos($objeto), self::datos($reporte));
        if ($p < config('recuperaya.umbral_coincidencia')) {
            return false;
        }
        $c = Coincidencia::firstOrCreate(
            ['reporte_perdida_id' => $reporte->id, 'objeto_id' => $objeto->id],
            ['puntaje' => $p]
        );
        if (! $c->wasRecentlyCreated) {
            return false;
        }
        $objeto->loadMissing('categoria');
        Notificador::enviar(
            $reporte->user_id,
            'Posible coincidencia con tu reporte',
            "Encontramos un objeto ({$objeto->codigo} – {$objeto->categoria->nombre}, {$objeto->color}) que podría ser tuyo. Revísalo y, si es tuyo, solicita el reclamo.",
            route('catalogo.show', $objeto)
        );
        return true;
    }
}
