@extends('layouts.acceso')
@section('titulo', 'Crear cuenta')
@section('contenido')
<div class="tarjeta">
    <h2>Crear cuenta</h2>
    <p style="color:var(--gris);margin-top:0">Solo para la comunidad de la Universidad Continental.</p>
    <form method="POST" action="{{ route('registro.store') }}" novalidate>
        @csrf
        <div @class(['campo', 'con-error' => $errors->has('name')])>
            <label for="name">Nombre completo</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" maxlength="120" required>
            @include('partials.error', ['campo' => 'name'])
        </div>
        <div @class(['campo', 'con-error' => $errors->has('email')])>
            <label for="email">Correo institucional</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="codigo@continental.edu.pe" required>
            @include('partials.error', ['campo' => 'email'])
        </div>
        <div @class(['campo', 'con-error' => $errors->has('codigo_universitario')])>
            <label for="codigo_universitario">Código universitario <small>(opcional)</small></label>
            <input type="text" id="codigo_universitario" name="codigo_universitario" value="{{ old('codigo_universitario') }}" maxlength="20">
            @include('partials.error', ['campo' => 'codigo_universitario'])
        </div>
        <div @class(['campo', 'con-error' => $errors->has('password')])>
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" autocomplete="new-password" required>
            <small>Mínimo 8 caracteres, con letras y números.</small>
            @include('partials.error', ['campo' => 'password'])
        </div>
        <div class="campo">
            <label for="password_confirmation">Repite la contraseña</label>
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
        </div>
        <label class="check"><input type="checkbox" name="acepta" value="1" @checked(old('acepta'))> Acepto la <a href="{{ route('privacidad') }}" target="_blank">política de privacidad</a> (Ley N.° 29733).</label>
        @include('partials.error', ['campo' => 'acepta'])
        <button class="boton bloque" type="submit" style="margin-top:8px">Crear cuenta</button>
    </form>
    <p style="text-align:center;margin-bottom:0">¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión</a></p>
</div>
@endsection
