@extends('layouts.app')
@section('titulo', 'Mis reclamos')
@section('contenido')
@php $guia = ['Pendiente' => 'Vuelve a responder las preguntas.', 'Aprobado' => 'Recógelo en Bienestar con tu carné.', 'Rechazado' => 'Acércate a Bienestar si es un error.', 'Entregado' => 'Objeto recibido.']; @endphp
<div class="encabezado"><div><h1>Mis reclamos</h1><p>Estado de los objetos que reclamaste.</p></div></div>
<div class="tarjeta tabla-scroll">
    @if ($reclamos->isEmpty())
        <p class="vacio">Aún no reclamaste objetos. Búscalos en el <a href="{{ route('catalogo.index') }}">catálogo</a>.</p>
    @else
    <table>
        <thead><tr><th>Foto</th><th>Objeto</th><th class="oculto-movil">Lugar</th><th>Intentos</th><th>Estado</th><th>Qué hacer</th></tr></thead>
        <tbody>
        @foreach ($reclamos as $r)
            <tr>
                <td><img class="miniatura" src="{{ $r->objeto->urlFoto() }}" alt=""></td>
                <td><a href="{{ route('catalogo.show', $r->objeto) }}">{{ $r->objeto->codigo }}</a><br><small>{{ $r->objeto->categoria->nombre }}</small></td>
                <td class="oculto-movil">{{ $r->objeto->lugar->nombre }}</td>
                <td>{{ $r->intentos }} / {{ config('recuperaya.max_intentos_reclamo') }}</td>
                <td><span class="insignia {{ $r->claseEstado() }}">{{ $r->estado }}</span></td>
                <td>{{ $guia[$r->estado] ?? '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @endif
</div>
@endsection
