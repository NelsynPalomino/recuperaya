@extends('layouts.app')
@section('titulo', $item->exists ? 'Editar categoría' : 'Nuevo(a) categoría')
@section('contenido')
<div class="encabezado"><div><h1>{{ $item->exists ? 'Editar categoría' : 'Nuevo(a) categoría' }}</h1></div></div>
<form class="tarjeta" method="POST" action="{{ $item->exists ? route('categorias.update', $item) : route('categorias.store') }}" style="max-width:520px" novalidate>
    @csrf
    @if ($item->exists) @method('PUT') @endif
    <div @class(['campo', 'con-error' => $errors->has('nombre')])>
        <label for="nombre">Nombre *</label>
        <input type="text" id="nombre" name="nombre" maxlength="60" value="{{ old('nombre', $item->nombre) }}" required>
        @include('partials.error', ['campo' => 'nombre'])
    </div>
    <label class="check"><input type="checkbox" name="activo" value="1" @checked(old('activo', $item->activo))> Activo (aparece en los formularios)</label>
    <div class="acciones"><button class="boton" type="submit">Guardar</button><a class="boton secundario" href="{{ route('categorias.index') }}">Cancelar</a></div>
</form>
@endsection
