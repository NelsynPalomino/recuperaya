@extends('layouts.app')
@section('titulo', 'Usuarios')
@section('contenido')
@php $roles = ['usuario' => 'Usuario', 'encargado' => 'Encargado de custodia', 'administrador' => 'Administrador']; @endphp
<div class="encabezado"><div><h1>Usuarios y roles</h1><p>Asigna el rol de cada persona o desactiva su cuenta.</p></div></div>
<div class="tarjeta tabla-scroll">
    <table>
        <thead><tr><th>Nombre</th><th class="oculto-movil">Correo</th><th>Rol</th><th>Activo</th><th></th></tr></thead>
        <tbody>
        @foreach ($usuarios as $us)
            <tr>
                <td>{{ $us->name }}</td>
                <td class="oculto-movil">{{ $us->email }}</td>
                <td>{{ $roles[$us->rol] ?? $us->rol }}</td>
                <td><span class="insignia {{ $us->activo ? 'verde' : 'gris' }}">{{ $us->activo ? 'Sí' : 'No' }}</span></td>
                <td><a class="boton secundario pequeno" href="{{ route('usuarios.edit', $us) }}">Editar</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $usuarios->links() }}
</div>
<h2 style="margin-top:24px">Bitácora reciente</h2>
<div class="tarjeta tabla-scroll">
    <table>
        <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th class="oculto-movil">Detalle</th></tr></thead>
        <tbody>
        @foreach ($auditoria as $a)
            <tr><td>{{ $a->created_at->format('d/m/Y H:i') }}</td><td>{{ $a->usuario->name ?? '—' }}</td><td>{{ $a->accion }}</td><td class="oculto-movil">{{ $a->detalle }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
