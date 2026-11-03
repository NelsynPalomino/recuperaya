@extends('layouts.app')
@section('titulo', 'Reportar pérdida')
@section('contenido')
<div class="encabezado"><div><h1>Reportar un objeto perdido</h1><p>Te avisaremos cuando se registre un objeto parecido.</p></div></div>
<form class="tarjeta" method="POST" action="{{ route('reportes.store') }}" novalidate style="max-width:720px">
    @csrf
    <div class="grid grid-2">
        <div @class(['campo', 'con-error' => $errors->has('categoria_id')])><label for="categoria_id">¿Qué perdiste? *</label>
            <select id="categoria_id" name="categoria_id" required><option value="">Selecciona…</option>
                @foreach ($categorias as $c)<option value="{{ $c->id }}" @selected((string) old('categoria_id') === (string) $c->id)>{{ $c->nombre }}</option>@endforeach
            </select>@include('partials.error', ['campo' => 'categoria_id'])</div>
        <div @class(['campo', 'con-error' => $errors->has('lugar_id')])><label for="lugar_id">¿Dónde crees que lo perdiste?</label>
            <select id="lugar_id" name="lugar_id"><option value="">No estoy seguro(a)</option>
                @foreach ($lugares as $l)<option value="{{ $l->id }}" @selected((string) old('lugar_id') === (string) $l->id)>{{ $l->nombre }}</option>@endforeach
            </select>@include('partials.error', ['campo' => 'lugar_id'])</div>
        <div @class(['campo', 'con-error' => $errors->has('color')])><label for="color">Color *</label><input type="text" id="color" name="color" maxlength="40" value="{{ old('color') }}" required>@include('partials.error', ['campo' => 'color'])</div>
        <div @class(['campo', 'con-error' => $errors->has('fecha_perdida')])><label for="fecha_perdida">¿Cuándo lo perdiste? *</label><input type="date" id="fecha_perdida" name="fecha_perdida" max="{{ now()->toDateString() }}" value="{{ old('fecha_perdida', now()->toDateString()) }}" required>@include('partials.error', ['campo' => 'fecha_perdida'])</div>
    </div>
    <div @class(['campo', 'con-error' => $errors->has('descripcion')])><label for="descripcion">Descripción *</label>
        <textarea id="descripcion" name="descripcion" maxlength="500" required placeholder="Marca, tamaño, señas particulares…">{{ old('descripcion') }}</textarea>
        <small>No incluyas datos que solo tú debes saber (los usarás para reclamar).</small>@include('partials.error', ['campo' => 'descripcion'])</div>
    <div class="acciones"><button class="boton acento" type="submit">Registrar reporte</button><a class="boton secundario" href="{{ route('catalogo.index') }}">Buscar primero en el catálogo</a></div>
</form>
@endsection
