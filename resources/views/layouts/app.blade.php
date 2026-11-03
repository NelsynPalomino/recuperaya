@php
    $u = auth()->user();
    $noLeidas = $u ? $u->notificaciones()->where('leida', false)->count() : 0;
    $roles = ['administrador' => 'Administrador', 'encargado' => 'Encargado', 'usuario' => 'Usuario'];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Inicio') – RecuperaYa</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="icon" href="{{ asset('img/icono.svg') }}" type="image/svg+xml">
</head>
<body>
<header class="barra">
    <div class="contenedor">
        <a class="marca" href="{{ route('inicio') }}"><span class="logo">R</span><span>RecuperaYa<small>Universidad Continental</small></span></a>
        <input type="checkbox" id="toggle-menu" class="toggle-menu">
        <label for="toggle-menu" class="boton-menu" aria-label="Abrir menú"><span></span><span></span><span></span></label>
        <nav class="menu">
            <a href="{{ route('inicio') }}" @class(['activo' => request()->routeIs('inicio')])>Inicio</a>
            <a href="{{ route('catalogo.index') }}" @class(['activo' => request()->routeIs('catalogo.*')])>Catálogo</a>
            @can('reportar')
                <a href="{{ route('reportes.create') }}" @class(['activo' => request()->routeIs('reportes.create')])>Reportar pérdida</a>
                <a href="{{ route('reportes.index') }}" @class(['activo' => request()->routeIs('reportes.index')])>Mis reportes</a>
                <a href="{{ route('reclamos.mis') }}" @class(['activo' => request()->routeIs('reclamos.*')])>Mis reclamos</a>
            @endcan
            @can('gestionar')
                <a href="{{ route('objetos.create') }}" @class(['activo' => request()->routeIs('objetos.create')])>Registrar objeto</a>
                <a href="{{ route('objetos.index') }}" @class(['activo' => request()->routeIs('objetos.index', 'objetos.edit')])>Objetos</a>
                <a href="{{ route('entregas.index') }}" @class(['activo' => request()->routeIs('entregas.*')])>Entregas</a>
                <a href="{{ route('estadisticas.index') }}" @class(['activo' => request()->routeIs('estadisticas.*')])>Reportes</a>
            @endcan
            @can('administrar')
                <a href="{{ route('usuarios.index') }}" @class(['activo' => request()->routeIs('usuarios.*')])>Usuarios</a>
                <a href="{{ route('categorias.index') }}" @class(['activo' => request()->routeIs('categorias.*', 'lugares.*')])>Catálogos</a>
            @endcan
            <a href="{{ route('notificaciones.index') }}" @class(['activo' => request()->routeIs('notificaciones.*')])>Avisos@if($noLeidas)<span class="punto">{{ $noLeidas }}</span>@endif</a>
            <span class="usuario-chip">{{ $u->name }} · {{ $roles[$u->rol] ?? '' }}</span>
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" title="Cerrar sesión de {{ $u->name }}">Salir</button></form>
        </nav>
    </div>
</header>
<main>
    <div class="contenedor">
        @include('partials.mensajes')
        @yield('contenido')
    </div>
</main>
<footer class="pie">RecuperaYa v2.0 (Laravel) · Oficina de Bienestar Universitario · <a href="{{ route('privacidad') }}">Política de privacidad</a></footer>
</body>
</html>
