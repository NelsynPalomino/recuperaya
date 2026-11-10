@extends('layouts.app')
@section('titulo', 'Reportes')
@section('contenido')
<div class="encabezado"><div><h1>Reportes</h1><p>Indicadores de objetos registrados entre {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} y {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}.</p></div></div>
@include('partials.mensajes')
<form id="filtros-reporte" class="tarjeta filtros f3 no-imprimir" method="GET" action="{{ route('estadisticas.index') }}" style="margin-bottom:18px">
    <div class="campo"><label for="desde">Desde</label><input type="date" id="desde" name="desde" value="{{ $desde }}"></div>
    <div class="campo"><label for="hasta">Hasta</label><input type="date" id="hasta" name="hasta" value="{{ $hasta }}"></div>
    <div class="campo"><label for="categoria">Categoría</label>
        <select id="categoria" name="categoria"><option value="">Todas</option>
            @foreach ($categorias as $c)<option value="{{ $c->id }}" @selected($categoria === $c->id)>{{ $c->nombre }}</option>@endforeach
        </select></div>
    <div class="campo"><label for="estado">Estado</label>
        <select id="estado" name="estado"><option value="">Todos</option>
            @foreach (\App\Models\Objeto::ESTADOS as $e)<option value="{{ $e }}" @selected($estado === $e)>{{ $e }}</option>@endforeach
        </select></div>
    <div class="acciones">
        <button class="boton" type="submit" id="btn-filtrar">Ver</button>
        <a class="boton secundario" href="{{ route('estadisticas.imprimir', $filtros) }}" target="_blank">PDF / imprimir</a>
        <a class="boton secundario" href="{{ route('estadisticas.csv', $filtros) }}">Excel (CSV)</a>
        @can('administrar')<a class="boton secundario" href="{{ route('estadisticas.ml') }}">Datos para ML</a>@endcan
    </div>
</form>
@include('estadisticas._kpis')
<div class="tarjeta" style="margin-bottom:16px" id="grafico-mensual"><h2>Objetos registrados por mes</h2>@include('estadisticas._barras', ['datos' => $r['por_mes']->map(fn ($x) => ['nombre' => $x->nombre, 'total' => (int) $x->total])->all()])</div>
<div class="grid grid-3">
    <div class="tarjeta">
        <h2>Por estado</h2>
        @include('estadisticas._barras', ['datos' => collect(\App\Models\Objeto::ESTADOS)->map(fn ($e) => ['nombre' => $e, 'total' => $r['por_estado'][$e] ?? 0])->all()])
        <p style="color:var(--gris);font-size:.85rem;margin-bottom:0">Reclamos rechazados: {{ $r['reclamos_rechazados'] }}</p>
    </div>
    <div class="tarjeta"><h2>Por categoría</h2>@include('estadisticas._barras', ['datos' => $r['por_categoria']->map(fn ($x) => ['nombre' => $x->nombre, 'total' => (int) $x->total])->all()])</div>
    <div class="tarjeta"><h2>Por lugar del campus</h2>@include('estadisticas._barras', ['datos' => $r['por_lugar']->map(fn ($x) => ['nombre' => $x->nombre, 'total' => (int) $x->total])->all()])</div>
</div>
@endsection
