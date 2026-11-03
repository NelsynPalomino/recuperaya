@extends('layouts.app')
@section('titulo', 'Entregas')
@section('contenido')
<div class="encabezado"><div><h1>Entregas</h1><p>Reclamos aprobados: verifica el carné universitario antes de entregar el objeto.</p></div></div>
<h2>Pendientes de entrega ({{ $aprobados->count() }})</h2>
@if ($aprobados->isEmpty())<div class="tarjeta vacio">No hay reclamos aprobados pendientes.</div>@endif
<div class="grid grid-2">
@foreach ($aprobados as $a)
    <div class="tarjeta">
        <div style="display:flex;gap:14px">
            <img class="miniatura" style="width:96px;height:76px" src="{{ $a->objeto->urlFoto() }}" alt="">
            <div>
                <strong>{{ $a->objeto->codigo }}</strong> · {{ $a->objeto->categoria->nombre }}<br>
                <span>Reclamado por <strong>{{ $a->usuario->name }}</strong></span><br>
                <small>{{ $a->usuario->email }}{{ $a->usuario->codigo_universitario ? ' · Código ' . $a->usuario->codigo_universitario : '' }}</small>
            </div>
        </div>
        <form method="POST" action="{{ route('entregas.store') }}" style="margin-top:14px" novalidate>
            @csrf
            <input type="hidden" name="reclamo_id" value="{{ $a->id }}">
            <div class="campo"><label for="carne{{ $a->id }}">Código del carné presentado *</label><input type="text" id="carne{{ $a->id }}" name="codigo_carne" maxlength="20" required></div>
            <div class="campo"><label for="obs{{ $a->id }}">Observación</label><input type="text" id="obs{{ $a->id }}" name="observacion" maxlength="255"></div>
            <label class="check"><input type="checkbox" name="carne_verificado" value="1"> Verifiqué que el carné corresponde a la persona que reclama.</label>
            <button class="boton" type="submit">Registrar entrega</button>
        </form>
    </div>
@endforeach
</div>
<h2 style="margin-top:24px">Últimas entregas</h2>
<div class="tarjeta tabla-scroll">
    @if ($recientes->isEmpty())<p class="vacio">Aún no hay entregas registradas.</p>@else
    <table>
        <thead><tr><th>Fecha</th><th>Objeto</th><th>Entregado a</th><th class="oculto-movil">Carné</th><th class="oculto-movil">Encargado</th></tr></thead>
        <tbody>
        @foreach ($recientes as $r)
            <tr><td>{{ $r->fecha_entrega->format('d/m/Y H:i') }}</td><td>{{ $r->objeto->codigo }} · {{ $r->objeto->categoria->nombre }}</td><td>{{ $r->receptor->name }}</td><td class="oculto-movil">{{ $r->codigo_carne }}</td><td class="oculto-movil">{{ $r->encargado->name }}</td></tr>
        @endforeach
        </tbody>
    </table>
    @endif
</div>
@endsection
