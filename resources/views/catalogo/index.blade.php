@extends('layouts.app')
@section('titulo', 'Catálogo')
@section('contenido')
<div class="encabezado">
    <div><h1>Catálogo de objetos encontrados</h1><p>Objetos que están en custodia en la oficina de Bienestar Universitario.</p></div>
    @can('reportar')<a class="boton acento" href="{{ route('reportes.create') }}">¿No lo encuentras? Reporta tu pérdida</a>@endcan
</div>

<form class="tarjeta filtros" method="GET" action="{{ route('catalogo.index') }}" style="margin-bottom:18px">
    <div class="campo"><label for="q">Buscar</label><input type="search" id="q" name="q" value="{{ $filtros['q'] }}" maxlength="100" placeholder="Ej.: celular negro, mochila, OBJ-0002"></div>
    <div class="campo"><label for="categoria">Categoría</label>
        <select id="categoria" name="categoria"><option value="">Todas</option>
            @foreach ($categorias as $c)<option value="{{ $c->id }}" @selected((string) $filtros['categoria'] === (string) $c->id)>{{ $c->nombre }}</option>@endforeach
        </select></div>
    <div class="campo"><label for="lugar">Lugar</label>
        <select id="lugar" name="lugar"><option value="">Todos</option>
            @foreach ($lugares as $l)<option value="{{ $l->id }}" @selected((string) $filtros['lugar'] === (string) $l->id)>{{ $l->nombre }}</option>@endforeach
        </select></div>
    <div class="campo"><label for="desde">Desde</label><input type="date" id="desde" name="desde" value="{{ $filtros['desde'] }}"></div>
    <div class="campo"><label for="hasta">Hasta</label><input type="date" id="hasta" name="hasta" value="{{ $filtros['hasta'] }}"></div>
    <div class="acciones"><button class="boton" type="submit">Buscar</button><a class="boton secundario" href="{{ route('catalogo.index') }}">Limpiar</a></div>
</form>

<p style="color:var(--gris)">{{ $objetos->total() }} objeto(s) encontrado(s)</p>
@if ($objetos->isEmpty())
    <div class="tarjeta vacio">No se encontraron objetos con esos filtros.@can('reportar')<br><a href="{{ route('reportes.create') }}">Registra un reporte de pérdida</a> y te avisaremos si aparece.@endcan</div>
@else
    <div class="objetos">
        @foreach ($objetos as $o) @include('partials.tarjeta-objeto', ['o' => $o]) @endforeach
    </div>
    {{ $objetos->links() }}
@endif
@endsection
