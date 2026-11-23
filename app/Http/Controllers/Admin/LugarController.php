<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCatalogoRequest;
use App\Models\Auditoria;
use App\Models\Objeto;
use App\Models\ReportePerdida;
use App\Models\Lugar;

/** CRUD completo de lugares con Route::resource (RF-13). */
class LugarController extends Controller
{
    public function index()
    {
        return view('admin.lugares.index', ['items' => Lugar::orderBy('nombre')->get()]);
    }

    public function create()
    {
        return view('admin.lugares.form', ['item' => new Lugar(['activo' => true])]);
    }

    public function store(StoreCatalogoRequest $request)
    {
        $item = Lugar::create($request->validated());   // asignación masiva con $guarded = []
        Auditoria::registrar('crear_lugar', $item->nombre);
        return redirect()->route('lugares.index')->with('exito', $this->mensaje($item->nombre, 'creado'));
    }

    public function edit(Lugar $lugar)
    {
        return view('admin.lugares.form', ['item' => $lugar]);
    }

    public function update(StoreCatalogoRequest $request, Lugar $lugar)
    {
        $lugar->update($request->validated());
        return redirect()->route('lugares.index')->with('exito', $this->mensaje($lugar->nombre, 'actualizado'));
    }

    /** Eliminar registro: solo si no está en uso; si está en uso, se sugiere desactivarlo. */
    public function destroy(Lugar $lugar)
    {
        $enUso = Objeto::where('lugar_id', $lugar->id)->exists() || ReportePerdida::where('lugar_id', $lugar->id)->exists();
        if ($enUso) {
            return back()->with('error', 'No se puede eliminar "' . $lugar->nombre . '" porque ya se usa en objetos o reportes. Puedes desactivarlo(a).');
        }
        $nombre = $lugar->nombre;
        $lugar->delete();
        Auditoria::registrar('eliminar_lugar', $nombre);
        return redirect()->route('lugares.index')->with('exito', $this->mensaje($nombre, 'eliminado'));
    }

    /** Mensaje de confirmación de las acciones del CRUD. */
    private function mensaje(string $nombre, string $accion): string
    {
        return sprintf('Lugar "%s" %s(a).', $nombre, $accion);
    }
}
