<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEntregaRequest;
use App\Models\Entrega;
use App\Models\Reclamo;
use App\Services\ReclamoService;

/** Entregas (RF-11, RN-05). */
class EntregaController extends Controller
{
    public function index()
    {
        return view('entregas.index', [
            'aprobados' => Reclamo::with(['objeto.categoria', 'usuario'])->where('estado', Reclamo::APROBADO)->oldest('updated_at')->get(),
            'recientes' => Entrega::with(['objeto.categoria', 'receptor', 'encargado'])->latest('fecha_entrega')->take(20)->get(),
        ]);
    }

    public function store(StoreEntregaRequest $request)
    {
        $r = ReclamoService::entregar(Reclamo::findOrFail($request->reclamo_id), $request->user()->id, $request->codigo_carne, $request->observacion);
        return redirect()->route('entregas.index')->with($r['tipo'], $r['mensaje']);
    }
}
