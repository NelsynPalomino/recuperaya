<?php

namespace App\Services;

use App\Models\Objeto;
use App\Models\Reclamo;
use App\Models\ReportePerdida;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Reportes (RF-12) y exportación de datos para machine learning. */
class EstadisticaService
{
    /** Columna agregada usada en todos los conteos agrupados. */
    private const TOTAL = 'COUNT(*) as total';

    /** Consulta base con los filtros del reporte: rango de fechas, categoría y estado. */
    private static function consulta(string $desde, string $hasta, ?int $categoriaId = null, ?string $estado = null)
    {
        // whereDate: compara solo la fecha (en SQLite la columna se guarda como "Y-m-d 00:00:00").
        return Objeto::query()->whereDate('objetos.fecha_hallazgo', '>=', $desde)->whereDate('objetos.fecha_hallazgo', '<=', $hasta)
            ->when($categoriaId, fn ($q, $v) => $q->where('objetos.categoria_id', $v))
            ->when(in_array($estado, Objeto::ESTADOS, true) ? $estado : null, fn ($q, $v) => $q->where('objetos.estado', $v));
    }

    public static function resumen(string $desde, string $hasta, ?int $categoriaId = null, ?string $estado = null): array
    {
        $base = fn () => self::consulta($desde, $hasta, $categoriaId, $estado);
        $porEstado = $base()->select('estado', DB::raw(self::TOTAL))->groupBy('estado')->pluck('total', 'estado')->map(fn ($v) => (int) $v)->all();
        $total = array_sum($porEstado);
        $devueltos = $porEstado[Objeto::DEVUELTO] ?? 0;
        $dias = $base()->join('entregas', 'entregas.objeto_id', '=', 'objetos.id')
            ->get(['objetos.fecha_hallazgo', 'entregas.fecha_entrega'])
            ->map(fn ($r) => max(0, $r->fecha_hallazgo->diffInDays(\Carbon\Carbon::parse($r->fecha_entrega)->startOfDay())));

        return [
            'total' => $total,
            'por_estado' => $porEstado,
            'tasa_recuperacion' => $total ? round($devueltos * 100 / $total, 1) : 0,
            'dias_promedio' => $dias->count() ? round($dias->avg(), 1) : null,
            'reportes' => ReportePerdida::whereDate('fecha_perdida', '>=', $desde)->whereDate('fecha_perdida', '<=', $hasta)
                ->when($categoriaId, fn ($q, $v) => $q->where('categoria_id', $v))->count(),
            'reclamos' => Reclamo::whereHas('objeto', fn ($q) => $q->whereIn('objetos.id', $base()->select('objetos.id')))->count(),
            'reclamos_rechazados' => Reclamo::where('estado', Reclamo::RECHAZADO)
                ->whereHas('objeto', fn ($q) => $q->whereIn('objetos.id', $base()->select('objetos.id')))->count(),
            'devueltos' => $devueltos,
            'en_custodia' => $porEstado[Objeto::EN_CUSTODIA] ?? 0,
            'por_mes' => $base()->get(['objetos.fecha_hallazgo'])
                ->groupBy(fn ($o) => $o->fecha_hallazgo->format('Y-m'))->map->count()->sortKeys()
                ->map(fn ($total, $mes) => (object) ['nombre' => $mes, 'total' => $total])->values(),
            'por_categoria' => $base()->join('categorias', 'categorias.id', '=', 'objetos.categoria_id')
                ->select('categorias.nombre', DB::raw(self::TOTAL))->groupBy('categorias.nombre')->orderByDesc('total')->orderBy('categorias.nombre')->get(),
            'por_lugar' => $base()->join('lugares', 'lugares.id', '=', 'objetos.lugar_id')
                ->select('lugares.nombre', DB::raw(self::TOTAL))->groupBy('lugares.nombre')->orderByDesc('total')->orderBy('lugares.nombre')->get(),
        ];
    }

    public static function filasObjetos(string $desde, string $hasta, ?int $categoriaId = null, ?string $estado = null): array
    {
        $filas = [['Código', 'Categoría', 'Lugar', 'Descripción', 'Color', 'Fecha de hallazgo', 'Estado', 'Fecha de entrega']];
        $objetos = self::consulta($desde, $hasta, $categoriaId, $estado)->with(['categoria', 'lugar', 'entrega'])->orderBy('fecha_hallazgo')->get();
        foreach ($objetos as $o) {
            $filas[] = [$o->codigo, $o->categoria->nombre, $o->lugar->nombre, $o->descripcion, $o->color,
                $o->fecha_hallazgo->format('d/m/Y'), $o->estado, $o->entrega?->fecha_entrega?->format('d/m/Y H:i') ?? ''];
        }
        return $filas;
    }

    /** Dataset para el Laboratorio 01: variable objetivo recuperado_30d. */
    public static function datasetML(): array
    {
        $filas = [['id', 'categoria', 'lugar', 'dia_semana', 'franja_horaria', 'tiene_foto', 'long_descripcion', 'hubo_reporte_perdida', 'num_coincidencias', 'recuperado_30d']];
        $dias = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
        $objetos = Objeto::with(['categoria', 'lugar', 'entrega'])->withCount('reclamos')->orderBy('id')->get();
        foreach ($objetos as $o) {
            $h = (int) $o->created_at->format('G');
            $hubo = ReportePerdida::where('categoria_id', $o->categoria_id)->where('created_at', '<=', $o->created_at)->exists() ? 1 : 0;
            $coinc = DB::table('coincidencias')->where('objeto_id', $o->id)->count();
            $rec = $o->entrega && $o->fecha_hallazgo->diffInDays($o->entrega->fecha_entrega->copy()->startOfDay()) <= 30 ? 1 : 0;
            $filas[] = [$o->id, $o->categoria->nombre, $o->lugar->nombre, $dias[(int) $o->created_at->format('w')],
                self::franjaHoraria($h), $o->foto ? 1 : 0,
                str_word_count(Texto::normalizar($o->descripcion)), $hubo, $coinc, $rec];
        }
        return $filas;
    }

    /** Franja del día en que se registró el objeto (variable del dataset de ML). */
    private static function franjaHoraria(int $hora): string
    {
        return match (true) {
            $hora < 12 => 'manana',
            $hora < 18 => 'tarde',
            default => 'noche',
        };
    }

    public static function csv(string $nombre, array $filas): StreamedResponse
    {
        return response()->streamDownload(function () use ($filas) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            foreach ($filas as $f) {
                fputcsv($out, $f, ';');
            }
            fclose($out);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
