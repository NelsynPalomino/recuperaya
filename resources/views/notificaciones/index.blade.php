@extends('layouts.app')
@section('titulo', 'Avisos')
@section('contenido')
<div class="encabezado"><div><h1>Avisos</h1><p>Coincidencias, resultados de reclamos y entregas.</p></div></div>
<div class="tarjeta">
    @forelse ($notificaciones as $n)
        @if ($loop->first)<ul class="lista">@endif
        <li @class(['nueva' => ! $n->leida])>
            <strong>{{ $n->titulo }}</strong><br>{{ $n->mensaje }}<br>
            <span class="fecha">{{ $n->created_at->format('d/m/Y H:i') }}{{ $n->enviada_correo ? ' · enviado por correo' : '' }}</span>
            @if ($n->enlace) · <a href="{{ $n->enlace }}">Ver detalle</a>@endif
        </li>
        @if ($loop->last)</ul>@endif
    @empty
        <p class="vacio">No tienes avisos.</p>
    @endforelse
</div>
@endsection
