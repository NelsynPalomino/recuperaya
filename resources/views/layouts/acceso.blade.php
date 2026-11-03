<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Acceso') – RecuperaYa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=2">
    <link rel="icon" href="{{ asset('img/icono.svg') }}" type="image/svg+xml">
</head>
<body>
<div class="acceso">
    <section class="lado">
        <div class="marca" style="margin-bottom:28px"><span class="logo">R</span><span>RecuperaYa<small>Universidad Continental</small></span></div>
        <span class="etiqueta-sede">📍 Sede Huancayo · Objetos perdidos y encontrados</span>
        <h1>¿Perdiste algo en el <span class="resaltado">campus</span>?</h1>
        <p>Busca tu objeto en el catálogo, reporta tu pérdida y te avisaremos cuando aparezca algo parecido.</p>
        <ul class="beneficios">
            <li><span class="icono">🔍</span>Catálogo de objetos encontrados con foto</li>
            <li><span class="icono">🔔</span>Avisos automáticos de coincidencias</li>
            <li><span class="icono">🛡️</span>Entrega segura con verificación de identidad</li>
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
