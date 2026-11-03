<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reclamo extends Model
{
    public const PENDIENTE = 'Pendiente';
    public const APROBADO = 'Aprobado';
    public const RECHAZADO = 'Rechazado';
    public const ENTREGADO = 'Entregado';

    protected $table = 'reclamos';

    /**
     * Asignación masiva con guarded: el "estado" y los "intentos" están protegidos,
     * porque solo el sistema los cambia al validar las respuestas (RN-06).
     */
    protected $guarded = ['id', 'estado', 'intentos'];

    public function objeto(): BelongsTo
    {
        return $this->belongsTo(Objeto::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function claseEstado(): string
    {
        return [self::PENDIENTE => 'ambar', self::APROBADO => 'verde', self::RECHAZADO => 'rojo', self::ENTREGADO => 'verde'][$this->estado] ?? 'gris';
    }
}
