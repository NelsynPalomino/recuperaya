@extends('layouts.app')
@section('titulo', 'Categorías de objetos')
@section('contenido')
<div class="encabezado">
    <div><h1>Categorías de objetos</h1><p>Se usan en los formularios de registro y en los filtros del catálogo.</p></div>
    <div class="acciones">
        <a class="boton secundario" href="{{ route('categorias.index') }}">Categorías</a>
        <a class="boton secundario" href="{{ route('lugares.index') }}">Lugares</a>
        <a class="boton acento" href="{{ route('categorias.create') }}">Nuevo(a) categoría</a>
    </div>
</div>
<div class="tarjeta tabla-scroll" style="max-width:760px">
    <table>
        <thead><tr><th>Nombre</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
        @foreach ($items as $x)
            <tr>
                <td>{{ $x->nombre }}</td>
                <td><span class="insignia {{ $x->activo ? 'verde' : 'gris' }}">{{ $x->activo ? 'Activo' : 'Inactivo' }}</span></td>
                <td>
                    <div class="acciones">
                        <a class="boton secundario pequeno" href="{{ route('categorias.edit', $x) }}">Editar</a>
                        <form method="POST" action="{{ route('categorias.destroy', $x) }}" onsubmit="return confirm('¿Eliminar {{ $x->nombre }}?')">
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
</div>
@endsection
