<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUsuarioRequest;
use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Http\Request;

/** Usuarios y roles (RF-02). Route::resource con index, edit y update. */
class UsuarioController extends Controller
{
    public function index()
    {
        return view('admin.usuarios.index', [
            'usuarios' => User::orderBy('rol')->orderBy('name')->paginate(20),
            'auditoria' => Auditoria::with('usuario')->latest()->take(15)->get(),
        ]);
    }

    public function edit(User $usuario)
    {
        return view('admin.usuarios.edit', ['usuario' => $usuario]);
    }

    public function update(UpdateUsuarioRequest $request, User $usuario)
    {
        if ($usuario->id === $request->user()->id && ($request->rol !== User::ADMIN || ! $request->boolean('activo'))) {
            return back()->with('error', 'No puedes quitarte el rol de administrador ni desactivar tu propia cuenta.');
        }
        // "rol" y "activo" no son fillable: se asignan con forceFill solo desde esta pantalla de administrador.
        $usuario->forceFill(['rol' => $request->rol, 'activo' => $request->boolean('activo')])->save();
        Auditoria::registrar('cambio_rol', "Usuario {$usuario->id}: {$request->rol}");
        return redirect()->route('usuarios.index')->with('exito', "Usuario {$usuario->name} actualizado.");
    }
}
