@extends('layouts.app')
@section('titulo', 'Editar ' . $o->codigo)
@section('contenido')
<div class="encabezado"><div><h1>Editar objeto {{ $o->codigo }}</h1><p>Estado actual: <span class="insignia {{ $o->claseEstado() }}">{{ $o->estado }}</span></p></div></div>
<form class="tarjeta" method="POST" action="{{ route('objetos.update', $o) }}" enctype="multipart/form-data" novalidate>
    @csrf
    @method('PUT')
    @include('objetos._form')
    <div class="acciones"><button class="boton acento" type="submit">Guardar cambios</button><a class="boton secundario" href="{{ route('objetos.index') }}">Cancelar</a></div>
</form>
@endsection
