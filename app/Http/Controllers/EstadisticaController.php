<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\Categoria;
use App\Models\Objeto;
use App\Services\EstadisticaService;
use Illuminate\Http\Request;

/** Reportes (RF-12): pantalla, impresión/PDF, Excel (CSV) y datos para machine learning. */
class EstadisticaController extends Controller
{
    /** Filtros del reporte: [desde, hasta, categoría, estado]. */
    private function rango(Request $request): array
    {
        $v = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'categoria' => ['nullable', 'integer', 'exists:categorias,id'],
            'estado' => ['nullable', 'in:' . implode(',', Objeto::ESTADOS)],
        ], ['hasta.after_or_equal' => 'La fecha "hasta" debe ser igual o posterior a la fecha "desde".']);
        return [$v['desde'] ?? now()->startOfYear()->toDateString(), $v['hasta'] ?? now()->toDateString(),
            isset($v['categoria']) ? (int) $v['categoria'] : null, $v['estado'] ?? null];
    }

    private function datosVista(array $filtros): array
    {
        [$desde, $hasta, $categoria, $estado] = $filtros;
        return ['desde' => $desde, 'hasta' => $hasta, 'categoria' => $categoria, 'estado' => $estado,
            'filtros' => array_filter(['desde' => $desde, 'hasta' => $hasta, 'categoria' => $categoria, 'estado' => $estado])];
    }

    public function index(Request $request)
    {
        Objeto::marcarNoReclamados();
        $f = $this->rango($request);
        return view('estadisticas.index', ['r' => EstadisticaService::resumen(...$f), 'categorias' => Categoria::orderBy('nombre')->get()] + $this->datosVista($f));
    }

    public function imprimir(Request $request)
    {
        $f = $this->rango($request);
        return view('estadisticas.imprimir', ['r' => EstadisticaService::resumen(...$f), 'filas' => EstadisticaService::filasObjetos(...$f)] + $this->datosVista($f));
    }

    public function csv(Request $request)
    {
        $f = $this->rango($request);
        [$desde, $hasta] = $f;
        Auditoria::registrar('exportar_reporte', "$desde a $hasta");
        return EstadisticaService::csv("recuperaya_objetos_{$desde}_{$hasta}.csv", EstadisticaService::filasObjetos(...$f));
    }

    public function ml()
    {
        Auditoria::registrar('exportar_dataset_ml');
        return EstadisticaService::csv('recuperaya_dataset_ml.csv', EstadisticaService::datasetML());
    }
}
