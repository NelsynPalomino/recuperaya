@extends('layouts.app')
@section('titulo', 'Inicio')
@section('contenido')
@php $u = auth()->user(); @endphp
<div class="encabezado">
    <div>
        <h1>Hola, {{ explode(' ', $u->name)[0] }}</h1>
        <p>{{ $u->esUsuario() ? '¿Perdiste algo? Busca en el catálogo o reporta tu pérdida.' : 'Resumen de la oficina de objetos perdidos.' }}</p>
    </div>
    <div class="acciones">
        @can('reportar')
            <a class="boton acento" href="{{ route('reportes.create') }}">Reportar una pérdida</a>
            <a class="boton secundario" href="{{ route('catalogo.index') }}">Ver catálogo</a>
        @else
            <a class="boton acento" href="{{ route('objetos.create') }}">Registrar objeto encontrado</a>
            <a class="boton secundario" href="{{ route('entregas.index') }}">Entregas pendientes</a>
        @endcan
    </div>
</div>

@cannot('reportar')
<div class="grid grid-4" style="margin-bottom:16px">
    <div class="tarjeta kpi"><div class="valor">{{ $custodia }}</div><div class="etiqueta">Objetos en custodia</div></div>
    <div class="tarjeta kpi"><div class="valor">{{ $porEntregar }}</div><div class="etiqueta">Reclamos aprobados por entregar</div></div>
    <div class="tarjeta kpi"><div class="valor">{{ $devueltosMes }}</div><div class="etiqueta">Devueltos este mes</div></div>
    <div class="tarjeta kpi"><div class="valor">{{ $reportesActivos }}</div><div class="etiqueta">Reportes de pérdida activos</div></div>
</div>
@endcannot

<div class="grid grid-2">
    <div class="tarjeta">
        <div class="encabezado" style="margin-bottom:8px"><h2>Últimos avisos</h2><a href="{{ route('notificaciones.index') }}">Ver todos</a></div>
        @forelse ($notificaciones as $n)
            @if ($loop->first)<ul class="lista">@endif
            <li @class(['nueva' => ! $n->leida])>
                <strong>{{ $n->titulo }}</strong><br>{{ $n->mensaje }}<br>
                <span class="fecha">{{ $n->created_at->format('d/m/Y H:i') }}</span>
                @if ($n->enlace) · <a href="{{ $n->enlace }}">Ver</a>@endif
            </li>
            @if ($loop->last)</ul>@endif
        @empty
            <p class="vacio">Aún no tienes avisos.</p>
        @endforelse
    </div>

    @can('reportar')
    <div class="tarjeta">
        <div class="encabezado" style="margin-bottom:8px"><h2>Mis reportes y reclamos</h2></div>
        @if ($misReportes->isEmpty() && $misReclamos->isEmpty())
            <p class="vacio">No tienes reportes ni reclamos.<br><a href="{{ route('reportes.create') }}">Reportar una pérdida</a></p>
        @else
            <ul class="lista">
                @foreach ($misReportes as $r)
                    <li>Reporte: <strong>{{ $r->categoria->nombre }}</strong> ({{ $r->color }}) – <span class="insignia {{ $r->claseEstado() }}">{{ $r->estado }}</span></li>
                @endforeach
                @foreach ($misReclamos as $r)
                    <li>Reclamo: <strong>{{ $r->objeto->codigo }}</strong> – <span class="insignia {{ $r->claseEstado() }}">{{ $r->estado }}</span></li>
                @endforeach
            </ul>
        @endif
    </div>
    @else
    <div class="tarjeta">
        <div class="encabezado" style="margin-bottom:8px"><h2>Registrados recientemente</h2><a href="{{ route('objetos.index') }}">Ver todos</a></div>
        @forelse ($recientes as $o)
            @if ($loop->first)<ul class="lista">@endif
            <li><a href="{{ route('catalogo.show', $o) }}"><strong>{{ $o->codigo }}</strong></a> · {{ $o->categoria->nombre }} ({{ $o->color }}) · {{ $o->lugar->nombre }} · {{ $o->fecha_hallazgo->format('d/m/Y') }}</li>
            @if ($loop->last)</ul>@endif
        @empty
            <p class="vacio">No hay objetos en custodia.</p>
        @endforelse
    </div>
    @endcan
</div>

@can('reportar')
    @if ($recientes->isNotEmpty())
        <h2 style="margin-top:24px">Encontrados recientemente</h2>
        <div class="objetos">
            @foreach ($recientes as $o) @include('partials.tarjeta-objeto', ['o' => $o]) @endforeach
        </div>
    @endif
@endcan
@endsection
