<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReclamoRequest;
use App\Models\Objeto;
use App\Services\ReclamoService;
use Illuminate\Http\Request;

/** Reclamos del usuario (RF-09, RF-10). */
class ReclamoController extends Controller
{
    public function store(StoreReclamoRequest $request, Objeto $objeto)
    {
        $r = ReclamoService::reclamar($request->user()->id, $objeto, $request->respuesta1, $request->respuesta2);
        return $r['ok']
            ? redirect()->route('reclamos.mis')->with($r['tipo'], $r['mensaje'])
            : redirect()->route('catalogo.show', $objeto)->with($r['tipo'], $r['mensaje']);
    }

    public function mis(Request $request)
    {
        return view('reclamos.mis', ['reclamos' => $request->user()->reclamos()->with('objeto.categoria', 'objeto.lugar')->latest('updated_at')->get()]);
    }
}
