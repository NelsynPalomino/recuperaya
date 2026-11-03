@extends('layouts.app')
@section('titulo', 'Mis reportes')
@section('contenido')
<div class="encabezado">
    <div><h1>Mis reportes de pérdida</h1><p>Cada reporte se compara automáticamente con los objetos que llegan a la oficina.</p></div>
    <a class="boton acento" href="{{ route('reportes.create') }}">Nuevo reporte</a>
</div>
@forelse ($reportes as $r)
    <div class="tarjeta">
        <div class="encabezado" style="margin-bottom:6px">
            <div><h2>{{ $r->categoria->nombre }} · {{ $r->color }}</h2><p>Perdido el {{ $r->fecha_perdida->format('d/m/Y') }} · {{ $r->lugar->nombre ?? 'Lugar no indicado' }}</p></div>
            <div class="acciones">
                <span class="insignia {{ $r->claseEstado() }}">{{ $r->estado }}</span>
                @if ($r->estado === \App\Models\ReportePerdida::ACTIVO)
                    <form method="POST" action="{{ route('reportes.destroy', $r) }}" onsubmit="return confirm('¿Cerrar este reporte? Ya no recibirás avisos.')">
                        @csrf
                        @method('DELETE')
                        <button class="boton secundario pequeno" type="submit">Cerrar reporte</button>
                    </form>
                @endif
            </div>
        </div>
        <p style="margin-top:0">{{ $r->descripcion }}</p>
        @if ($r->objetosCoincidentes->isNotEmpty())
            <h3>Posibles coincidencias</h3>
            <div class="objetos">
                @foreach ($r->objetosCoincidentes as $o) @include('partials.tarjeta-objeto', ['o' => $o, 'puntaje' => $o->pivot->puntaje]) @endforeach
            </div>
        @elseif ($r->estado === \App\Models\ReportePerdida::ACTIVO)
            <div class="alerta info" style="margin:0">Aún no hay coincidencias. Te avisaremos cuando llegue un objeto parecido.</div>
        @endif
    </div>
@empty
    <div class="tarjeta vacio">Aún no registraste reportes.</div>
@endforelse
@endsection
