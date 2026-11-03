<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entrega extends Model
{
    protected $table = 'entregas';

    protected $fillable = ['objeto_id', 'reclamo_id', 'receptor_id', 'encargado_id', 'codigo_carne', 'observacion', 'fecha_entrega'];

    protected $casts = ['fecha_entrega' => 'datetime'];

    public function objeto(): BelongsTo
    {
        return $this->belongsTo(Objeto::class);
    }

    public function receptor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receptor_id');
    }

    public function encargado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'encargado_id');
    }
}
