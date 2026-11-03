<?php

namespace App\Http\Controllers;

use App\Models\Entrega;
use App\Models\Objeto;
use App\Models\Reclamo;
use App\Models\ReportePerdida;
use Illuminate\Http\Request;

class InicioController extends Controller
{
    public function __invoke(Request $request)
    {
        Objeto::marcarNoReclamados();
        $u = $request->user();
        $datos = [
            'notificaciones' => $u->notificaciones()->latest()->take(5)->get(),
            'recientes' => Objeto::with(['categoria', 'lugar'])->enCustodia()->latest('fecha_hallazgo')->take(4)->get(),
        ];
        if ($u->esUsuario()) {
            $datos['misReportes'] = $u->reportes()->with('categoria')->latest()->take(3)->get();
            $datos['misReclamos'] = $u->reclamos()->with('objeto')->latest()->take(3)->get();
        } else {
            $datos['custodia'] = Objeto::enCustodia()->count();
            $datos['porEntregar'] = Reclamo::where('estado', Reclamo::APROBADO)->count();
            $datos['devueltosMes'] = Entrega::where('fecha_entrega', '>=', now()->startOfMonth())->count();
            $datos['reportesActivos'] = ReportePerdida::where('estado', ReportePerdida::ACTIVO)->count();
        }
        return view('inicio.index', $datos);
    }
}
