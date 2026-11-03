<div class="grid grid-4" style="margin-bottom:16px">
    <div class="tarjeta kpi"><div class="valor">{{ $r['total'] }}</div><div class="etiqueta">Objetos registrados</div></div>
    <div class="tarjeta kpi"><div class="valor">{{ $r['tasa_recuperacion'] }}%</div><div class="etiqueta">Tasa de recuperación</div></div>
    <div class="tarjeta kpi"><div class="valor">{{ $r['dias_promedio'] ?? '—' }}</div><div class="etiqueta">Días promedio hasta la entrega</div></div>
    <div class="tarjeta kpi"><div class="valor">{{ $r['reportes'] }}</div><div class="etiqueta">Reportes de pérdida</div></div>
</div>
