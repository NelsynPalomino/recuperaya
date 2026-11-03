@extends('layouts.app')
@section('titulo', 'Registrar objeto')
@section('contenido')
<div class="encabezado"><div><h1>Registrar objeto encontrado</h1><p>El objeto se publicará en el catálogo con estado “En custodia”.</p></div></div>
<form class="tarjeta" method="POST" action="{{ route('objetos.store') }}" enctype="multipart/form-data" novalidate>
    @csrf
    @include('objetos._form', ['o' => new \App\Models\Objeto()])
    <div class="acciones"><button class="boton acento" type="submit">Registrar objeto</button><a class="boton secundario" href="{{ route('objetos.index') }}">Cancelar</a></div>
</form>
@endsection
