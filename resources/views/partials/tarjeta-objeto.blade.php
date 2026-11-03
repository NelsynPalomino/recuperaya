<a class="objeto" href="{{ route('catalogo.show', $o) }}">
    <div class="foto" role="img" aria-label="Foto de {{ $o->categoria->nombre }}" style="background-image:url('{{ $o->urlFoto() }}')"></div>
    <div class="cuerpo">
        <span class="codigo">{{ $o->codigo }}{{ isset($puntaje) ? ' · ' . $puntaje . '% de coincidencia' : '' }}</span>
        <span class="titulo">{{ $o->categoria->nombre }} · {{ $o->color }}</span>
        <span class="meta">{{ $o->lugar->nombre }} · {{ $o->fecha_hallazgo->format('d/m/Y') }}</span>
        @isset($puntaje)<span><span class="insignia {{ $o->claseEstado() }}">{{ $o->estado }}</span></span>@endisset
    </div>
</a>
