<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
{
    protected $table = 'auditorias';

    protected $fillable = ['user_id', 'accion', 'detalle'];

    public static function registrar(string $accion, string $detalle = ''): void
    {
        static::create(['user_id' => auth()->id(), 'accion' => $accion, 'detalle' => mb_substr($detalle, 0, 255)]);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
