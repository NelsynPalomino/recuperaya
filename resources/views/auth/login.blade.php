@extends('layouts.acceso')
@section('titulo', 'Iniciar sesión')
@section('contenido')
<div class="tarjeta">
    <h2>Iniciar sesión</h2>
    <p style="color:var(--gris);margin-top:0">Ingresa con tu correo institucional.</p>
    <form method="POST" action="{{ route('login.store') }}" novalidate>
        @csrf
        <div @class(['campo', 'con-error' => $errors->has('email')])>
            <label for="email">Correo institucional</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="codigo@continental.edu.pe" autocomplete="username" required>
            @include('partials.error', ['campo' => 'email'])
        </div>
        <div @class(['campo', 'con-error' => $errors->has('password')])>
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
            @include('partials.error', ['campo' => 'password'])
        </div>
        <button class="boton bloque" type="submit">Ingresar</button>
    </form>
    <p style="text-align:center;margin-bottom:0">¿No tienes cuenta? <a href="{{ route('registro') }}">Regístrate</a></p>
</div>
@endsection
