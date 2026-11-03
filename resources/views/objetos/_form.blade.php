@php $editar = $o->exists; @endphp
<div class="grid grid-2">
    <div>
        <h2>Datos del objeto</h2>
        <div @class(['campo', 'con-error' => $errors->has('categoria_id')])><label for="categoria_id">Categoría *</label>
            <select id="categoria_id" name="categoria_id" required><option value="">Selecciona…</option>
                @foreach ($categorias as $c)<option value="{{ $c->id }}" @selected((string) old('categoria_id', $o->categoria_id) === (string) $c->id)>{{ $c->nombre }}</option>@endforeach
            </select>@include('partials.error', ['campo' => 'categoria_id'])</div>
        <div @class(['campo', 'con-error' => $errors->has('lugar_id')])><label for="lugar_id">Lugar donde se encontró *</label>
            <select id="lugar_id" name="lugar_id" required><option value="">Selecciona…</option>
                @foreach ($lugares as $l)<option value="{{ $l->id }}" @selected((string) old('lugar_id', $o->lugar_id) === (string) $l->id)>{{ $l->nombre }}</option>@endforeach
            </select>@include('partials.error', ['campo' => 'lugar_id'])</div>
        <div class="grid grid-2">
            <div @class(['campo', 'con-error' => $errors->has('color')])><label for="color">Color *</label><input type="text" id="color" name="color" maxlength="40" value="{{ old('color', $o->color) }}" required>@include('partials.error', ['campo' => 'color'])</div>
            <div @class(['campo', 'con-error' => $errors->has('fecha_hallazgo')])><label for="fecha_hallazgo">Fecha de hallazgo *</label><input type="date" id="fecha_hallazgo" name="fecha_hallazgo" max="{{ now()->toDateString() }}" value="{{ old('fecha_hallazgo', $o->fecha_hallazgo?->toDateString() ?? now()->toDateString()) }}" required>@include('partials.error', ['campo' => 'fecha_hallazgo'])</div>
        </div>
        <div @class(['campo', 'con-error' => $errors->has('descripcion')])><label for="descripcion">Descripción *</label>
            <textarea id="descripcion" name="descripcion" maxlength="500" required>{{ old('descripcion', $o->descripcion) }}</textarea>
            <small>No escribas datos personales del dueño (nombres, DNI). Mínimo 10 caracteres.</small>@include('partials.error', ['campo' => 'descripcion'])</div>
        <div @class(['campo', 'con-error' => $errors->has('foto')])><label for="foto">Foto del objeto {{ $editar ? '(opcional: solo si deseas cambiarla)' : '*' }}</label>
            @if ($editar)<img class="miniatura" style="width:120px;height:90px" src="{{ $o->urlFoto() }}" alt="Foto actual">@endif
            <input type="file" id="foto" name="foto" accept="image/jpeg,image/png" capture="environment" @required(! $editar)>
            <small>JPG o PNG, máximo 5 MB, mínimo 200 × 200 píxeles.</small>@include('partials.error', ['campo' => 'foto'])</div>
    </div>
    <div>
        <h2>Preguntas de verificación</h2>
        <p style="color:var(--gris);margin-top:0">Detalles que solo el dueño conoce. Las respuestas se guardan cifradas.{{ $editar ? ' Deja las respuestas vacías para conservar las actuales.' : '' }}</p>
        <div @class(['campo', 'con-error' => $errors->has('pregunta1')])><label for="pregunta1">Pregunta 1 *</label><input type="text" id="pregunta1" name="pregunta1" maxlength="200" value="{{ old('pregunta1', $o->pregunta1) }}" placeholder="Ej.: ¿Qué fondo de pantalla tiene?" required>@include('partials.error', ['campo' => 'pregunta1'])</div>
        <div @class(['campo', 'con-error' => $errors->has('respuesta1')])><label for="respuesta1">Respuesta 1 {{ $editar ? '' : '*' }}</label><input type="text" id="respuesta1" name="respuesta1" maxlength="100" autocomplete="off">@include('partials.error', ['campo' => 'respuesta1'])</div>
        <div @class(['campo', 'con-error' => $errors->has('pregunta2')])><label for="pregunta2">Pregunta 2 *</label><input type="text" id="pregunta2" name="pregunta2" maxlength="200" value="{{ old('pregunta2', $o->pregunta2) }}" placeholder="Ej.: ¿Qué tiene en el bolsillo pequeño?" required>@include('partials.error', ['campo' => 'pregunta2'])</div>
        <div @class(['campo', 'con-error' => $errors->has('respuesta2')])><label for="respuesta2">Respuesta 2 {{ $editar ? '' : '*' }}</label><input type="text" id="respuesta2" name="respuesta2" maxlength="100" autocomplete="off">@include('partials.error', ['campo' => 'respuesta2'])</div>
        @unless ($editar)<div class="alerta info">Al guardar, el sistema buscará reportes de pérdida parecidos y avisará a sus dueños.</div>@endunless
    </div>
</div>
