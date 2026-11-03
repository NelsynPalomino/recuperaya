@extends('layouts.app')
@section('titulo', 'Lugares del campus')
@section('contenido')
<div class="encabezado">
    <div><h1>Lugares del campus</h1><p>Se usan en los formularios de registro y en los filtros del catálogo.</p></div>
    <div class="acciones">
        <a class="boton secundario" href="{{ route('categorias.index') }}">Categorías</a>
        <a class="boton secundario" href="{{ route('lugares.index') }}">Lugares</a>
        <a class="boton acento" href="{{ route('lugares.create') }}">Nuevo(a) lugar</a>
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
                        <a class="boton secundario pequeno" href="{{ route('lugares.edit', $x) }}">Editar</a>
                        <form method="POST" action="{{ route('lugares.destroy', $x) }}" onsubmit="return confirm('¿Eliminar {{ $x->nombre }}?')">
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
