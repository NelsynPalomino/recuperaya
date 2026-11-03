@extends('layouts.app')
@section('titulo', $o->codigo)
@section('contenido')
<p><a href="{{ route('catalogo.index') }}">← Volver al catálogo</a></p>
<div class="tarjeta detalle">
    <img class="foto-grande" src="{{ $o->urlFoto() }}" alt="Foto del objeto {{ $o->codigo }}">
    <div>
        <span class="insignia {{ $o->claseEstado() }}">{{ $o->estado }}</span>
        <h1 style="margin-top:8px">{{ $o->categoria->nombre }} · {{ $o->color }}</h1>
        <dl class="datos">
            <dt>Código</dt><dd>{{ $o->codigo }}</dd>
            <dt>Lugar</dt><dd>{{ $o->lugar->nombre }}</dd>
            <dt>Encontrado el</dt><dd>{{ $o->fecha_hallazgo->format('d/m/Y') }}</dd>
            <dt>Descripción</dt><dd style="font-weight:400">{{ $o->descripcion }}</dd>
        </dl>

        @can('reportar')
            @if ($reclamo?->estado === \App\Models\Reclamo::APROBADO)
                <div class="alerta exito">Tu reclamo fue aprobado. Recoge el objeto en la oficina de Bienestar Universitario presentando tu carné.</div>
            @elseif ($reclamo?->estado === \App\Models\Reclamo::ENTREGADO)
                <div class="alerta exito">Este objeto ya te fue entregado.</div>
            @elseif ($reclamo?->estado === \App\Models\Reclamo::RECHAZADO)
                <div class="alerta error">Tu reclamo fue rechazado porque las respuestas no coinciden. Si crees que es un error, acércate a la oficina de Bienestar.</div>
            @elseif ($o->estado === \App\Models\Objeto::EN_CUSTODIA)
                <h2>¿Es tuyo? Reclámalo</h2>
                <p style="color:var(--gris);margin-top:0">Responde las preguntas de verificación. Tienes {{ config('recuperaya.max_intentos_reclamo') - ($reclamo->intentos ?? 0) }} intento(s).</p>
                <form method="POST" action="{{ route('reclamos.store', $o) }}" autocomplete="off" novalidate>
                    @csrf
                    <div @class(['campo', 'con-error' => $errors->has('respuesta1')])><label for="respuesta1">{{ $o->pregunta1 }}</label><input type="text" id="respuesta1" name="respuesta1" maxlength="100" required>@include('partials.error', ['campo' => 'respuesta1'])</div>
                    <div @class(['campo', 'con-error' => $errors->has('respuesta2')])><label for="respuesta2">{{ $o->pregunta2 }}</label><input type="text" id="respuesta2" name="respuesta2" maxlength="100" required>@include('partials.error', ['campo' => 'respuesta2'])</div>
                    <button class="boton acento" type="submit">Enviar reclamo</button>
                </form>
            @endif
        @else
            <h3>Preguntas de verificación</h3>
            <ol><li>{{ $o->pregunta1 }}</li><li>{{ $o->pregunta2 }}</li></ol>
            @if ($o->entrega)
                <div class="alerta exito">Entregado a {{ $o->entrega->receptor->name }} el {{ $o->entrega->fecha_entrega->format('d/m/Y H:i') }} (carné {{ $o->entrega->codigo_carne }}).</div>
            @endif
            @can('gestionar')<a class="boton secundario" href="{{ route('objetos.edit', $o) }}">Editar objeto</a>@endcan
        @endcan
    </div>
</div>
@endsection
