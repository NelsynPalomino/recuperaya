@extends('layouts.app')
@section('titulo', 'Editar usuario')
@section('contenido')
<div class="encabezado"><div><h1>Editar usuario</h1><p>{{ $usuario->name }} · {{ $usuario->email }}</p></div></div>
<form class="tarjeta" method="POST" action="{{ route('usuarios.update', $usuario) }}" style="max-width:520px" novalidate>
    @csrf
    @method('PUT')
    <div @class(['campo', 'con-error' => $errors->has('rol')])><label for="rol">Rol</label>
        <select id="rol" name="rol">
            @foreach (['usuario' => 'Usuario', 'encargado' => 'Encargado de custodia', 'administrador' => 'Administrador'] as $k => $v)
                <option value="{{ $k }}" @selected(old('rol', $usuario->rol) === $k)>{{ $v }}</option>
            @endforeach
        </select>@include('partials.error', ['campo' => 'rol'])</div>
    <div @class(['campo', 'con-error' => $errors->has('activo')])><label for="activo">Cuenta activa</label>
        <select id="activo" name="activo"><option value="1" @selected(old('activo', (int) $usuario->activo) == 1)>Sí</option><option value="0" @selected(old('activo', (int) $usuario->activo) == 0)>No</option></select>
        @include('partials.error', ['campo' => 'activo'])</div>
    <div class="acciones"><button class="boton" type="submit">Guardar</button><a class="boton secundario" href="{{ route('usuarios.index') }}">Cancelar</a></div>
</form>
@endsection
