@extends('layouts.app')
@section('titulo', 'Objetos')
@section('contenido')
<div class="encabezado">
    <div><h1>Objetos registrados</h1><p>Todos los objetos y su estado actual.</p></div>
    <a class="boton acento" href="{{ route('objetos.create') }}">Registrar objeto</a>
</div>
<form class="tarjeta filtros f5" method="GET" action="{{ route('objetos.index') }}" style="margin-bottom:18px">
    <div class="campo"><label for="q">Buscar</label><input type="search" id="q" name="q" value="{{ $filtros['q'] }}" placeholder="Código, descripción o color"></div>
    <div class="campo"><label for="estado">Estado</label><select id="estado" name="estado"><option value="">Todos</option>
        @foreach (\App\Models\Objeto::ESTADOS as $es)<option @selected($filtros['estado'] === $es)>{{ $es }}</option>@endforeach</select></div>
    <div class="campo"><label for="categoria">Categoría</label><select id="categoria" name="categoria"><option value="">Todas</option>
        @foreach ($categorias as $c)<option value="{{ $c->id }}" @selected((string) $filtros['categoria'] === (string) $c->id)>{{ $c->nombre }}</option>@endforeach</select></div>
    <div class="campo"><label for="lugar">Lugar</label><select id="lugar" name="lugar"><option value="">Todos</option>
        @foreach ($lugares as $l)<option value="{{ $l->id }}" @selected((string) $filtros['lugar'] === (string) $l->id)>{{ $l->nombre }}</option>@endforeach</select></div>
    <div class="acciones"><button class="boton" type="submit">Filtrar</button></div>
</form>
<div class="tarjeta tabla-scroll">
    @if ($objetos->isEmpty())<p class="vacio">No hay objetos con esos filtros.</p>@else
    <table>
        <thead><tr><th>Foto</th><th>Código</th><th>Categoría</th><th class="oculto-movil">Lugar</th><th>Fecha</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
        @foreach ($objetos as $o)
            <tr>
                <td><img class="miniatura" src="{{ $o->urlFoto() }}" alt=""></td>
                <td><a href="{{ route('catalogo.show', $o) }}">{{ $o->codigo }}</a></td>
                <td>{{ $o->categoria->nombre }} · {{ $o->color }}</td>
                <td class="oculto-movil">{{ $o->lugar->nombre }}</td>
                <td>{{ $o->fecha_hallazgo->format('d/m/Y') }}</td>
                <td><span class="insignia {{ $o->claseEstado() }}">{{ $o->estado }}</span></td>
                <td>
                    <div class="acciones">
                        <a class="boton secundario pequeno" href="{{ route('objetos.edit', $o) }}">Editar</a>
                        <form method="POST" action="{{ route('objetos.destroy', $o) }}" onsubmit="return confirm('¿Eliminar el objeto {{ $o->codigo }}? Esta acción no se puede deshacer.')">
                            @csrf
                            @method('DELETE')
                            <button class="boton peligro pequeno" type="submit">Eliminar</button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $objetos->links() }}
    @endif
</div>
@endsection
