<div class="grid grid-4" style="margin-bottom:16px" id="indicadores">
    <div class="tarjeta kpi" data-kpi="total"><div class="valor">{{ $r['total'] }}</div><div class="etiqueta">Objetos registrados</div></div>
    <div class="tarjeta kpi"><div class="valor">{{ $r['tasa_recuperacion'] }}%</div><div class="etiqueta">Tasa de recuperación</div></div>
    <div class="tarjeta kpi"><div class="valor">{{ $r['dias_promedio'] ?? '—' }}</div><div class="etiqueta">Días promedio hasta la entrega</div></div>
    <div class="tarjeta kpi"><div class="valor">{{ $r['reportes'] }}</div><div class="etiqueta">Reportes de pérdida</div></div>
    <div class="tarjeta kpi" data-kpi="devueltos"><div class="valor">{{ $r['devueltos'] ?? 0 }}</div><div class="etiqueta">Objetos devueltos a su dueño</div></div>
    <div class="tarjeta kpi" data-kpi="custodia"><div class="valor">{{ $r['en_custodia'] ?? 0 }}</div><div class="etiqueta">Objetos en custodia</div></div>
    <div class="tarjeta kpi" data-kpi="reclamos"><div class="valor">{{ $r['reclamos'] ?? 0 }}</div><div class="etiqueta">Reclamos recibidos</div></div>
    <div class="tarjeta kpi" data-kpi="rechazados"><div class="valor">{{ $r['reclamos_rechazados'] ?? 0 }}</div><div class="etiqueta">Reclamos rechazados</div></div>
</div>
