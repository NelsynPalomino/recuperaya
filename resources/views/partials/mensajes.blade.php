@foreach (['exito', 'error', 'aviso', 'info'] as $tipo)
    @if (session($tipo))
        <div class="alerta {{ $tipo }}" role="alert">{{ session($tipo) }}</div>
    @endif
@endforeach
@if ($errors->any())
    <div class="alerta error" role="alert">
        <strong>Revisa los datos del formulario:</strong>
        <ul style="margin:6px 0 0;padding-left:18px">
            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif
