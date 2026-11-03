<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Lugar;
use App\Models\Objeto;
use App\Models\Reclamo;
use Illuminate\Http\Request;

/** Catálogo de objetos en custodia (RF-04, RF-06). */
class CatalogoController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'categoria' => ['nullable', 'integer'],
            'lugar' => ['nullable', 'integer'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);
        $objetos = Objeto::with(['categoria', 'lugar'])->enCustodia()
            ->when($f['q'] ?? null, function ($q, $texto) {
                $like = '%' . mb_strtolower($texto) . '%';
                $q->where(fn ($w) => $w->whereRaw('LOWER(descripcion) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(color) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(codigo) LIKE ?', [$like])
                    ->orWhereHas('categoria', fn ($c) => $c->whereRaw('LOWER(nombre) LIKE ?', [$like])));
            })
            ->when($f['categoria'] ?? null, fn ($q, $v) => $q->where('categoria_id', $v))
            ->when($f['lugar'] ?? null, fn ($q, $v) => $q->where('lugar_id', $v))
            ->when($f['desde'] ?? null, fn ($q, $v) => $q->whereDate('fecha_hallazgo', '>=', $v))
            ->when($f['hasta'] ?? null, fn ($q, $v) => $q->whereDate('fecha_hallazgo', '<=', $v))
            ->latest('fecha_hallazgo')->paginate(12)->withQueryString();

        return view('catalogo.index', [
            'objetos' => $objetos,
            'filtros' => $f + ['q' => '', 'categoria' => '', 'lugar' => '', 'desde' => '', 'hasta' => ''],
            'categorias' => Categoria::activos()->get(),
            'lugares' => Lugar::activos()->get(),
        ]);
    }

    /** Detalle con URL amigable: /catalogo/{slug} */
    public function show(Request $request, Objeto $objeto)
    {
        $u = $request->user();
        $reclamo = $u->esUsuario() ? Reclamo::where('user_id', $u->id)->where('objeto_id', $objeto->id)->latest('id')->first() : null;
        abort_if($u->esUsuario() && $objeto->estado !== Objeto::EN_CUSTODIA && ! $reclamo, 404);
        $objeto->load(['categoria', 'lugar', 'entrega.receptor']);
        return view('catalogo.show', ['o' => $objeto, 'reclamo' => $reclamo]);
    }
}
