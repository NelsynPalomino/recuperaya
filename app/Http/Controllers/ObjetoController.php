<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreObjetoRequest;
use App\Http\Requests\UpdateObjetoRequest;
use App\Models\Auditoria;
use App\Models\Categoria;
use App\Models\Lugar;
use App\Models\Objeto;
use App\Services\ImagenService;
use App\Services\MotorCoincidencias;
use App\Services\Texto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** CRUD de objetos encontrados con Route::resource (RF-03). Solo encargado y administrador. */
class ObjetoController extends Controller
{
    public function index(Request $request)
    {
        Objeto::marcarNoReclamados();
        $f = $request->only(['q', 'estado', 'categoria', 'lugar']);
        $objetos = Objeto::with(['categoria', 'lugar'])
            ->when($f['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('codigo', 'like', "%$t%")->orWhere('descripcion', 'like', "%$t%")->orWhere('color', 'like', "%$t%")))
            ->when(in_array($f['estado'] ?? '', Objeto::ESTADOS, true) ? $f['estado'] : null, fn ($q, $v) => $q->where('estado', $v))
            ->when($f['categoria'] ?? null, fn ($q, $v) => $q->where('categoria_id', (int) $v))
            ->when($f['lugar'] ?? null, fn ($q, $v) => $q->where('lugar_id', (int) $v))
            ->latest('fecha_hallazgo')->latest('id')->paginate(15)->withQueryString();

        return view('objetos.index', ['objetos' => $objetos, 'filtros' => $f + ['q' => '', 'estado' => '', 'categoria' => '', 'lugar' => ''],
            'categorias' => Categoria::activos()->get(), 'lugares' => Lugar::activos()->get()]);
    }

    public function create()
    {
        return view('objetos.create', ['categorias' => Categoria::activos()->get(), 'lugares' => Lugar::activos()->get()]);
    }

    public function store(StoreObjetoRequest $request)
    {
        // Asignación masiva: solo los campos de $fillable (categoría, lugar, descripción, color, fecha y preguntas).
        $objeto = new Objeto($request->validated());
        // Campos protegidos que asigna el sistema:
        $objeto->codigo = 'TMP-' . Str::random(8);
        $objeto->slug = Str::random(12);
        $objeto->estado = Objeto::EN_CUSTODIA;
        $objeto->foto = ImagenService::guardar($request->file('foto'));
        $objeto->respuesta1_hash = password_hash(Texto::normalizar($request->respuesta1), PASSWORD_BCRYPT);
        $objeto->respuesta2_hash = password_hash(Texto::normalizar($request->respuesta2), PASSWORD_BCRYPT);
        $objeto->registrado_por = $request->user()->id;
        $objeto->save();
        $this->asignarCodigoYSlug($objeto);

        Auditoria::registrar('registro_objeto', $objeto->codigo);
        $n = MotorCoincidencias::procesarObjeto($objeto);
        return redirect()->route('objetos.index')->with('exito', "Objeto {$objeto->codigo} registrado y publicado en el catálogo."
            . ($n ? " Se notificó a $n usuario(s) con reportes que coinciden." : ''));
    }

    /** Código OBJ-0001 y slug "celular-negro-obj-0001" para la URL amigable. */
    private function asignarCodigoYSlug(Objeto $objeto): void
    {
        $objeto->loadMissing('categoria');
        $objeto->codigo = sprintf('OBJ-%04d', $objeto->id);
        $objeto->slug = Str::slug($objeto->categoria->nombre . ' ' . $objeto->color . ' ' . $objeto->codigo);
        $objeto->save();
    }

    public function show(Objeto $objeto)
    {
        return redirect()->route('catalogo.show', $objeto);
    }

    public function edit(Objeto $objeto)
    {
        return view('objetos.edit', ['o' => $objeto, 'categorias' => Categoria::activos()->get(), 'lugares' => Lugar::activos()->get()]);
    }

    public function update(UpdateObjetoRequest $request, Objeto $objeto)
    {
        // Asignación masiva en la actualización (Sesión 09): update() con los datos validados.
        $objeto->update($request->validated());
        if ($request->hasFile('foto')) {
            Storage::disk('public')->delete((string) $objeto->foto);
            $objeto->foto = ImagenService::guardar($request->file('foto'));
        }
        if ($request->filled('respuesta1')) {
            $objeto->respuesta1_hash = password_hash(Texto::normalizar($request->respuesta1), PASSWORD_BCRYPT);
            $objeto->respuesta2_hash = password_hash(Texto::normalizar($request->respuesta2), PASSWORD_BCRYPT);
        }
        $objeto->save();
        $this->asignarCodigoYSlug($objeto->fresh());
        Auditoria::registrar('edicion_objeto', $objeto->codigo);
        return redirect()->route('objetos.index')->with('exito', "Objeto {$objeto->codigo} actualizado.");
    }

    /** Eliminar registro (Sesión 09): solo si el objeto no tiene reclamos ni entregas. */
    public function destroy(Objeto $objeto)
    {
        if ($objeto->reclamos()->exists() || $objeto->entrega()->exists()) {
            return back()->with('error', "No se puede eliminar {$objeto->codigo} porque ya tiene reclamos o entregas registradas.");
        }
        $codigo = $objeto->codigo;
        Storage::disk('public')->delete((string) $objeto->foto);
        $objeto->delete();
        Auditoria::registrar('eliminacion_objeto', $codigo);
        return redirect()->route('objetos.index')->with('exito', "Objeto $codigo eliminado.");
    }
}
