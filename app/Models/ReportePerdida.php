<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ReportePerdida extends Model
{
    public const ACTIVO = 'Activo';
    public const RESUELTO = 'Resuelto';
    public const CERRADO = 'Cerrado';

    protected $table = 'reportes_perdida';

    // El usuario y el estado no se llenan desde el formulario.
    protected $fillable = ['categoria_id', 'lugar_id', 'descripcion', 'color', 'fecha_perdida'];

    protected $casts = ['fecha_perdida' => 'date'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function lugar(): BelongsTo
    {
        return $this->belongsTo(Lugar::class);
    }

    public function objetosCoincidentes(): BelongsToMany
    {
        return $this->belongsToMany(Objeto::class, 'coincidencias', 'reporte_perdida_id', 'objeto_id')
            ->withPivot('puntaje')->withTimestamps()->orderByPivot('puntaje', 'desc');
    }

    public function claseEstado(): string
    {
        return [self::ACTIVO => 'azul', self::RESUELTO => 'verde', self::CERRADO => 'gris'][$this->estado] ?? 'gris';
    }
}
