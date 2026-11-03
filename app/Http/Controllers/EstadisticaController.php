<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\Objeto;
use App\Services\EstadisticaService;
use Illuminate\Http\Request;

/** Reportes (RF-12): pantalla, impresión/PDF, Excel (CSV) y datos para machine learning. */
class EstadisticaController extends Controller
{
    private function rango(Request $request): array
    {
        $v = $request->validate(['desde' => ['nullable', 'date'], 'hasta' => ['nullable', 'date', 'after_or_equal:desde']],
            ['hasta.after_or_equal' => 'La fecha "hasta" debe ser igual o posterior a la fecha "desde".']);
        return [$v['desde'] ?? now()->startOfYear()->toDateString(), $v['hasta'] ?? now()->toDateString()];
    }

    public function index(Request $request)
    {
        Objeto::marcarNoReclamados();
        [$desde, $hasta] = $this->rango($request);
        return view('estadisticas.index', ['r' => EstadisticaService::resumen($desde, $hasta), 'desde' => $desde, 'hasta' => $hasta]);
    }

    public function imprimir(Request $request)
    {
        [$desde, $hasta] = $this->rango($request);
        return view('estadisticas.imprimir', ['r' => EstadisticaService::resumen($desde, $hasta), 'filas' => EstadisticaService::filasObjetos($desde, $hasta), 'desde' => $desde, 'hasta' => $hasta]);
    }

    public function csv(Request $request)
    {
        [$desde, $hasta] = $this->rango($request);
        Auditoria::registrar('exportar_reporte', "$desde a $hasta");
        return EstadisticaService::csv("recuperaya_objetos_{$desde}_{$hasta}.csv", EstadisticaService::filasObjetos($desde, $hasta));
    }

    public function ml()
    {
        Auditoria::registrar('exportar_dataset_ml');
        return EstadisticaService::csv('recuperaya_dataset_ml.csv', EstadisticaService::datasetML());
    }
}
