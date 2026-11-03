<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCatalogoRequest;
use App\Models\Auditoria;
use App\Models\Objeto;
use App\Models\ReportePerdida;
use App\Models\Categoria;

/** CRUD completo de categorias con Route::resource (RF-13). */
class CategoriaController extends Controller
{
    public function index()
    {
        return view('admin.categorias.index', ['items' => Categoria::orderBy('nombre')->get()]);
    }

    public function create()
    {
        return view('admin.categorias.form', ['item' => new Categoria(['activo' => true])]);
    }

    public function store(StoreCatalogoRequest $request)
    {
        $item = Categoria::create($request->validated());   // asignación masiva con $guarded = []
        Auditoria::registrar('crear_categoria', $item->nombre);
        return redirect()->route('categorias.index')->with('exito', 'Categoría "' . $item->nombre . '" creado(a).');
    }

    public function edit(Categoria $categoria)
    {
        return view('admin.categorias.form', ['item' => $categoria]);
    }

    public function update(StoreCatalogoRequest $request, Categoria $categoria)
    {
        $categoria->update($request->validated());
        return redirect()->route('categorias.index')->with('exito', 'Categoría "' . $categoria->nombre . '" actualizado(a).');
    }

    /** Eliminar registro: solo si no está en uso; si está en uso, se sugiere desactivarlo. */
    public function destroy(Categoria $categoria)
    {
        $enUso = Objeto::where('categoria_id', $categoria->id)->exists() || ReportePerdida::where('categoria_id', $categoria->id)->exists();
        if ($enUso) {
            return back()->with('error', 'No se puede eliminar "' . $categoria->nombre . '" porque ya se usa en objetos o reportes. Puedes desactivarlo(a).');
        }
        $nombre = $categoria->nombre;
        $categoria->delete();
        Auditoria::registrar('eliminar_categoria', $nombre);
        return redirect()->route('categorias.index')->with('exito', 'Categoría "' . $nombre . '" eliminado(a).');
    }
}
