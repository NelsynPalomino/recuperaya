@php $max = max(1, collect($datos)->max('total') ?? 1); @endphp
@forelse ($datos as $x)
    <div class="barra-dato">
        <span>{{ $x['nombre'] }}</span>
        <span class="pista"><span class="relleno" style="display:block;width:{{ round($x['total'] * 100 / $max) }}%"></span></span>
        <strong>{{ $x['total'] }}</strong>
    </div>
@empty
    <p class="vacio">Sin datos en el periodo.</p>
@endforelse
