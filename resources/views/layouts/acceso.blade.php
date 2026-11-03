<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Acceso') – RecuperaYa</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="icon" href="{{ asset('img/icono.svg') }}" type="image/svg+xml">
</head>
<body>
<div class="acceso">
    <section class="lado">
        <div class="marca" style="margin-bottom:24px"><span class="logo">R</span><span>RecuperaYa<small>Universidad Continental</small></span></div>
        <h1>¿Perdiste algo en el campus?</h1>
        <p>Busca tu objeto en el catálogo, reporta tu pérdida y te avisaremos cuando aparezca algo parecido.</p>
        <ul>
            <li>Catálogo de objetos encontrados con foto</li>
            <li>Avisos automáticos de coincidencias</li>
            <li>Entrega segura con verificación de identidad</li>
        </ul>
    </section>
    <section class="formulario">
        <div style="width:100%;max-width:420px">
            @include('partials.mensajes')
            @yield('contenido')
        </div>
    </section>
</div>
</body>
</html>
