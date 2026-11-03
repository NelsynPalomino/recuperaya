@extends('layouts.impresion')
@section('contenido')
<div class="encabezado">
    <div>
        <h1>RecuperaYa – Reporte de objetos perdidos y encontrados</h1>
        <p>Universidad Continental · Oficina de Bienestar Universitario · Periodo: {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }} · Generado el {{ now()->format('d/m/Y H:i') }}</p>
    </div>
    <button class="boton no-imprimir" onclick="window.print()">Imprimir / Guardar como PDF</button>
</div>
@include('estadisticas._kpis')
<div class="tarjeta tabla-scroll">
    <table>
        <thead><tr>@foreach ($filas[0] as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
        <tbody>
        @foreach (array_slice($filas, 1) as $f)
            <tr>@foreach ($f as $v)<td>{{ $v === '' ? '—' : $v }}</td>@endforeach</tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
