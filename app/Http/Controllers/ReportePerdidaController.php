<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportePerdidaRequest;
use App\Models\Auditoria;
use App\Models\Categoria;
use App\Models\Lugar;
use App\Models\ReportePerdida;
use App\Services\MotorCoincidencias;
use Illuminate\Http\Request;

/** Reportes de pérdida del usuario (RF-05) con Route::resource (index, create, store, destroy). */
class ReportePerdidaController extends Controller
{
    public function index(Request $request)
    {
        $reportes = $request->user()->reportes()->with(['categoria', 'lugar', 'objetosCoincidentes.categoria', 'objetosCoincidentes.lugar'])->latest()->get();
        return view('reportes.index', ['reportes' => $reportes]);
    }

    public function create()
    {
        return view('reportes.create', ['categorias' => Categoria::activos()->get(), 'lugares' => Lugar::activos()->get()]);
    }

    public function store(StoreReportePerdidaRequest $request)
    {
        // Asignación masiva a través de la relación: el user_id lo pone Laravel.
        $reporte = $request->user()->reportes()->create($request->validated());
        Auditoria::registrar('reporte_perdida', "Reporte {$reporte->id}");
        $n = MotorCoincidencias::procesarReporte($reporte);
        return redirect()->route('reportes.index')->with($n ? 'exito' : 'info', $n
            ? "Reporte registrado. ¡Encontramos $n posible(s) coincidencia(s)! Revísalas abajo."
            : 'Reporte registrado. Te avisaremos cuando llegue un objeto parecido.');
    }

    /** "Eliminar" un reporte = cerrarlo (se conserva para las estadísticas). */
    public function destroy(Request $request, ReportePerdida $reporte)
    {
        abort_unless($reporte->user_id === $request->user()->id, 403);
        if ($reporte->estado === ReportePerdida::ACTIVO) {
            $reporte->estado = ReportePerdida::CERRADO;
            $reporte->save();
        }
        return redirect()->route('reportes.index')->with('exito', 'Reporte cerrado. Ya no recibirás avisos de coincidencias para él.');
    }
}
